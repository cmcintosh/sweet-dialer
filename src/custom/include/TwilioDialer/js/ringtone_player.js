/**
 * S-099: Ringtone Playback Integration
 * Play dual ringtone in browser on inbound calls
 * Stop on answer/timeout
 */

if (typeof SUGAR.RingtonePlayer === 'undefined') {
    SUGAR.RingtonePlayer = {
        audio: null,
        isPlaying: false,
        currentRingtoneUrl: null,
        
        /**
         * Initialize ringtone player
         * @param {string} ringtoneUrl - URL to ringtone audio file
         */
        init: function(ringtoneUrl) {
            this.currentRingtoneUrl = ringtoneUrl || '/custom/include/TwilioDialer/audio/default_ring.mp3';
        },
        
        /**
         * Play ringtone on inbound call
         */
        playInbound: function() {
            if (this.isPlaying) {
                return;
            }
            
            this.audio = new Audio(this.currentRingtoneUrl);
            this.audio.loop = true;
            this.audio.volume = 0.7;
            
            var self = this;
            this.audio.play().then(function() {
                self.isPlaying = true;
                console.log('[RingtonePlayer] Playing inbound ringtone');
            }).catch(function(error) {
                console.error('[RingtonePlayer] Error playing ringtone:', error);
            });
            
            // Auto-stop after 30 seconds (timeout)
            setTimeout(function() {
                self.stop();
            }, 30000);
        },
        
        /**
         * Stop ringtone playback
         */
        stop: function() {
            if (this.audio && this.isPlaying) {
                this.audio.pause();
                this.audio.currentTime = 0;
                this.isPlaying = false;
                console.log('[RingtonePlayer] Ringtone stopped');
            }
        },
        
        /**
         * Handle call answered event
         */
        onAnswer: function() {
            this.stop();
            console.log('[RingtonePlayer] Call answered - ringtone stopped');
        },
        
        /**
         * Handle call timeout/rejected event
         */
        onTimeout: function() {
            this.stop();
            console.log('[RingtonePlayer] Call timeout - ringtone stopped');
        }
    };
}

// Event listeners for Twilio Device callbacks
document.addEventListener('twilio-incoming', function(event) {
    if (event.detail.ringtoneUrl) {
        SUGAR.RingtonePlayer.init(event.detail.ringtoneUrl);
    }
    SUGAR.RingtonePlayer.playInbound();
});

document.addEventListener('twilio-answer', function() {
    SUGAR.RingtonePlayer.onAnswer();
});

document.addEventListener('twilio-timeout', function() {
    SUGAR.RingtonePlayer.onTimeout();
});

document.addEventListener('twilio-reject', function() {
    SUGAR.RingtonePlayer.onTimeout();
});
