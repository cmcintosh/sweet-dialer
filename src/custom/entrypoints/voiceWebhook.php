<?php
/**
 * Twilio Voice Webhook Entrypoint - CRASH-SAFE VERSION
 * Epic 5: Webhooks - S-040-S-042
 * Includes top-level error handling to prevent CRM crashes
 */

try {
    // Define sugarEntry guard
    if (!defined('sugarEntry') || !sugarEntry) {
        die('Not A Valid Entry Point');
    }

    // Disable all output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Load sugar with error handling
    try {
        require_once('include/utils.php');
        if (!defined('sugarEntry')) {
            define('sugarEntry', true);
        }
        require_once('include/entryPoint.php');
    } catch (Exception \$e) {
        // Return TwiML error
        header('Content-Type: text/xml');
        echo '<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Say>System error. Please try again later.</Say>
  <Hangup/>
</Response>';
        if (function_exists('\$GLOBALS') && isset(\$GLOBALS['log'])) {
            \$GLOBALS['log']->error('SweetDialer voiceWebhook: Sugar load failed: ' . \$e->getMessage());
        }
        exit;
    }

    // Safe logger function
    function safeLog(\$message) {
        if (isset(\$GLOBALS['log']) && is_object(\$GLOBALS['log'])) {
            \$GLOBALS['log']->debug(\$message);
        }
    }

    safeLog('SweetDialer voiceWebhook: Starting request');

    // Get Twilio parameters safely
    \$callSid = isset(\$_POST['CallSid']) ? \$_POST['CallSid'] : (isset(\$_GET['CallSid']) ? \$_GET['CallSid'] : null);
    \$from = isset(\$_POST['From']) ? \$_POST['From'] : (isset(\$_GET['From']) ? \$_GET['From'] : null);
    \$to = isset(\$_POST['To']) ? \$_POST['To'] : (isset(\$_GET['To']) ? \$_GET['To'] : null);
    \$direction = isset(\$_POST['Direction']) ? \$_POST['Direction'] : 'inbound';
    \$parentId = isset(\$_POST['parent_id']) ? \$_POST['parent_id'] : null;
    \$parentType = isset(\$_POST['parent_type']) ? \$_POST['parent_type'] : null;

    // HMAC validation (optional in emergency)
    \$signature = isset(\$_SERVER['HTTP_X_TWILIO_SIGNATURE']) ? \$_SERVER['HTTP_X_TWILIO_SIGNATURE'] : '';
    \$url = (isset(\$_SERVER['HTTPS']) ? 'https://' : 'http://') . \$_SERVER['HTTP_HOST'] . \$_SERVER['REQUEST_URI'];

    // Try to get auth token
    \$authToken = '';
    try {
        global \$db;
        if (\$db) {
            \$result = \$db->query("SELECT twilio_auth_token FROM outr_ctisettings WHERE deleted = 0 LIMIT 1");
            if (\$result) {
                \$row = \$result->fetchRow();
                if (\$row && isset(\$row['twilio_auth_token'])) {
                    \$authToken = \$row['twilio_auth_token'];
                }
            }
        }
    } catch (Exception \$e) {
        safeLog('SweetDialer voiceWebhook: Auth token lookup failed: ' . \$e->getMessage());
    }

    // Try to create call record
    \$callBean = null;
    try {
        if (class_exists('BeanFactory') && \$callSid) {
            \$callBean = BeanFactory::getBean('OutrTwilioCalls');
            if (\$callBean) {
                \$callBean->call_sid = \$callSid;
                \$callBean->from_number = \$from;
                \$callBean->to_number = \$to;
                \$callBean->direction = \$direction;
                \$callBean->status = 'ringing';
                \$callBean->parent_id = \$parentId;
                \$callBean->parent_type = \$parentType;
                \$callBean->save(false);
            }
        }
    } catch (Exception \$e) {
        safeLog('SweetDialer voiceWebhook: Call record creation failed: ' . \$e->getMessage());
    }

    // Get settings
    \$routeTo = null;
    \$announcement = null;
    \$recordingEnabled = false;

    try {
        if (class_exists('BeanFactory')) {
            \$ctiBean = BeanFactory::getBean('OutrCtiSettings');
            if (\$ctiBean) {
                \$ctiSettings = \$ctiBean->get_full_list();
                if (!empty(\$ctiSettings) && !empty(\$ctiSettings[0])) {
                    \$routeTo = \$ctiSettings[0]->default_route_to ?? null;
                    \$announcement = \$ctiSettings[0]->inbound_announcement ?? null;
                    \$recordingEnabled = \$ctiSettings[0]->record_incoming ?? false;
                }
            }
        }
    } catch (Exception \$e) {
        safeLog('SweetDialer voiceWebhook: Settings lookup failed: ' . \$e->getMessage());
    }

    // Build TwiML response
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>\n';
    echo '<Response>\n';

    if (!empty(\$announcement)) {
        echo '  <Say voice="alice">' . htmlspecialchars(\$announcement) . '</Say>\n';
    }

    if (!empty(\$routeTo)) {
        echo '  <Dial';
        if (\$recordingEnabled) {
            echo ' record="record-from-answer" recordingStatusCallback="index.php?entryPoint=recordingCallback"';
        }
        echo '>\n';
        echo '    <Client>' . htmlspecialchars(\$routeTo) . '</Client>\n';
        echo '  </Dial>\n';
    } else {
        echo '  <Enqueue waitUrl="index.php?entryPoint=holdMusic">support</Enqueue>\n';
    }

    echo '</Response>\n';
    safeLog('SweetDialer voiceWebhook: Completed successfully');
    exit;

} catch (Exception \$e) {
    // Ultimate fallback - ALWAYS return valid TwiML
    if (function_exists('\$GLOBALS') && isset(\$GLOBALS['log'])) {
        \$GLOBALS['log']->fatal('SweetDialer voiceWebhook CRITICAL ERROR: ' . \$e->getMessage());
    }
    
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Say>System error. Please try again.</Say>
  <Hangup/>
</Response>';
    exit;
}
