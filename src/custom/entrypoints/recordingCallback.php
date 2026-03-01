<?php
/**
 * Twilio Recording Callback Entrypoint
 * Epic 5: Webhooks - S-043-S-045 (5 pts)
 * Handles recording status and storage
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

while (ob_get_level()) {
    ob_end_clean();
}

// Get Twilio recording parameters
$recordingSid = isset($_POST['RecordingSid']) ? $_POST['RecordingSid'] : null;
$callSid = isset($_POST['CallSid']) ? $_POST['CallSid'] : null;
$recordingUrl = isset($_POST['RecordingUrl']) ? $_POST['RecordingUrl'] : null;
$recordingDuration = isset($_POST['RecordingDuration']) ? intval($_POST['RecordingDuration']) : 0;
$status = isset($_POST['RecordingStatus']) ? $_POST['RecordingStatus'] : 'completed';

if (empty($recordingSid)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Missing RecordingSid';
    exit;
}

// Find call record
if (!empty($callSid)) {
    $callBean = BeanFactory::getBean('OutrTwilioCalls');
    $calls = $callBean->get_full_list(null, "outr_twiliocalls.call_sid = '".$callSid."'");
    
    if (!empty($calls) && !empty($calls[0])) {
        $call = $calls[0];
        $call->recording_sid = $recordingSid;
        $call->recording_url = $recordingUrl;
        $call->recording_status = $status;
        $call->recording_duration = $recordingDuration;
        $call->save(false);
        
        // Optionally download recording
        $ctiBean = BeanFactory::getBean('OutrCtiSettings');
        $cti = $ctiBean->get_full_list();
        
        if (!empty($cti) && !empty($cti[0]) && $cti[0]->store_recordings_locally) {
            // Download and store locally
            $uploadDir = 'upload://recordings/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $localPath = $uploadDir . $recordingSid . '.mp3';
            // file_put_contents($localPath, file_get_contents($recordingUrl));
        }
        
        // Fire event for UI notification
        $eventData = array(
            'recording_sid' => $recordingSid,
            'call_sid' => $callSid,
            'status' => $status,
            'duration' => $recordingDuration
        );
    }
}

echo 'OK';
exit;
