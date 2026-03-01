<?php
// SuiteCRM 8.8.0 Compatible Module Manifest
$manifest = array(
    'name' => 'SweetDialer',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM',
    'version' => '1.0.0',
    'author' => 'Wembassy',
    'type' => 'module',
    'is_uninstallable' => true,
    'acceptable_sugar_versions' => array(
        'regex_matches' => array(
            '8\.8\.\d+',
            '8\.\d+\.\d+',
            '7\.\d+\.\d+',
        ),
    ),
    'acceptable_sugar_flavors' => array('CE', 'PRO', 'ENT', 'ULT'),
);

$installdefs = array(
    'id' => 'SweetDialer',
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
);
