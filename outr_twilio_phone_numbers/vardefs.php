<?php
$dictionary["outr_twilio_phone_numbers"] = array(
    "table" => "outr_twilio_phone_numbers",
    "audited" => true,
    "fields" => array(
        "id" => array("name" => "id", "vname" => "LBL_ID", "type" => "id", "required" => true),
        "name" => array("name" => "name", "vname" => "LBL_NAME", "type" => "name", "len" => 255),
        "phone_number" => array("name" => "phone_number", "vname" => "LBL_PHONE_NUMBER", "type" => "varchar", "len" => 255),
        "friendly_name" => array("name" => "friendly_name", "vname" => "LBL_FRIENDLY_NAME", "type" => "varchar", "len" => 255),
        "phone_sid" => array("name" => "phone_sid", "vname" => "LBL_PHONE_SID", "type" => "varchar", "len" => 255),
        "capabilities_voice" => array("name" => "capabilities_voice", "vname" => "LBL_CAPABILITIES_VOICE", "type" => "varchar", "len" => 255),
        "capabilities_sms" => array("name" => "capabilities_sms", "vname" => "LBL_CAPABILITIES_SMS", "type" => "varchar", "len" => 255),
        "assignment_status" => array("name" => "assignment_status", "vname" => "LBL_ASSIGNMENT_STATUS", "type" => "varchar", "len" => 255),
        "date_entered" => array("name" => "date_entered", "vname" => "LBL_DATE_ENTERED", "type" => "datetime"),
        "date_modified" => array("name" => "date_modified", "vname" => "LBL_DATE_MODIFIED", "type" => "datetime"),
        "deleted" => array("name" => "deleted", "vname" => "LBL_DELETED", "type" => "bool", "default" => 0),
    ),
    "indices" => array(
        array("name" => "idx_outr_twilio_phone_numbers_id", "type" => "primary", "fields" => array("id")),
    ),
);