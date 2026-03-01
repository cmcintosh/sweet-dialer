<?php
// S-054: Status callback webhook
if (!defined("sugarEntry")) define("sugarEntry", true);
chdir(dirname(__FILE__) . "/../../../../");
require_once "include/entryPoint.php";

require_once "custom/include/TwilioDialer/CallStatusHandler.php";

$handler = new CallStatusHandler();
$handler->handleStatusCallback($_POST);

header("Content-Type: application/xml");
echo "<?xml version="1.0" encoding="UTF-8"?>
<Response/>";
