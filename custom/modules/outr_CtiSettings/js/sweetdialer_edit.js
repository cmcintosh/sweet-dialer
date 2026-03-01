/**
 * sweetdialer_edit.js
 *
 * Sweet-Dialer CTI Settings Edit View JavaScript
 *
 * - Auto-fetches phone numbers when credentials are entered
 * - Handles masked field toggles for sensitive data (S-043)
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (typeof SweetDialer === 'undefined') {
    var SweetDialer = {};
}

/**
 * Edit View Handler
 */
SweetDialer.EditView = {
    /**
     * Phone number cache
     */
    phoneNumberCache: {},

    /**
     * Initialize the edit view
     */
    init: function() {
        this.bindCredentialBlur();
        this.bindMaskedFieldToggles();
        this.checkExistingCredentials();
    },

    /**
     * Bind blur events to credentials fields for auto-fetch (S-039)
     */
    bindCredentialBlur: function() {
        var self = this;
        var $accountSid = $('#account_sid, input[name="account_sid"]');
        var $authToken = $('#auth_token, input[name="auth_token"]');

        // Trigger on blur from auth_token if account_sid is present
        $authToken.on('blur', function() {
            var accountSid = $accountSid.val();
            var authToken = $(this).val();

            // Skip if auth_token is masked or empty
            if (authToken === '********' || !authToken || authToken.trim() === '') {
                return;
            }

            if (accountSid && authToken && accountSid.length > 0 && authToken.length > 0) {
                self.fetchPhoneNumbers(accountSid, authToken);
            }
        });

        // Also trigger on account_sid blur if auth_token already has value
        $accountSid.on('blur', function() {
            var accountSid = $(this).val();
            var authToken = $authToken.val();

            if (accountSid && authToken && accountSid.length > 0 && authToken.length > 0 && authToken !== '********') {
                self.fetchPhoneNumbers(accountSid, authToken);
            }
        });
    },

    /**
     * Bind masked field toggle buttons (S-043)
     */
    bindMaskedFieldToggles: function() {
        var self = this;

        // Bind click on "Change" buttons for masked fields
        $('[data-sweetdialer-field="auth_token"], [data-sweetdialer-field="api_key_secret"]').each(function() {
            var $field = $(this);
            var $toggleBtn = $field.siblings('.sweetdialer-change-btn');

            $toggleBtn.on('click', function(e) {
                e.preventDefault();
                self.toggleMaskedField($field);
            });
        });
    },

    /**
     * Toggle masked field visibility
     *
     * @param {jQuery} $field The field element
     */
    toggleMaskedField: function($field) {
        var isMasked = $field.attr('type') === 'password';
        var currentValue = $field.val();

        if (isMasked || currentValue === '********') {
            // Reveal field
            $field.attr('type', 'text');
            $field.val(''); // Clear for new input
            $field.attr('placeholder', 'Enter new value...');
            $field.focus();

            // Update button text
            var $btn = $field.siblings('.sweetdialer-change-btn');
            $btn.text('Cancel');
            $btn.addClass('sweetdialer-cancel-btn');
        } else {
            // Reset to masked
            $field.attr('type', 'password');
            $field.val('********');
            $field.attr('placeholder', '');

            // Update button text
            var $btn = $field.siblings('.sweetdialer-change-btn');
            $btn.text('Change');
            $btn.removeClass('sweetdialer-cancel-btn');
        }
    },

    /**
     * Check if existing credentials should trigger auto-fetch
     */
    checkExistingCredentials: function() {
        var $accountSid = $('#account_sid, input[name="account_sid"]');
        var $agentPhone = $('#agent_phone_number, input[name="agent_phone_number"], select[name="agent_phone_number"]');

        // If we have account_sid but no phone selected, try to auto-fetch
        if ($accountSid.val() && !$agentPhone.val()) {
            // Will be triggered if auth_token is available
            // Otherwise user needs to enter new credentials
        }
    },

    /**
     * Fetch phone numbers from Twilio via AJAX
     *
     * @param {string} accountSid Twilio Account SID
     * @param {string} authToken Twilio Auth Token
     */
    fetchPhoneNumbers: function(accountSid, authToken) {
        var self = this;

        // Check cache first
        var cacheKey = accountSid + authToken.substring(0, 4);
        if (this.phoneNumberCache[cacheKey]) {
            this.populatePhoneNumberDropdowns(this.phoneNumberCache[cacheKey]);
            return;
        }

        // Show loading indicator
        this.showLoadingSpinner();

        // Make AJAX request
        $.ajax({
            url: 'index.php?entryPoint=sweetdialer_ajax_phone_numbers',
            type: 'POST',
            dataType: 'json',
            data: {
                account_sid: accountSid,
                auth_token: authToken,
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            success: function(response) {
                self.hideLoadingSpinner();

                if (response.success && response.phone_numbers) {
                    // Cache the results
                    self.phoneNumberCache[cacheKey] = response.phone_numbers;

                    // Populate dropdowns
                    self.populatePhoneNumberDropdowns(response.phone_numbers);

                    // Show success message
                    self.showMessage('Phone numbers retrieved successfully!', 'success');
                } else {
                    self.showMessage(response.error || 'Failed to fetch phone numbers', 'error');
                }
            },
            error: function(xhr, status, error) {
                self.hideLoadingSpinner();

                var message = 'Error fetching phone numbers';
                if (xhr.status === 401) {
                    message = 'Authentication failed. Please check your credentials.';
                } else if (xhr.status === 404) {
                    message = 'No phone numbers found in your Twilio account.';
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }

                self.showMessage(message, 'error');
            },
        });
    },

    /**
     * Populate phone number dropdowns
     *
     * @param {Array} phoneNumbers Array of phone number objects
     */
    populatePhoneNumberDropdowns: function(phoneNumbers) {
        var $agentPhone = $('#agent_phone_number, select[name="agent_phone_number"]');
        var $phoneSid = $('#phone_sid, input[name="phone_sid"]');

        // Store current value
        var currentValue = $agentPhone.val();

        // Clear existing options (except placeholder)
        $agentPhone.find('option:not([value=""])').remove();

        // Add options
        phoneNumbers.forEach(function(number) {
            var displayText = number.number;
            if (number.friendly_name) {
                displayText += ' (' + number.friendly_name + ')';
            }

            var option = $('<option></option>')
                .attr('value', number.number)
                .text(displayText)
                .data('sid', number.sid)
                .data('capabilities', number.capabilities);

            $agentPhone.append(option);
        });

        // Restore selection or select first
        if (currentValue) {
            $agentPhone.val(currentValue);
        }

        // Bind change event to update SID field
        $agentPhone.off('change.sweetdialer').on('change.sweetdialer', function() {
            var selectedOption = $(this).find('option:selected');
            var sid = selectedOption.data('sid');
            if (sid) {
                $phoneSid.val(sid);
            }
        });

        // Trigger change if we have a selection
        if ($agentPhone.val()) {
            $agentPhone.trigger('change');
        }
    },

    /**
     * Show loading spinner
     */
    showLoadingSpinner: function() {
        if ($('#sweetdialer-loading').length === 0) {
            $('body').append('<div id="sweetdialer-loading" class="sweetdialer-spinner">Loading...</div>');
        }
        $('#sweetdialer-loading').show();
    },

    /**
     * Hide loading spinner
     */
    hideLoadingSpinner: function() {
        $('#sweetdialer-loading').hide();
    },

    /**
     * Show message to user
     *
     * @param {string} message Message text
     * @param {string} type Message type (success, error, info)
     */
    showMessage: function(message, type) {
        var alertClass = 'alert-info';
        if (type === 'success') alertClass = 'alert-success';
        if (type === 'error') alertClass = 'alert-danger';

        var $message = $('<div class="alert ' + alertClass + ' sweetdialer-message" style="margin: 10px 0;">' +
            message + '</div>');

        // Remove existing messages
        $('.sweetdialer-message').remove();

        // Add new message before the form
        $('#EditView').before($message);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    },
};

// Initialize when document is ready
$(document).ready(function() {
    if (document.location.href.indexOf('module=outr_CtiSettings') !== -1 &&
        (document.location.href.indexOf('action=EditView') !== -1 ||
         document.location.href.indexOf('action=edit') !== -1)) {
        SweetDialer.EditView.init();
    }
});
