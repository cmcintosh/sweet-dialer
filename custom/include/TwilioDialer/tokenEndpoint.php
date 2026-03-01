<?php
// Epic 4/S-041: Token Generation API Endpoint
if (!defined("sugarEntry")) define("sugarEntry", true);
chdir(dirname(__FILE__) . "/../../../../");
require_once "include/entryPoint.php";

header("Content-Type: application/json");

// Check authentication
if (empty($_SESSION["authenticated_user_id"])) {
    http_response_code(401);
    echo json_encode(array("error" => "Unauthorized"));
    exit;
}

try {
    // Get current user
    $currentUser = BeanFactory::getBean("Users", $_SESSION["authenticated_user_id"]);
    $agentIdentity = $currentUser ? $currentUser->user_name : "agent";
    
    // Get active CTI settings
    $settingsBean = BeanFactory::getBean("outr_twilio_settings");
    $settingsList = $settingsBean->get_list("", "outr_twilio_settings.status = "Active"", 0, 1);
    
    if (empty($settingsList["list"])) {
        throw new Exception("No active CTI settings found");
    }
    
    $settings = $settingsList["list"][0];
    
    // Validate required fields
    if (empty($settings->api_key_sid) || empty($settings->api_key_secret)) {
        throw new Exception("API credentials not configured");
    }
    
    require_once "custom/include/TwilioDialer/TokenGenerator.php";
    $token = TokenGenerator::generate(
        $settings->api_key_sid,
        $settings->api_key_secret,
        $settings->accounts_sid,
        $settings->twiml_app_sid,
        $agentIdentity
    );
    
    echo json_encode(array(
        "success" => true,
        "token" => $token,
        "identity" => $agentIdentity
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "error" => $e->getMessage()
    ));
}
