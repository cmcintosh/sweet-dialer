<?php
/**
 * Sweet-Dialer CTI Calls Module
 *
 * @package SweetDialer
 */

$dictionary['SweetDialerCalls'] = array(
    'table' => 'twiliodialer_calls',
    'audited' => true,
    'activity_enabled' => true,
    'fields' => array(
        'id' => array(
            'name' => 'id',
            'vname' => 'LBL_ID',
            'type' => 'id',
            'required' => true,
            'reportable' => true,
        ),
        'name' => array(
            'name' => 'name',
            'vname' => 'LBL_NAME',
            'type' => 'name',
            'dbType' => 'varchar',
            'len' => 255,
            'required' => false,
        ),
        'call_sid' => array(
            'name' => 'call_sid',
            'vname' => 'LBL_CALL_SID',
            'type' => 'varchar',
            'len' => 255,
            'required' => true,
        ),
        // S-015: Polymorphic relationship fields
        'parent_type' => array(
            'name' => 'parent_type',
            'vname' => 'LBL_PARENT_TYPE',
            'type' => 'varchar',
            'len' => 100,
            'comment' => 'The module type of the parent record',
        ),
        'parent_id' => array(
            'name' => 'parent_id',
            'vname' => 'LBL_PARENT_ID',
            'type' => 'id',
            'comment' => 'The ID of the parent record',
        ),
        'parent_name' => array(
            'name' => 'parent_name',
            'vname' => 'LBL_PARENT',
            'type' => 'parent',
            'dbType' => 'varchar',
            'len' => 255,
            'parent_type' => 'record_type_display',
            'parent_id' => 'parent_id',
            'required' => false,
            'source' => 'non-db',
            'options' => 'parent_type_display',
        ),
        // S-014: CTI Calls -> CTI Settings relationship
        'cti_setting_id' => array(
            'name' => 'cti_setting_id',
            'vname' => 'LBL_CTI_SETTING_ID',
            'type' => 'id',
            'comment' => 'Link to CTI Setting',
        ),
        'cti_settings_name' => array(
            'name' => 'cti_settings_name',
            'vname' => 'LBL_CTI_SETTINGS_NAME',
            'type' => 'relate',
            'rname' => 'name',
            'id_name' => 'cti_setting_id',
            'module' => 'SweetDialerCTI',
            'source' => 'non-db',
        ),
        // S-016: CTI Calls -> Accounts relationship
        'account_id' => array(
            'name' => 'account_id',
            'vname' => 'LBL_ACCOUNT_ID',
            'type' => 'id',
            'comment' => 'Link to Account',
        ),
        'account_name' => array(
            'name' => 'account_name',
            'vname' => 'LBL_ACCOUNT_NAME',
            'type' => 'relate',
            'rname' => 'name',
            'id_name' => 'account_id',
            'module' => 'Accounts',
            'source' => 'non-db',
        ),
        'direction' => array(
            'name' => 'direction',
            'vname' => 'LBL_DIRECTION',
            'type' => 'enum',
            'options' => 'call_direction_list',
            'len' => 100,
            'required' => true,
            'default' => 'outbound',
        ),
        'status' => array(
            'name' => 'status',
            'vname' => 'LBL_STATUS',
            'type' => 'enum',
            'options' => 'call_status_list',
            'len' => 100,
        ),
        'duration' => array(
            'name' => 'duration',
            'vname' => 'LBL_DURATION',
            'type' => 'int',
            'len' => 11,
        ),
        'duration_minutes' => array(
            'name' => 'duration_minutes',
            'vname' => 'LBL_DURATION_MINUTES',
            'type' => 'int',
            'len' => 11,
        ),
        'from_number' => array(
            'name' => 'from_number',
            'vname' => 'LBL_FROM_NUMBER',
            'type' => 'phone',
            'len' => 50,
        ),
        'to_number' => array(
            'name' => 'to_number',
            'vname' => 'LBL_TO_NUMBER',
            'type' => 'phone',
            'len' => 50,
        ),
        'recording_url' => array(
            'name' => 'recording_url',
            'vname' => 'LBL_RECORDING_URL',
            'type' => 'url',
            'len' => 255,
        ),
        'notes' => array(
            'name' => 'notes',
            'vname' => 'LBL_NOTES',
            'type' => 'text',
        ),
        // Standard fields
        'date_entered' => array(
            'name' => 'date_entered',
            'vname' => 'LBL_DATE_ENTERED',
            'type' => 'datetime',
            'required' => true,
        ),
        'date_modified' => array(
            'name' => 'date_modified',
            'vname' => 'LBL_DATE_MODIFIED',
            'type' => 'datetime',
            'required' => true,
        ),
        'created_by' => array(
            'name' => 'created_by',
            'type' => 'id',
        ),
        'modified_user_id' => array(
            'name' => 'modified_user_id',
            'type' => 'id',
        ),
        'deleted' => array(
            'name' => 'deleted',
            'type' => 'bool',
            'default' => '0',
        ),
    ),
    'indices' => array(
        'id' => array(
            'name' => 'idx_sweetdialercalls_pk',
            'type' => 'primary',
            'fields' => array('id'),
        ),
        'call_sid' => array(
            'name' => 'idx_sweetdialercalls_sid',
            'type' => 'index',
            'fields' => array('call_sid'),
        ),
        'parent' => array(
            'name' => 'idx_sweetdialercalls_parent',
            'type' => 'index',
            'fields' => array('parent_type', 'parent_id'),
        ),
        'account' => array(
            'name' => 'idx_sweetdialercalls_account',
            'type' => 'index',
            'fields' => array('account_id'),
        ),
        'cti_setting' => array(
            'name' => 'idx_sweetdialercalls_cti',
            'type' => 'index',
            'fields' => array('cti_setting_id'),
        ),
    ),
    'optimistic_locking' => true,
    'relationships' => array(),
);

if (!class_exists('VardefManager')) {
    require_once('include/SugarObjects/VardefManager.php');
}
VardefManager::createVardef('SweetDialerCalls', 'SweetDialerCalls', array('basic'));
