/**
 * SweetDialerCTI Client-Side Validation
 * S-025: Client-side validation
 */

// Validation error messages
var CTIValidationMessages = {
    nameRequired: 'Name is required',
    accountSidFormat: 'Account SID must start with "AC"',
    phoneSidFormat: 'Phone SID must start with "PN"',
    apiKeySidFormat: 'API Key SID must start with "SK"',
    phoneFormat: 'Phone number must be in E.164 format (e.g., +12345678901)',
    fileType: 'Only MP3 files are allowed',
    fileSize: 'File size must be less than 10MB',
    maxFileSize: 10 * 1024 * 1024 // 10MB in bytes
};

/**
 * Main form validation function
 */
function validateCTIForm() {
    var errors = [];
    
    // Name validation
    var nameField = document.getElementById('name');
    if (nameField) {
        if (!nameField.value || nameField.value.trim() === '') {
            errors.push({field: nameField, message: CTIValidationMessages.nameRequired});
        }
    }
    
    // Account SID validation
    var accountSid = document.getElementById('accounts_sid');
    if (accountSid && accountSid.value.trim() !== '') {
        if (!accountSid.value.startsWith('AC')) {
            errors.push({field: accountSid, message: CTIValidationMessages.accountSidFormat});
        }
    }
    
    // Phone SID validation
    var phoneSid = document.getElementById('phone_sid');
    if (phoneSid && phoneSid.value.trim() !== '') {
        if (!phoneSid.value.startsWith('PN')) {
            errors.push({field: phoneSid, message: CTIValidationMessages.phoneSidFormat});
        }
    }
    
    // API Key SID validation
    var apiKeySid = document.getElementById('api_key_sid');
    if (apiKeySid && apiKeySid.value.trim() !== '') {
        if (!apiKeySid.value.startsWith('SK')) {
            errors.push({field: apiKeySid, message: CTIValidationMessages.apiKeySidFormat});
        }
    }
    
    // Agent Phone validation
    var phoneField = document.getElementById('agent_phone_number');
    if (phoneField && phoneField.value.trim() !== '') {
        if (!validateE164(phoneField.value)) {
            errors.push({field: phoneField, message: CTIValidationMessages.phoneFormat});
        }
    }
    
    return displayErrors(errors);
}

function validateE164(phoneNumber) {
    var e164Regex = /^\+\d{10,15}$/;
    return e164Regex.test(phoneNumber);
}

function displayErrors(errors) {
    clearValidationErrors();
    if (errors.length === 0) return true;
    
    for (var i = 0; i < errors.length; i++) {
        var error = errors[i];
        error.field.classList.add('validation-failed');
        
        var errorDiv = document.createElement('div');
        errorDiv.className = 'inline-validation-error';
        errorDiv.style.color = '#c00';
        errorDiv.style.fontSize = '11px';
        errorDiv.style.marginTop = '3px';
        errorDiv.innerHTML = error.message;
        error.field.parentNode.insertBefore(errorDiv, error.field.nextSibling);
    }
    
    if (errors[0].field) errors[0].field.focus();
    return false;
}

function clearValidationErrors() {
    var failedFields = document.querySelectorAll('.validation-failed');
    for (var i = 0; i < failedFields.length; i++) {
        failedFields[i].classList.remove('validation-failed');
    }
    
    var errorMessages = document.querySelectorAll('.inline-validation-error');
    for (var j = errorMessages.length - 1; j >= 0; j--) {
        if (errorMessages[j].parentNode) {
            errorMessages[j].parentNode.removeChild(errorMessages[j]);
        }
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        addValidationListeners();
    });
} else {
    addValidationListeners();
}

function addValidationListeners() {
    // Add blur listeners
    var nameField = document.getElementById('name');
    if (nameField) {
        nameField.addEventListener('blur', function() {
            if (!this.value || this.value.trim() === '') {
                showFieldError(this, CTIValidationMessages.nameRequired);
            } else {
                clearFieldError(this);
            }
        });
    }
}

function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('validation-failed');
    var errorDiv = document.createElement('div');
    errorDiv.className = 'inline-validation-error';
    errorDiv.style.color = '#c00';
    errorDiv.style.fontSize = '11px';
    errorDiv.style.marginTop = '3px';
    errorDiv.innerHTML = message;
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

function clearFieldError(field) {
    field.classList.remove('validation-failed');
    var errorDiv = field.nextSibling;
    while (errorDiv && errorDiv.className === 'inline-validation-error') {
        var next = errorDiv.nextSibling;
        errorDiv.parentNode.removeChild(errorDiv);
        errorDiv = next;
    }
}
