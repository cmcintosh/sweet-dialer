<?php
// Sweet Dialer v2.0.0 - Twilio AI-Powered Dialer for SuiteCRM
// Target: SuiteCRM 8.8.0+

\$manifest = array(
    "name" => "Twilio AI-Powered Dialer for SuiteCRM",
    "description" => "Complete CTI integration - Browser-based calling, voicemail, call tracking",
    "version" => "2.0.0",
    "author" => "Wembassy",
    "type" => "module",
    "is_uninstallable" => true,
    "published_date" => "2026-02-28",
    "acceptable_sugar_versions" => array(
        "regex_matches" => array("8\\.8\\.[0-9]+"),
    ),
    "acceptable_sugar_flavors" => array("CE", "PRO", "ENT"),
);

\$installdefs = array(
    "id" => "TwilioDialer_200",
    "beans" => array(
        array("module" => "outr_twilio_settings", "class" => "outr_twilio_settings", "path" => "modules/outr_twilio_settings/outr_twilio_settings.php", "tab" => true),
        array("module" => "outr_twilio_calls", "class" => "outr_twilio_calls", "path" => "modules/outr_twilio_calls/outr_twilio_calls.php", "tab" => true),
        array("module" => "outr_twilio_voicemail", "class" => "outr_twilio_voicemail", "path" => "modules/outr_twilio_voicemail/outr_twilio_voicemail.php", "tab" => true),
        array("module" => "outr_twilio_voicemail_recordings", "class" => "outr_twilio_voicemail_recordings", "path" => "modules/outr_twilio_voicemail_recordings/outr_twilio_voicemail_recordings.php", "tab" => false),
        array("module" => "outr_twilio_opted_out", "class" => "outr_twilio_opted_out", "path" => "modules/outr_twilio_opted_out/outr_twilio_opted_out.php", "tab" => true),
        array("module" => "outr_twilio_dual_ringtone", "class" => "outr_twilio_dual_ringtone", "path" => "modules/outr_twilio_dual_ringtone/outr_twilio_dual_ringtone.php", "tab" => true),
        array("module" => "outr_twilio_hold_ringtone", "class" => "outr_twilio_hold_ringtone", "path" => "modules/outr_twilio_hold_ringtone/outr_twilio_hold_ringtone.php", "tab" => true),
        array("module" => "outr_twilio_common_settings", "class" => "outr_twilio_common_settings", "path" => "modules/outr_twilio_common_settings/outr_twilio_common_settings.php", "tab" => true),
        array("module" => "outr_twilio_error_logs", "class" => "outr_twilio_error_logs", "path" => "modules/outr_twilio_error_logs/outr_twilio_error_logs.php", "tab" => true),
        array("module" => "outr_twilio_logger", "class" => "outr_twilio_logger", "path" => "modules/outr_twilio_logger/outr_twilio_logger.php", "tab" => true),
        array("module" => "outr_twilio_phone_numbers", "class" => "outr_twilio_phone_numbers", "path" => "modules/outr_twilio_phone_numbers/outr_twilio_phone_numbers.php", "tab" => true),
    ),
    "copy" => array(
        array("from" => "<basepath>/outr_twilio_settings", "to" => "modules/outr_twilio_settings"),
        array("from" => "<basepath>/outr_twilio_calls", "to" => "modules/outr_twilio_calls"),
        array("from" => "<basepath>/outr_twilio_voicemail", "to" => "modules/outr_twilio_voicemail"),
        array("from" => "<basepath>/outr_twilio_voicemail_recordings", "to" => "modules/outr_twilio_voicemail_recordings"),
        array("from" => "<basepath>/outr_twilio_opted_out", "to" => "modules/outr_twilio_opted_out"),
        array("from" => "<basepath>/outr_twilio_dual_ringtone", "to" => "modules/outr_twilio_dual_ringtone"),
        array("from" => "<basepath>/outr_twilio_hold_ringtone", "to" => "modules/outr_twilio_hold_ringtone"),
        array("from" => "<basepath>/outr_twilio_common_settings", "to" => "modules/outr_twilio_common_settings"),
        array("from" => "<basepath>/outr_twilio_error_logs", "to" => "modules/outr_twilio_error_logs"),
        array("from" => "<basepath>/outr_twilio_logger", "to" => "modules/outr_twilio_logger"),
        array("from" => "<basepath>/outr_twilio_phone_numbers", "to" => "modules/outr_twilio_phone_numbers"),
        array("from" => "<basepath>/custom", "to" => "custom"),
        array("from" => "<basepath>/upload", "to" => "upload"),
    ),
    "post_execute" => array(
        "<basepath>/ModuleInstall/install.php",
    ),
    "post_uninstall" => array(
        "<basepath>/ModuleInstall/uninstall.php",
    ),
);
