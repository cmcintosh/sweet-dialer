<?php
/**
 * Conference Control Entrypoint
 * S-116-S-120: Conference control actions
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global \$current_user;

// Auth check
if (empty(\$current_user) || empty(\$current_user->id)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get parameters
\$action = isset(\$_REQUEST['action']) ? \$_REQUEST['action'] : null;
\$conferenceSid = isset(\$_REQUEST['conference_sid']) ? \$_REQUEST['conference_sid'] : null;
\$participantSid = isset(\$_REQUEST['participant_sid']) ? \$_REQUEST['participant_sid'] : null;
\$phone = isset(\$_REQUEST['phone']) ? \$_REQUEST['phone'] : null;

if (empty(\$action) || empty(\$conferenceSid)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Action and conference_sid required']);
    exit;
}

require_once('custom/include/TwilioDialer/TwilioClient.php');

\$twilioClient = \SuiteCRM\TwilioClient::getInstance();
\$result = array('success' => false);

try {
    switch (\$action) {
        case 'participants':
        case 'list':
            \$participants = \$twilioClient->getConferenceParticipants(\$conferenceSid);
            \$result = [
                'success' => true,
                'participants' => \$participants,
                'participant_count' => count(\$participants)
            ];
            break;

        case 'mute':
            if (empty(\$participantSid)) {
                throw new Exception('participant_sid required');
            }
            \$twilioClient->muteConferenceParticipant(\$conferenceSid, \$participantSid, true);
            \$result = ['success' => true, 'action' => 'muted'];
            break;

        case 'unmute':
            if (empty(\$participantSid)) {
                throw new Exception('participant_sid required');
            }
            \$twilioClient->muteConferenceParticipant(\$conferenceSid, \$participantSid, false);
            \$result = ['success' => true, 'action' => 'unmuted'];
            break;

        case 'kick':
        case 'remove':
            if (empty(\$participantSid)) {
                throw new Exception('participant_sid required');
            }
            \$twilioClient->removeConferenceParticipant(\$conferenceSid, \$participantSid);
            \$result = ['success' => true, 'action' => 'removed'];
            break;

        case 'add_participant':
        case 'add':
            if (empty(\$phone)) {
                throw new Exception('phone required');
            }
            \$callSid = \$twilioClient->addParticipantToConference(\$conferenceSid, \$phone);
            \$result = ['success' => true, 'action' => 'added', 'call_sid' => \$callSid];
            break;

        case 'mute_all':
            \$twilioClient->muteAllConferenceParticipants(\$conferenceSid, true);
            \$result = ['success' => true, 'action' => 'mute_all'];
            break;

        case 'unmute_all':
            \$twilioClient->muteAllConferenceParticipants(\$conferenceSid, false);
            \$result = ['success' => true, 'action' => 'unmute_all'];
            break;

        case 'hold':
            if (empty(\$participantSid)) {
                throw new Exception('participant_sid required');
            }
            \$twilioClient->holdConferenceParticipant(\$conferenceSid, \$participantSid, true);
            \$result = ['success' => true, 'action' => 'held'];
            break;

        case 'unhold':
            if (empty(\$participantSid)) {
                throw new Exception('participant_sid required');
            }
            \$twilioClient->holdConferenceParticipant(\$conferenceSid, \$participantSid, false);
            \$result = ['success' => true, 'action' => 'unheld'];
            break;

        case 'start_recording':
            \$recording = \$twilioClient->startConferenceRecording(\$conferenceSid);
            \$result = ['success' => true, 'action' => 'recording_started', 'recording_sid' => \$recording->sid];
            break;

        case 'stop_recording':
            \$twilioClient->stopConferenceRecording(\$conferenceSid);
            \$result = ['success' => true, 'action' => 'recording_stopped'];
            break;

        case 'end':
        case 'terminate':
            \$twilioClient->endConference(\$conferenceSid);
            // Update conference record
            \$confBean = BeanFactory::getBean('OutrConference');
            \$confs = \$confBean->get_full_list(null, "outr_conference.conference_sid = '".\$conferenceSid."'");
            if (!empty(\$confs) && !empty(\$confs[0])) {
                \$confs[0]->status = 'Ended';
                \$confs[0]->save();
            }
            \$result = ['success' => true, 'action' => 'ended'];
            break;

        default:
            throw new Exception('Unknown action: '.\$action);
    }
} catch (Exception \$e) {
    header('HTTP/1.1 500 Internal Server Error');
    \$result = ['success' => false, 'error' => \$e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode(\$result);
exit;
