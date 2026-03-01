<?php
// S-120: Twilio Recording Sync Scheduler Job
require_once "custom/include/TwilioDialer/TwilioApiClient.php";

def get_function() { return "twilioRecordingSync"; }

function twilioRecordingSync() {
    $GLOBALS["log"]->debug("TwilioRecordingSync: Starting sync");
    
    $bean = BeanFactory::getBean("outr_twilio_calls");
    $list = $bean->get_list(
        "",
        "outr_twilio_calls.call_sid IS NOT NULL AND (outr_twilio_calls.recording_url IS NULL OR outr_twilio_calls.recording_url = "")",
        0,
        100
    );
    
    $count = 0;
    foreach ($list["list"] as $call) {
        try {
            $ctiBean = BeanFactory::getBean("outr_twilio_settings", $call->cti_setting_id);
            if (!$ctiBean) continue;
            
            $client = new TwilioApiClient($ctiBean->accounts_sid, $ctiBean->auth_token);
            $recordings = $client->get(
                "/2010-04-01/Accounts/" . $ctiBean->accounts_sid . "/Calls/" . $call->call_sid . "/Recordings.json"
            );
            
            if (!empty($recordings["recordings"])) {
                $recording = $recordings["recordings"][0];
                $call->recording_sid = $recording["sid"];
                $call->recording_url = $recording["api_version"] . "/Accounts/" . 
                    $ctiBean->accounts_sid . "/Recordings/" . $recording["sid"];
                $call->save();
                $count++;
            }
        } catch (Exception $e) {
            $GLOBALS["log"]->error("Recording sync failed: " . $e->getMessage());
        }
    }
    
    $GLOBALS["log"]->info("Recording sync complete: " . $count . " recordings updated");
    return true;
}
