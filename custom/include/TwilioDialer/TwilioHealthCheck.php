<?php
// Epic 12: Health Check
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");

class TwilioHealthCheck {
    
    public function check() {
        $checks = array();
        $overallStatus = "healthy";
        
        $checks["cti_settings"] = $this->checkCtiSettings();
        if (!$checks["cti_settings"]["pass"]) $overallStatus = "degraded";
        
        $checks["twilio_api"] = $this->checkTwilioApi();
        if (!$checks["twilio_api"]["pass"]) $overallStatus = "unhealthy";
        
        return array(
            "status" => $overallStatus,
            "timestamp" => date("Y-m-d H:i:s"),
            "checks" => $checks
        );
    }
    
    private function checkCtiSettings() {
        $bean = BeanFactory::getBean("outr_twilio_settings");
        $list = $bean->get_list("", "outr_twilio_settings.status = "Active"", 0, 1);
        return array(
            "pass" => count($list["list"]) > 0,
            "message" => count($list["list"]) . " active CTI settings"
        );
    }
    
    private function checkTwilioApi() {
        try {
            $bean = $this->getFirstActiveCtiSetting();
            if (!$bean) return array("pass" => false, "message" => "No active settings");
            
            require_once "custom/include/TwilioDialer/TwilioApiClient.php";
            $client = new TwilioApiClient($bean->accounts_sid, $bean->auth_token);
            $result = $client->validateCredentials();
            
            return array(
                "pass" => $result["success"],
                "message" => $result["message"]
            );
        } catch (Exception $e) {
            return array("pass" => false, "message" => $e->getMessage());
        }
    }
    
    private function getFirstActiveCtiSetting() {
        $bean = BeanFactory::getBean("outr_twilio_settings");
        $list = $bean->get_list("", "outr_twilio_settings.status = "Active"", 0, 1);
        return !empty($list["list"][0]) ? $list["list"][0] : null;
    }
}
