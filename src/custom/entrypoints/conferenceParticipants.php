<?php
/**
 * Conference Participants List Entrypoint
 * S-116-S-120: Get participant list for conference
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

$sid = isset($_GET['sid']) ? $_GET['sid'] : null;

if (empty($sid)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'sid required']);
    exit;
}

// Load conference to get config
$confBean = BeanFactory::getBean('OutrConference');
$conferences = $confBean->get_full_list(null, "outr_conference.friendly_name = '".$sid."' OR outr_conference.conference_sid = '".$sid."'");

$maxParticipants = 10;
$recording = false;

if (!empty($conferences) && !empty($conferences[0])) {
    $maxParticipants = $conferences[0]->max_participants;
    $recording = (bool)$conferences[0]->recording_enabled;
}

// TODO: Fetch actual participants from Twilio API
// For now, return mock data structure
$participants = array();

$result = array(
    'success' => true,
    'conference_sid' => $sid,
    'participant_count' => count($participants),
    'max_participants' => $maxParticipants,
    'recording' => $recording,
    'participants' => $participants
);

header('Content-Type: application/json');
echo json_encode($result);
exit;
