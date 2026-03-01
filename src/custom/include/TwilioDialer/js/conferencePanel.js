/**
 * SweetDialer Conference Panel
 * S-113-S-115: Dialpad Conference Integration
 * @version 1.0
 */

(function(window) {
    'use strict';

    window.ConferencePanel = window.ConferencePanel || {};

    var panelEl = null;
    var participantListEl = null;
    var conferenceSid = null;
    var refreshInterval = null;

    /**
     * Initialize conference panel
     */
    window.ConferencePanel.init = function(sid) {
        conferenceSid = sid;
        createPanel();
        loadParticipants();
        startAutoRefresh();
    };

    /**
     * Create panel DOM
     */
    function createPanel() {
        if (document.getElementById('sd-conference-panel')) return;

        panelEl = document.createElement('div');
        panelEl.id = 'sd-conference-panel';
        panelEl.style.cssText = 'position:fixed;bottom:20px;right:20px;width:380px;background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.3);z-index:9998;font-family:Arial,sans-serif;';

        panelEl.innerHTML = [
            '<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#0b1a2e;color:#fff;border-radius:12px 12px 0 0;">',
            '  <span id="sd-conf-title" style="font-weight:600;">Conference Room</span>',
            '  <button onclick="ConferencePanel.close()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:16px;">&times;</button>',
            '</div>',
            '<div style="padding:16px;">',
            '  <div id="sd-conf-info" style="margin-bottom:12px;font-size:13px;color:#666;"></div>',
            '  <div style="display:flex;gap:8px;margin-bottom:12px;">',
            '    <input type="text" id="sd-conf-add-phone" placeholder="Phone number..." style="flex:1;padding:8px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;">',
            '    <button onclick="ConferencePanel.addParticipant()" style="padding:8px 16px;background:#00d4aa;color:#0b1a2e;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">Add</button>',
            '  </div>',
            '  <div id="sd-conf-participants" style="max-height:200px;overflow-y:auto;"></div>',
            '  <div style="display:flex;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid #eee;">',
            '    <button onclick="ConferencePanel.muteAll()" style="flex:1;padding:8px;background:#f5f5f5;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:12px;">Mute All</button>',
            '    <button onclick="ConferencePanel.toggleRecording()" id="sd-conf-record-btn" style="flex:1;padding:8px;background:#f5f5f5;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:12px;">Start Recording</button>',
            '    <button onclick="ConferencePanel.endConference()" style="padding:8px 16px;background:#e74c3c;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">End</button>',
            '  </div>',
            '</div>'
        ].join('');

        document.body.appendChild(panelEl);
    }

    /**
     * Load participants
     */
    function loadParticipants() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'index.php?entryPoint=conferenceParticipants&sid=' + encodeURIComponent(conferenceSid), true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);
                renderParticipants(data.participants || []);
                updateInfo(data);
            }
        };
        xhr.send();
    }

    /**
     * Render participant list
     */
    function renderParticipants(participants) {
        var listEl = document.getElementById('sd-conf-participants');
        if (!participants.length) {
            listEl.innerHTML = '<div style="padding:16px;text-align:center;color:#999;font-size:13px;">No participants yet</div>';
            return;
        }

        var html = participants.map(function(p) {
            return [
                '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;" data-cid="' + p.call_sid + '">',
                '  <div>',
                '    <div style="font-size:13px;font-weight:600;">' + (p.name || p.phone || 'Unknown') + '</div>',
                '    <div style="font-size:11px;color:#999;">' + p.phone + ' <span style="margin-left:8px;" class="sd-conf-status" data-sid="' + p.call_sid + '" data-muted="' + (p.muted ? '1' : '0') + '" style="color:' + (p.muted ? '#e74c3c' : '#00d4aa') + '">' + (p.muted ? 'Muted' : 'Active') + '</span></div>',
                '  </div>',
                '  <div>',
                '    <button onclick="ConferencePanel.toggleMute(\'' + p.call_sid + '\', this)" style="padding:4px 8px;margin-right:4px;background:none;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:11px;">Mic</button>',
                '    <button onclick="ConferencePanel.kick(\'' + p.call_sid + '\')" style="padding:4px 8px;background:none;border:1px solid #e74c3c;color:#e74c3c;border-radius:4px;cursor:pointer;font-size:11px;">Kick</button>',
                '  </div>',
                '</div>'
            ].join('');
        }).join('');

        listEl.innerHTML = html;
    }

    /**
     * Update conference info
     */
    function updateInfo(data) {
        var info = document.getElementById('sd-conf-info');
        if (info) {
            info.innerHTML = 'Participants: ' + (data.participant_count || 0) + ' / ' + (data.max_participants || '∞') +
                ' <span style="margin-left:16px;">Status: ' + (data.recording ? '🔴 Recording' : 'Not recording') + '</span>';
        }
    }

    /**
     * Auto refresh participant list
     */
    function startAutoRefresh() {
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(loadParticipants, 5000);
    }

    // Public methods
    window.ConferencePanel.addParticipant = function() {
        var phone = document.getElementById('sd-conf-add-phone').value.replace(/\D/g, '');
        if (!phone || phone.length < 10) {
            alert('Please enter a valid phone number');
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?entryPoint=conferenceControl', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById('sd-conf-add-phone').value = '';
                loadParticipants();
            }
        };
        xhr.send('action=add_participant&conference_sid=' + encodeURIComponent(conferenceSid) + '&phone=' + encodeURIComponent(phone));
    };

    window.ConferencePanel.toggleMute = function(callSid, btn) {
        var statusEl = document.querySelector('[data-sid="' + callSid + '"]');
        var isMuted = statusEl.dataset.muted === '1';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?entryPoint=conferenceControl', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                statusEl.dataset.muted = isMuted ? '0' : '1';
                statusEl.textContent = isMuted ? 'Active' : 'Muted';
                statusEl.style.color = isMuted ? '#00d4aa' : '#e74c3c';
                btn.textContent = isMuted ? 'Mute' : 'Unmute';
            }
        };
        xhr.send('action=' + (isMuted ? 'unmute' : 'mute') + '&conference_sid=' + encodeURIComponent(conferenceSid) + '&participant_sid=' + encodeURIComponent(callSid));
    };

    window.ConferencePanel.kick = function(callSid) {
        if (!confirm('Remove this participant?')) return;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?entryPoint=conferenceControl', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                loadParticipants();
            }
        };
        xhr.send('action=kick&conference_sid=' + encodeURIComponent(conferenceSid) + '&participant_sid=' + encodeURIComponent(callSid));
    };

    window.ConferencePanel.muteAll = function() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?entryPoint=conferenceControl', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                loadParticipants();
            }
        };
        xhr.send('action=mute_all&conference_sid=' + encodeURIComponent(conferenceSid));
    };

    window.ConferencePanel.toggleRecording = function() {
        var btn = document.getElementById('sd-conf-record-btn');
        var isRecording = btn.textContent.indexOf('Stop') !== -1;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?entryPoint=conferenceControl', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                btn.textContent = isRecording ? 'Start Recording' : 'Stop Recording';
                btn.style.background = isRecording ? '#f5f5f5' : '#e74c3c';
                btn.style.color = isRecording ? '#000' : '#fff';
                loadParticipants();
            }
        };
        xhr.send('action=' + (isRecording ? 'stop_recording' : 'start_recording') + '&conference_sid=' + encodeURIComponent(conferenceSid));
    };

    window.ConferencePanel.endConference = function() {
        if (!confirm('End this conference for all participants?')) return;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?entryPoint=conferenceControl', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                window.ConferencePanel.close();
            }
        };
        xhr.send('action=end&conference_sid=' + encodeURIComponent(conferenceSid));
    };

    window.ConferencePanel.close = function() {
        if (refreshInterval) clearInterval(refreshInterval);
        if (panelEl) {
            panelEl.remove();
            panelEl = null;
        }
    };

})(window);
