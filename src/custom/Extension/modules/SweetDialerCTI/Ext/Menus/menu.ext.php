<?php
/**
 * S-017: Twilio Settings Sidebar Menu - CTI Settings Module
 */

global $mod_strings;

// Main menu section
$module_menu = array(
    // Settings section
    array('Create CTI Settings', 'CTI', 'LBL_CTI_CREATE', 'SweetDialerCTI', 'EditView', '', false, 'twilio-settings-module', 'sidebar'),
    array('See CTI Settings', 'CTI', 'LBL_CTI_VIEW', 'SweetDialerCTI', 'ListView', '', false, 'twilio-settings-module', 'sidebar'),
    array('Common Settings', 'Settings', 'LBL_TWILIO_COMMON_SETTINGS', 'SweetDialerCTI', 'index', '', false, 'twilio-settings-module', 'sidebar'),
);
