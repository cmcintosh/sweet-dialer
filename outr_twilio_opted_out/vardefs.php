<?php
$dictionary["outr_twilio_opted_out"] = array(
    "table" => "outr_twilio_opted_out",
    "audited" => true,
    "fields" => array(
        "id" => array("name" => "id", "vname" => "LBL_ID", "type" => "id", "required" => true),
        "name" => array("name" => "name", "vname" => "LBL_NAME", "type" => "name", "len" => 255),
        "phone_number" => array("name" => "phone_number", "vname" => "LBL_PHONE_NUMBER", "type" => "varchar", "len" => 255),
        "reason" => array("name" => "reason", "vname" => "LBL_REASON", "type" => "varchar", "len" => 255),
        "date_opted_out" => array("name" => "date_opted_out", "vname" => "LBL_DATE_OPTED_OUT", "type" => "varchar", "len" => 255),
        "date_entered" => array("name" => "date_entered", "vname" => "LBL_DATE_ENTERED", "type" => "datetime"),
        "date_modified" => array("name" => "date_modified", "vname" => "LBL_DATE_MODIFIED", "type" => "datetime"),
        "deleted" => array("name" => "deleted", "vname" => "LBL_DELETED", "type" => "bool", "default" => 0),
    ),
    "indices" => array(
        array("name" => "idx_outr_twilio_opted_out_id", "type" => "primary", "fields" => array("id")),
    ),
);