<?php
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");
require_once "include/SugarObjects/templates/basic/Basic.php";

class outr_twilio_settings extends Basic {
    public $module_dir = "outr_twilio_settings";
    public $object_name = "outr_twilio_settings";
    public $table_name = "outr_twilio_settings";
    public $new_schema = true;
    
    // Module fields
    public $accounts_sid;
    public $auth_token;
    public $agent_phone_number;
    public $phone_sid;
    public $incoming_calls_modules;
    public $status;
    public $bg_color;
    public $text_color;
    public $api_key_sid;
    public $api_key_secret;
    public $twiml_app_sid;
    public $last_validation_message;
}
