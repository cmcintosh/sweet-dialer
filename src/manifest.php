<?php
/**
 * Sweet-Dialer Package Manifest
 *
 * SuiteCRM Twilio AI-Powered Dialer Module
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

$manifest = array(
    'name' => 'Sweet-Dialer',
    'description' => 'Twilio AI-Powered Dialer for SuiteCRM - Click-to-call, call tracking, voicemail, and more',
    'version' => '1.0.0',
    'author' => 'Wembassy',
    'is_uninstallable' => true,
    'type' => 'module',
    'acceptable_sugar_versions' => array(
        'regex_matches' => array(
            '7\.\d+\.\d+',
            '8\.\d+\.\d+',
        ),
    ),
    'acceptable_sugar_flavors' => array(
        'CE',
        'PRO',
        'ENT',
        'CORP',
    ),
    'dependencies' => array(
        array(
            'id_name' => 'suitecrm_version',
            'version' => '7.0.0'
        ),
    ),
);

$installdefs = array(
    'id' => 'SweetDialer',
    'copy' => array(
        array(
            'from' => '<basepath>/custom',
            'to' => 'custom',
        ),
        array(
            'from' => '<basepath>/modules',
            'to' => 'modules',
        ),
    ),
    'pre_install' => array(
        '<basepath>/ModuleInstall/pre_install.php',
    ),
    'post_install' => array(
        '<basepath>/ModuleInstall/install.php',
    ),
    'post_uninstall' => array(
        '<basepath>/ModuleInstall/uninstall.php',
    ),
    'beans' => array(),
    'language' => array(),
);
