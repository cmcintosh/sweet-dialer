<?php
/**
 * SweetDialer Manifest - SuiteCRM 8.8.0 Compatible
 */

$manifest = array(
    'name' => 'SweetDialer',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM 8.x',
    'version' => '1.0.0',
    'author' => 'Wembassy',
    'is_uninstallable' => true,
    'type' => 'module',
    'published_date' => '2026-03-01',
    'acceptable_sugar_versions' => array(
        'regex_matches' => array(
            '8\.8\.\d+',
            '8\.\d+\.\d+',
            '7\.\d+\.\d+',
        ),
    ),
    'acceptable_sugar_flavors' => array(
        'CE',
        'ENT',
        'ULT',
    ),
);

$installdefs = array(
    'id' => 'SweetDialer',
    'module' => 'SweetDialer',
    'built_in_version' => '1.0.0',
    'copy' => array(
        array('from' => '<basedir>/custom', 'to' => 'custom'),
        array('from' => '<basedir>/modules', 'to' => 'modules'),
        array('from' => '<basedir>/config', 'to' => 'config'),
    ),
);
