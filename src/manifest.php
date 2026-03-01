<?php
$manifest = array(
    'name' => 'SweetDialer',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM',
    'version' => '1.0.0',
    'author' => 'Wembassy',
    'type' => 'module',
    'is_uninstallable' => true,
    'published_date' => '2026-03-01',
    'readme' => '',
    'acceptable_sugar_versions' => array(
        'regex_matches' => array('8\.8\.\d+', '8\.\d+\.\d+', '7\.\d+\.\d+'),
    ),
    'acceptable_sugar_flavors' => array('CE', 'ENT', 'ULT'),
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
    ),
    'copy' => array(
        array('from' => '<basedir>/custom', 'to' => 'custom'),
        array('from' => '<basedir>/modules', 'to' => 'modules'),
    ),
    'language' => array(
        array('from' => '<basedir>/custom', 'to' => 'custom'),
    ),
    'entrypoints' => array(
        array('from' => '<basedir>/custom/entrypoints/voiceWebhook.php', 'to' => 'custom/entrypoints/voiceWebhook.php'),
    ),
);
