/**
 * SweetDialerCTI EditView JavaScript
 * Handles popup selectors and file uploads
 */

function openVoicemailPopup() {
    window.open(
        'index.php?module=SweetDialerVoicemail&action=Popup&callback=selectVoicemail',
        'VoicemailPopup',
        'width=600,height=400,resizable=yes'
    );
}

function selectVoicemail(id, name) {
    document.getElementById('twilio_voice_mail_id').value = id;
    document.getElementById('twilio_voice_mail').value = name;
}

function clearVoicemailSelection() {
    document.getElementById('twilio_voice_mail_id').value = '';
    document.getElementById('twilio_voice_mail').value = '';
}

function removeDualRing() {
    document.getElementById('dual_ring_file_name').value = '';
    var fileInput = document.getElementById('dual_ring_file');
    if (fileInput) fileInput.style.display = 'block';
    var savedDiv = document.getElementById('dual_ring_saved');
    if (savedDiv) savedDiv.style.display = 'none';
}

function removeHoldRing() {
    document.getElementById('hold_ring_file_name').value = '';
    var fileInput = document.getElementById('hold_ring_file');
    if (fileInput) fileInput.style.display = 'block';
    var savedDiv = document.getElementById('hold_ring_saved');
    if (savedDiv) savedDiv.style.display = 'none';
}

function setupColorPickers() {
    var bgPicker = document.getElementById('bg_color');
    var textPicker = document.getElementById('text_color');
    
    if (bgPicker) {
        bgPicker.addEventListener('input', function() {
            var display = document.getElementById('bg_color_hex');
            if (display) display.innerHTML = this.value.toUpperCase();
        });
    }
    
    if (textPicker) {
        textPicker.addEventListener('input', function() {
            var display = document.getElementById('text_color_hex');
            if (display) display.innerHTML = this.value.toUpperCase();
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setupColorPickers();
    });
} else {
    setupColorPickers();
}
