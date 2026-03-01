<?php
// S-048: Inbound Call Webhook Handler
if (!defined("sugarEntry")) define("sugarEntry", true);
chdir(dirname(__FILE__) . "/../../../../");
require_once "include/entryPoint.php";

$toNumber = $_REQUEST["To"] ?? "";
$fromNumber = $_REQUEST["From"] ?? "";

echo "<?xml version="1.0" encoding="UTF-8"?>
<Response>
";

// Find CTI setting for this phone number
$ctiSettings = BeanFactory::getBean("outr_twilio_settings");
$settingsList = $ctiSettings->get_list("", "outr_twilio_settings.agent_phone_number = "" . $db->quote($toNumber) . """, 0, 1);

if (!empty($settingsList["list"])) {
    $settings = $settingsList["list"][0];
    $statusCallbackUrl = $GLOBALS["sugar_config"]["site_url"] . "/index.php?entryPoint=twilioVoiceStatus";
    $voicemailUrl = $GLOBALS["sugar_config"]["site_url"] . "/index.php?entryPoint=twilioVoiceVoicemail";
    
    echo "<Dial action="" . $voicemailUrl . "" statusCallback="" . $statusCallbackUrl . "" timeout="30">
";
    echo "<Client>" . htmlentities($settings->outbound_inbound_agent_name ?? "agent") . "</Client>
";
    echo "</Dial>
";
} else {
    echo "<Say>No agent available for this number</Say>
";
}

echo "</Response>";
