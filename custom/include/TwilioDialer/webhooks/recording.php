<?php
// S-057: Recording callback webhook
if (!defined("sugarEntry")) define("sugarEntry", true);
chdir(dirname(__FILE__) . "/../../../../");
require_once "include/entryPoint.php";

$callSid = $_POST["CallSid"] ?? null;
$recordingSid = $_POST["RecordingSid"] ?? null;
$recordingUrl = $_POST["RecordingUrl"] ?? null;
$recordingDuration = $_POST["RecordingDuration"] ?? 0;

if ($callSid && $recordingSid) {
    // Find the call record
    $callBean = BeanFactory::getBean("outr_twilio_calls");
    $list = $callBean->get_list("", "outr_twilio_calls.call_sid = "" . $callBean->db->quote($callSid) . """, 0, 1);
    
    if (!empty($list["list"])) {
        $call = $list["list"][0];
        $call->recording_sid = $recordingSid;
        $call->recording_url = $recordingUrl;
        $call->duration = $recordingDuration;
        $call->save();
    }
}

header("Content-Type: application/xml");
echo "<?xml version="1.0" encoding="UTF-8"?>
<Response/>";
