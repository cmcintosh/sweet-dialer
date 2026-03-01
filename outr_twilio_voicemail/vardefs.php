<?php
$dictionary["outr_twilio_voicemail"] = array(
    "table" => "outr_twilio_voicemail",
    "audited" => true,
    "fields" => array(
        "id" => array("name" => "id", "vname" => "LBL_ID", "type" => "id", "required" => true),
        "name" => array("name" => "name", "vname" => "LBL_NAME", "type" => "name", "len" => 255),
        "file" => array("name" => "file", "vname" => "LBL_FILE", "type" => "varchar", "len" => 255),
        "voice_mail_message" => array("name" => "voice_mail_message", "vname" => "LBL_VOICE_MAIL_MESSAGE", "type" => "varchar", "len" => 255),
        "voice_speech_by" => array("name" => "voice_speech_by", "vname" => "LBL_VOICE_SPEECH_BY", "type" => "varchar", "len" => 255),
        "voice_finish_key" => array("name" => "voice_finish_key", "vname" => "LBL_VOICE_FINISH_KEY", "type" => "varchar", "len" => 255),
        "voice_max_length" => array("name" => "voice_max_length", "vname" => "LBL_VOICE_MAX_LENGTH", "type" => "varchar", "len" => 255),
        "date_entered" => array("name" => "date_entered", "vname" => "LBL_DATE_ENTERED", "type" => "datetime"),
        "date_modified" => array("name" => "date_modified", "vname" => "LBL_DATE_MODIFIED", "type" => "datetime"),
        "deleted" => array("name" => "deleted", "vname" => "LBL_DELETED", "type" => "bool", "default" => 0),
    ),
    "indices" => array(
        array("name" => "idx_outr_twilio_voicemail_id", "type" => "primary", "fields" => array("id")),
    ),
);