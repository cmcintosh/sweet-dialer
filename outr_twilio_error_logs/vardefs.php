<?php
$dictionary["outr_twilio_error_logs"] = array(
    "table" => "outr_twilio_error_logs",
    "audited" => true,
    "fields" => array(
        "id" => array("name" => "id", "vname" => "LBL_ID", "type" => "id", "required" => true),
        "name" => array("name" => "name", "vname" => "LBL_NAME", "type" => "name", "len" => 255),
        "error_code" => array("name" => "error_code", "vname" => "LBL_ERROR_CODE", "type" => "varchar", "len" => 255),
        "error_message" => array("name" => "error_message", "vname" => "LBL_ERROR_MESSAGE", "type" => "varchar", "len" => 255),
        "call_sid" => array("name" => "call_sid", "vname" => "LBL_CALL_SID", "type" => "varchar", "len" => 255),
        "endpoint" => array("name" => "endpoint", "vname" => "LBL_ENDPOINT", "type" => "varchar", "len" => 255),
        "request_body" => array("name" => "request_body", "vname" => "LBL_REQUEST_BODY", "type" => "varchar", "len" => 255),
        "response_body" => array("name" => "response_body", "vname" => "LBL_RESPONSE_BODY", "type" => "varchar", "len" => 255),
        "date_created" => array("name" => "date_created", "vname" => "LBL_DATE_CREATED", "type" => "varchar", "len" => 255),
        "date_entered" => array("name" => "date_entered", "vname" => "LBL_DATE_ENTERED", "type" => "datetime"),
        "date_modified" => array("name" => "date_modified", "vname" => "LBL_DATE_MODIFIED", "type" => "datetime"),
        "deleted" => array("name" => "deleted", "vname" => "LBL_DELETED", "type" => "bool", "default" => 0),
    ),
    "indices" => array(
        array("name" => "idx_outr_twilio_error_logs_id", "type" => "primary", "fields" => array("id")),
    ),
);