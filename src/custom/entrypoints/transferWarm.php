<?php
/**
 * Warm Transfer Entrypoint
 * Epic 10: Transfer - S-100-S-102 (9 pts)
 * Holds current call, invites agent, creates conference
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

// Update call record transfer status
\$callBean = BeanFactory::getBean('OutrTwilioCalls');
\$calls = \$callBean->get_full_list(null, "outr_twiliocalls.call_sid = '".\$callSid."'");

if (!empty(\$calls) && !empty(\$calls[0])) {
    \$call = \$calls[0];
    \$call->transfer_type = 'warm';
    \$call->transfer_status = 'in_progress';
    \$call->transfer_to_id = \$targetAgentId;
    \$call->transfer_from_id = \$current_user->id;
    \$call->save(false);
}

// Use Twilio Client to initiate warm transfer
require_once('custom/include/TwilioDialer/TwilioClient.php');

\$twilio = \SuiteCRM\TwilioClient::getInstance();

// Create conference for warm transfer
\$friendlyName = 'warm-transfer-' . \$callSid;
\$conferenceSid = null;

try {
    // Put caller on hold in conference
    \$conferenceSid = \$twilio->dialIntoConference(\$callSid, \$friendlyName, [
        'hold' => true,
        'beep' => false
    ]);

    // Call target agent
    \$agentCall = \$twilio->inviteToConference(\$targetAgent->phone_mobile, \$friendlyName);

    echo json_encode([
        'success' => true,
        'transfer_type' => 'warm',
        'conference_sid' => \$conferenceSid,
        'target_agent' => \$targetAgent->full_name,
        'status' => 'in_progress',
        'message' => 'Warm transfer initiated. Agent is being called.'
    ]);

} catch (Exception \$e) {
    // Update transfer status to failed
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
