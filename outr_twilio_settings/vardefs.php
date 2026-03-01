<?php
// S-004: outr_twilio_settings table definition
$dictionary["outr_twilio_settings"] = array(
    "table" => "outr_twilio_settings",
    "audited" => true,
    "fields" => array(
        "id" => array(
            "name" => "id",
            "vname" => "LBL_ID",
            "type" => "id",
            "required" => true,
        ),
        "name" => array(
            "name" => "name",
            "vname" => "LBL_NAME",
            "type" => "name",
            "dbType" => "varchar",
            "len" => 255,
            "required" => true,
        ),
        "accounts_sid" => array(
            "name" => "accounts_sid",
            "vname" => "LBL_ACCOUNTS_SID",
            "type" => "varchar",
            "len" => 100,
        ),
        "auth_token" => array(
            "name" => "auth_token",
            "vname" => "LBL_AUTH_TOKEN",
            "type" => "varchar",
            "len" => 100,
        ),
        "agent_phone_number" => array(
            "name" => "agent_phone_number",
            "vname" => "LBL_AGENT_PHONE_NUMBER",
            "type" => "varchar",
            "len" => 20,
        ),
        "phone_sid" => array(
            "name" => "phone_sid",
            "vname" => "LBL_PHONE_SID",
            "type" => "varchar",
            "len" => 50,
        ),
        "incoming_calls_modules" => array(
            "name" => "incoming_calls_modules",
            "vname" => "LBL_INCOMING_CALLS_MODULES",
            "type" => "enum",
            "options" => "twilio_incoming_modules_list",
            "len" => 50,
        ),
        "status" => array(
            "name" => "status",
            "vname" => "LBL_STATUS",
            "type" => "enum",
            "options" => "twilio_status_list",
            "len" => 20,
            "default" => "Active",
        ),
        "bg_color" => array(
            "name" => "bg_color",
            "vname" => "LBL_BG_COLOR",
            "type" => "varchar",
            "len" => 7,
        ),
        "text_color" => array(
            "name" => "text_color",
            "vname" => "LBL_TEXT_COLOR",
            "type" => "varchar",
            "len" => 7,
        ),
        "api_key_sid" => array(
            "name" => "api_key_sid",
            "vname" => "LBL_API_KEY_SID",
            "type" => "varchar",
            "len" => 100,
        ),
        "api_key_secret" => array(
            "name" => "api_key_secret",
            "vname" => "LBL_API_KEY_SECRET",
            "type" => "varchar",
            "len" => 100,
        ),
        "twiml_app_sid" => array(
            "name" => "twiml_app_sid",
            "vname" => "LBL_TWIML_APP_SID",
            "type" => "varchar",
            "len" => 50,
        ),
        "last_validation_message" => array(
            "name" => "last_validation_message",
            "vname" => "LBL_LAST_VALIDATION_MESSAGE",
            "type" => "text",
        ),
        "date_entered" => array(
            "name" => "date_entered",
            "vname" => "LBL_DATE_ENTERED",
            "type" => "datetime",
        ),
        "date_modified" => array(
            "name" => "date_modified",
            "vname" => "LBL_DATE_MODIFIED",
            "type" => "datetime",
        ),
        "deleted" => array(
            "name" => "deleted",
            "vname" => "LBL_DELETED",
            "type" => "bool",
            "default" => 0,
        ),
        "created_by" => array(
            "name" => "created_by",
            "vname" => "LBL_CREATED_BY",
            "type" => "id",
            "len" => 36,
        ),
        "modified_user_id" => array(
            "name" => "modified_user_id",
            "vname" => "LBL_MODIFIED_USER_ID",
            "type" => "id",
            "len" => 36,
        ),
    ),
    "indices" => array(
        array(
            "name" => "idx_outr_twilio_settings_id",
            "type" => "primary",
            "fields" => array("id"),
        ),
        array(
            "name" => "idx_outr_twilio_settings_status",
            "type" => "index",
            "fields" => array("status"),
        ),
    ),
);
