<?php
// S-003: Uninstall script
function pre_uninstall() {
    // Return true to allow uninstall
    return true;
}

function post_uninstall() {
    $db = $GLOBALS["db"] ?? null;
    if (!$db) {
        return false;
    }
    
    // Drop tables
    $tables = array(
        "outr_twilio_settings",
        "outr_twilio_calls",
        "outr_twilio_voicemail",
        "outr_twilio_voicemail_recordings",
        "outr_twilio_opted_out",
        "outr_twilio_dual_ringtone",
        "outr_twilio_hold_ringtone",
        "outr_twilio_common_settings",
        "outr_twilio_error_logs",
        "outr_twilio_logger",
        "outr_twilio_phone_numbers"
    );
    
    foreach ($tables as $table) {
        $db->query("DROP TABLE IF EXISTS " . $db->quoteIdentifier($table));
    }
    
    // Remove schedulers
    $db->query("DELETE FROM schedulers WHERE name LIKE "Twilio%"");
    
    // Clean up upload directory
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "upload" . DIRECTORY_SEPARATOR;
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . "*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($uploadDir);
    }
    
    return true;
}
