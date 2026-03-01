<?php
/**
 * Sweet-Dialer Voice Status Callback Handler (S-054 + S-055)
 *
 * Handles Twilio status callbacks
 * Creates/updates outr_twilio_calls records
 * Parses CallSid, CallStatus, CallDuration, From, To, Direction
 * Maps status to ENUM: incoming, outgoing, missed, rejected
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    define('sugarEntry', true);
}

require_once 'data/SugarBean.php';
require_once 'data/BeanFactory.php';
require_once 'custom/include/TwilioDialer/Security/TwilioWebhookValidator.php';
require_once 'custom/include/TwilioDialer/CrmRecordMatcher.php';

// Log incoming webhook for debugging
$GLOBALS['log']->info("SweetDialer: voiceStatus.php - Status callback received from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Validate Twilio webhook signature
$authToken = '';
$ctiConfig = null;
$db = DBManagerFactory::getInstance();

// Look for CTI setting based on To/From number
$toNumber = $_POST['To'] ?? '';
$fromNumber = $_POST['From'] ?? '';

// Try to find the CTI config associated with this call
if (!empty($toNumber) || !empty($fromNumber)) {
    $normalizedTo = preg_replace('/[^0-9]/', '', $toNumber);
    $normalizedFrom = preg_replace('/[^0-9]/', '', $fromNumber);
    
    $sql = "SELECT id, twilio_auth_token FROM outr_twilio_settings 
            WHERE deleted = 0 
            AND (REPLACE(twilio_phone_number, '-', '') LIKE '%" . $db->quote($normalizedTo) . "%' 
                 OR REPLACE(twilio_phone_number, '-', '') LIKE '%" . $db->quote($normalizedFrom) . "%')
            LIMIT 1";
    $result = $db->query($sql);
    $row = $db->fetchByAssoc($result);
    if ($row) {
        $authToken = $row['twilio_auth_token'];
    }
}

// Get the current URL path
$endpointPath = '/custom/entrypoints/voiceStatus.php';

// If we have an auth token, validate the webhook
if (!empty($authToken)) {
    TwilioWebhookValidator::requireValidSignature($authToken, $endpointPath);
} else {
    $GLOBALS['log']->warn("SweetDialer: voiceStatus.php - No CTI config found for validation, proceeding without signature check");
}

// Extract Twilio parameters
$callSid = $_POST['CallSid'] ?? '';
$callStatus = $_POST['CallStatus'] ?? '';
$callDuration = $_POST['CallDuration'] ?? 0;
$fromNumber = $_POST['From'] ?? '';
$toNumber = $_POST['To'] ?? '';
$direction = $_POST['Direction'] ?? '';
$recordingUrl = $_POST['RecordingUrl'] ?? '';
$recordingSid = $_POST['RecordingSid'] ?? '';
$recordingDuration = $_POST['RecordingDuration'] ?? 0;
$answerStatus = $_POST['AnsweredBy'] ?? '';

$GLOBALS['log']->info("SweetDialer: voiceStatus.php - CallSid=$callSid, Status=$callStatus, Direction=$direction, From=$fromNumber, To=$toNumber");

// Validate required fields
if (empty($callSid) || empty($callStatus)) {
    $GLOBALS['log']->error("SweetDialer: voiceStatus.php - Missing required fields");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Find existing call record by CallSid
$callBean = BeanFactory::getBean('outr_TwilioCalls');
$callBean->retrieve_by_string_fields(['call_sid' => $callSid]);

// Determine call type based on direction and status (S-055)
$callType = classifyCallType($direction, $callStatus, $answerStatus);

if (empty($callBean->id)) {
    // Create new call record
    $GLOBALS['log']->info("SweetDialer: voiceStatus.php - Creating new call record for $callSid");
    $callBean->call_sid = $callSid;
    $callBean->status = $callStatus;
    $callBean->call_type = $callType;
    $callBean->from_number = $fromNumber;
    $callBean->to_number = $toNumber;
    $callBean->duration = (int)$callDuration;
    
    // Match phone number to CRM record (S-056)
    $matcher = new CrmRecordMatcher();
    $matchResult = $matcher->matchByPhone($fromNumber);
    
    if (!empty($matchResult['parent_type']) && !empty($matchResult['parent_id'])) {
        $callBean->parent_type = $matchResult['parent_type'];
        $callBean->parent_id = $matchResult['parent_id'];
        $GLOBALS['log']->info("SweetDialer: voiceStatus.php - Matched to {$matchResult['parent_type']}:{$matchResult['parent_id']}");
    }
} else {
    // Update existing call record
    $GLOBALS['log']->info("SweetDialer: voiceStatus.php - Updating existing call record {$callBean->id}");
    $callBean->status = $callStatus;
    
    // Update call_type if status changed it (e.g., ringing -> missed)
    $callBean->call_type = $callType;
    
    if (!empty($callDuration) && $callDuration > $callBean->duration) {
        $callBean->duration = (int)$callDuration;
    }
}

// Update recording info if present
if (!empty($recordingUrl)) {
    $callBean->recording_url = $recordingUrl;
}
if (!empty($recordingSid)) {
    $callBean->recording_sid = $recordingSid;
}
if (!empty($recordingDuration)) {
    $callBean->duration = (int)$recordingDuration;
}

// Save the record
$callId = $callBean->save();

$GLOBALS['log']->info("SweetDialer: voiceStatus.php - Call record saved: $callId");

// Return success response for Twilio
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'call_id' => $callId,
    'status' => $callStatus,
    'call_type' => $callType
]);
exit;

/**
 * S-055: Classify call type based on direction and status
 * 
 * @param string $direction
 * @param string $status
 * @param string $answeredBy
 * @return string (incoming, outgoing, missed, rejected)
 */
function classifyCallType($direction, $status, $answeredBy = '')
{
    $isIncoming = (stripos($direction, 'inbound') !== false);
    $isOutgoing = (stripos($direction, 'outbound') !== false);
    $statusLower = strtolower($status);
    
    // Completed calls
    if ($statusLower === 'completed') {
        return $isIncoming ? 'incoming' : 'outgoing';
    }
    
    // Rejected status: busy, canceled (when caller hangs up before answer)
    if (in_array($statusLower, ['busy', 'rejected'])) {
        return $isIncoming ? 'rejected' : 'outgoing';
    }
    
    // Missed status: no-answer, canceled, failed
    if (in_array($statusLower, ['no-answer', 'canceled', 'failed'])) {
        return $isIncoming ? 'missed' : 'outgoing';
    }
    
    // Ringing/In-progress - use direction
    if (in_array($statusLower, ['ringing', 'queued', 'initiated', 'in-progress'])) {
        return $isIncoming ? 'incoming' : 'outgoing';
    }
    
    // Default based on direction
    return $isIncoming ? 'incoming' : 'outgoing';
}
