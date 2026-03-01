<?php
/**
 * SweetDialer Module Manifest
 */

$manifest = array(
    'name' => 'SweetDialer',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM',
    'version' => '1.0.0',
    'author' => 'Wembassy',
    'type' => 'module',
    'is_uninstallable' => true,
    'acceptable_sugar_flavors' => array('CE'),
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(),
        'regex_matches' => array('6\\.5\\.[0-9]+', '8\\.[0-9]+\\.[0-9]+'),
    ),
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
        array(
            'from' => '<basedir>/config',
            'to' => 'config',
        ),
    ),
);
