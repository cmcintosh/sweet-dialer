/**
 * Sweet-Dialer Twilio Client SDK (Epic 7)
 *
 * Stories S-061 through S-070: Complete Twilio.Device client implementation
 */

(function(global) {
    'use strict';

    const TwilioClient = {
        device: null,
        activeCall: null,
        callState: {
            status: 'idle',
            direction: null,
            phoneNumber: null,
            crmRecordType: null,
            crmRecordId: null,
            callSid: null,
            startTime: null,
            endTime: null,
            isMuted: false,
            isOnHold: false,
            duration: 0
        },
        config: {
            tokenUrl: 'index.php?entryPoint=twilio_token',
            holdUrl: 'index.php?entryPoint=twilio_hold',
            unholdUrl: 'index.php?entryPoint=twilio_unhold',
            reconnect: true,
            reconnectAttempts: 3,
            reconnectDelay: 2000,
            tokenRefreshBuffer: 60
        },
        timers: {
            tokenRefresh: null,
            callDuration: null,
            reconnect: null
        },
        retry: { tokenFetch: 0, maxTokenRetries: 3 },
        errorCodes: {
            '31201': { type: 'device', message: 'No valid account', actionable: 'Check your Twilio account configuration' },
            '31202': { type: 'device', message: 'Invalid JWT token', actionable: 'Token has expired. Attempting refresh...' },
            '31204': { type: 'device', message: 'JWT token expired', actionable: 'Token has expired. Attempting refresh...' },
            '31205': { type: 'device', message: 'JWT token expiration too long', actionable: 'Contact support for configuration' },
            '31206': { type: 'device', message: 'Rate limited', actionable: 'Too many requests. Please wait and try again' },
            '31000': { type: 'device', message: 'Device setup error', actionable: 'Please check browser permissions and refresh' },
            '31001': { type: 'device', message: 'WebSocket connection failed', actionable: 'Network issue detected. Checking connection...' },
            '31002': { type: 'device', message: 'WebSocket disconnected', actionable: 'Connection lost. Attempting to reconnect...' },
            '31003': { type: 'device', message: 'Registration failed', actionable: 'Unable to register with Twilio. Retrying...' },
            '31050': { type: 'media', message: 'Audio device error', actionable: 'Check your microphone and speaker settings' },
            '31210': { type: 'signature', message: 'Invalid signature', actionable: 'Security error. Please log out and back in' },
            '31211': { type: 'general', message: 'Call declined', actionable: 'The call was declined by the recipient' },
            '31212': { type: 'general', message: 'Call cancelled', actionable: 'The call was cancelled' },
            '31401': { type: 'general', message: 'General error', actionable: 'An unexpected error occurred. Please try again' }
        },

        init: function(options) {
            Object.assign(this.config, options || {});
            console.log('[SweetDialer] TwilioClient initializing...');
            this._emitEvent('client:initializing', { timestamp: new Date().toISOString() });
            this._fetchTokenAndSetup();
            return this;
        },

        _fetchTokenAndSetup: function() {
            const self = this;
            this._ajax({
                url: this.config.tokenUrl,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.token) {
                        self._setupDevice(response.token, response.expiresIn || 3600);
                        self.retry.tokenFetch = 0;
                        self._emitEvent('client:ready', { timestamp: new Date().toISOString() });
                    } else {
                        self._handleError('token_fetch_failed', 'Failed to fetch valid token', response);
                    }
                },
                error: function() {
                    self._handleRetry('tokenFetch', function() { self._fetchTokenAndSetup(); });
                }
            });
        },

        _setupDevice: function(token, expiresIn) {
            const self = this;
            if (typeof Twilio === 'undefined') {
                this._handleError('sdk_not_loaded', 'Twilio SDK not available', null);
                return;
            }
            if (this.device) { this.device.destroy(); }
            this.device = new Twilio.Device(token, {
                codecPreferences: ['opus', 'pcmu'],
                fakeLocalDTMF: true,
                enableRingingState: true,
                debug: (window.SweetDialer && window.SweetDialer.debug) || false
            });
            this.device.on('ready', function() {
                console.log('[SweetDialer] Device ready');
                self._emitEvent('device:ready', { timestamp: new Date().toISOString() });
            });
            this.device.on('incoming', function(call) { self._handleIncomingCall(call); });
            this.device.on('error', function(error) { self._handleDeviceError(error); });
            this.device.on('disconnect', function() {
                console.log('[SweetDialer] Device disconnected');
                self._emitEvent('device:disconnect', { timestamp: new Date().toISOString() });
            });
            this._scheduleTokenRefresh(expiresIn);
            this.device.on('tokenWillExpire', function() { self._handleTokenWillExpire(); });
            this.device.on('tokenExpired', function() { self._handleTokenExpired(); });
            console.log('[SweetDialer] Device initialized successfully');
        },

        _scheduleTokenRefresh: function(expiresIn) {
            const self = this;
            const refreshDelay = (expiresIn - this.config.tokenRefreshBuffer) * 1000;
            if (this.timers.tokenRefresh) { clearTimeout(this.timers.tokenRefresh); }
            this.timers.tokenRefresh = setTimeout(function() { self._refreshToken(); }, Math.max(refreshDelay, 5000));
            console.log('[SweetDialer] Token refresh scheduled in ' + (refreshDelay / 1000) + ' seconds');
        },

        _handleTokenWillExpire: function() {
            console.log('[SweetDialer] Token will expire soon, refreshing...');
            this._emitEvent('token:willExpire', { timestamp: new Date().toISOString() });
            this._refreshToken();
        },

        _handleTokenExpired: function() {
            console.log('[SweetDialer] Token expired, refreshing...');
            this._emitEvent('token:expired', { timestamp: new Date().toISOString() });
            this._refreshToken();
        },

        _refreshToken: function() {
            const self = this;
            this._emitEvent('token:refreshing', { timestamp: new Date().toISOString() });
            this._ajax({
                url: this.config.tokenUrl,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.token && self.device) {
                        self.device.updateToken(response.token);
                        self._scheduleTokenRefresh(response.expiresIn || 3600);
                        self.retry.tokenFetch = 0;
                        console.log('[SweetDialer] Token refreshed successfully');
                        self._emitEvent('token:refreshed', { timestamp: new Date().toISOString() });
                    } else {
                        self._handleRetry('tokenFetch', function() { self._refreshToken(); });
                    }
                },
                error: function() {
                    self._handleRetry('tokenFetch', function() { self._refreshToken(); });
                }
            });
        },

        _handleRetry: function(operation, retryFn) {
            this.retry[operation] = (this.retry[operation] || 0) + 1;
            if (this.retry[operation] <= this.retry.maxTokenRetries) {
                const delay = this.config.reconnectDelay * this.retry[operation];
                console.log('[SweetDialer] Retrying ' + operation + ' in ' + delay + 'ms (attempt ' + this.retry[operation] + ')');
                this._emitEvent('retry:scheduled', { operation: operation, attempt: this.retry[operation], delay: delay });
                setTimeout(retryFn, delay);
            } else {
                this._handleError(operation + '_failed', 'Max retries exceeded for ' + operation, null);
                this.retry[operation] = 0;
            }
        },

        _handleDeviceError: function(error) {
            const errorCode = error.code || error.errorCode || '31401';
            const errorInfo = this.errorCodes[errorCode] || this.errorCodes['31401'];
            console.error('[SweetDialer] Device Error:', errorCode, error.message);
            if (errorCode === '31202' || errorCode === '31204') {
                this._refreshToken();
                return;
            }
            this._emitEvent('device:error', {
                code: errorCode,
                message: errorInfo.message,
                actionable: errorInfo.actionable,
                type: errorInfo.type,
                timestamp: new Date().toISOString()
            });
            this._updateCallState('error', { errorCode: errorCode, errorMessage: error.message });
        },

        _handleError: function(code, message, context) {
            console.error('[SweetDialer] Error:', code, message, context);
            this._emitEvent('client:error', {
                code: code,
                message: message,
                context: context,
                timestamp: new Date().toISOString()
            });
            this._showNotification(message, null, 'error');
        },

        _showNotification: function(message, action, type) {
            this._emitEvent('notification:show', { message: message, action: action, type: type, timestamp: new Date().toISOString() });
            console[type === 'error' ? 'error' : 'log']('[SweetDialer] Notification:', message, action);
        },

        makeCall: function(phoneNumber, crmRecordType, crmRecordId) {
            if (!this.device) {
                this._handleError('device_not_ready', 'Twilio device not initialized', null);
                return null;
            }
            if (this.activeCall) {
                this._handleError('call_in_progress', 'Another call is already in progress', null);
                return null;
            }
            console.log('[SweetDialer] Making call to:', phoneNumber);
            this._updateCallState('connecting', { direction: 'outbound', phoneNumber: phoneNumber, crmRecordType: crmRecordType, crmRecordId: crmRecordId });
            const call = this.device.connect({ phoneNumber: phoneNumber, crmRecordType: crmRecordType, crmRecordId: crmRecordId, direction: 'outbound' });
            this._setupCallHandlers(call);
            this.activeCall = call;
            this._emitEvent('call:initiated', {
                direction: 'outbound', phoneNumber: phoneNumber, crmRecordType: crmRecordType, crmRecordId: crmRecordId,
                callSid: call.parameters ? call.parameters.CallSid : null, timestamp: new Date().toISOString()
            });
            return call;
        },

        _setupCallHandlers: function(call) {
            const self = this;
            call.on('ringing', function(hasEarlyMedia) {
                self._updateCallState('ringing', { hasEarlyMedia: hasEarlyMedia });
                self._emitEvent('call:ringing', {
                    hasEarlyMedia: hasEarlyMedia,
                    callSid: call.parameters ? call.parameters.CallSid : null,
                    timestamp: new Date().toISOString()
                });
            });
            call.on('accept', function() {
                self._updateCallState('connected', { callSid: call.parameters ? call.parameters.CallSid : null, startTime: new Date().toISOString() });
                self._startCallTimer();
                self._emitEvent('call:connected', {
                    callSid: call.parameters ? call.parameters.CallSid : null,
                    timestamp: new Date().toISOString()
                });
            });
            call.on('disconnect', function() {
                self._endCallCleanup();
                self._emitEvent('call:disconnected', { duration: self.callState.duration, timestamp: new Date().toISOString() });
            });
            call.on('cancel', function() {
                self._endCallCleanup();
                self._updateCallState('cancelled');
                self._emitEvent('call:cancelled', { timestamp: new Date().toISOString() });
            });
            call.on('reject', function() {
                self._endCallCleanup();
                self._updateCallState('rejected');
                self._emitEvent('call:rejected', { timestamp: new Date().toISOString() });
            });
            call.on('error', function(error) {
                self._endCallCleanup();
                self._handleDeviceError(error);
            });
            call.on('mute', function(isMuted) {
                self.callState.isMuted = isMuted;
                self._emitEvent('call:mute', { isMuted: isMuted, timestamp: new Date().toISOString() });
            });
            call.on('volume', function(inputVolume, outputVolume) {
                self._emitEvent('call:volume', { inputVolume: inputVolume, outputVolume: outputVolume, timestamp: new Date().toISOString() });
            });
        },

        _updateCallState: function(status, data) {
            this.callState.status = status;
            if (data) { Object.assign(this.callState, data); }
            this._emitEvent('call:stateChange', { state: this.callState, previousStatus: this.callState.status, timestamp: new Date().toISOString() });
            console.log('[SweetDialer] Call state updated:', status, this.callState);
        },

        _startCallTimer: function() {
            const self = this;
            this.callState.duration = 0;
            this.timers.callDuration = setInterval(function() {
                self.callState.duration++;
                self._emitEvent('call:tick', { duration: self.callState.duration, timestamp: new Date().toISOString() });
            }, 1000);
        },

        _stopCallTimer: function() {
            if (this.timers.callDuration) { clearInterval(this.timers.callDuration); this.timers.callDuration = null; }
        },

        _handleIncomingCall: function(call) {
            const self = this;
            const parameters = call.parameters || {};
            const callerInfo = { phoneNumber: parameters.From, callerName: parameters.CallerName || 'Unknown', callSid: parameters.CallSid, direction: 'inbound' };
            this._updateCallState('ringing', callerInfo);
            this.activeCall = call;
            this._emitEvent('call:incoming', { callerInfo: callerInfo, timestamp: new Date().toISOString() });
            this._lookupCrmRecord(parameters.From, function(record) {
                if (record) {
                    self.callState.crmRecordType = record.module;
                    self.callState.crmRecordId = record.id;
                    self._emitEvent('crm:recordFound', { record: record, timestamp: new Date().toISOString() });
                } else {
                    self._emitEvent('crm:recordNotFound', { phoneNumber: parameters.From, timestamp: new Date().toISOString() });
                }
            });
            this._setupCallHandlers(call);
        },

        _lookupCrmRecord: function(phoneNumber, callback) {
            this._ajax({
                url: 'index.php?entryPoint=twilio_lookup',
                method: 'POST',
                data: { phoneNumber: phoneNumber },
                success: function(response) { callback((response.success && response.record) ? response.record : null); },
                error: function() { callback(null); }
            });
        },

        answerCall: function() {
            if (!this.activeCall) { return false; }
            console.log('[SweetDialer] Answering incoming call');
            this.activeCall.accept();
            this._updateCallState('connecting');
            this._emitEvent('call:answering', { callSid: this.callState.callSid, timestamp: new Date().toISOString() });
            return true;
        },

        rejectCall: function() {
            if (!this.activeCall) { return false; }
            console.log('[SweetDialer] Rejecting incoming call');
            this.activeCall.reject();
            this._updateCallState('rejected');
            this._endCallCleanup();
            this._emitEvent('call:rejecting', { callSid: this.callState.callSid, timestamp: new Date().toISOString() });
            return true;
        },

        endCall: function(saveCallLog) {
            if (!this.activeCall) { return false; }
            console.log('[SweetDialer] Ending call');
            this.activeCall.disconnect();
            this._updateCallState('disconnecting', { endTime: new Date().toISOString() });
            if (saveCallLog !== false) { this._saveCallLog(); }
            this._endCallCleanup();
            this._emitEvent('call:ending', { duration: this.callState.duration, timestamp: new Date().toISOString() });
            return true;
        },

        _endCallCleanup: function() {
            this._stopCallTimer();
            const finalDuration = this.callState.duration;
            this.callState = { status: 'idle', direction: null, phoneNumber: null, crmRecordType: null, crmRecordId: null, callSid: null, startTime: null, endTime: null, isMuted: false, isOnHold: false, duration: 0 };
            this.activeCall = null;
            console.log('[SweetDialer] Call cleanup complete. Duration:', finalDuration);
        },

        _saveCallLog: function() {
            const callData = {
                callSid: this.callState.callSid, direction: this.callState.direction,
                phoneNumber: this.callState.phoneNumber, crmRecordType: this.callState.crmRecordType,
                crmRecordId: this.callState.crmRecordId, duration: this.callState.duration,
                startTime: this.callState.startTime, endTime: new Date().toISOString()
            };
            this._emitEvent('call:save', { callData: callData, timestamp: new Date().toISOString() });
            console.log('[SweetDialer] Call save triggered:', callData);
        },

        toggleMute: function() {
            if (!this.activeCall) { return null; }
            const newMuteState = !this.callState.isMuted;
            this.activeCall.mute(newMuteState);
            this.callState.isMuted = newMuteState;
            this._emitEvent('call:mute:toggled', { isMuted: newMuteState, timestamp: new Date().toISOString() });
            return newMuteState;
        },

        sendDtmf: function(digits) {
            if (!this.activeCall) { return false; }
            if (!/^[0-9*#]+$/.test(digits)) { return false; }
            this.activeCall.sendDigits(digits);
            this._emitEvent('call:dtmf:sent', { digits: digits, timestamp: new Date().toISOString() });
            return true;
        },

        holdCall: function() {
            const self = this;
            if (!this.activeCall) { return Promise.reject(new Error('No active call')); }
            return new Promise(function(resolve, reject) {
                self._ajax({
                    url: self.config.holdUrl, method: 'POST',
                    data: { callSid: self.callState.callSid, action: 'hold' },
                    success: function(response) {
                        if (response.success) { self.callState.isOnHold = true; self._emitEvent('call:hold', { callSid: self.callState.callSid, timestamp: new Date().toISOString() }); resolve(response); }
                        else { reject(new Error(response.message || 'Hold failed')); }
                    },
                    error: function() { reject(new Error('Hold request failed')); }
                });
            });
        },

        unholdCall: function() {
            const self = this;
            if (!this.activeCall) { return Promise.reject(new Error('No active call')); }
            return new Promise(function(resolve, reject) {
                self._ajax({
                    url: self.config.unholdUrl, method: 'POST',
                    data: { callSid: self.callState.callSid, action: 'unhold' },
                    success: function(response) {
                        if (response.success) { self.callState.isOnHold = false; self._emitEvent('call:unhold', { callSid: self.callState.callSid, timestamp: new Date().toISOString() }); resolve(response); }
                        else { reject(new Error(response.message || 'Unhold failed')); }
                    },
                    error: function() { reject(new Error('Unhold request failed')); }
                });
            });
        },

        _emitEvent: function(type, detail) {
            const event = new CustomEvent('twilio:' + type, { detail: detail, bubbles: true, cancelable: true });
            window.dispatchEvent(event);
        },

        _ajax: function(options) {
            if (typeof jQuery !== 'undefined') {
                jQuery.ajax({ url: options.url, type: options.method || 'GET', data: options.data || null, dataType: 'json', success: options.success, error: options.error });
                return;
            }
            fetch(options.url, { method: options.method || 'GET', headers: { 'Content-Type': 'application/json' }, body: options.data ? JSON.stringify(options.data) : null, credentials: 'same-origin' })
                .then(function(r) { return r.json(); }).then(options.success)
                .catch(function(error) { if (options.error) options.error(null, 'error', error); });
        },

        destroy: function() {
            Object.keys(this.timers).forEach(function(key) {
                if (this.timers[key]) { clearTimeout(this.timers[key]); clearInterval(this.timers[key]); this.timers[key] = null; }
            }.bind(this));
            if (this.activeCall) { this.endCall(); }
            if (this.device) { this.device.destroy(); this.device = null; }
            console.log('[SweetDialer] Client destroyed');
        },

        getCallState: function() { return Object.assign({}, this.callState); },
        isReady: function() { return this.device !== null && this.callState.status === 'idle'; },
        hasActiveCall: function() { return this.activeCall !== null; }
    };

    global.SweetDialer = global.SweetDialer || {};
    global.SweetDialer.TwilioClient = TwilioClient;

    if (typeof define === 'function' && define.amd) {
        define('SweetDialer/TwilioClient', function() { return TwilioClient; });
    }
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = TwilioClient;
    }

})(typeof window !== 'undefined' ? window : this);
