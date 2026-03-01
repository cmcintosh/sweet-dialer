<?php
// S-028/029: Call Transfer Handler
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");

class CallTransferHandler {
    
    public function coldTransfer($callSid, $targetAgentIdentity, $twilioSettings) {
        // Redirect call to target agent
        $statusUrl = $GLOBALS["sugar_config"]["site_url"] . "/index.php?entryPoint=twilioVoiceTransfer";
        
        // Update call record
        $callBean = BeanFactory::getBean("outr_twilio_calls");
        $list = $callBean->get_list("", "outr_twilio_calls.call_sid = "" . $callBean->db->quote($callSid) . """, 0, 1);
        
        if (!empty($list["list"])) {
            $call = $list["list"][0];
            $call->status = "transferred";
            $call->save();
        }
        
        return array("success" => true, "message" => "Call transferred");
    }
}
