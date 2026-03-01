<?php
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");
require_once "include/SugarObjects/templates/basic/Basic.php";

class outr_twilio_common_settings extends Basic {
    public $module_dir = "outr_twilio_common_settings";
    public $object_name = "outr_twilio_common_settings";
    public $table_name = "outr_twilio_common_settings";
    public $new_schema = true;
}
