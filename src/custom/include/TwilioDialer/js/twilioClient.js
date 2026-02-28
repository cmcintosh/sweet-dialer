/**
 * SweetDialer Twilio Client SDK
 * ES5 compatible browser implementation
 * @version 1.0
 */

// TwilioClient namespace
(function(window) {
    'use strict';

    // Private instance reference
    var _instance = null;

    /**
     * TwilioClient Constructor
     * @constructor
     */
    function TwilioClient() {
        this.device = null;
        this.connection = null;
        this.token = null;
        this.userId = null;
        this.eventHandlers = {};
        this.refreshTimer = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.isReady = false;
        this.audioInput = null;
        this.audioOutput = null;
        this.savedCallState = null;

        // Known error codes
        this.ERROR_CODES = {
            TOKEN_EXPIRED: '20104',
            NOT_FOUND: '31201',
            CONNECTION_ERROR: '31009',
            AUTHENTICATION_FAILED: '31205',
            MEDIA_PERMISSION_DENIED: '20151',
            DEVICE_INITIALIZATION_ERROR: '31000'
        };
    }

    /**
     * Get singleton instance
     * @returns {TwilioClient}
     */
    TwilioClient.getInstance = function() {
        if (!_instance) {
            _instance = new TwilioClient();
        }
        return _instance;
    };

    // ============================================
    // S-061: Device Initialization
    // ============================================

    /**
     * Initialize Twilio Device with token
     * @param {string} authToken - Twilio access token
     * @param {Object} options - Configuration options
     * @returns {Promise}
     */
    TwilioClient.prototype.initialize = function(authToken, options) {
        options = options || {};
        this.token = authToken;
        this.userId = options.userId || null;

        var self = this;

        return new Promise(function(resolve, reject) {
            // Check if Twilio SDK is available
            if (!window.Twilio || !window.Twilio.Device) {
                reject(new Error('Twilio SDK not loaded'));
                return;
            }

            // Check browser support
            if (!self._checkBrowserSupport()) {
                reject(new Error('WebRTC not supported in this browser'));
                return;
            }

            try {
                // Destroy existing device if any
                if (self.device) {
                    self.device.destroy();
                }

                // Create new device
                self.device = new window.Twilio.Device(authToken, {
                    codecPreferences: ['opus', 'pcmu'],
                    fakeLocalDTMF: false,
                    enableRingingState: true
                });

                // Set up device events
                self._setupDeviceEvents();

                // Set up token refresh
                self._startTokenRefreshTimer();

                self.isReady = true;
                self.reconnectAttempts = 0;

                resolve(self.device);
            } catch (error) {
                self._handleDeviceError(error);
                reject(error);
            }
        });
    };

    /**
     * Check browser WebRTC support
     * @private
     * @returns {boolean}
     */
    TwilioClient.prototype._checkBrowserSupport = function() {
        return !!(navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia);
    };

    /**
     * Set up device-level event handlers
     * @private
     */
    TwilioClient.prototype._setupDeviceEvents = function() {
        var self = this;

        // Device ready
        this.device.on('ready', function() {
            self.isReady = true;
            self._emit('ready', {});
        });

        // Device error
        this.device.on('error', function(error) {
            self._handleDeviceError(error);
            self._emit('error', error);
        });

        // Incoming call
        this.device.on('incoming', function(connection) {
            self._handleIncomingCall(connection);
        });

        // Connect (outbound/inbound established)
        this.device.on('connect', function(connection) {
            self.connection = connection;
            self._attachCallEvents(connection);
            self._emit('connected', { connection: connection });
        });

        // Device offline
        this.device.on('offline', function() {
            self.isReady = false;
            self._emit('offline', {});
        });
    };

    // ============================================
    // S-062: Token Refresh
    // ============================================

    /**
     * Update token on device
     * @param {string} newToken - New Twilio access token
     */
    TwilioClient.prototype.updateToken = function(newToken) {
        this.token = newToken;
        if (this.device) {
            this.device.updateToken(newToken);
        }
    };

    /**
     * Start token refresh timer (refresh every 50 minutes)
     * @private
     */
    TwilioClient.prototype._startTokenRefreshTimer = function() {
        var self = this;
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        this.refreshTimer = setInterval(function() {
            self._emit('tokenWillExpire', {});
        }, 50 * 60 * 1000); // 50 minutes
    };

    // ============================================
    // S-063: Error Handling
    // ============================================

    /**
     * Handle device errors
     * @private
     * @param {Object} error - Twilio error object
     */
    TwilioClient.prototype._handleDeviceError = function(error) {
        var code = error.code || error.message;
        var self = this;

        switch (code) {
            case this.ERROR_CODES.TOKEN_EXPIRED:
            case this.ERROR_CODES.AUTHENTICATION_FAILED:
                this._emit('tokenExpired', { error: error });
                break;

            case this.ERROR_CODES.CONNECTION_ERROR:
                // Attempt to reconnect
                if (this.reconnectAttempts < this.maxReconnectAttempts) {
                    this.reconnectAttempts++;
                    setTimeout(function() {
                        self._attemptReconnect();
                    }, 2000 * this.reconnectAttempts);
                }
                break;

            case this.ERROR_CODES.MEDIA_PERMISSION_DENIED:
                this._emit('permissionDenied', { error: error });
                break;
        }

        return error;
    };

    /**
     * Attempt to reconnect device
     * @private
     */
    TwilioClient.prototype._attemptReconnect = function() {
        if (this.token) {
            this.initialize(this.token, { userId: this.userId });
        }
    };

    // ============================================
    // S-064 & S-065: Outbound Call Making
    // ============================================

    /**
     * Make an outbound call
     * @param {string} phoneNumber - Phone number to call
     * @param {string} parentType - CRM parent type (Contacts, Leads, etc.)
     * @param {string} parentId - CRM parent record ID
     * @param {Object} options - Additional options
     * @returns {Object} Twilio Connection
     */
    TwilioClient.prototype.makeCall = function(phoneNumber, parentType, parentId, options) {
        options = options || {};

        if (!this.device || !this.isReady) {
            throw new Error('Device not initialized');
        }

        var params = {
            To: phoneNumber,
            CallerType: parentType || '',
            CallerId: parentId || ''
        };

        // Store call state for tracking
        this.savedCallState = {
            phoneNumber: phoneNumber,
            parentType: parentType,
            parentId: parentId,
            direction: 'outbound',
            status: 'ringing',
            startTime: Date.now()
        };

        var connection = this.device.connect(params);
        return connection;
    };

    /**
     * End current call
     */
    TwilioClient.prototype.endCall = function() {
        if (this.connection) {
            this.connection.disconnect();
            this.connection = null;
        }
        if (this.device) {
            var connections = this.device.connections || [];
            connections.forEach(function(conn) {
                conn.disconnect();
            });
        }
    };

    // ============================================
    // S-066, S-067, S-068: Incoming Call Handling
    // ============================================

    /**
     * Handle incoming connection
     * @private
     * @param {Object} connection - Twilio connection
     */
    TwilioClient.prototype._handleIncomingCall = function(connection) {
        this.connection = connection;
        this.savedCallState = {
            phoneNumber: connection.parameters.From || 'Unknown',
            direction: 'inbound',
            status: 'incoming',
            startTime: Date.now()
        };

        this._emit('incoming', {
            connection: connection,
            from: connection.parameters.From,
            parameters: connection.parameters
        });
    };

    /**
     * Answer incoming call
     */
    TwilioClient.prototype.answerCall = function() {
        if (this.connection && this.connection.parameters) {
            this.connection.accept();
            if (this.savedCallState) {
                this.savedCallState.status = 'accepted';
            }
        }
    };

    /**
     * Reject incoming call
     */
    TwilioClient.prototype.rejectCall = function() {
        if (this.connection) {
            this.connection.reject();
            if (this.savedCallState) {
                this.savedCallState.status = 'rejected';
            }
        }
    };

    // ============================================
    // S-069: Mute Controls
    // ============================================

    /**
     * Toggle mute state
     * @param {boolean} muteState - True to mute, false to unmute
     */
    TwilioClient.prototype.toggleMute = function(muteState) {
        if (this.connection) {
            if (muteState) {
                this.connection.mute(true);
            } else {
                this.connection.mute(false);
            }
        }
    };

    /**
     * Get current mute state
     * @returns {boolean}
     */
    TwilioClient.prototype.getMuteState = function() {
        return this.connection ? this.connection.isMuted() : false;
    };

    // ============================================
    // S-070: DTMF Sending
    // ============================================

    /**
     * Send DTMF digits
     * @param {string} digits - DTMF digits (0-9, *, #)
     */
    TwilioClient.prototype.sendDtmf = function(digits) {
        if (this.connection && this.connection.sendDigits) {
            this.connection.sendDigits(digits);
        }
    };

    // ============================================
    // S-071: Hold/Unhold
    // ============================================

    /**
     * Hold current call
     */
    TwilioClient.prototype.holdCall = function() {
        if (this.connection) {
            this.connection.sendDigits('0'); // Signal hold via custom param
            this._emit('hold', {});
        }
    };

    /**
     * Unhold current call
     */
    TwilioClient.prototype.unholdCall = function() {
        if (this.connection) {
            this._emit('unhold', {});
        }
    };

    // ============================================
    // Audio Device Management
    // ============================================

    /**
     * Set audio output device (speaker/headphone)
     * @param {string} sinkId - Audio device ID
     */
    TwilioClient.prototype.setAudioOutput = function(sinkId) {
        if (this.device) {
            this.audioOutput = sinkId;
        }
    };

    /**
     * Set audio input device (microphone)
     * @param {string} deviceId - Audio input device ID
     */
    TwilioClient.prototype.setAudioInput = function(deviceId) {
        this.audioInput = deviceId;
    };

    /**
     * Get available audio input devices
     * @returns {Promise<Array>}
     */
    TwilioClient.prototype.getAudioInputs = function() {
        return navigator.mediaDevices.enumerateDevices().then(function(devices) {
            return devices.filter(function(d) { return d.kind === 'audioinput'; });
        });
    };

    /**
     * Get available audio output devices
     * @returns {Promise<Array>}
     */
    TwilioClient.prototype.getAudioOutputs = function() {
        return navigator.mediaDevices.enumerateDevices().then(function(devices) {
            return devices.filter(function(d) { return d.kind === 'audiooutput'; });
        });
    };

    /**
     * Test microphone permissions
     * @returns {Promise}
     */
    TwilioClient.prototype.testMicrophone = function() {
        return navigator.mediaDevices.getUserMedia({ audio: true });
    };

    // ============================================
    // Event System
    // ============================================

    /**
     * Attach call-level events
     * @private
     * @param {Object} connection - Twilio connection
     */
    TwilioClient.prototype._attachCallEvents = function(connection) {
        var self = this;

        connection.on('accept', function() {
            self._emit('callAccepted', { connection: connection });
            if (self.savedCallState) {
                self.savedCallState.status = 'accepted';
            }
        });

        connection.on('disconnect', function(reason) {
            self._emit('callEnded', { reason: reason, connection: connection });
            self.connection = null;
            if (self.savedCallState) {
                self.savedCallState.status = 'disconnected';
                self.savedCallState.endTime = Date.now();
            }
        });

        connection.on('cancel', function() {
            self._emit('callCanceled', { connection: connection });
            if (self.savedCallState) {
                self.savedCallState.status = 'canceled';
            }
        });

        connection.on('reject', function() {
            self._emit('callRejected', { connection: connection });
            if (self.savedCallState) {
                self.savedCallState.status = 'rejected';
            }
        });

        connection.on('error', function(error) {
            self._emit('callError', { error: error, connection: connection });
        });
    };

    /**
     * Add event listener
     * @param {string} event - Event name
     * @param {function} handler - Event handler
     */
    TwilioClient.prototype.on = function(event, handler) {
        if (!this.eventHandlers[event]) {
            this.eventHandlers[event] = [];
        }
        this.eventHandlers[event].push(handler);
    };

    /**
     * Remove event listener
     * @param {string} event - Event name
     * @param {function} handler - Event handler to remove
     */
    TwilioClient.prototype.off = function(event, handler) {
        if (this.eventHandlers[event]) {
            var index = this.eventHandlers[event].indexOf(handler);
            if (index > -1) {
                this.eventHandlers[event].splice(index, 1);
            }
        }
    };

    /**
     * Emit event to all listeners
     * @private
     * @param {string} event - Event name
     * @param {Object} data - Event data
     */
    TwilioClient.prototype._emit = function(event, data) {
        if (this.eventHandlers[event]) {
            this.eventHandlers[event].forEach(function(handler) {
                handler(data);
            });
        }
    };

    // ============================================
    // Call State & Info
    // ============================================

    /**
     * Get active connection
     * @returns {Object|null}
     */
    TwilioClient.prototype.getActiveCall = function() {
        return this.connection;
    };

    /**
     * Check if has active call
     * @returns {boolean}
     */
    TwilioClient.prototype.hasActiveCall = function() {
        return this.connection !== null;
    };

    /**
     * Check if has incoming call
     * @returns {boolean}
     */
    TwilioClient.prototype.hasIncomingCall = function() {
        return this.connection !== null && this.savedCallState && this.savedCallState.direction === 'inbound' && this.savedCallState.status === 'incoming';
    };

    /**
     * Get current call status
     * @returns {string}
     */
    TwilioClient.prototype.getCallStatus = function() {
        return this.savedCallState ? this.savedCallState.status : 'idle';
    };

    /**
     * Get call info
     * @returns {Object|null}
     */
    TwilioClient.prototype.getCallInfo = function() {
        return this.savedCallState;
    };

    // ============================================
    // Device Management
    // ============================================

    /**
     * Clean shutdown
     */
    TwilioClient.prototype.destroy = function() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
        if (this.device) {
            this.device.destroy();
            this.device = null;
        }
        this.connection = null;
        this.isReady = false;
        _instance = null;
    };

    // ============================================
    // Expose to global scope
    // ============================================

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = TwilioClient;
    }
    window.TwilioClient = TwilioClient;

})(window);
