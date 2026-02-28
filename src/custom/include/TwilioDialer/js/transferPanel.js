/**
 * Transfer Panel - Agent Selector Modal
 * Provides UI for selecting an agent to transfer a call to
 */
(function() {
    'use strict';

    const TransferPanel = {
        modal: null,
        agents: [],
        selectedAgent: null,
        onTransferCallback: null,
        selectedTransferType: 'warm',

        /**
         * Initialize the transfer panel
         */
        init() {
            this.createStyles();
        },

        /**
         * Create CSS styles for the modal
         */
        createStyles() {
            if (document.getElementById('transfer-panel-styles')) return;

            const styles = document.createElement('style');
            styles.id = 'transfer-panel-styles';
            styles.textContent = `
                .transfer-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.6);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                }
                .transfer-modal {
                    background: #fff;
                    border-radius: 12px;
                    width: 420px;
                    max-height: 80vh;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    overflow: hidden;
                }
                .transfer-modal-header {
                    padding: 20px;
                    border-bottom: 1px solid #e0e0e0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .transfer-modal-title {
                    font-size: 18px;
                    font-weight: 600;
                    margin: 0;
                    color: #333;
                }
                .transfer-modal-close {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: #999;
                    padding: 0;
                    width: 32px;
                    height: 32px;
                    line-height: 32px;
                    border-radius: 6px;
                }
                .transfer-modal-close:hover {
                    background: #f0f0f0;
                    color: #333;
                }
                .transfer-search-box {
                    padding: 16px 20px;
                    background: #f8f9fa;
                    border-bottom: 1px solid #e0e0e0;
                }
                .transfer-search-input {
                    width: 100%;
                    padding: 12px 16px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    font-size: 14px;
                    box-sizing: border-box;
                }
                .transfer-search-input:focus {
                    outline: none;
                    border-color: #4a90d9;
                }
                .transfer-agents-list {
                    max-height: 350px;
                    overflow-y: auto;
                }
                .transfer-agent-item {
                    padding: 16px 20px;
                    display: flex;
                    align-items: center;
                    cursor: pointer;
                    border-bottom: 1px solid #f0f0f0;
                    transition: background 0.15s;
                }
                .transfer-agent-item:hover {
                    background: #f8f9fa;
                }
                .transfer-agent-item.selected {
                    background: #e8f4fd;
                }
                .transfer-agent-avatar {
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    background: #4a90d9;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: 600;
                    font-size: 16px;
                    margin-right: 14px;
                }
                .transfer-agent-info {
                    flex: 1;
                }
                .transfer-agent-name {
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 3px;
                }
                .transfer-agent-ext {
                    font-size: 13px;
                    color: #888;
                }
                .transfer-agent-status {
                    display: flex;
                    align-items: center;
                    font-size: 12px;
                    font-weight: 500;
                }
                .status-indicator {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    margin-right: 6px;
                }
                .status-available {
                    color: #28a745;
                }
                .status-available .status-indicator {
                    background: #28a745;
                }
                .status-busy {
                    color: #dc3545;
                }
                .status-busy .status-indicator {
                    background: #dc3545;
                }
                .status-away {
                    color: #ffc107;
                }
                .status-away .status-indicator {
                    background: #ffc107;
                }
                .transfer-modal-actions {
                    padding: 16px 20px;
                    border-top: 1px solid #e0e0e0;
                    display: flex;
                    gap: 12px;
                    justify-content: flex-end;
                }
                .transfer-btn {
                    padding: 12px 24px;
                    border-radius: 8px;
                    border: none;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.15s;
                }
                .transfer-btn-secondary {
                    background: #f0f0f0;
                    color: #555;
                }
                .transfer-btn-secondary:hover {
                    background: #e0e0e0;
                }
                .transfer-btn-primary {
                    background: #4a90d9;
                    color: white;
                }
                .transfer-btn-primary:hover:not(:disabled) {
                    background: #357abd;
                }
                .transfer-btn-primary:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }
                .transfer-empty-state {
                    padding: 40px;
                    text-align: center;
                    color: #888;
                }
                .transfer-tabs {
                    display: flex;
                    border-bottom: 1px solid #e0e0e0;
                }
                .transfer-tab {
                    flex: 1;
                    padding: 14px;
                    border: none;
                    background: none;
                    cursor: pointer;
                    font-weight: 500;
                    color: #666;
                    border-bottom: 2px solid transparent;
                }
                .transfer-tab.active {
                    color: #4a90d9;
                    border-bottom-color: #4a90d9;
                }
            `;
            document.head.appendChild(styles);
        },

        /**
         * Open the transfer modal
         * @param {Array} agents - Array of agent objects
         * @param {Function} onTransfer - Callback when transfer is initiated
         */
        open(agents, onTransfer) {
            this.agents = agents || [];
            this.onTransferCallback = onTransfer;
            this.selectedAgent = null;
            this.selectedTransferType = 'warm';
            this.render();
        },

        /**
         * Close the transfer modal
         */
        close() {
            if (this.modal) {
                this.modal.remove();
                this.modal = null;
            }
        },

        /**
         * Render the modal
         */
        render() {
            this.close();
            this.modal = document.createElement('div');
            this.modal.className = 'transfer-modal-overlay';
            this.modal.innerHTML = `
                <div class="transfer-modal">
                    <div class="transfer-modal-header">
                        <h3 class="transfer-modal-title">Transfer Call</h3>
                        <button class="transfer-modal-close">&times;</button>
                    </div>
                    <div class="transfer-tabs">
                        <button class="transfer-tab active" data-type="warm">Warm Transfer</button>
                        <button class="transfer-tab" data-type="cold">Cold Transfer</button>
                    </div>
                    <div class="transfer-search-box">
                        <input type="text" class="transfer-search-input" 
                               placeholder="Search agents by name or extension...">
                    </div>
                    <div class="transfer-agents-list">
                        ${this.renderAgentsList()}
                    </div>
                    <div class="transfer-modal-actions">
                        <button class="transfer-btn transfer-btn-secondary" id="transfer-cancel">Cancel</button>
                        <button class="transfer-btn transfer-btn-primary" id="transfer-confirm" disabled>
                            Transfer
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(this.modal);
            this.attachEvents();
        },

        /**
         * Render the agents list
         * @returns {string} HTML string
         */
        renderAgentsList() {
            if (!this.agents.length) {
                return '<div class="transfer-empty-state">No agents available</div>';
            }
            return this.agents.map(agent => this.renderAgentItem(agent)).join('');
        },

        /**
         * Render a single agent item
         * @param {Object} agent - Agent data
         * @returns {string} HTML string
         */
        renderAgentItem(agent) {
            const initial = agent.name ? agent.name[0].toUpperCase() : '?';
            const statusClass = agent.status === 'available' ? 'status-available' : 
                               agent.status === 'busy' ? 'status-busy' : 'status-away';
            const statusText = agent.status || 'away';
            
            return `
                <div class="transfer-agent-item" data-agent-id="${agent.id}">
                    <div class="transfer-agent-avatar">${initial}</div>
                    <div class="transfer-agent-info">
                        <div class="transfer-agent-name">${agent.name}</div>
                        <div class="transfer-agent-ext">Ext: ${agent.extension || 'N/A'}</div>
                    </div>
                    <div class="transfer-agent-status ${statusClass}">
                        <span class="status-indicator"></span>
                        ${statusText.charAt(0).toUpperCase() + statusText.slice(1)}
                    </div>
                </div>
            `;
        },

        /**
         * Attach event listeners
         */
        attachEvents() {
            const closeBtn = this.modal.querySelector('.transfer-modal-close');
            const cancelBtn = this.modal.querySelector('#transfer-cancel');
            const confirmBtn = this.modal.querySelector('#transfer-confirm');
            const searchInput = this.modal.querySelector('.transfer-search-input');
            const agentItems = this.modal.querySelectorAll('.transfer-agent-item');
            const tabs = this.modal.querySelectorAll('.transfer-tab');

            closeBtn.addEventListener('click', () => this.close());
            cancelBtn.addEventListener('click', () => this.close());
            confirmBtn.addEventListener('click', () => this.confirmTransfer());

            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));

            agentItems.forEach(item => {
                item.addEventListener('click', () => this.selectAgent(item.dataset.agentId));
            });

            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => this.switchTab(e.target.dataset.type));
            });

            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) this.close();
            });
        },

        /**
         * Handle search input
         * @param {string} query - Search query
         */
        handleSearch(query) {
            const items = this.modal.querySelectorAll('.transfer-agent-item');
            const lowerQuery = query.toLowerCase();

            items.forEach(item => {
                const name = item.querySelector('.transfer-agent-name').textContent.toLowerCase();
                const ext = item.querySelector('.transfer-agent-ext').textContent.toLowerCase();
                const match = name.includes(lowerQuery) || ext.includes(lowerQuery);
                item.style.display = match ? 'flex' : 'none';
            });
        },

        /**
         * Select an agent
         * @param {string} agentId - Agent ID
         */
        selectAgent(agentId) {
            const items = this.modal.querySelectorAll('.transfer-agent-item');
            items.forEach(item => {
                item.classList.toggle('selected', item.dataset.agentId === agentId);
            });

            this.selectedAgent = this.agents.find(a => a.id === agentId);
            const confirmBtn = this.modal.querySelector('#transfer-confirm');
            confirmBtn.disabled = !this.selectedAgent;
        },

        /**
         * Switch between transfer type tabs
         * @param {string} type - warm or cold
         */
        switchTab(type) {
            const tabs = this.modal.querySelectorAll('.transfer-tab');
            tabs.forEach(tab => {
                tab.classList.toggle('active', tab.dataset.type === type);
            });
            this.selectedTransferType = type;
        },

        /**
         * Confirm the transfer
         */
        confirmTransfer() {
            if (!this.selectedAgent) return;

            if (this.onTransferCallback) {
                this.onTransferCallback({
                    agent: this.selectedAgent,
                    type: this.selectedTransferType || 'warm'
                });
            }
            this.close();
        }
    };

    window.TransferPanel = TransferPanel;
    TransferPanel.init();
})();
