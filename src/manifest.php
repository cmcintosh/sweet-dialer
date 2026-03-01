<?php
// SuiteCRM 8.8.0 Compatible Module Manifest
$manifest = [
    'name' => 'SweetDialer',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM',
    'version' => '1.0.0',
    'author' => 'Wembassy',
    'type' => 'module',
    'is_uninstallable' => true,
    'built_in_version' => '7.10.0',
    'acceptable_sugar_versions' => [
        'exact_matches' => [],
        'regex_matches' => [
            '8\\.8\\..*',
            '8\\..*',
            '7\\..*',
        ],
    ],
    'acceptable_sugar_flavors' => ['CE', 'PRO', 'ENT', 'ULT'],
];

$installdefs = [
    'id' => 'SweetDialer',
    'copy' => [
        [
            'from' => '<basedir>/custom',
            'to' => 'custom',
        ],
        [
            'from' => '<basedir>/modules',
            'to' => 'modules',
        ],
    ],
];
