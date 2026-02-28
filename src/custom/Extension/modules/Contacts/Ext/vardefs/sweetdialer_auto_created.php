<?php
/**
 * Sweet-Dialer Auto-Created Fields for Contacts (S-060)
 *
 * Adds auto_created and auto_created_date fields to Contacts module
 * Flags contacts auto-created by SweetDialer during outbound calls
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

// Prevent direct access
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

// Auto-created flag field
\$dictionary['Contact']['fields']['auto_created'] = array(
    'name' => 'auto_created',
    'vname' => 'LBL_AUTO_CREATED',
    'type' => 'bool',
    'default' => '0',
    'comment' => 'Flag indicating contact was auto-created by SweetDialer',
    'audited' => true,
    'massupdate' => false,
    'duplicate_merge' => 'disabled',
    'reportable' => true,
    'importable' => false,
);

// Auto-created date field
\$dictionary['Contact']['fields']['auto_created_date'] = array(
    'name' => 'auto_created_date',
    'vname' => 'LBL_AUTO_CREATED_DATE',
    'type' => 'datetime',
    'comment' => 'Date when contact was auto-created by SweetDialer',
    'audited' => true,
    'massupdate' => false,
    'duplicate_merge' => 'disabled',
    'reportable' => true,
    'importable' => false,
);
