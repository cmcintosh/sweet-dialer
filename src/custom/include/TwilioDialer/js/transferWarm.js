/**
 * Warm Transfer JavaScript Handler
 * Epic 10: Transfer - S-100-S-102 (additional JS)
 * Manages warm transfer flow: hold, invite, conference merge
 */

(function(window) {
    'use strict';

    window.WarmTransfer = window.WarmTransfer || {};

    var transferState = {
        callSid: null,
        conferenceSid: null,
        agentCallSid: null,
        status: 'idle', // idle, inviting, connected, merged, failed
        participantCount: 0
    };

    var statusEl = null;

    /**
     * Initialize warm transfer for a call
     */
    window.WarmTransfer.init = function(callSid) {
        transferState.callSid = callSid;
        transferState.status = 'inviting';
        createStatusUI();
        pollTransferStatus();
    };

    /**
     * Create status UI element
     */
    function createStatusUI() {
        if (statusEl) statusEl.remove();

        statusEl = document.createElement('div');
        statusEl.id = 'sd-warm-transfer-status';
        statusEl.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#0b1a2e;color:#fff;padding:12px 24px;border-radius:8px;z-index:9999;font-family:Arial,sans-serif;display:flex;align-items:center;gap:12px;';
        
        updateStatusUI('Connecting to agent...');
        document.body.appendChild(statusEl);
    }

    /**
     * Update status text
     */
    function updateStatusUI(message) {
        if (!statusEl) return;
        statusEl.innerHTML = [
            '<span style="width:10px;height:10px;background:#ffd700;border-radius:50%;animation:pulse 1s infinite;"></span>',
            '<span>' + message + '</span>',
            '<button onclick="WarmTransfer.cancel()" style="margin-left:12px;padding:4px 10px;background:#e74c3c;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Cancel</button>'
        ].join('');
    }

    /**
     * Poll for transfer status
     */
    function pollTransferStatus() {
        if (!transferState.callSid) return;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'index.php?entryPoint=transferStatus&call_sid=' + encodeURIComponent(transferState.callSid), true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);
                handleStatusUpdate(data);
                
                if (data.status !== 'completed' && data.status !== 'failed') {
                    setTimeout(pollTransferStatus, 2000);
                }
            }
        };
        xhr.send();
    }

    /**
     * Handle status update
     */
    function handleStatusUpdate(data) {
        switch (data.status) {
            case 'connected':
                updateStatusUI('Agent connected. Click to merge...');
                addMergeButton();
                break;
            case 'merged':
                updateStatusUI('Transfer complete! Connected to agent.');
                setTimeout(cleanup, 3000);
                break;
            case 'failed':
                updateStatusUI('Transfer failed.');
                setTimeout(cleanup, 3000);
                break;
        }
    }

    /**
     * Add merge call button
     */
    function addMergeButton() {
        if (!statusEl) return;
        var btn = document.createElement('button');
        btn.textContent = 'Merge Calls';
        btn.style.cssText = 'padding:4px 12px;background:#00d4aa;color:#0b1a2e;border:none;border-radius:4px;cursor:pointer;font-size:12px;font-weight:600;margin-left:8px;';
        btn.onclick = mergeCalls;
        statusEl.appendChild(btn);
    }

    /**
     * Merge caller and agent into conference
     */
    function mergeCalls() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?entryPoint=transferMerge', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                updateStatusUI('Merging calls...');
            } else {
                alert('Merge failed');
            }
        };
        xhr.send('call_sid=' + encodeURIComponent(transferState.callSid));
    }

    /**
     * Cancel warm transfer
     */
    window.WarmTransfer.cancel = function() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?entryPoint=transferCancel', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            cleanup();
            // Resume original call
            if (typeof TwilioDialerUI !== 'undefined') {
                TwilioDialerUI.unholdCall();
            }
        };
        xhr.send('call_sid=' + encodeURIComponent(transferState.callSid));
    };

    /**
     * Cleanup UI
     */
    function cleanup() {
        if (statusEl) {
            statusEl.remove();
            statusEl = null;
        }
        transferState = {
            callSid: null,
            conferenceSid: null,
            agentCallSid: null,
            status: 'idle',
            participantCount: 0
        };
    }

})(window);
