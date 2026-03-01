<?php
/**
 * Sweet-Dialer Voicemail Module
 *
 * @package SweetDialer
 */

$dictionary['SweetDialerVoicemail'] = array(
    'table' => 'twiliodialer_voicemail',
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
        'voicemail_sid' => array(
            'name' => 'voicemail_sid',
            'vname' => 'LBL_VOICEMAIL_SID',
            'type' => 'varchar',
            'len' => 255,
            'required' => true,
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
        'transcription' => array(
            'name' => 'transcription',
            'vname' => 'LBL_TRANSCRIPTION',
            'type' => 'text',
        ),
        'duration' => array(
            'name' => 'duration',
            'vname' => 'LBL_DURATION',
            'type' => 'int',
            'len' => 11,
        ),
        'listened' => array(
            'name' => 'listened',
            'vname' => 'LBL_LISTENED',
            'type' => 'bool',
            'default' => '0',
        ),
        'cti_setting_id' => array(
            'name' => 'cti_setting_id',
            'vname' => 'LBL_CTI_SETTING_ID',
            'type' => 'id',
        ),
        // Polymorphic related record
        'parent_type' => array(
            'name' => 'parent_type',
            'vname' => 'LBL_PARENT_TYPE',
            'type' => 'varchar',
            'len' => 100,
        ),
        'parent_id' => array(
            'name' => 'parent_id',
            'vname' => 'LBL_PARENT_ID',
            'type' => 'id',
        ),
        'parent_name' => array(
            'name' => 'parent_name',
            'vname' => 'LBL_PARENT',
            'type' => 'parent',
            'dbType' => 'varchar',
            'parent_type' => 'parent_type',
            'parent_id' => 'parent_id',
            'source' => 'non-db',
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
            'name' => 'idx_sweetdialervoicemail_pk',
            'type' => 'primary',
            'fields' => array('id'),
        ),
        'voicemail_sid' => array(
            'name' => 'idx_sweetdialervoicemail_sid',
            'type' => 'index',
            'fields' => array('voicemail_sid'),
        ),
        'from_number' => array(
            'name' => 'idx_sweetdialervoicemail_from',
            'type' => 'index',
            'fields' => array('from_number'),
        ),
        'listened' => array(
            'name' => 'idx_sweetdialervoicemail_listened',
            'type' => 'index',
            'fields' => array('listened'),
        ),
    ),
    'optimistic_locking' => true,
    'relationships' => array(),
);

if (!class_exists('VardefManager')) {
    require_once('include/SugarObjects/VardefManager.php');
}
VardefManager::createVardef('SweetDialerVoicemail', 'SweetDialerVoicemail', array('basic'));
