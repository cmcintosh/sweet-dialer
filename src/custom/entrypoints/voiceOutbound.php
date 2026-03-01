<?php
/**
 * Sweet-Dialer Outbound Voice Handler (S-059)
 *
 * Placed outbound calls
 * Auto-creates contact if not found in CRM
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
require_once 'include/entryPoint.php';
require_once 'custom/include/TwilioDialer/CrmRecordMatcher.php';

// Log incoming request
$GLOBALS['log']->info("SweetDialer: voiceOutbound.php - Outbound call request from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Validate session
if (empty($_SESSION['authenticated_user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$current_user = BeanFactory::getBean('Users', $_SESSION['authenticated_user_id']);
$toNumber = $_POST['To'] ?? $_GET['To'] ?? '';
$fromNumber = $_POST['From'] ?? $_GET['From'] ?? '';
$ctiSettingId = $_POST['cti_setting_id'] ?? $_GET['cti_setting_id'] ?? '';
$skipContactCheck = $_POST['skip_contact_check'] ?? $_GET['skip_contact_check'] ?? false;

if (empty($toNumber)) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing To number']);
    exit;
}

// S-059: Auto-create contact logic - Search CRM, create placeholder if not found
$matcher = new CrmRecordMatcher();
$matchResult = $matcher->matchByPhone($toNumber);
$contactId = null;
$contactName = '';
$isAutoCreated = false;

if (empty($matchResult['parent_id']) && !$skipContactCheck) {
    // No contact found - create placeholder contact
    $GLOBALS['log']->info("SweetDialer: voiceOutbound.php - No contact found for $toNumber, creating placeholder");
    
    $contactBean = BeanFactory::newBean('Contacts');
    $contactBean->first_name = 'Unknown';
    $contactBean->last_name = 'Caller';
    $contactBean->phone_work = $toNumber;
    $contactBean->description = "Auto-created by SweetDialer on outbound call to $toNumber";
    $contactBean->assigned_user_id = $current_user->id;
    
    // Use custom fields to mark as auto-created
    if (isset($contactBean->field_defs['auto_created'])) {
        $contactBean->auto_created = 1;
    }
    if (isset($contactBean->field_defs['auto_created_date'])) {
        $contactBean->auto_created_date = date('Y-m-d H:i:s');
    }
    
    $contactId = $contactBean->save();
    $isAutoCreated = true;
    $contactName = $contactBean->full_name ?? 'Unknown Caller';
    
    $GLOBALS['log']->info("SweetDialer: voiceOutbound.php - Auto-created contact: $contactId");
    
    // Update match result
    $matchResult = [
        'parent_type' => 'Contacts',
        'parent_id' => $contactId
    ];
} elseif (!empty($matchResult['parent_id'])) {
    // Contact found - load for display
    if ($matchResult['parent_type'] === 'Contacts') {
        $contactBean = BeanFactory::getBean('Contacts', $matchResult['parent_id']);
        if ($contactBean) {
            $contactId = $contactBean->id;
            $contactName = $contactBean->full_name ?? $contactBean->name ?? 'Unknown';
        }
    }
}

// Get CTI settings for the agent
if (empty($ctiSettingId)) {
    // Try to find CTI config for current user
    $db = DBManagerFactory::getInstance();
    $sql = "SELECT id FROM outr_twilio_settings 
            WHERE deleted = 0 
            AND outbound_inbound_agent_id = '" . $db->quote($current_user->id) . "'
            LIMIT 1";
    $result = $db->query($sql);
    $row = $db->fetchByAssoc($result);
    if ($row) {
        $ctiSettingId = $row['id'];
    }
}

$ctiConfig = null;
if (!empty($ctiSettingId)) {
    $ctiConfig = BeanFactory::getBean('outr_TwilioSettings', $ctiSettingId);
}

if (empty($ctiConfig) || empty($ctiConfig->id)) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No CTI configuration found for this user']);
    exit;
}

// Validate from number
if (empty($fromNumber)) {
    $fromNumber = $ctiConfig->twilio_phone_number;
}

// Log call attempt in call tracker
$callBean = BeanFactory::newBean('outr_TwilioCalls');
$callBean->call_type = 'outgoing';
$callBean->from_number = $fromNumber;
$callBean->to_number = $toNumber;
$callBean->agent_id = $current_user->id;
$callBean->assigned_user_id = $current_user->id;
$callBean->status = 'initiated';
$callBean->cti_setting_id = $ctiSettingId;

if (!empty($matchResult['parent_type']) && !empty($matchResult['parent_id'])) {
    $callBean->parent_type = $matchResult['parent_type'];
    $callBean->parent_id = $matchResult['parent_id'];
    
    // Try to get company ID
    $accountId = $matcher->findRelatedAccount($matchResult['parent_type'], $matchResult['parent_id']);
    if (!empty($accountId)) {
        $callBean->company_id = $accountId;
    }
}

$callId = $callBean->save();

// Return response with contact info
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'call_id' => $callId,
    'to_number' => $toNumber,
    'from_number' => $fromNumber,
    'contact' => [
        'id' => $matchResult['parent_id'] ?? null,
        'name' => $contactName,
        'type' => $matchResult['parent_type'] ?? null,
        'auto_created' => $isAutoCreated
    ],
    'dial_permission' => true,
    'message' => $isAutoCreated ? 'Contact auto-created' : 'Contact found'
]);
exit;
