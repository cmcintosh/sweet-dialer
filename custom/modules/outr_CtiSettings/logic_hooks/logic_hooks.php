<?php
/**
 * logic_hooks.php
 *
 * Sweet-Dialer CTI Settings Logic Hooks Registration
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

$hook_version = 1;
$hook_array = [
    // before_save hook for credential validation and encryption
    [
        1, // Processing order
        'Validate and encrypt Twilio credentials on save',
        'custom/modules/outr_CtiSettings/logic_hooks/CtiSettingsHooks.php',
        'CtiSettingsHooks',
        'beforeSave',
    ],
    // after_retrieve hook for decryption and masking
    [
        1,
        'Decrypt and mask sensitive fields on retrieve',
        'custom/modules/outr_CtiSettings/logic_hooks/CtiSettingsHooks.php',
        'CtiSettingsHooks',
        'afterRetrieve',
    ],
];
