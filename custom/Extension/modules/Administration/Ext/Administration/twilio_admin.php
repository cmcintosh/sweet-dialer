<?php
// S-018: Admin Panel Section for Twilio
$admin_option_defs["Administration"]["twilio_settings"] = array(
    "Administration",
    "LBL_TWILIO_SETTINGS",
    "LBL_TWILIO_SETTINGS_DESC",
    "./index.php?module=outr_twilio_settings&action=index",
);

$admin_option_defs["Administration"]["twilio_calls"] = array(
    "outr_twilio_calls",
    "LBL_TWILIO_CALLS",
    "LBL_TWILIO_CALLS_DESC",
    "./index.php?module=outr_twilio_calls&action=index",
);

$admin_option_defs["Administration"]["twilio_voicemail"] = array(
    "outr_twilio_voicemail",
    "LBL_TWILIO_VOICEMAIL",
    "LBL_TWILIO_VOICEMAIL_DESC",
    "./index.php?module=outr_twilio_voicemail&action=index",
);

$admin_option_defs["Administration"]["twilio_opted_out"] = array(
    "outr_twilio_opted_out",
    "LBL_TWILIO_OPTED_OUT",
    "LBL_TWILIO_OPTED_OUT_DESC",
    "./index.php?module=outr_twilio_opted_out&action=index",
);

$admin_option_defs["Administration"]["twilio_logger"] = array(
    "outr_twilio_logger",
    "LBL_TWILIO_LOGGER",
    "LBL_TWILIO_LOGGER_DESC",
    "./index.php?module=outr_twilio_logger&action=index",
);

$admin_group_header[] = array(
    "LBL_TWILIO_SECTION_HEADER",
    "",
    false,
    $admin_option_defs,
    "LBL_TWILIO_SECTION_DESC",
);
