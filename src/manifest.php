<?php
/**
 * SweetDialer Module Manifest
 * SuiteCRM 8.8.x Compatible
 */

$manifest = array(
    'name' => 'SweetDialer',
    'label' => 'LBL_SWEETDIALER',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM 8.x',
    'version' => '1.0.0',
    'author' => 'Wembassy',
    'type' => 'module',
    'is_uninstallable' => true,
    'published_date' => '2026-03-01',
    'acceptable_sugar_flavors' => array('CE'),
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(),
        'regex_matches' => array(
            '8\.8\.[0-9]+',
            '8\.[0-9]+\.[0-9]+',
            '7\.[0-9]+\.[0-9]+',
            '6\.5\.[0-9]+',
        ),
    ),
);

$installdefs = array(
    'id' => 'SweetDialer',
    'copy' => array(
        array(
            'from' => '<basepath>/custom/',
            'to' => 'custom/',
        ),
        array(
            'from' => '<basepath>/modules/',
            'to' => 'modules/',
        ),
    ),
    'entrypoints' => array(
        array('from' => '<basepath>/custom/entrypoints/voiceWebhook.php', 'to' => 'custom/entrypoints/voiceWebhook.php'),
        array('from' => '<basepath>/custom/entrypoints/statusCallback.php', 'to' => 'custom/entrypoints/statusCallback.php'),
        array('from' => '<basepath>/custom/entrypoints/recordingCallback.php', 'to' => 'custom/entrypoints/recordingCallback.php'),
        array('from' => '<basepath>/custom/entrypoints/transferWarm.php', 'to' => 'custom/entrypoints/transferWarm.php'),
        array('from' => '<basepath>/custom/entrypoints/transferCold.php', 'to' => 'custom/entrypoints/transferCold.php'),
    ),
);
