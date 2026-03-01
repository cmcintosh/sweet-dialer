<?php
$dictionary["outr_twilio_voicemail_recordings"] = array(
    "table" => "outr_twilio_voicemail_recordings",
    "audited" => true,
    "fields" => array(
        "id" => array("name" => "id", "vname" => "LBL_ID", "type" => "id", "required" => true),
        "name" => array("name" => "name", "vname" => "LBL_NAME", "type" => "name", "len" => 255),
        "voicemail_id" => array("name" => "voicemail_id", "vname" => "LBL_VOICEMAIL_ID", "type" => "varchar", "len" => 255),
        "recording_url" => array("name" => "recording_url", "vname" => "LBL_RECORDING_URL", "type" => "varchar", "len" => 255),
        "duration" => array("name" => "duration", "vname" => "LBL_DURATION", "type" => "varchar", "len" => 255),
        "from_number" => array("name" => "from_number", "vname" => "LBL_FROM_NUMBER", "type" => "varchar", "len" => 255),
        "date_entered" => array("name" => "date_entered", "vname" => "LBL_DATE_ENTERED", "type" => "datetime"),
        "date_modified" => array("name" => "date_modified", "vname" => "LBL_DATE_MODIFIED", "type" => "datetime"),
        "deleted" => array("name" => "deleted", "vname" => "LBL_DELETED", "type" => "bool", "default" => 0),
    ),
    "indices" => array(
        array("name" => "idx_outr_twilio_voicemail_recordings_id", "type" => "primary", "fields" => array("id")),
    ),
);