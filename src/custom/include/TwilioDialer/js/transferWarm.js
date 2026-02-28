/**
 * Warm Transfer Logic
 * Handles holding calls and managing conference-based warm transfers
 */
(function() {
    'use strict';

    const WarmTransfer = {
        currentCallSid: null,
        transferStatus: 'idle',
        conferenceName: null,
        agentCallSid: null,
        transferCallbacks: {},
        pollingInterval: null,
        selectedTransferType: 'warm',

        /**
         * Initialize warm transfer
         * @param {string} callSid - Current call SID
         */
        init(callSid) {
            this.currentCallSid = callSid;
            this.transferStatus = 'idle';
        },

        /**
         * Hold the current call (places in conference)
         * @returns {Promise} Resolves when on hold
         */
        async hold() {
            if (!this.currentCallSid) {
                throw new Error('No active call to hold');
            }

            this.transferStatus = 'holding';
            this.triggerCallback('onHoldStart');

            try {
                const response = await fetch('custom/entrypoints/holdCall.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        call_sid: this.currentCallSid,
                        action: 'hold'
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to hold call');
                }

                this.transferStatus = 'holding';
                this.triggerCallback('onHoldComplete', data);
                return data;

            } catch (error) {
                this.transferStatus = 'idle';
                this.triggerCallback('onError', error);
                throw error;
            }
        },

        /**
         * Resume held call
         * @returns {Promise} Resolves when resumed
         */
        async resume() {
            if (!this.currentCallSid) {
                throw new Error('No active call to resume');
            }

            this.triggerCallback('onResumeStart');

            try {
                const response = await fetch('custom/entrypoints/holdCall.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        call_sid: this.currentCallSid,
                        action: 'resume'
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to resume call');
                }

                this.transferStatus = 'idle';
                this.triggerCallback('onResumeComplete', data);
                return data;

            } catch (error) {
                this.triggerCallback('onError', error);
                throw error;
            }
        },

        /**
         * Initiate warm transfer
         * @param {Object} targetAgent - Agent to transfer to
         * @param {string} type - Transfer type (warm or cold)
         * @returns {Promise} Resolves with transfer result
         */
        async initiate(targetAgent, type = 'warm') {
            if (!this.currentCallSid || !targetAgent) {
                throw new Error('Missing required parameters for transfer');
            }

            this.selectedTransferType = type;
            
            if (type === 'cold') {
                return this.initiateColdTransfer(targetAgent);
            }
            
            return this.initiateWarmTransfer(targetAgent);
        },

        /**
         * Initiate warm transfer via conference
         * @param {Object} targetAgent - Agent to transfer to
         * @returns {Promise}
         */
        async initiateWarmTransfer(targetAgent) {
            this.transferStatus = 'inviting';
            this.triggerCallback('onTransferStart', { agent: targetAgent });

            try {
                const response = await fetch('custom/entrypoints/transferWarm.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        call_sid: this.currentCallSid,
                        target_agent_id: targetAgent.id
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to initiate transfer');
                }

                this.conferenceName = data.conference_name;
                this.agentCallSid = data.agent_call_sid;
                this.transferStatus = 'inviting';

                // Start polling for conference status
                this.startStatusPolling();

                this.triggerCallback('onTransferInitiated', data);
                return data;

            } catch (error) {
                this.transferStatus = 'failed';
                this.triggerCallback('onError', error);
                throw error;
            }
        },

        /**
         * Initiate cold transfer (blind transfer)
         * @param {Object} targetAgent - Agent to transfer to
         * @returns {Promise}
         */
        async initiateColdTransfer(targetAgent) {
            this.transferStatus = 'transferring';
            this.triggerCallback('onTransferStart', { agent: targetAgent });

            try {
                const response = await fetch('custom/entrypoints/transferCold.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        call_sid: this.currentCallSid,
                        target_agent_id: targetAgent.id
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to initiate transfer');
                }

                this.transferStatus = 'completed';
                this.triggerCallback('onTransferComplete', data);
                return data;

            } catch (error) {
                this.transferStatus = 'failed';
                this.triggerCallback('onError', error);
                throw error;
            }
        },

        /**
         * Complete the transfer (remove current agent from conference)
         * @returns {Promise}
         */
        async complete() {
            this.transferStatus = 'completed';
            this.stopStatusPolling();

            try {
                const response = await fetch('custom/entrypoints/transferComplete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        call_sid: this.currentCallSid,
                        conference_name: this.conferenceName
                    })
                });

                const data = await response.json();
                this.triggerCallback('onTransferComplete', data);
                return data;

            } catch (error) {
                this.triggerCallback('onError', error);
                throw error;
            }
        },

        /**
         * Cancel the transfer (return to caller)
         * @returns {Promise}
         */
        async cancel() {
            this.stopStatusPolling();

            try {
                // Remove agent from conference first
                if (this.agentCallSid) {
                    await fetch('custom/entrypoints/transferCancel.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            agent_call_sid: this.agentCallSid
                        })
                    });
                }

                // Resume call
                const data = await this.resume();
                this.transferStatus = 'idle';
                this.triggerCallback('onTransferCancel', data);
                return data;

            } catch (error) {
                this.triggerCallback('onError', error);
                throw error;
            }
        },

        /**
         * Start polling for conference status
         */
        startStatusPolling() {
            this.stopStatusPolling();
            this.pollingInterval = setInterval(() => this.pollStatus(), 3000);
        },

        /**
         * Stop polling
         */
        stopStatusPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        },

        /**
         * Poll for conference status
         */
        async pollStatus() {
            if (!this.conferenceName) return;

            try {
                const response = await fetch(
                    `custom/entrypoints/conferenceStatus.php?conference=${encodeURIComponent(this.conferenceName)}`
                );
                const data = await response.json();

                if (data.participants >= 2) {
                    this.transferStatus = 'connected';
                    this.triggerCallback('onAgentConnected', data);
                }

            } catch (error) {
                console.error('Status poll error:', error);
            }
        },

        /**
         * Register callback
         * @param {string} event - Event name
         * @param {Function} callback - Callback function
         */
        on(event, callback) {
            this.transferCallbacks[event] = callback;
        },

        /**
         * Trigger callback
         * @param {string} event - Event name
         * @param {*} data - Event data
         */
        triggerCallback(event, data) {
            if (this.transferCallbacks[event]) {
                this.transferCallbacks[event](data);
            }
        },

        /**
         * Get current status
         * @returns {string}
         */
        getStatus() {
            return this.transferStatus;
        },

        /**
         * Get status label for display
         * @returns {string}
         */
        getStatusLabel() {
            const labels = {
                idle: 'Ready',
                holding: 'On Hold',
                inviting: 'Calling Agent',
                transferring: 'Transferring',
                connected: 'Agent Connected',
                completed: 'Transfer Complete',
                failed: 'Transfer Failed'
            };
            return labels[this.transferStatus] || this.transferStatus;
        },

        /**
         * Check if transfer is in progress
         * @returns {boolean}
         */
        isInProgress() {
            return ['holding', 'inviting', 'transferring', 'connected'].includes(this.transferStatus);
        },

        /**
         * Clean up resources
         */
        destroy() {
            this.stopStatusPolling();
            this.transferCallbacks = {};
            this.currentCallSid = null;
            this.conferenceName = null;
            this.agentCallSid = null;
            this.transferStatus = 'idle';
        }
    };

    window.WarmTransfer = WarmTransfer;
})();
