<?php
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");
require_once "include/SugarObjects/templates/basic/Basic.php";

class outr_twilio_calls extends Basic {
    public $module_dir = "outr_twilio_calls";
    public $object_name = "outr_twilio_calls";
    public $table_name = "outr_twilio_calls";
    public $new_schema = true;
    
    public $call_sid;
    public $from_number;
    public $to_number;
    public $call_type;
    public $agent_id;
    public $status;
    public $duration;
    public $recording_url;
    public $recording_sid;
    public $parent_type;
    public $parent_id;
    public $company_id;
    public $notes;
    public $cti_setting_id;
    public $direction;
}
