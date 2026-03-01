<?php
$dictionary["outr_twilio_common_settings"] = array(
    "table" => "outr_twilio_common_settings",
    "audited" => true,
    "fields" => array(
        "id" => array("name" => "id", "vname" => "LBL_ID", "type" => "id", "required" => true),
        "name" => array("name" => "name", "vname" => "LBL_NAME", "type" => "name", "len" => 255),
        "ring_timeout_seconds_before_vm" => array("name" => "ring_timeout_seconds_before_vm", "vname" => "LBL_RING_TIMEOUT_SECONDS_BEFORE_VM", "type" => "varchar", "len" => 255),
        "date_entered" => array("name" => "date_entered", "vname" => "LBL_DATE_ENTERED", "type" => "datetime"),
        "date_modified" => array("name" => "date_modified", "vname" => "LBL_DATE_MODIFIED", "type" => "datetime"),
        "deleted" => array("name" => "deleted", "vname" => "LBL_DELETED", "type" => "bool", "default" => 0),
    ),
    "indices" => array(
        array("name" => "idx_outr_twilio_common_settings_id", "type" => "primary", "fields" => array("id")),
    ),
);