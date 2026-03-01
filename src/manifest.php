<?php
// SuiteCRM 8.8.0 Module Manifest

$manifest = array(
    'name' => 'SweetDialer',
    'label' => 'SweetDialer',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM',
    'version' => '1.0.0',
    'author' => 'Wembassy',
    'is_uninstallable' => true,
    'type' => 'module',
    'acceptable_sugar_versions' => array(
        'exact_matches' => array('8.8.0', '8.8.1', '8.8.2', '8.8.3', '8.8.4'),
        'regex_matches' => array('8\.[0-9]+\.[0-9]+', '7\.[0-9]+\.[0-9]+'),
    ),
    'acceptable_sugar_flavors' => array('CE'),
);

$installdefs = array(
    'id' => 'SweetDialer',
    'beans' => array(),
    'copy' => array(
        array('from' => '<basedir>/custom', 'to' => 'custom'),
        array('from' => '<basedir>/modules', 'to' => 'modules'),
    ),
);

$config = array(
    'name' => 'SweetDialer',
    'version' => '1.0.0',
);
