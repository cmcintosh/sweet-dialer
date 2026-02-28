<?php
/**
 * SweetDialerCTI Vardefs (S-009)
 * outr_twilio_settings table
 */

$dictionary['outr_TwilioSettings'] = array(
    'table' => 'outr_twilio_settings',
    'audited' => true,
    'unified_search' => true,
    'fields' => array(
        'id' => array(
            'name' => 'id',
            'vname' => 'LBL_ID',
            'type' => 'id',
            'required' => true,
            'reportable' => false,
        ),
        'name' => array(
            'name' => 'name',
            'vname' => 'LBL_NAME',
            'type' => 'name',
            'dbType' => 'varchar',
            'len' => 255,
            'required' => true,
            'importable' => 'required',
        ),
        'accounts_sid' => array(
            'name' => 'accounts_sid',
            'vname' => 'LBL_ACCOUNTS_SID',
            'type' => 'varchar',
            'len' => 255,
        ),
        'auth_token' => array(
            'name' => 'auth_token',
            'vname' => 'LBL_AUTH_TOKEN',
            'type' => 'text',
        ),
        'agent_phone_number' => array(
            'name' => 'agent_phone_number',
            'vname' => 'LBL_AGENT_PHONE_NUMBER',
            'type' => 'phone',
            'dbType' => 'varchar',
            'len' => 50,
        ),
        'phone_sid' => array(
            'name' => 'phone_sid',
            'vname' => 'LBL_PHONE_SID',
            'type' => 'varchar',
            'len' => 255,
        ),
        'incoming_calls_modules' => array(
            'name' => 'incoming_calls_modules',
            'vname' => 'LBL_INCOMING_CALLS_MODULES',
            'type' => 'text',
        ),
        'status' => array(
            'name' => 'status',
            'vname' => 'LBL_STATUS',
            'type' => 'enum',
            'options' => 'twilio_status_list',
            'dbType' => 'enum',
            'len' => 30,
            'default' => 'Active',
        ),
        'bg_color' => array(
            'name' => 'bg_color',
            'vname' => 'LBL_BG_COLOR',
            'type' => 'varchar',
            'len' => 50,
        ),
        'text_color' => array(
            'name' => 'text_color',
            'vname' => 'LBL_TEXT_COLOR',
            'type' => 'varchar',
            'len' => 50,
        ),
        'api_key_sid' => array(
            'name' => 'api_key_sid',
            'vname' => 'LBL_API_KEY_SID',
            'type' => 'varchar',
            'len' => 255,
        ),
        'api_key_secret' => array(
            'name' => 'api_key_secret',
            'vname' => 'LBL_API_KEY_SECRET',
            'type' => 'text',
        ),
        'twiml_app_sid' => array(
            'name' => 'twiml_app_sid',
            'vname' => 'LBL_TWIML_APP_SID',
            'type' => 'varchar',
            'len' => 255,
        ),
        'date_created' => array(
            'name' => 'date_created',
            'vname' => 'LBL_DATE_CREATED',
            'type' => 'datetime',
        ),
        'date_modified' => array(
            'name' => 'date_modified',
            'vname' => 'LBL_DATE_MODIFIED',
            'type' => 'datetime',
        ),
        'deleted' => array(
            'name' => 'deleted',
            'vname' => 'LBL_DELETED',
            'type' => 'bool',
            'default' => 0,
        ),
        'created_by' => array(
            'name' => 'created_by',
            'rname' => 'user_name',
            'id_name' => 'created_by',
            'vname' => 'LBL_CREATED',
            'type' => 'assigned_user_name',
            'table' => 'users',
            'isnull' => false,
            'dbType' => 'id',
        ),
        'modified_user_id' => array(
            'name' => 'modified_user_id',
            'vname' => 'LBL_MODIFIED_USER_ID',
            'type' => 'relate',
            'dbType' => 'id',
            'rname' => 'user_name',
            'id_name' => 'modified_user_id',
            'table' => 'users',
        ),
    ),
    'indices' => array(
        array('name' => 'idx_twilio_settings_name', 'type' => 'index', 'fields' => array('name')),
        array('name' => 'idx_twilio_settings_status', 'type' => 'index', 'fields' => array('status')),
        array('name' => 'idx_twilio_settings_deleted', 'type' => 'index', 'fields' => array('deleted')),
    ),
    'optimistic_locking' => true,
    'unified_search' => true,
);
