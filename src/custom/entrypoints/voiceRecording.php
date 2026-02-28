<?php
/**
 * Sweet-Dialer Recording Callback Handler (S-057)
 *
 * Handles Twilio recording callbacks
 * Updates recording_url, recording_sid, duration
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

// Log incoming webhook
$GLOBALS['log']->info("SweetDialer: voiceRecording.php - Recording callback received from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Validate Twilio webhook signature
$authToken = '';
$db = DBManagerFactory::getInstance();

// Look for CTI setting based on To/From number
$toNumber = $_POST['To'] ?? '';
$fromNumber = $_POST['From'] ?? '';

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

$endpointPath = '/custom/entrypoints/voiceRecording.php';

if (!empty($authToken)) {
    TwilioWebhookValidator::requireValidSignature($authToken, $endpointPath);
} else {
    $GLOBALS['log']->warn("SweetDialer: voiceRecording.php - No CTI config found for validation, proceeding without signature check");
}

// Extract recording parameters
$recordingSid = $_POST['RecordingSid'] ?? '';
$recordingUrl = $_POST['RecordingUrl'] ?? '';
$recordingDuration = $_POST['RecordingDuration'] ?? 0;
$callSid = $_POST['CallSid'] ?? '';

$GLOBALS['log']->info("SweetDialer: voiceRecording.php - RecordingSid=$recordingSid, CallSid=$callSid, Duration=$recordingDuration");

// Validate required fields
if (empty($recordingSid) || empty($callSid)) {
    $GLOBALS['log']->error("SweetDialer: voiceRecording.php - Missing required fields (RecordingSid, CallSid)");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Find the call record by CallSid
$callBean = BeanFactory::getBean('outr_TwilioCalls');
$callBean->retrieve_by_string_fields(['call_sid' => $callSid]);

if (empty($callBean->id)) {
    $GLOBALS['log']->warn("SweetDialer: voiceRecording.php - Call record not found for CallSid: $callSid");
    // Return success anyway so Twilio doesn't retry indefinitely
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'warning' => 'Call record not found']);
    exit;
}

// Update recording information
$callBean->recording_sid = $recordingSid;
$callBean->recording_url = $recordingUrl;

if (!empty($recordingDuration)) {
    $callBean->duration = (int)$recordingDuration;
}

// Save the record
$callId = $callBean->save();

$GLOBALS['log']->info("SweetDialer: voiceRecording.php - Recording info saved for call {$callBean->id}");

// Return success response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'call_id' => $callId,
    'recording_sid' => $recordingSid,
    'recording_url' => $recordingUrl,
    'duration' => (int)$recordingDuration
]);
exit;
