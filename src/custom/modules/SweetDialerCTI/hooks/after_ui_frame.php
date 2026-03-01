<?php
/**
 * SweetDialer CTI - After UI Frame Hook
 * Injects click-to-call UI and dialer widget into SuiteCRM pages
 *
 * @package SweetDialer
 * @subpackage CTI
 * @author Wembassy Development Team
 * @license AGPL-3.0
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class SweetDialerUIHook
{
    /**
     * Main hook entry point - called after UI frame is rendered
     * @param object \$event
     * @param array \$arguments
     */
    public function after_ui_frame(\$event, \$arguments)
    {
        // Only inject for authenticated users with CTI access
        if (!\$this->hasCTIAccess()) {
            return;
        }

        // Get current user info
        global \$current_user;
        \$userId = \$current_user->id;
        \$userName = \$current_user->user_name;

        // Output the injection script
        echo \$this->getInjectionScript(\$userId, \$userName);
    }

    /**
     * Check if current user has CTI access
     * @return bool
     */
    private function hasCTIAccess()
    {
        global \$current_user;

        if (empty(\$current_user) || empty(\$current_user->id)) {
            return false;
        }

        // Check if user has 'sweetdialer_cti_enabled' preference
        \$ctiEnabled = \$current_user->getPreference('sweetdialer_cti_enabled', 'global');

        // Default to enabled if preference not set (agents get it by default)
        if (\$ctiEnabled === null) {
            // Check if user has a configured OutrCtiUserAgent
            \$bean = BeanFactory::getBean('OutrCtiUserAgents');
            \$agents = \$bean->get_full_list(
                "date_end DESC",
                "outr_cti_user_agents.assigned_user_id = '{\$current_user->id}' AND (outr_cti_user_agents.date_end IS NULL OR outr_cti_user_agents.date_end > NOW())"
            );
            return !empty(\$agents);
        }

        return \$ctiEnabled === '1' || \$ctiEnabled === true;
    }

    /**
     * Get the JavaScript injection HTML
     * @param string \$userId
     * @param string \$userName
     * @return string
     */
    private function getInjectionScript(\$userId, \$userName)
    {
        \$jsUrl = 'custom/include/TwilioDialer/js/twilioClient.js';
        \$overlayUrl = 'custom/include/TwilioDialer/js/dialerOverlay.js';

        return "<script type=\"text/javascript\">
(function() {
    window.SweetDialerCTI = window.SweetDialerCTI || {};
    window.SweetDialerCTI.userId = '\$userId';
    window.SweetDialerCTI.userName = '\$userName';
    window.SweetDialerCTI.baseUrl = 'index.php?entryPoint=';

    var styles = document.createElement('style');
    styles.id = 'sweetdialer-cti-styles';
    styles.textContent = '.sweetdialer-phone-icon{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;margin-left:8px;cursor:pointer;color:#00d4aa;font-size:14px;transition:transform 0.2s}.sweetdialer-phone-icon:hover{transform:scale(1.2)}.sweetdialer-header-widget{display:flex;align-items:center;gap:8px;padding:4px 12px;background:rgba(0,212,170,0.1);border:1px solid rgba(0,212,170,0.2);border-radius:20px;margin-right:16px}.sweetdialer-header-widget input{width:150px;padding:4px 10px;border:1px solid #ddd;border-radius:12px;font-size:13px}.sweetdialer-header-widget button{padding:4px 12px;background:#00d4aa;color:#0b1a2e;border:none;border-radius:12px;cursor:pointer;font-size:12px;font-weight:600}';
    document.head.appendChild(styles);

    function addClickToCallIcons() {
        var phoneFields = document.querySelectorAll('td[field=\"phone_work\"],td[field=\"phone_mobile\"],td[field=\"phone_home\"],td[field=\"phone_other\"],.phone');
        phoneFields.forEach(function(field) {
            var phoneText = field.textContent.trim();
            var cleanPhone = phoneText.replace(/\\D/g, '');
            if (cleanPhone.length >= 10 && !field.querySelector('.sweetdialer-phone-icon')) {
                var icon = document.createElement('span');
                icon.className = 'sweetdialer-phone-icon';
                icon.innerHTML = '&#9742;';
                icon.title = 'Click to call ' + phoneText;
                icon.onclick = function(e) {
                    e.preventDefault();
                    initiateCall(cleanPhone);
                };
                field.appendChild(icon);
            }
        });
    }

    function addHeaderDialer() {
        var headerRight = document.querySelector('#globalLinks') || document.querySelector('.navbar-right');
        if (headerRight && !document.getElementById('sweetdialer-header-widget')) {
            var widget = document.createElement('div');
            widget.id = 'sweetdialer-header-widget';
            widget.className = 'sweetdialer-header-widget';
            widget.innerHTML = '<input type=\"text\" id=\"sweetdialer-dial-input\" placeholder=\"Enter phone...\" maxlength=\"15\"><button id=\"sweetdialer-dial-btn\">&#9742;</button>';
            headerRight.parentNode.insertBefore(widget, headerRight);
            
            document.getElementById('sweetdialer-dial-btn').onclick = function() {
                var phone = document.getElementById('sweetdialer-dial-input').value.replace(/\\D/g, '');
                if (phone.length >= 10) initiateCall(phone);
            };
        }
    }

    function initiateCall(phoneNumber) {
        if (window.TwilioClient && window.TwilioClient.getInstance) {
            var client = window.TwilioClient.getInstance();
            if (client) client.makeCall(phoneNumber);
            else alert('Phone system not ready. Please refresh.');
        } else {
            console.error('SweetDialer: Twilio client not loaded');
        }
    }

    function init() {
        addClickToCallIcons();
        addHeaderDialer();
        setInterval(addClickToCallIcons, 2000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>";
    }
}
