/**
 * Dialer Overlay
 * Main UI component for the call overlay with transfer controls
 */
(function() {
    'use strict';

    const DialerOverlay = {
        overlay: null,
        currentCallSid: null,
        transferManager: null,
        transferStatusElement: null,
        
        /**
         * Initialize the dialer overlay
         */
        init() {
            this.createStyles();
            this.transferManager = new TransferManager(this);
        },

        /**
         * Create CSS styles
         */
        createStyles() {
            if (document.getElementById('dialer-overlay-styles')) return;

            const styles = document.createElement('style');
            styles.id = 'dialer-overlay-styles';
            styles.textContent = `
                .dialer-overlay {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    width: 320px;
                    background: #1a1a2e;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
                    z-index: 10000;
                    color: #fff;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                }
                .dialer-overlay-header {
                    padding: 16px 20px;
                    border-bottom: 1px solid rgba(255,255,255,0.1);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .dialer-overlay-title {
                    font-size: 14px;
                    font-weight: 600;
                    margin: 0;
                }
                .dialer-overlay-status {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 12px;
                    color: #28a745;
                }
                .status-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background: #28a745;
                    animation: pulse 2s infinite;
                }
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.5; }
                }
                .dialer-overlay-body {
                    padding: 16px 20px;
                }
                .call-info {
                    text-align: center;
                    margin-bottom: 16px;
                }
                .call-number {
                    font-size: 20px;
                    font-weight: 600;
                    margin-bottom: 4px;
                }
                .call-timer {
                    font-size: 14px;
                    color: #888;
                }
                .dialer-controls {
                    display: flex;
                    justify-content: center;
                    gap: 12px;
                    margin-bottom: 16px;
                }
                .dialer-btn {
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    border: none;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.15s;
                    font-size: 18px;
                }
                .dialer-btn-mute {
                    background: rgba(255,255,255,0.1);
                    color: #fff;
                }
                .dialer-btn-hold {
                    background: rgba(255,193,7,0.2);
                    color: #ffc107;
                }
                .dialer-btn-hold.active {
                    background: #ffc107;
                    color: #000;
                }
                .dialer-btn-transfer {
                    background: rgba(74,144,217,0.2);
                    color: #4a90d9;
                }
                .dialer-btn-transfer:hover {
                    background: rgba(74,144,217,0.3);
                }
                .dialer-btn-transfer.active {
                    background: #4a90d9;
                    color: #fff;
                }
                .dialer-btn-keypad {
                    background: rgba(255,255,255,0.1);
                    color: #fff;
                }
                .dialer-btn-end {
                    background: #dc3545;
                    color: #fff;
                }
                .dialer-btn-end:hover {
                    background: #c82333;
                }
                .transfer-status {
                    padding: 10px 16px;
                    border-radius: 8px;
                    background: rgba(74,144,217,0.15);
                    margin-top: 12px;
                    font-size: 13px;
                    text-align: center;
                    color: #4a90d9;
                    display: none;
                }
                .transfer-status.active {
                    display: block;
                }
                .transfer-status.holding {
                    background: rgba(255,193,7,0.15);
                    color: #ffc107;
                }
                .transfer-status.connected {
                    background: rgba(40,167,69,0.15);
                    color: #28a745;
                }
                .transfer-status.failed {
                    background: rgba(220,53,69,0.15);
                    color: #dc3545;
                }
                .transfer-actions {
                    display: flex;
                    gap: 8px;
                    margin-top: 12px;
                    justify-content: center;
                }
                .transfer-action-btn {
                    padding: 8px 16px;
                    border-radius: 6px;
                    border: none;
                    font-size: 12px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.15s;
                }
                .btn-complete {
                    background: #28a745;
                    color: #fff;
                }
                .btn-complete:hover {
                    background: #218838;
                }
                .btn-cancel {
                    background: rgba(255,255,255,0.1);
                    color: #fff;
                }
                .btn-cancel:hover {
                    background: rgba(255,255,255,0.2);
                }
            `;
            document.head.appendChild(styles);
        },

        /**
         * Show the overlay for an active call
         * @param {Object} callData - Call information
         */
        show(callData) {
            this.currentCallSid = callData.callSid;
            this.render(callData);
            this.startTimer();
        },

        /**
         * Hide the overlay
         */
        hide() {
            if (this.overlay) {
                this.overlay.remove();
                this.overlay = null;
            }
            this.stopTimer();
        },

        /**
         * Render the overlay
         * @param {Object} callData - Call information
         */
        render(callData) {
            this.hide();
            
            this.overlay = document.createElement('div');
            this.overlay.className = 'dialer-overlay';
            this.overlay.innerHTML = `
                <div class="dialer-overlay-header">
                    <h4 class="dialer-overlay-title">Active Call</h4>
                    <div class="dialer-overlay-status">
                        <span class="status-dot"></span>
                        Connected
                    </div>
                </div>
                <div class="dialer-overlay-body">
                    <div class="call-info">
                        <div class="call-number">${callData.number || 'Unknown'}</div>
                        <div class="call-timer" id="call-timer">00:00</div>
                    </div>
                    <div class="dialer-controls">
                        <button class="dialer-btn dialer-btn-mute" title="Mute">🎤</button>
                        <button class="dialer-btn dialer-btn-hold" title="Hold">⏸️</button>
                        <button class="dialer-btn dialer-btn-transfer" title="Transfer" id="btn-transfer"
>🔄</button>
                        <button class="dialer-btn dialer-btn-keypad" title="Keypad">#️⃣</button>
                        <button class="dialer-btn dialer-btn-end" title="End Call">📞</button>
                    </div>
                    <div class="transfer-status" id="transfer-status"></div>
                    <div class="transfer-actions" id="transfer-actions" style="display:none;">
                        <button class="transfer-action-btn btn-complete" id="btn-complete">Complete</button>
                        <button class="transfer-action-btn btn-cancel" id="btn-cancel">Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(this.overlay);
            this.attachEvents(callData);
        },

        /**
         * Attach event listeners
         * @param {Object} callData - Call information
         */
        attachEvents(callData) {
            const muteBtn = this.overlay.querySelector('.dialer-btn-mute');
            const holdBtn = this.overlay.querySelector('.dialer-btn-hold');
            const transferBtn = this.overlay.querySelector('#btn-transfer');
            const endBtn = this.overlay.querySelector('.dialer-btn-end');
            const keypadBtn = this.overlay.querySelector('.dialer-btn-keypad');

            muteBtn.addEventListener('click', () => this.toggleMute());
            holdBtn.addEventListener('click', () => this.toggleHold());
            transferBtn.addEventListener('click', () => this.openTransferPanel());
            endBtn.addEventListener('click', () => this.endCall());
            keypadBtn.addEventListener('click', () => this.showKeypad());

            // Transfer action buttons
            const completeBtn = this.overlay.querySelector('#btn-complete');
            const cancelBtn = this.overlay.querySelector('#btn-cancel');
            if (completeBtn) {
                completeBtn.addEventListener('click', () => this.completeTransfer());
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => this.cancelTransfer());
            }
        },

        /**
         * Open transfer panel with agent list
         */
        openTransferPanel() {
            if (typeof TransferPanel === 'undefined') {
                console.error('TransferPanel not loaded');
                return;
            }

            // Fetch available agents
            this.fetchAgents().then(agents => {
                TransferPanel.open(agents, (result) => {
                    this.handleTransfer(result);
                });
            });
        },

        /**
         * Fetch available agents from server
         * @returns {Promise}
         */
        async fetchAgents() {
            try {
                const response = await fetch('custom/entrypoints/getAgents.php');
                const data = await response.json();
                return data.agents || [];
            } catch (error) {
                console.error('Failed to fetch agents:', error);
                // Return mock data for demo
                return [
                    { id: '1', name: 'Alice Smith', extension: '1001', status: 'available' },
                    { id: '2', name: 'Bob Jones', extension: '1002', status: 'busy' },
                    { id: '3', name: 'Carol White', extension: '1003', status: 'available' }
                ];
            }
        },

        /**
         * Handle transfer initiation
         * @param {Object} result - Transfer result with agent and type
         */
        handleTransfer(result) {
            if (!result || !result.agent) return;

            if (typeof WarmTransfer !== 'undefined') {
                WarmTransfer.init(this.currentCallSid);
                
                // Register callbacks
                WarmTransfer.on('onTransferStart', (data) => {
                    this.showTransferStatus('Initiating transfer...', 'holding');
                });
                
                WarmTransfer.on('onHoldComplete', (data) => {
                    this.showTransferStatus('Caller on hold. Calling agent...', 'holding');
                });
                
                WarmTransfer.on('onAgentConnected', (data) => {
                    this.showTransferStatus('Agent connected. Click Complete to finish.', 'connected');
                    this.showTransferActions(true);
                });
                
                WarmTransfer.on('onTransferComplete', (data) => {
                    this.hide();
                });
                
                WarmTransfer.on('onTransferCancel', (data) => {
                    this.showTransferStatus('', '');
                    this.showTransferActions(false);
                });

                WarmTransfer.on('onError', (error) => {
                    this.showTransferStatus('Transfer failed: ' + error.message, 'failed');
                    this.showTransferActions(false);
                });

                WarmTransfer.initiate(result.agent, result.type);
            }
        },

        /**
         * Show transfer status message
         * @param {string} message - Status message
         * @param {string} state - State class (holding, connected, failed)
         */
        showTransferStatus(message, state) {
            const statusEl = this.overlay.querySelector('#transfer-status');
            statusEl.textContent = message;
            statusEl.className = 'transfer-status' + (state ? ' ' + state : '');
            statusEl.classList.toggle('active', !!message);
            this.transferStatusElement = statusEl;
        },

        /**
         * Show/hide transfer action buttons
         * @param {boolean} show - Whether to show
         */
        showTransferActions(show) {
            const actionsEl = this.overlay.querySelector('#transfer-actions');
            actionsEl.style.display = show ? 'flex' : 'none';
        },

        /**
         * Complete the warm transfer
         */
        completeTransfer() {
            if (typeof WarmTransfer !== 'undefined') {
                WarmTransfer.complete();
            }
        },

        /**
         * Cancel the transfer
         */
        cancelTransfer() {
            if (typeof WarmTransfer !== 'undefined') {
                WarmTransfer.cancel();
            }
        },

        /**
         * Toggle mute
         */
        toggleMute() {
            const btn = this.overlay.querySelector('.dialer-btn-mute');
            btn.classList.toggle('active');
            // Implement mute logic
        },

        /**
         * Toggle hold
         */
        toggleHold() {
            const btn = this.overlay.querySelector('.dialer-btn-hold');
            btn.classList.toggle('active');
            // Implement hold logic
        },

        /**
         * Show keypad
         */
        showKeypad() {
            // Implement keypad display
        },

        /**
         * End the current call
         */
        endCall() {
            if (typeof DialerCore !== 'undefined') {
                DialerCore.hangup();
            }
            this.hide();
        },

        /**
         * Start call timer
         */
        startTimer() {
            this.callStartTime = Date.now();
            this.timerInterval = setInterval(() => {
                const elapsed = Math.floor((Date.now() - this.callStartTime) / 1000);
                const mins = String(Math.floor(elapsed / 60)).padStart(2, '0');
                const secs = String(elapsed % 60).padStart(2, '0');
                const timer = document.getElementById('call-timer');
                if (timer) timer.textContent = `${mins}:${secs}`;
            }, 1000);
        },

        /**
         * Stop call timer
         */
        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        }
    };

    // Transfer Manager helper class
    class TransferManager {
        constructor(overlay) {
            this.overlay = overlay;
        }
    }

    window.DialerOverlay = DialerOverlay;
    
    // Auto-initialize if needed
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => DialerOverlay.init());
    } else {
        DialerOverlay.init();
    }
})();
