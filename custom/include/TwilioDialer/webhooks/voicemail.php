<?php
// S-050: Voicemail callback webhook
if (!defined("sugarEntry")) define("sugarEntry", true);
chdir(dirname(__FILE__) . "/../../../../");
require_once "include/entryPoint.php";

// Default voicemail response
echo "<?xml version="1.0" encoding="UTF-8"?>
<Response>
";
echo "<Say>Please leave a message after the tone</Say>
";
echo "<Record maxLength="300" finishOnKey="#" recordingStatusCallback="" . $GLOBALS["sugar_config"]["site_url"] . "/index.php?entryPoint=twilioVoiceRecording"/>
";
echo "</Response>";
