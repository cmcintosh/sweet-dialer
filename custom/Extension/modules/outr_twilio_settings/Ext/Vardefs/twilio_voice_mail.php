<?php
// S-013: Relationship - outr_twilio_settings -> outr_twilio_voicemail
$dictionary["outr_twilio_settings"]["fields"]["twilio_voice_mail"] = array(
    "name" => "twilio_voice_mail",
    "vname" => "LBL_TWILIO_VOICE_MAIL",
    "type" => "relate",
    "module" => "outr_twilio_voicemail",
    "id_name" => "twilio_voice_mail_id",
    "source" => "non-db",
    "massupdate" => false,
);

$dictionary["outr_twilio_settings"]["fields"]["twilio_voice_mail_id"] = array(
    "name" => "twilio_voice_mail_id",
    "vname" => "LBL_TWILIO_VOICE_MAIL_ID",
    "type" => "id",
    "len" => 36,
);
