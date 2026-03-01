<?php
$dictionary["outr_twilio_hold_ringtone"] = array(
    "table" => "outr_twilio_hold_ringtone",
    "audited" => true,
    "fields" => array(
        "id" => array("name" => "id", "vname" => "LBL_ID", "type" => "id", "required" => true),
        "name" => array("name" => "name", "vname" => "LBL_NAME", "type" => "name", "len" => 255),
        "file" => array("name" => "file", "vname" => "LBL_FILE", "type" => "varchar", "len" => 255),
        "category" => array("name" => "category", "vname" => "LBL_CATEGORY", "type" => "varchar", "len" => 255),
        "sub_category" => array("name" => "sub_category", "vname" => "LBL_SUB_CATEGORY", "type" => "varchar", "len" => 255),
        "assigned_to" => array("name" => "assigned_to", "vname" => "LBL_ASSIGNED_TO", "type" => "varchar", "len" => 255),
        "publish_date" => array("name" => "publish_date", "vname" => "LBL_PUBLISH_DATE", "type" => "varchar", "len" => 255),
        "expiration_date" => array("name" => "expiration_date", "vname" => "LBL_EXPIRATION_DATE", "type" => "varchar", "len" => 255),
        "status" => array("name" => "status", "vname" => "LBL_STATUS", "type" => "varchar", "len" => 255),
        "description" => array("name" => "description", "vname" => "LBL_DESCRIPTION", "type" => "varchar", "len" => 255),
        "date_entered" => array("name" => "date_entered", "vname" => "LBL_DATE_ENTERED", "type" => "datetime"),
        "date_modified" => array("name" => "date_modified", "vname" => "LBL_DATE_MODIFIED", "type" => "datetime"),
        "deleted" => array("name" => "deleted", "vname" => "LBL_DELETED", "type" => "bool", "default" => 0),
    ),
    "indices" => array(
        array("name" => "idx_outr_twilio_hold_ringtone_id", "type" => "primary", "fields" => array("id")),
    ),
);