<?php
/**
 * Sweet-Dialer CTI Settings Module
 *
 * @package SweetDialer
 * @author Wembassy
 * @license GNU AGPLv3
 */

$dictionary['SweetDialerCTI'] = array(
    'table' => 'twiliodialer_cti_settings',
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
            'required' => true,
        ),
        'account_sid' => array(
            'name' => 'account_sid',
            'vname' => 'LBL_ACCOUNT_SID',
            'type' => 'varchar',
            'len' => 255,
            'required' => true,
        ),
        'auth_token' => array(
            'name' => 'auth_token',
            'vname' => 'LBL_AUTH_TOKEN',
            'type' => 'text',
            'reportable' => false,
        ),
        'api_key' => array(
            'name' => 'api_key',
            'vname' => 'LBL_API_KEY',
            'type' => 'text',
            'reportable' => false,
        ),
        'api_key_secret' => array(
            'name' => 'api_key_secret',
            'vname' => 'LBL_API_KEY_SECRET',
            'type' => 'text',
            'reportable' => false,
        ),
        'twilio_phone_number' => array(
            'name' => 'twilio_phone_number',
            'vname' => 'LBL_TWILIO_PHONE_NUMBER',
            'type' => 'phone',
            'len' => 50,
        ),
        'twilio_app_sid' => array(
            'name' => 'twilio_app_sid',
            'vname' => 'LBL_TWILIO_APP_SID',
            'type' => 'varchar',
            'len' => 255,
        ),
        'caller_name' => array(
            'name' => 'caller_name',
            'vname' => 'LBL_CALLER_NAME',
            'type' => 'varchar',
            'len' => 255,
        ),
        'incoming_call_type' => array(
            'name' => 'incoming_call_type',
            'vname' => 'LBL_INCOMING_CALL_TYPE',
            'type' => 'enum',
            'options' => 'twilio_incoming_call_type_list',
            'len' => 100,
            'default' => 'user',
        ),
        'recording_enabled' => array(
            'name' => 'recording_enabled',
            'vname' => 'LBL_RECORDING_ENABLED',
            'type' => 'bool',
            'default' => '1',
        ),
        'ai_enabled' => array(
            'name' => 'ai_enabled',
            'vname' => 'LBL_AI_ENABLED',
            'type' => 'bool',
            'default' => '0',
        ),
        'status' => array(
            'name' => 'status',
            'vname' => 'LBL_STATUS',
            'type' => 'enum',
            'options' => 'twilio_cti_status_list',
            'len' => 100,
            'default' => 'Active',
        ),
        'domain_name' => array(
            'name' => 'domain_name',
            'vname' => 'LBL_DOMAIN_NAME',
            'type' => 'varchar',
            'len' => 255,
        ),
        // S-012: CTI Settings -> Users relationship
        'outbound_inbound_agent' => array(
            'name' => 'outbound_inbound_agent',
            'vname' => 'LBL_OUTBOUND_INBOUND_AGENT',
            'type' => 'relate',
            'rname' => 'user_name',
            'id_name' => 'outbound_inbound_agent_id',
            'module' => 'Users',
            'source' => 'non-db',
            'massupdate' => false,
        ),
        'outbound_inbound_agent_id' => array(
            'name' => 'outbound_inbound_agent_id',
            'vname' => 'LBL_OUTBOUND_INBOUND_AGENT_ID',
            'type' => 'id',
            'reportable' => false,
        ),
        // S-013: CTI Settings -> Voicemail relationship
        'twilio_voice_mail_id' => array(
            'name' => 'twilio_voice_mail_id',
            'vname' => 'LBL_TWILIO_VOICE_MAIL_ID',
            'type' => 'id',
        ),
        // Standard fields
        'assigned_user_id' => array(
            'name' => 'assigned_user_id',
            'rname' => 'user_name',
            'id_name' => 'assigned_user_id',
            'vname' => 'LBL_ASSIGNED_TO_NAME',
            'type' => 'assigned_user_name',
            'table' => 'users',
            'module' => 'Users',
        ),
        'assigned_user_name' => array(
            'name' => 'assigned_user_name',
            'vname' => 'LBL_ASSIGNED_TO_NAME',
            'type' => 'relate',
            'source' => 'non-db',
            'rname' => 'user_name',
            'id_name' => 'assigned_user_id',
            'module' => 'Users',
        ),
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
            'name' => 'idx_sweetdialercti_pk',
            'type' => 'primary',
            'fields' => array('id'),
        ),
        'status' => array(
            'name' => 'idx_sweetdialercti_status',
            'type' => 'index',
            'fields' => array('status'),
        ),
        'assigned_user' => array(
            'name' => 'idx_sweetdialercti_assigned',
            'type' => 'index',
            'fields' => array('assigned_user_id'),
        ),
    ),
    'optimistic_locking' => true,
    'relationships' => array(),
);

if (!class_exists('VardefManager')) {
    require_once('include/SugarObjects/VardefManager.php');
}
VardefManager::createVardef('SweetDialerCTI', 'SweetDialerCTI', array('basic', 'assignable'));
