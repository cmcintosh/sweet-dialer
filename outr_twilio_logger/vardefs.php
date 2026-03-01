<?php
$dictionary["outr_twilio_logger"] = array(
    "table" => "outr_twilio_logger",
    "audited" => true,
    "fields" => array(
        "id" => array("name" => "id", "vname" => "LBL_ID", "type" => "id", "required" => true),
        "name" => array("name" => "name", "vname" => "LBL_NAME", "type" => "name", "len" => 255),
        "log_level" => array("name" => "log_level", "vname" => "LBL_LOG_LEVEL", "type" => "varchar", "len" => 255),
        "message" => array("name" => "message", "vname" => "LBL_MESSAGE", "type" => "varchar", "len" => 255),
        "context" => array("name" => "context", "vname" => "LBL_CONTEXT", "type" => "varchar", "len" => 255),
        "date_created" => array("name" => "date_created", "vname" => "LBL_DATE_CREATED", "type" => "varchar", "len" => 255),
        "date_entered" => array("name" => "date_entered", "vname" => "LBL_DATE_ENTERED", "type" => "datetime"),
        "date_modified" => array("name" => "date_modified", "vname" => "LBL_DATE_MODIFIED", "type" => "datetime"),
        "deleted" => array("name" => "deleted", "vname" => "LBL_DELETED", "type" => "bool", "default" => 0),
    ),
    "indices" => array(
        array("name" => "idx_outr_twilio_logger_id", "type" => "primary", "fields" => array("id")),
    ),
);