<?php
/**
 * S-018: Add "Twilio Settings" section to Administration panel
 *
 * @package SweetDialer
 */

// Define the Twilio Settings section
$admin_group_header['twilio_settings'] = array(
    'LBL_TWILIO_SETTINGS_ADMIN',
    '',
    false,
    array(
        // Settings submenu
        array(
            'LBL_CTI_SETTINGS_TITLE',
            'index.php?module=SweetDialerCTI',
            'LBL_CTI_SETTINGS_TITLE_DESC',
        ),
        array(
            'LBL_TWILIO_COMMON_SETTINGS',
            'index.php?module=SweetDialerCTI',
            'LBL_TWILIO_COMMON_SETTINGS_DESC',
        ),
        array(
            'LBL_TWILIO_SYSTEM_HEALTH',
            'index.php?module=SweetDialerCTI',
            'LBL_TWILIO_SYSTEM_HEALTH_DESC',
        ),
        // Call Management submenu
        array(
            'LBL_CALL_TRACKER',
            'index.php?module=SweetDialerTracker',
            'LBL_CALL_TRACKER_DESC',
        ),
        array(
            'LBL_VOICEMAIL_VIEW',
            'index.php?module=SweetDialerVoicemail',
            'LBL_VOICEMAIL_VIEW_DESC',
        ),
        // Configuration submenu
        array(
            'LBL_TWILIO_PHONE_NUMBERS',
            'index.php?module=SweetDialerPhoneNumbers',
            'LBL_TWILIO_PHONE_NUMBERS_DESC',
        ),
        array(
            'LBL_TWILIO_RINGTONES',
            'index.php?module=SweetDialerRingtones',
            'LBL_TWILIO_RINGTONES_DESC',
        ),
        // Logs submenu
        array(
            'LBL_TWILIO_ERROR_LOGS',
            'index.php?module=SweetDialerLogger',
            'LBL_TWILIO_ERROR_LOGS_DESC',
        ),
    ),
);
