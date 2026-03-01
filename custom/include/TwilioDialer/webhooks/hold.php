<?php
// S-052: Hold music webhook
if (!defined("sugarEntry")) define("sugarEntry", true);
chdir(dirname(__FILE__) . "/../../../../");
require_once "include/entryPoint.php";

echo "<?xml version="1.0" encoding="UTF-8"?>
<Response>
";
echo "<Play loop="0">https://demo.twilio.com/docs/classic.mp3</Play>
";
echo "</Response>";
