<?php
/**
 * Menu for SweetDialerCTI (S-009)
 */

global $mod_strings;
$module_menu = array(
    array(
        'index.php?module=outr_TwilioSettings&action=EditView&return_module=outr_TwilioSettings&return_action=DetailView',
        $mod_strings['LNK_NEW_RECORD'],
        'Create',
        'outr_TwilioSettings',
    ),
    array(
        'index.php?module=outr_TwilioSettings&action=index',
        $mod_strings['LNK_LIST'],
        'List',
        'outr_TwilioSettings',
    ),
    array(
        'index.php?module=Import&action=Step1&import_module=outr_TwilioSettings&return_module=outr_TwilioSettings&return_action=index',
        $mod_strings['LBL_IMPORT'],
        'Import',
        'outr_TwilioSettings',
    ),
);
