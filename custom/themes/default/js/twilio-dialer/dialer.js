/**
 * Twilio Dialer JavaScript - SuiteCRM Integration
 * Epic 7-8: Twilio Client SDK Integration
 */
(function() {
    "use strict";
    
    window.TwilioDialer = {
        device: null,
        activeCall: null,
        currentToken: null,
        callState: {
            status: "idle",
            muted: false,
            onHold: false,
            duration: 0,
            timer: null
        },
        
        init: function() {
            var self = this;
            console.log("[TwilioDialer] Initializing...");
            
            this.fetchToken().then(function(token) {
                console.log("[TwilioDialer] Token acquired");
                self.setupDevice(token);
            }).catch(function(err) {
                console.error("[TwilioDialer] Failed to get token:", err);
            });
            
            this.bindEvents();
            this.injectOverlay();
        },
        
        fetchToken: function() {
            return fetch("index.php?entryPoint=twilioToken")
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.error) throw new Error(data.error);
                    TwilioDialer.currentToken = data.token;
                    return data.token;
                });
        },
        
        setupDevice: function(token) {
            if (!window.Twilio || !window.Twilio.Device) {
                console.error("[TwilioDialer] Twilio SDK not loaded");
                return;
            }
            
            this.device = new Twilio.Device(token, {
                codecPreferences: ["opus", "pcmu"],
                fakeLocalDTMF: true,
                enableRingingState: true
            });
            
            this.device.on("ready", function() {
                console.log("[TwilioDialer] Device ready");
            });
            
            this.device.on("error", function(error) {
                console.error("[TwilioDialer] Device error:", error);
            });
            
            this.device.on("incoming", function(call) {
                TwilioDialer.handleIncoming(call);
            });
        },
        
        makeCall: function(phoneNumber, crmRecordType, crmRecordId) {
            if (!this.device) {
                console.error("[TwilioDialer] Device not initialized");
                return;
            }
            
            var params = {
                To: phoneNumber,
                parentType: crmRecordType || "",
                parentId: crmRecordId || ""
            };
            
            this.activeCall = this.device.connect(params);
            this.showOverlay(phoneNumber);
            this.setupCallEvents(this.activeCall);
        },
        
        setupCallEvents: function(call) {
            call.on("ringing", function() {
                TwilioDialer.updateStatus("Ringing...", "ringing");
            });
            
            call.on("accept", function() {
                TwilioDialer.updateStatus("Connected", "active");
                TwilioDialer.startTimer();
            });
            
            call.on("disconnect", function() {
                TwilioDialer.handleDisconnect();
            });
            
            call.on("cancel", function() {
                TwilioDialer.updateStatus("Cancelled", "disconnected");
            });
        },
        
        handleIncoming: function(call) {
            this.activeCall = call;
            var fromNumber = call.parameters.From || "Unknown";
            this.showOverlay(fromNumber);
            this.updateStatus("Incoming Call", "ringing");
            
            // Auto-accept for now (could add UI prompt)
            call.accept();
        },
        
        toggleMute: function() {
            if (this.activeCall) {
                this.callState.muted = !this.callState.muted;
                this.activeCall.mute(this.callState.muted);
                this.updateMuteButton();
                return this.callState.muted;
            }
        },
        
        endCall: function() {
            if (this.activeCall) {
                this.activeCall.disconnect();
            }
        },
        
        sendDtmf: function(digit) {
            if (this.activeCall) {
                this.activeCall.sendDigits(digit);
            }
        },
        
        updateStatus: function(status, cssClass) {
            this.callState.status = status;
            var statusEl = document.getElementById("twilio-status");
            var dotEl = document.getElementById("twilio-status-dot");
            if (statusEl) statusEl.textContent = status;
            if (dotEl) dotEl.className = "status-dot " + cssClass;
        },
        
        updateMuteButton: function() {
            var btn = document.getElementById("twilio-mute-btn");
            if (btn) {
                btn.textContent = this.callState.muted ? "Unmute" : "Mute";
                btn.classList.toggle("muted", this.callState.muted);
            }
        },
        
        startTimer: function() {
            var self = this;
            this.callState.timer = setInterval(function() {
                self.callState.duration++;
                var mins = Math.floor(self.callState.duration / 60);
                var secs = self.callState.duration % 60;
                var timerEl = document.getElementById("twilio-timer");
                if (timerEl) {
                    timerEl.textContent = 
                        (mins < 10 ? "0" : "") + mins + ":" + 
                        (secs < 10 ? "0" : "") + secs;
                }
            }, 1000);
        },
        
        handleDisconnect: function() {
            this.updateStatus("Disconnected", "disconnected");
            clearInterval(this.callState.timer);
            this.saveCallNotes();
            
            var self = this;
            setTimeout(function() {
                TwilioDialer.hideOverlay();
            }, 3000);
        },
        
        saveCallNotes: function() {
            var notesEl = document.getElementById("twilio-call-notes");
            if (notesEl && notesEl.value && this.activeCall) {
                console.log("[TwilioDialer] Saving notes:", notesEl.value);
                // TODO: Ajax call to save notes
            }
        },
        
        showOverlay: function(phoneNumber) {
            var overlay = document.getElementById("twilio-dialer-overlay");
            var phoneEl = document.getElementById("twilio-phone-number");
            var timerEl = document.getElementById("twilio-timer");
            var notesEl = document.getElementById("twilio-call-notes");
            
            if (overlay) {
                overlay.style.display = "block";
                if (phoneEl) phoneEl.textContent = phoneNumber;
                if (timerEl) timerEl.textContent = "00:00";
                if (notesEl) notesEl.value = "";
            }
        },
        
        hideOverlay: function() {
            var overlay = document.getElementById("twilio-dialer-overlay");
            if (overlay) overlay.style.display = "none";
            this.callState.duration = 0;
            this.callState.muted = false;
            this.activeCall = null;
        },
        
        injectOverlay: function() {
            var overlay = document.createElement("div");
            overlay.id = "twilio-dialer-overlay";
            overlay.className = "twilio-dialer-overlay";
            overlay.style.display = "none";
            overlay.innerHTML = `
                <div class="twilio-dialer-header">
                    <span id="twilio-status" class="twilio-status">Ready</span>
                    <span id="twilio-status-dot" class="status-dot ready"></span>
                </div>
                <div class="twilio-dialer-body">
                    <div id="twilio-phone-number" class="phone-number"></div>
                    <div id="twilio-timer" class="timer">00:00</div>
                    <div class="twilio-dialer-controls">
                        <button id="twilio-mute-btn" class="twilio-dialer-btn mute" onclick="TwilioDialer.toggleMute()">Mute</button>
                        <button class="twilio-dialer-btn end" onclick="TwilioDialer.endCall()" style="background:#dc3545;color:#fff">End</button>
                    </div>
                    <textarea id="twilio-call-notes" placeholder="Call notes..."></textarea>
                </div>
            `;
            
            document.body.appendChild(overlay);
        },
        
        bindEvents: function() {
            var self = this;
            
            // Click-to-call buttons
            document.addEventListener("click", function(e) {
                var target = e.target;
                if (target.classList.contains("twilio-click-to-call")) {
                    e.preventDefault();
                    var phone = target.getAttribute("data-phone") || target.textContent.replace(/\s/g, "");
                    var module = target.getAttribute("data-module");
                    var recordId = target.getAttribute("data-id");
                    
                    phone = phone.replace(/[^\d+]/g, ""); // Clean phone number
                    if (phone) {
                        if (!phone.startsWith("+")) phone = "+1" + phone; // Default US
                        self.makeCall(phone, module, recordId);
                    }
                }
            });
            
            // Wrap phone numbers in click-to-call
            this.wrapPhoneNumbers();
        },
        
        wrapPhoneNumbers: function() {
            // Find all phone number text and wrap in click-to-call
            var self = this;
            setTimeout(function() {
                var phoneRegex = /(\+?1?[-.\s]?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4})/g;
                
                // Find elements with phone info
                var phoneElements = document.querySelectorAll("[data-field-name*='phone']");
                phoneElements.forEach(function(el) {
                    var text = el.textContent.trim();
                    if (phoneRegex.test(text) && !el.querySelector(".twilio-click-to-call")) {
                        var btn = document.createElement("a");
                        btn.className = "twilio-click-to-call";
                        btn.href = "#";
                        btn.textContent = " Call";
                        btn.setAttribute("data-phone", text.replace(/[^\d+]/g, ""));
                        el.appendChild(btn);
                    }
                });
            }, 2000);
        }
    };
    
    // Initialize on DOM ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function() {
            TwilioDialer.init();
        });
    } else {
        TwilioDialer.init();
    }
})();
