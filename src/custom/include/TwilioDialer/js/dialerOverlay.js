/**
 * SweetDialer Floating Call Overlay
 * Draggable call interface with controls
 * @version 1.0
 */

(function() {
    'use strict';

    // Create namespace
    window.SweetDialerOverlay = window.SweetDialerOverlay || {};

    // Call state
    var callState = {
        isActive: false,
        isIncoming: false,
        isMuted: false,
        isOnHold: false,
        phoneNumber: '',
        parentType: '',
        parentId: '',
        callSid: '',
        startTime: null,
        callDuration: 0,
        timerInterval: null
    };

    // Overlay elements
    var overlay, callInfo, controls, keypad, dialerPanel;
    var notesPanel, historyPanel, logsPanel;
    var activeTab = 'notes';

    /**
     * Initialize the overlay
     */
    window.SweetDialerOverlay.init = function() {
        if (document.getElementById('sweetdialer-overlay')) {
            return;
        }

        injectStyles();
        createOverlay();
        bindEvents();
    };

    /**
     * Show incoming call
     */
    window.SweetDialerOverlay.showIncoming = function(phone, name) {
        callState.isIncoming = true;
        callState.phoneNumber = phone;
        callState.startTime = null;

        var callerName = document.getElementById('sd-caller-name');
        var callerNumber = document.getElementById('sd-caller-number');
        var callerStatus = document.getElementById('sd-caller-status');
        var avatar = document.getElementById('sd-caller-avatar');

        if (callerName) callerName.textContent = name || 'Unknown Caller';
        if (callerNumber) callerNumber.textContent = formatPhone(phone);
        if (callerStatus) callerStatus.textContent = 'Incoming Call...';
        if (avatar) avatar.textContent = name ? name.charAt(0).toUpperCase() : '?';

        // Show answer/reject buttons, hide controls
        document.getElementById('sd-incoming-controls').style.display = 'flex';
        document.getElementById('sd-call-controls').style.display = 'none';
        document.getElementById('sd-keypad-panel').style.display = 'none';

        showOverlay();
        playRingtone();
    };

    /**
     * Show active call
     */
    window.SweetDialerOverlay.showActive = function(phone, parentType, parentId) {
        callState.isActive = true;
        callState.isIncoming = false;
        callState.phoneNumber = phone;
        callState.parentType = parentType || '';
        callState.parentId = parentId || '';
        callState.startTime = Date.now();
        callState.callDuration = 0;

        var callerStatus = document.getElementById('sd-caller-status');
        if (callerStatus) callerStatus.textContent = 'On Call';

        // Hide incoming controls, show call controls
        document.getElementById('sd-incoming-controls').style.display = 'none';
        document.getElementById('sd-call-controls').style.display = 'flex';
        document.getElementById('sd-keypad-panel').style.display = 'none';

        showOverlay();
        startTimer();
        stopRingtone();
    };

    /**
     * End call
     */
    window.SweetDialerOverlay.endCall = function() {
        callState.isActive = false;
        callState.isIncoming = false;
        stopTimer();
        stopRingtone();
        resetUI();
        hideOverlay();
    };

    /**
     * Create overlay DOM
     */
    function createOverlay() {
        overlay = document.createElement('div');
        overlay.id = 'sweetdialer-overlay';
        overlay.style.cssText = 'position:fixed;top:100px;right:20px;width:320px;background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.3);z-index:9999;font-family:Arial,sans-serif;display:none;';

        overlay.innerHTML = [
            '<div id="sd-header" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#0b1a2e;color:#fff;border-radius:12px 12px 0 0;cursor:move;">',
            '  <span style="font-weight:600;font-size:14px;">&#9742; SweetDialer</span>',
            '  <button id="sd-minimize" style="background:none;border:none;color:#fff;cursor:pointer;font-size:18px;">−</button>',
            '</div>',
            '<div id="sd-call-info" style="padding:20px;text-align:center;background:linear-gradient(135deg,#0b1a2e,#132d4f);color:#fff;" draggable="false">',
            '  <div id="sd-caller-avatar" style="width:60px;height:60px;margin:0 auto 12px;border-radius:50%;background:#00d4aa;display:flex;align-items:center;justify-content:center;font-size:24px;color:#0b1a2e;font-weight:600;">?</div>',
            '  <div id="sd-caller-name" style="font-size:18px;font-weight:600;margin-bottom:4px;">Unknown</div>',
            '  <div id="sd-caller-number" style="font-size:14px;opacity:0.8;margin-bottom:8px;"></div>',
            '  <div id="sd-caller-status" style="font-size:12px;color:#00d4aa;">Ready</div>',
            '  <div id="sd-timer" style="font-size:24px;font-weight:600;margin-top:8px;display:none;">00:00</div>',
            '</div>',
            '<div id="sd-incoming-controls" style="display:none;padding:16px;gap:12px;">',
            '  <button id="sd-answer-btn" style="flex:1;padding:12px;background:#00d4aa;color:#0b1a2e;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:14px;">Answer</button>',
            '  <button id="sd-reject-btn" style="flex:1;padding:12px;background:#e74c3c;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:14px;">Reject</button>',
            '</div>',
            '<div id="sd-call-controls" style="display:none;flex-wrap:wrap;padding:12px 16px;gap:8px;background:#f8f9fa;">',
            '  <button id="sd-mute-btn" style="padding:8px 12px;background:#fff;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:12px;" title="Mute">Mic</button>',
            '  <button id="sd-hold-btn" style="padding:8px 12px;background:#fff;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:12px;" title="Hold">Hold</button>',
            '  <button id="sd-keypad-btn" style="padding:8px 12px;background:#fff;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:12px;" title="Keypad">#*</button>',
            '  <button id="sd-notes-btn" style="padding:8px 12px;background:#fff;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:12px;" title="Notes">✏ Notes</button>',
            '  <button id="sd-logs-btn" style="padding:8px 12px;background:#fff;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:12px;" title="Call Logs">Logs</button>',
            '  <button id="sd-transfer-btn" style="padding:8px 12px;background:#fff;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:12px;" title="Transfer">Transfer</button>',
            '  <button id="sd-end-btn" style="flex:1 100%;padding:12px;background:#e74c3c;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:14px;margin-top:8px;">End Call</button>',
            '</div>',
            '<div id="sd-keypad-panel" style="display:none;padding:12px;background:#f8f9fa;">',
            '  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">',
            '    <button data-key="1" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">1</button>',
            '    <button data-key="2" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">2</button>',
            '    <button data-key="3" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">3</button>',
            '    <button data-key="4" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">4</button>',
            '    <button data-key="5" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">5</button>',
            '    <button data-key="6" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">6</button>',
            '    <button data-key="7" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">7</button>',
            '    <button data-key="8" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">8</button>',
            '    <button data-key="9" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">9</button>',
            '    <button data-key="*" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">*</button>',
            '    <button data-key="0" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">0</button>',
            '    <button data-key="#" style="padding:15px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:16px;">#</button>',
            '  </div>',
            '  <button id="sd-keypad-close" style="width:100%;margin-top:8px;padding:8px;background:#ddd;border:none;border-radius:6px;cursor:pointer;">Close</button>',
            '</div>',
            '<div id="sd-notes-panel" style="display:none;padding:16px;">',
            '  <textarea id="sd-note-text" placeholder="Enter call notes..." style="width:100%;min-height:80px;padding:8px;border:1px solid #ddd;border-radius:6px;resize:vertical;"></textarea>',
            '  <div style="margin-top:8px;text-align:right;">',
            '    <span id="sd-note-saved" style="display:none;color:#00d4aa;font-size:12px;margin-right:12px;">✓ Saved</span>',
            '    <button id="sd-save-note" style="padding:8px 16px;background:#00d4aa;color:#0b1a2e;border:none;border-radius:6px;cursor:pointer;font-weight:600;">Save</button>',
            '  </div>',
            '</div>'
        ].join('');

        document.body.appendChild(overlay);
    }

    /**
     * Inject CSS styles
     */
    function injectStyles() {
        if (document.getElementById('sd-overlay-styles')) return;
        
        var styles = document.createElement('style');
        styles.id = 'sd-overlay-styles';
        styles.textContent = '.sweetdialer-overlay *{box-sizing:border-box}';
        document.head.appendChild(styles);
    }

    /**
     * Bind all event handlers
     */
    function bindEvents() {
        // Header drag
        var header = document.getElementById('sd-header');
        if (header) {
            var isDragging = false, startX, startY, initialX, initialY;
            header.onmousedown = function(e) {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                initialX = overlay.offsetLeft;
                initialY = overlay.offsetTop;
                document.onmousemove = onDrag;
                document.onmouseup = stopDrag;
            };
            function onDrag(e) {
                if (!isDragging) return;
                var dx = e.clientX - startX;
                var dy = e.clientY - startY;
                overlay.style.left = (initialX - dx) + 'px';
                overlay.style.top = (initialY + dy) + 'px';
                overlay.style.right = 'auto';
            }
            function stopDrag() {
                isDragging = false;
                document.onmousemove = null;
                document.onmouseup = null;
            }
        }

        // Incoming call controls
        document.getElementById('sd-answer-btn').onclick = function() {
            if (window.TwilioClient && window.TwilioClient.getInstance) {
                var client = window.TwilioClient.getInstance();
                if (client && client.answerCall) client.answerCall();
            }
        };

        document.getElementById('sd-reject-btn').onclick = function() {
            if (window.TwilioClient && window.TwilioClient.getInstance) {
                var client = window.TwilioClient.getInstance();
                if (client && client.rejectCall) client.rejectCall();
            }
            window.SweetDialerOverlay.endCall();
        };

        // Call controls
        document.getElementById('sd-mute-btn').onclick = function() {
            toggleMute();
        };

        document.getElementById('sd-hold-btn').onclick = function() {
            toggleHold();
        };

        document.getElementById('sd-keypad-btn').onclick = function() {
            var panel = document.getElementById('sd-keypad-panel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            document.getElementById('sd-notes-panel').style.display = 'none';
        };

        document.getElementById('sd-notes-btn').onclick = function() {
            var panel = document.getElementById('sd-notes-panel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            document.getElementById('sd-keypad-panel').style.display = 'none';
        };

        document.getElementById('sd-keypad-close').onclick = function() {
            document.getElementById('sd-keypad-panel').style.display = 'none';
        };

        document.getElementById('sd-end-btn').onclick = function() {
            if (window.TwilioClient && window.TwilioClient.getInstance) {
                var client = window.TwilioClient.getInstance();
                if (client && client.endCall) client.endCall();
            }
            window.SweetDialerOverlay.endCall();
        };

        // DTMF keypad
        var keypadButtons = document.querySelectorAll('#sd-keypad-panel button[data-key]');
        keypadButtons.forEach(function(btn) {
            btn.onclick = function() {
                var key = this.dataset.key;
                if (window.TwilioClient && window.TwilioClient.getInstance) {
                    var client = window.TwilioClient.getInstance();
                    if (client && client.sendDtmf) client.sendDtmf(key);
                }
            };
        });

        // Save note
        var noteArea = document.getElementById('sd-note-text');
        var saveBtn = document.getElementById('sd-save-note');
        var savedIndicator = document.getElementById('sd-note-saved');

        saveBtn.onclick = function() {
            var note = noteArea.value;
            if (note && callState.callSid) {
                saveCallNote(callState.callSid, note);
                savedIndicator.style.display = 'inline';
                setTimeout(function() {
                    savedIndicator.style.display = 'none';
                }, 2000);
            }
        };

        // Auto-save notes on typing
        var autoSaveTimer;
        noteArea.oninput = function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                saveBtn.click();
            }, 3000);
        };
    }

    /**
     * Toggle mute state
     */
    function toggleMute() {
        callState.isMuted = !callState.isMuted;
        var btn = document.getElementById('sd-mute-btn');
        btn.style.background = callState.isMuted ? '#e74c3c' : '#fff';
        btn.style.color = callState.isMuted ? '#fff' : '#000';
        
        if (window.TwilioClient && window.TwilioClient.getInstance) {
            var client = window.TwilioClient.getInstance();
            if (client && client.toggleMute) client.toggleMute(callState.isMuted);
        }
    }

    /**
     * Toggle hold state
     */
    function toggleHold() {
        callState.isOnHold = !callState.isOnHold;
        var btn = document.getElementById('sd-hold-btn');
        var status = document.getElementById('sd-caller-status');
        btn.style.background = callState.isOnHold ? '#f5c542' : '#fff';
        btn.textContent = callState.isOnHold ? 'Resume' : 'Hold';
        if (status) status.textContent = callState.isOnHold ? 'On Hold' : 'On Call';
        
        if (window.TwilioClient && window.TwilioClient.getInstance) {
            var client = window.TwilioClient.getInstance();
            if (client) {
                if (callState.isOnHold && client.holdCall) client.holdCall();
                if (!callState.isOnHold && client.unholdCall) client.unholdCall();
            }
        }
    }

    /**
     * Start call timer
     */
    function startTimer() {
        var timerEl = document.getElementById('sd-timer');
        if (timerEl) timerEl.style.display = 'block';
        
        callState.timerInterval = setInterval(function() {
            callState.callDuration++;
            var mins = Math.floor(callState.callDuration / 60);
            var secs = callState.callDuration % 60;
            if (timerEl) {
                timerEl.textContent = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
            }
        }, 1000);
    }

    /**
     * Stop call timer
     */
    function stopTimer() {
        if (callState.timerInterval) {
            clearInterval(callState.timerInterval);
            callState.timerInterval = null;
        }
        var timerEl = document.getElementById('sd-timer');
        if (timerEl) timerEl.style.display = 'none';
    }

    /**
     * Reset UI elements
     */
    function resetUI() {
        document.getElementById('sd-incoming-controls').style.display = 'none';
        document.getElementById('sd-call-controls').style.display = 'none';
        document.getElementById('sd-keypad-panel').style.display = 'none';
        document.getElementById('sd-notes-panel').style.display = 'none';
        var status = document.getElementById('sd-caller-status');
        if (status) status.textContent = 'Ready';
    }

    /**
     * Show overlay
     */
    function showOverlay() {
        if (overlay) overlay.style.display = 'block';
    }

    /**
     * Hide overlay
     */
    function hideOverlay() {
        if (overlay) overlay.style.display = 'none';
    }

    /**
     * Format phone number
     */
    function formatPhone(phone) {
        var cleaned = phone.replace(/\\D/g, '');
        if (cleaned.length === 10) {
            return '(' + cleaned.slice(0,3) + ') ' + cleaned.slice(3,6) + '-' + cleaned.slice(6);
        }
        return phone;
    }

    /**
     * Save call note via AJAX
     */
    function saveCallNote(callSid, note) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?entryPoint=sweetdialer_save_note', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('call_sid=' + encodeURIComponent(callSid) + '&note=' + encodeURIComponent(note));
    }

    /**
     * Play ringtone audio
     */
    var ringtoneAudio = null;
    function playRingtone() {
        // In production, load actual ringtone file
        console.log('SweetDialer: Playing ringtone');
    }

    function stopRingtone() {
        if (ringtoneAudio) {
            ringtoneAudio.pause();
            ringtoneAudio = null;
        }
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.SweetDialerOverlay.init);
    } else {
        window.SweetDialerOverlay.init();
    }

})();
