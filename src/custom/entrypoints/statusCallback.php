<?php
/**
 * Twilio Status Callback Entrypoint
 * Epic 5: Webhooks - S-043-S-045 (10 pts)
 * Handles call status updates
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

while (ob_get_level()) {
    ob_end_clean();
}

// Get Twilio status parameters
\$callSid = isset(\$_POST['CallSid']) ? \$_POST['CallSid'] : null;
\$status = isset(\$_POST['CallStatus']) ? \$_POST['CallStatus'] : null;
\$duration = isset(\$_POST['CallDuration']) ? intval(\$_POST['CallDuration']) : 0;
\$recordingUrl = isset(\$_POST['RecordingUrl']) ? \$_POST['RecordingUrl'] : null;
\$recordingSid = isset(\$_POST['RecordingSid']) ? \$_POST['RecordingSid'] : null;
\$timestamp = isset(\$_POST['Timestamp']) ? \$_POST['Timestamp'] : TimeDate::getInstance()->nowDb();

if (empty(\$callSid) || empty(\$status)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Missing required parameters';
    exit;
}

// Find call record
\$callBean = BeanFactory::getBean('OutrTwilioCalls');
\$calls = \$callBean->get_full_list(null, "outr_twiliocalls.call_sid = '".\$callSid."'");

if (empty(\$calls) || empty(\$calls[0])) {
    // Create new call record if not found
    \$callBean->call_sid = \$callSid;
    \$callBean->status = \$status;
    \$callBean->date_start = \$timestamp;
    \$callBean->save(false);
} else {
    \$callBean = \$calls[0];
    
    // Map Twilio status to our status
    \$statusMap = array(
        'queued' => 'pending',
        'ringing' => 'ringing',
        'in-progress' => 'in-progress',
        'completed' => 'completed',
        'failed' => 'failed',
        'busy' => 'busy',
        'no-answer' => 'no-answer',
        'canceled' => 'canceled'
    );
    
    \$callBean->status = isset(\$statusMap[\$status]) ? \$statusMap[\$status] : \$status;
    \$callBean->duration = \$duration;
    
    // Calculate end time if completed
    if (\$status === 'completed' || \$status === 'failed' || \$status === 'busy' || 
        \$status === 'no-answer' || \$status === 'canceled') {
        \$callBean->date_end = \$timestamp;
        
        // Update billable duration
        if (\$duration > 0) {
            \$callBean->billable_duration = ceil(\$duration / 60); // Round up to minutes
        }
    }
    
    // Update recording info if provided
    if (!empty(\$recordingUrl)) {
        \$callBean->recording_url = \$recordingUrl;
    }
    if (!empty(\$recordingSid)) {
        \$callBean->recording_sid = \$recordingSid;
    }
    
    \$callBean->save(false);
}

// Trigger logic hooks for status changes
\$callBean->call_custom_logic('after_status_update', array('status' => \$status));

// Fire Sugar Event (for real-time UI updates)
\$eventData = array(
    'call_sid' => \$callSid,
    'status' => \$status,
    'duration' => \$duration,
    'call_id' => \$callBean->id
);

echo 'OK';
exit;
