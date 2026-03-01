<?php
/**
 * Sweet-Dialer Detail View Layout Extension (S-060)
 *
 * Adds auto_created flag to Contact detail view
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

// Prevent direct access
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

// Add auto_created field to detail view panels
$viewdefs['Contacts']['DetailView']['panels']['LBL_PANEL_ADVANCED']['newRow1'] = array(
    0 => array(
        'name' => 'auto_created',
        'label' => 'LBL_AUTO_CREATED',
    ),
    1 => array(
        'name' => 'auto_created_date',
        'label' => 'LBL_AUTO_CREATED_DATE',
    ),
);

// Alternative approach - add to standard panel
$viewdefs['Contacts']['DetailView']['panels']['lbl_contact_information']['newRow2'] = array(
    0 => array(
        'name' => 'auto_created',
        'label' => 'LBL_AUTO_CREATED',
        'type' => 'bool',
    ),
    1 => array(
        'name' => 'auto_created_date',
        'label' => 'LBL_AUTO_CREATED_DATE',
        'type' => 'datetime',
    ),
);
