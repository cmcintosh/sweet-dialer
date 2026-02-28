<?php
/**
 * S-019: Register "TWILIO SETTINGS" tab in top navigation
 */

$extension_navigation['Twilio'] = array(
    'title' => 'LBL_TWILIO_SETTINGS',
    'order' => 120,
    'module' => 'SweetDialerCTI',
    'action' => 'index',
    'icon' => 'fa-phone',
    'label' => 'LBL_TWILIO_SETTINGS',
    'submenu' => array(
        array(
            'key' => 'cti_settings',
            'module' => 'SweetDialerCTI',
            'action' => 'ListView',
            'label' => 'LBL_CTI_SETTINGS_TITLE',
        ),
        array(
            'key' => 'voicemail',
            'module' => 'SweetDialerVoicemail',
            'action' => 'ListView',
            'label' => 'LBL_VOICEMAILS',
        ),
        array(
            'key' => 'call_tracker',
            'module' => 'SweetDialerCalls',
            'action' => 'ListView',
            'label' => 'LBL_CALL_TRACKER',
        ),
    ),
);
