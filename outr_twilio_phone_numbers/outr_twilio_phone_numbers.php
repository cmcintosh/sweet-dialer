<?php
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");
require_once "include/SugarObjects/templates/basic/Basic.php";

class outr_twilio_phone_numbers extends Basic {
    public $module_dir = "outr_twilio_phone_numbers";
    public $object_name = "outr_twilio_phone_numbers";
    public $table_name = "outr_twilio_phone_numbers";
    public $new_schema = true;
}
