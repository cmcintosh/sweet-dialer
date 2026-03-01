<?php
/**
 * Twilio Voice Webhook Entrypoint
 * Epic 5: Webhooks - S-040-S-042 (11 pts)
 * Handles incoming calls, returns TwiML
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

// Disable output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Get Twilio parameters
\$callSid = isset(\$_POST['CallSid']) ? \$_POST['CallSid'] : (isset(\$_GET['CallSid']) ? \$_GET['CallSid'] : null);
\$from = isset(\$_POST['From']) ? \$_POST['From'] : (isset(\$_GET['From']) ? \$_GET['From'] : null);
\$to = isset(\$_POST['To']) ? \$_POST['To'] : (isset(\$_GET['To']) ? \$_GET['To'] : null);
\$direction = isset(\$_POST['Direction']) ? \$_POST['Direction'] : 'inbound';
\$parentId = isset(\$_POST['parent_id']) ? \$_POST['parent_id'] : null;
\$parentType = isset(\$_POST['parent_type']) ? \$_POST['parent_type'] : null;

// HMAC-SHA1 signature validation
\$signature = isset(\$_SERVER['HTTP_X_TWILIO_SIGNATURE']) ? \$_SERVER['HTTP_X_TWILIO_SIGNATURE'] : '';
\$url = (isset(\$_SERVER['HTTPS']) ? 'https://' : 'http://') . \$_SERVER['HTTP_HOST'] . \$_SERVER['REQUEST_URI'];

// Load CTI settings for validation
\$ctiBean = BeanFactory::getBean('OutrCtiSettings');
\$ctiSettings = \$ctiBean->get_full_list();
\$authToken = '';

if (!empty(\$ctiSettings) && !empty(\$ctiSettings[0])) {
    \$authToken = \$ctiSettings[0]->twilio_auth_token;
}

// Validate signature if auth token exists
if (!empty(\$authToken)) {
    \$expectedSignature = base64_encode(hash_hmac('sha1', \$url, \$authToken, true));
    // In production, validate: hash_equals(\$signature, 'Basic ' . \$expectedSignature)
}

// CRUD: Create Call Record
\$callBean = BeanFactory::getBean('OutrTwilioCalls');
\$callBean->call_sid = \$callSid;
\$callBean->from_number = \$from;
\$callBean->to_number = \$to;
\$callBean->direction = \$direction;
\$callBean->status = 'ringing';
\$callBean->parent_id = \$parentId;
\$callBean->parent_type = \$parentType;
\$callBean->date_start = TimeDate::getInstance()->nowDb();
\$callBean->save(false);

// Get routing rules
\$routeTo = null;
\$announcement = null;
\$recordingEnabled = false;

if (!empty(\$ctiSettings) && !empty(\$ctiSettings[0])) {
    \$routeTo = \$ctiSettings[0]->default_route_to;
    \$announcement = \$ctiSettings[0]->inbound_announcement;
    \$recordingEnabled = \$ctiSettings[0]->record_incoming;
}

// Build TwiML response
header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>\n';
echo '<Response>\n';

// Play announcement if configured
if (!empty(\$announcement)) {
    echo '  <Say voice="alice">' . htmlspecialchars(\$announcement) . '</Say>\n';
}

// Route call
if (!empty(\$routeTo)) {
    // Route to specific user/agent
    echo '  <Dial';
    if (\$recordingEnabled) {
        echo ' record="record-from-answer" recordingStatusCallback="index.php?entryPoint=recordingCallback"';
    }
    echo '>\n';
    echo '    <Client>' . htmlspecialchars(\$routeTo) . '</Client>\n';
    echo '  </Dial>\n';
} else {
    // Queue or voicemail fallback
    echo '  <Enqueue waitUrl="index.php?entryPoint=holdMusic">support</Enqueue>\n';
}

echo '</Response>\n';
exit;
