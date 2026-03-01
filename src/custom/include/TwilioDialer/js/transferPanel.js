/**
 * Transfer Panel - Agent Selector
 * Epic 10: Transfer - S-097-S-099 (9 pts)
 */

(function(window) {
    'use strict';

    window.TransferPanel = window.TransferPanel || {};

    var panelEl = null;
    var currentCallSid = null;
    var transferType = 'warm'; // 'warm' or 'cold'

    /**
     * Show transfer panel
     * @param {string} callSid - Current call SID
     * @param {string} type - 'warm' or 'cold'
     */
    window.TransferPanel.show = function(callSid, type) {
        currentCallSid = callSid;
        transferType = type || 'warm';
        createPanel();
        loadAgents();
    };

    /**
     * Create panel DOM
     */
    function createPanel() {
        if (document.getElementById('sd-transfer-panel')) {
            document.getElementById('sd-transfer-panel').remove();
        }

        panelEl = document.createElement('div');
        panelEl.id = 'sd-transfer-panel';
        panelEl.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:400px;max-height:80vh;background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.3);z-index:10000;font-family:Arial,sans-serif;';

        var title = transferType === 'warm' ? 'Warm Transfer' : 'Cold Transfer';
        
        panelEl.innerHTML = [
            '<div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;background:#0b1a2e;color:#fff;border-radius:12px 12px 0 0;">',
            '  <span style="font-weight:600;font-size:16px;">' + title + '</span>',
            '  <button onclick="TransferPanel.close()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:20px;">&times;</button>',
            '</div>',
            '<div style="padding:20px;">',
            '  <div style="margin-bottom:16px;">',
            '    <input type="text" id="sd-transfer-search" placeholder="Search agents..." style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;" onkeyup="TransferPanel.filterAgents()">',
            '  </div>',
            '  <div id="sd-transfer-agents" style="max-height:300px;overflow-y:auto;">',
            '    <div style="padding:20px;text-align:center;color:#999;">Loading agents...</div>',
            '  </div>',
            '</div>',
            '<div style="display:flex;gap:10px;padding:16px 20px;border-top:1px solid #eee;">',
            '  <button onclick="TransferPanel.close()" style="flex:1;padding:10px;background:#f5f5f5;border:1px solid #ddd;border-radius:6px;cursor:pointer;font-size:14px;">Cancel</button>',
            '</div>'
        ].join('');

        document.body.appendChild(panelEl);

        // Close on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') TransferPanel.close();
        }, { once: true });
    }

    /**
     * Load available agents
     */
    function loadAgents() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'index.php?module=Users&action=getAvailableUsers&to_pdf=1', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var agents = JSON.parse(xhr.responseText);
                    renderAgents(agents);
                } catch (e) {
                    renderAgents([]);
                }
            } else {
                renderAgents([]);
            }
        };
        xhr.onerror = function() {
            renderAgents([]);
        };
        xhr.send();
    }

    /**
     * Render agent list
     */
    function renderAgents(agents) {
        var container = document.getElementById('sd-transfer-agents');
        
        if (!agents || agents.length === 0) {
            container.innerHTML = '<div style="padding:20px;text-align:center;color:#999;">No agents available</div>';
            return;
        }

        var html = agents.map(function(agent) {
            var statusColor = agent.available ? '#00d4aa' : '#e74c3c';
            var statusText = agent.available ? 'Available' : 'Busy';
            var disabled = !agent.available ? 'opacity:0.5;pointer-events:none;' : '';

            return [
                '<div class="sd-transfer-agent" data-name="' + (agent.name || '').toLowerCase() + '" data-id="' + agent.id + '" style="display:flex;align-items:center;justify-content:space-between;padding:12px;border-bottom:1px solid #f0f0f0;cursor:pointer;' + disabled + '" onclick="TransferPanel.selectAgent(\'' + agent.id + '\', \'' + agent.name + '\')">',
                '  <div style="display:flex;align-items:center;">',
                '    <div style="width:36px;height:36px;border-radius:50%;background:#0b1a2e;color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;margin-right:12px;">' + (agent.name || '?').charAt(0).toUpperCase() + '</div>',
                '    <div>',
                '      <div style="font-weight:600;font-size:14px;">' + (agent.name || 'Unknown') + '</div>',
                '      <div style="font-size:12px;color:#666;">' + (agent.title || '') + '</div>',
                '    </div>',
                '  </div>',
                '  <span style="padding:4px 10px;background:' + statusColor + ';color:#fff;border-radius:12px;font-size:11px;">' + statusText + '</span>',
                '</div>'
            ].join('');
        }).join('');

        container.innerHTML = html;
    }

    /**
     * Filter agents by search
     */
    window.TransferPanel.filterAgents = function() {
        var query = document.getElementById('sd-transfer-search').value.toLowerCase();
        var agents = document.querySelectorAll('.sd-transfer-agent');

        agents.forEach(function(agent) {
            var name = agent.dataset.name;
            agent.style.display = name.indexOf(query) !== -1 ? 'flex' : 'none';
        });
    };

    /**
     * Select agent for transfer
     */
    window.TransferPanel.selectAgent = function(agentId, agentName) {
        if (!confirm('Transfer call to ' + agentName + '?')) return;

        var endpoint = transferType === 'warm' 
            ? 'index.php?entryPoint=transferWarm' 
            : 'index.php?entryPoint=transferCold';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', endpoint, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var result = JSON.parse(xhr.responseText);
                if (result.success) {
                    TransferPanel.close();
                    // Refresh UI
                    if (typeof TwilioDialerUI !== 'undefined') {
                        TwilioDialerUI.showTransferStatus(transferType, agentName, 'in_progress');
                    }
                } else {
                    alert('Transfer failed: ' + (result.error || 'Unknown error'));
                }
            } else {
                alert('Transfer failed. Please try again.');
            }
        };
        xhr.onerror = function() {
            alert('Network error. Please try again.');
        };
        xhr.send(
            'call_sid=' + encodeURIComponent(currentCallSid) + 
            '&target_agent_id=' + encodeURIComponent(agentId) +
            '&transfer_type=' + encodeURIComponent(transferType)
        );
    };

    /**
     * Close panel
     */
    window.TransferPanel.close = function() {
        if (panelEl) {
            panelEl.remove();
            panelEl = null;
        }
    };

})(window);
