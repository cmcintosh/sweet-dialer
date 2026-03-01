<?php
// S-046: Outbound Call Webhook Handler
if (!defined("sugarEntry")) define("sugarEntry", true);
chdir(dirname(__FILE__) . "/../../../../");
require_once "include/entryPoint.php";

$toNumber = $_REQUEST["To"] ?? "";
$fromNumber = $_REQUEST["From"] ?? "";

// Get TwiML App SID from active CTI setting
$ctiSettings = BeanFactory::getBean("outr_twilio_settings");
$settingsList = $ctiSettings->get_list("", "outr_twilio_settings.status = "Active"", 0, 1);

if (!empty($settingsList["list"])) {
    $settings = $settingsList["list"][0];
    $fromNumber = $settings->agent_phone_number;
}

$statusCallbackUrl = $GLOBALS["sugar_config"]["site_url"] . "/index.php?entryPoint=twilioVoiceStatus";

header("Content-Type: application/xml");

if (empty($toNumber)) {
    echo "<?xml version="1.0" encoding="UTF-8"?>
<Response>
<Say>Invalid destination number</Say>
</Response>";
    exit;
}

echo "<?xml version="1.0" encoding="UTF-8"?>
<Response>
";
echo "<Dial callerId="" . htmlentities($fromNumber) . "" statusCallback="" . $statusCallbackUrl . "" record="record-from-answer">
";
echo "<Number>" . htmlentities($toNumber) . "</Number>
";
echo "</Dial>
";
echo "</Response>";
