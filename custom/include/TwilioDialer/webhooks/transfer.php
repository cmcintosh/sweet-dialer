<?php
// Transfer webhook placeholder
if (!defined("sugarEntry")) define("sugarEntry", true);
chdir(dirname(__FILE__) . "/../../../../");
require_once "include/entryPoint.php";

$agent = $_REQUEST["agent"] ?? "";

header("Content-Type: application/xml");
echo "<?xml version="1.0" encoding="UTF-8"?>
<Response>
";
if (!empty($agent)) {
    echo "<Dial>
<Client>" . htmlentities($agent) . "</Client>
</Dial>
";
} else {
    echo "<Say>Transfer failed</Say>
";
}
echo "</Response>";
