<?php
/**
 * Conference Join Entrypoint
 * S-113-S-115: Browser-based conference joining
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $current_user;

// Auth check
if (empty($current_user) || empty($current_user->id)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get conference SID from request
$conferenceSid = isset($_GET['sid']) ? $_GET['sid'] : (isset($_POST['sid']) ? $_POST['sid'] : null);

if (empty($conferenceSid)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Conference SID required']);
    exit;
}

// Load conference record
$confBean = BeanFactory::getBean('OutrConference');
$conferences = $confBean->get_full_list(
    null,
    "outr_conference.friendly_name = '" . $conferenceSid . "' OR outr_conference.conference_sid = '" . $conferenceSid . "'"
);

if (empty($conferences) || empty($conferences[0])) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Conference not found']);
    exit;
}

$conference = $conferences[0];

// Check if conference is active
if ($conference->status != 'Active') {
    header('HTTP/1.1 409 Conflict');
    echo json_encode(['error' => 'Conference is not active']);
    exit;
}

// Load Twilio config
$helper = new \SuiteCRM\TwilioHelper();
$accountSid = $helper->getAccountSid();
$authToken = $helper->getAuthToken();

// Generate capability token for client
if (!class_exists('\Twilio\Jwt\AccessToken')) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Twilio SDK not available']);
    exit;
}

$userIdentity = $current_user->user_name;

// Create access token
$token = new \Twilio\Jwt\AccessToken(
    $accountSid,
    $helper->getTwilioApiKey(),
    $helper->getTwilioApiSecret(),
    3600,
    $userIdentity
);

// Add conference grant
$voiceGrant = new \Twilio\Jwt\Grants\VoiceGrant();
$voiceGrant->setOutgoingApplicationSid($helper->getAppSid());
$voiceGrant->setIncomingAllow(true);
$voiceGrant->setEndpointId('conference:' . $conferenceSid . ':' . $current_user->id);
$token->addGrant($voiceGrant);

// Get conference details
$data = array(
    'token' => $token->toJWT(),
    'user_id' => $current_user->id,
    'user_name' => $current_user->full_name,
    'conference_sid' => $conferenceSid,
    'conference_name' => $conference->name,
    'max_participants' => $conference->max_participants,
    'recording_enabled' => (bool)$conference->recording_enabled
);

header('Content-Type: application/json');
echo json_encode($data);
exit;
