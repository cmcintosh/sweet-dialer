<?php
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");
require_once "include/SugarObjects/templates/basic/Basic.php";

class outr_twilio_opted_out extends Basic {
    public $module_dir = "outr_twilio_opted_out";
    public $object_name = "outr_twilio_opted_out";
    public $table_name = "outr_twilio_opted_out";
    public $new_schema = true;
}
