<?php
/**
 * Sweet-Dialer Recording Playback Proxy (S-058)
 *
 * Secure proxy for recording playback
 * Validates SuiteCRM session
 * Returns 403 for unauthorized
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

if (!defined('sugarEntry') || !sugarEntry) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';
require_once 'data/SugarBean.php';
require_once 'data/BeanFactory.php';
require_once 'custom/include/TwilioDialer/RBAC/PermissionManager.php';

// Log access attempt
$GLOBALS['log']->info("SweetDialer: recordingPlayback.php - Access attempt from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Validate SuiteCRM session
$current_user = null;
if (isset($_SESSION['authenticated_user_id'])) {
    $current_user = BeanFactory::getBean('Users', $_SESSION['authenticated_user_id']);
}

if (empty($current_user) || empty($current_user->id)) {
    $GLOBALS['log']->security("SweetDialer: recordingPlayback.php - Unauthorized access attempt - no valid session");
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required', 'code' => 403]);
    exit;
}

// Get call ID from query parameter
$callId = $_GET['call_id'] ?? '';
$recordingSid = $_GET['recording_sid'] ?? '';

if (empty($callId) && empty($recordingSid)) {
    $GLOBALS['log']->error("SweetDialer: recordingPlayback.php - Missing required parameters (call_id or recording_sid)");
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing call_id or recording_sid parameter']);
    exit;
}

// Find the call record
$callBean = null;
if (!empty($callId)) {
    $callBean = BeanFactory::getBean('outr_TwilioCalls', $callId);
} elseif (!empty($recordingSid)) {
    $callBean = BeanFactory::getBean('outr_TwilioCalls');
    $callBean->retrieve_by_string_fields(['recording_sid' => $recordingSid]);
}

if (empty($callBean) || empty($callBean->id)) {
    $GLOBALS['log']->error("SweetDialer: recordingPlayback.php - Call record not found");
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Recording not found']);
    exit;
}

// Check if user has permission to access this recording
$canAccess = false;

// Admin and Manager can access all recordings
if (SweetDialerPermissionManager::isAdmin() || SweetDialerPermissionManager::isManager()) {
    $canAccess = true;
    $GLOBALS['log']->info("SweetDialer: recordingPlayback.php - Admin/Manager access granted for user {$current_user->id}");
} elseif ($callBean->agent_id === $current_user->id || $callBean->assigned_user_id === $current_user->id) {
    // Agent can access their own recordings
    $canAccess = true;
    $GLOBALS['log']->info("SweetDialer: recordingPlayback.php - Agent access granted for user {$current_user->id}");
} elseif ($callBean->created_by === $current_user->id) {
    // User who created the record can access
    $canAccess = true;
    $GLOBALS['log']->info("SweetDialer: recordingPlayback.php - Creator access granted for user {$current_user->id}");
}

// Check parent record access if no direct access
if (!$canAccess && !empty($callBean->parent_id) && !empty($callBean->parent_type)) {
    $parentBean = BeanFactory::getBean($callBean->parent_type, $callBean->parent_id);
    if ($parentBean && !empty($parentBean->id)) {
        // Check if current user can access the parent record
        if (ACLController::checkAccess($callBean->parent_type, 'view', true, $parentBean->acltype)) {
            $canAccess = true;
            $GLOBALS['log']->info("SweetDialer: recordingPlayback.php - Parent record access granted for user {$current_user->id}");
        }
    }
}

if (!$canAccess) {
    SweetDialerPermissionManager::auditAccess('outr_TwilioCalls', $callBean->id, 'view_recording', false);
    $GLOBALS['log']->security("SweetDialer: recordingPlayback.php - Access denied for user {$current_user->id} to recording {$callBean->id}");
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Access denied', 'code' => 403]);
    exit;
}

SweetDialerPermissionManager::auditAccess('outr_TwilioCalls', $callBean->id, 'view_recording', true);

// Get the recording URL
$recordingUrl = $callBean->recording_url;

if (empty($recordingUrl)) {
    $GLOBALS['log']->error("SweetDialer: recordingPlayback.php - No recording URL for call {$callBean->id}");
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Recording not available']);
    exit;
}

// Fetch recording from Twilio and stream to user
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $recordingUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Get Twilio auth for fetching recording
$db = DBManagerFactory::getInstance();
$sql = "SELECT twilio_account_sid, twilio_auth_token FROM outr_twilio_settings 
        WHERE deleted = 0 
        LIMIT 1";
$result = $db->query($sql);
$row = $db->fetchByAssoc($result);

if (!empty($row) && !empty($row['twilio_account_sid']) && !empty($row['twilio_auth_token'])) {
    curl_setopt($ch, CURLOPT_USERPWD, $row['twilio_account_sid'] . ':' . $row['twilio_auth_token']);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    $GLOBALS['log']->error("SweetDialer: recordingPlayback.php - cURL error: $curlError");
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => 'Failed to fetch recording']);
    exit;
}

if ($httpCode !== 200) {
    $GLOBALS['log']->error("SweetDialer: recordingPlayback.php - Twilio returned HTTP $httpCode");
    header('HTTP/1.1 ' . $httpCode);
    echo json_encode(['success' => false, 'error' => 'Recording fetch failed', 'http_code' => $httpCode]);
    exit;
}

// Log successful access
$GLOBALS['log']->info("SweetDialer: recordingPlayback.php - Serving recording for call {$callBean->id} to user {$current_user->id}");

// Stream the recording
header('Content-Type: ' . ($contentType ?: 'audio/wav'));
header('Content-Disposition: inline; filename="recording-' . $callBean->recording_sid . '.wav"');
header('Content-Length: ' . strlen($response));
header('Cache-Control: private, max-age=3600');

echo $response;
exit;
