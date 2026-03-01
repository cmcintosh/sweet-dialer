<?php
/**
 * Sweet-Dialer Package Manifest - SuiteCRM 8.x Compatible
 *
 * SuiteCRM Twilio AI-Powered Dialer Module
 * Safe install with error handling and rollback
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

\$manifest = array(
    'name' => 'Sweet-Dialer',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM 8.x - Click-to-call, call tracking, voicemail, and more. Now with SuiteCRM 8.x compatibility.',
    'version' => '1.0.0',
    'author' => 'Wembassy',
    'is_uninstallable' => true,
    'type' => 'module',
    'acceptable_sugar_versions' => array(
        'regex_matches' => array(
            '8\.\d+\.\d+',  // SuiteCRM 8.x
            '7\.\d+\.\d+',  // SuiteCRM 7.x (legacy)
        ),
    ),
    'acceptable_sugar_flavors' => array(
        'CE',
        'PRO',
        'ENT',
        'CORP',
        'ULT',
    ),
    'dependencies' => array(
        array(
            'id_name' => 'suitecrm_version',
            'version' => '7.10.0',
        ),
    ),
    'copy_files' => array(
        'from_dir' => '',
        'to_dir' => '',
        'force_copy' => array(),
    ),
    'post_execute' => array(
        0 => '<basepath>/ModuleInstall/install.php',
    ),
    'pre_execute' => array(
        0 => '<basepath>/ModuleInstall/pre_install.php',
    ),
    'post_uninstall' => array(
        0 => '<basepath>/ModuleInstall/uninstall.php',
    ),
);
