<?php
// S-014: Relationship - outr_twilio_calls -> outr_twilio_settings
$dictionary["outr_twilio_calls"]["fields"]["cti_setting"] = array(
    "name" => "cti_setting",
    "vname" => "LBL_CTI_SETTING",
    "type" => "relate",
    "module" => "outr_twilio_settings",
    "id_name" => "cti_setting_id",
    "source" => "non-db",
    "massupdate" => false,
);

$dictionary["outr_twilio_calls"]["fields"]["cti_setting_id"] = array(
    "name" => "cti_setting_id",
    "vname" => "LBL_CTI_SETTING_ID",
    "type" => "id",
    "len" => 36,
);
