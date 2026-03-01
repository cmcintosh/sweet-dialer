/**
 * S-096: Voicemail Player JavaScript
 * Handles audio playback in Call Tracker
 */

if (typeof SUGAR.voicemailPlayer === 'undefined') {
    SUGAR.voicemailPlayer = {
        init: function() {
            // Initialize audio players
            var players = document.querySelectorAll('.voicemail-player');
            players.forEach(function(player) {
                player.addEventListener('play', function(e) {
                    // Pause other players when one starts
                    players.forEach(function(p) {
                        if (p !== e.target) {
                            p.pause();
                        }
                    });
                });
            });
        }
    };
}

// Initialize on document ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', SUGAR.voicemailPlayer.init);
} else {
    SUGAR.voicemailPlayer.init();
}
