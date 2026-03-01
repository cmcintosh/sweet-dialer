<?php
/**
 * Cold Transfer Entrypoint (Blind Transfer)
 * Epic 10: Transfer - S-103-S-104 (4 pts)
 * Immediately transfers call to target without announcement
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

header('Content-Type: application/json');

global \$current_user;

// Auth check
if (empty(\$current_user) || empty(\$current_user->id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

\$callSid = isset(\$_POST['call_sid']) ? \$_POST['call_sid'] : null;
\$targetAgentId = isset(\$_POST['target_agent_id']) ? \$_POST['target_agent_id'] : null;

if (empty(\$callSid) || empty(\$targetAgentId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'call_sid and target_agent_id required']);
    exit;
}

// Get target agent
\$targetAgent = BeanFactory::getBean('Users', \$targetAgentId);
if (empty(\$targetAgent) || empty(\$targetAgent->id)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Target agent not found']);
    exit;
}

// Get target phone
\$targetPhone = \$targetAgent->phone_mobile;
if (empty(\$targetPhone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Target agent has no phone number configured']);
    exit;
}

// Update call record
\$callBean = BeanFactory::getBean('OutrTwilioCalls');
\$calls = \$callBean->get_full_list(null, "outr_twiliocalls.call_sid = '".\$callSid."'");

if (!empty(\$calls) && !empty(\$calls[0])) {
    \$call = \$calls[0];
    \$call->transfer_type = 'cold';
    \$call->transfer_status = 'in_progress';
    \$call->transfer_to_id = \$targetAgentId;
    \$call->transfer_from_id = \$current_user->id;
    \$call->save(false);
}

// Execute cold transfer via Twilio
require_once('custom/include/TwilioDialer/TwilioClient.php');

\$twilio = \SuiteCRM\TwilioClient::getInstance();

try {
    // Cold transfer: redirect call to target agent's client/phone
    \$result = \$twilio->transferCall(\$callSid, \$targetPhone, [
        'type' => 'cold',
        'callbackUrl' => \$GLOBALS['sugar_config']['site_url'] . '/index.php?entryPoint=transferStatus'
    ]);

    // Mark as completed immediately for cold transfer
    if (!empty(\$call)) {
        \$call->transfer_status = 'completed';
        \$call->date_end = TimeDate::getInstance()->nowDb();
        \$call->save(false);
    }

    echo json_encode([
        'success' => true,
        'transfer_type' => 'cold',
        'target_agent' => \$targetAgent->full_name,
        'status' => 'completed',
        'message' => 'Cold transfer completed. Call redirected to agent.'
    ]);

} catch (Exception \$e) {
    if (!empty(\$call)) {
        \$call->transfer_status = 'failed';
        \$call->save(false);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => \$e->getMessage()
    ]);
}

exit;
