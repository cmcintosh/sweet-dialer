<?php
// SuiteCRM 8.8.0 Module Manifest
// This manifest follows the SuiteCRM 8 Module Loader format

$manifest = array(
    'name' => 'SweetDialer',
    'label' => 'LBL_SWEETDIALER', 
    'description' => 'LBL_SWEETDIALER_DESCRIPTION',
    'author' => 'Wembassy',
    'version' => '1.0.0',
    'is_uninstallable' => true,
    'type' => 'module',
    'published_date' => '2026-03-01',
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(),
        'regex_matches' => array(
            '8\.\.',
            '7\.\.',
        ),
    ),
    'acceptable_sugar_flavors' => array(
        'CE',
        'ENT',
        'ULT'
    ),
);

$installdefs = array(
    'id' => 'SweetDialer',
    'beans' => array(
        array(
            'module' => 'SweetDialerCalls',
            'class' => 'SweetDialerCalls',
            'path' => 'modules/SweetDialerCalls/SweetDialerCalls.php',
            'tab' => true,
        ),
        array(
            'module' => 'SweetDialerTracker',
            'class' => 'SweetDialerTracker',
            'path' => 'modules/SweetDialerTracker/SweetDialerTracker.php',
            'tab' => false,
        ),
        array(
            'module' => 'SweetDialerVoicemail',
            'class' => 'SweetDialerVoicemail',
            'path' => 'modules/SweetDialerVoicemail/SweetDialerVoicemail.php',
            'tab' => true,
        ),
        array(
            'module' => 'SweetDialerRingtones',
            'class' => 'SweetDialerRingtones',
            'path' => 'modules/SweetDialerRingtones/SweetDialerRingtones.php',
            'tab' => true,
        ),
        array(
            'module' => 'SweetDialerPhoneNumbers',
            'class' => 'SweetDialerPhoneNumbers', 
            'path' => 'modules/SweetDialerPhoneNumbers/SweetDialerPhoneNumbers.php',
            'tab' => true,
        ),
        array(
            'module' => 'SweetDialerCTI',
            'class' => 'SweetDialerCTI',
            'path' => 'modules/SweetDialerCTI/SweetDialerCTI.php',
            'tab' => true,
        ),
        array(
            'module' => 'SweetDialerLogger',
            'class' => 'SweetDialerLogger',
            'path' => 'modules/SweetDialerLogger/SweetDialerLogger.php',
            'tab' => false,
        ),
        array(
            'module' => 'SweetDialerOptOut',
            'class' => 'SweetDialerOptOut',
            'path' => 'modules/SweetDialerOptOut/SweetDialerOptOut.php',
            'tab' => true,
        ),
    ),
    'image_dir' => '<basedir>/themes',
    'copy' => array(
        array(
            'from' => '<basedir>/custom',
            'to' => 'custom',
        ),
        array(
            'from' => '<basedir>/modules',
            'to' => 'modules',
        ),
    ),
    'language' => array(
        array(
            'from' => '<basedir>/custom/Extension/application/Ext/Language',
            'to' => 'custom/Extension/application/Ext/Language',
            'name' => 'en_us',
        ),
    ),
    'administration' => array(
        array(
            'from' => '<basedir>/custom/Extension/modules/Administration/Ext/Language/en_us.twilio_admin.php',
            'to' => 'custom/Extension/modules/Administration/Ext/Language/en_us.twilio_admin.php',
        ),
    ),
);

$upgrade_manifest = array(
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(),
        'regex_matches' => array(
            '8\.\.',
            '7\.\.',
        ),
    ),
);

// SuiteCRM 8.x config array
$config = array (
    'name' => 'SweetDialer',
    'version' => '1.0.0',
    'default' => true,
    'enabled' => true,
    'viewdefs' => array(
        'include' => array(
            'twilio_dialer' => array(
                'icon_url' => '',
            ),
        ),
    ),
    'installdefs' => array(
        'id' => 'SweetDialer',
    ),
);
