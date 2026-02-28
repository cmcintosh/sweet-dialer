<?php
/**
 * SweetDialerCTI Vardefs (Epic 3: CTI Settings Module)
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
        // S-020: Core Twilio Fields
        'accounts_sid' => array(
            'name' => 'accounts_sid',
            'vname' => 'LBL_ACCOUNTS_SID',
            'type' => 'varchar',
            'len' => 255,
        ),
        'auth_token' => array(
            'name' => 'auth_token',
            'vname' => 'LBL_AUTH_TOKEN',
            'type' => 'varchar',
            'len' => 255,
        ),
        'agent_phone_number' => array(
            'name' => 'agent_phone_number',
            'vname' => 'LBL_AGENT_PHONE_NUMBER',
            'type' => 'varchar',
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
            'type' => 'enum',
            'options' => 'twilio_incoming_calls_modules_list',
            'dbType' => 'enum',
            'len' => 50,
            'default' => 'Home',
        ),
        'status' => array(
            'name' => 'status',
            'vname' => 'LBL_STATUS',
            'type' => 'enum',
            'options' => 'twilio_cti_status_list',
            'dbType' => 'enum',
            'len' => 30,
            'default' => 'Active',
        ),
        // S-021: Relate Fields
        'twilio_voice_mail_id' => array(
            'name' => 'twilio_voice_mail_id',
            'vname' => 'LBL_TWILIO_VOICE_MAIL_ID',
            'type' => 'id',
            'required' => false,
        ),
        'twilio_voice_mail' => array(
            'name' => 'twilio_voice_mail',
            'vname' => 'LBL_TWILIO_VOICE_MAIL',
            'type' => 'relate',
            'rname' => 'name',
            'id_name' => 'twilio_voice_mail_id',
            'module' => 'SweetDialerVoicemail',
            'source' => 'non-db',
            'dbType' => 'varchar',
            'massupdate' => false,
            'quick_search' => array('enabled' => true),
        ),
        'outbound_inbound_agent_id' => array(
            'name' => 'outbound_inbound_agent_id',
            'vname' => 'LBL_OUTBOUND_INBOUND_AGENT_ID',
            'type' => 'id',
            'required' => false,
        ),
        'outbound_inbound_agent' => array(
            'name' => 'outbound_inbound_agent',
            'vname' => 'LBL_OUTBOUND_INBOUND_AGENT',
            'type' => 'relate',
            'rname' => 'user_name',
            'id_name' => 'outbound_inbound_agent_id',
            'module' => 'Users',
            'source' => 'non-db',
            'dbType' => 'varchar',
            'massupdate' => false,
            'quick_search' => array('enabled' => true),
        ),
        // S-022: Color Picker Fields
        'bg_color' => array(
            'name' => 'bg_color',
            'vname' => 'LBL_BG_COLOR',
            'type' => 'varchar',
            'len' => 7,
            'default' => '#ffffff',
        ),
        'text_color' => array(
            'name' => 'text_color',
            'vname' => 'LBL_TEXT_COLOR',
            'type' => 'varchar',
            'len' => 7,
            'default' => '#000000',
        ),
        // S-023: File Upload Fields
        'dual_ring_file' => array(
            'name' => 'dual_ring_file',
            'vname' => 'LBL_DUAL_RING_FILE',
            'type' => 'varchar',
            'len' => 255,
        ),
        'dual_ring_file_name' => array(
            'name' => 'dual_ring_file_name',
            'vname' => 'LBL_DUAL_RING_FILE_NAME',
            'type' => 'varchar',
            'len' => 255,
        ),
        'hold_ring_file' => array(
            'name' => 'hold_ring_file',
            'vname' => 'LBL_HOLD_RING_FILE',
            'type' => 'varchar',
            'len' => 255,
        ),
        'hold_ring_file_name' => array(
            'name' => 'hold_ring_file_name',
            'vname' => 'LBL_HOLD_RING_FILE_NAME',
            'type' => 'varchar',
            'len' => 255,
        ),
        // S-024: v2 API Credential Fields
        'api_key_sid' => array(
            'name' => 'api_key_sid',
            'vname' => 'LBL_API_KEY_SID',
            'type' => 'varchar',
            'len' => 255,
        ),
        'api_key_secret' => array(
            'name' => 'api_key_secret',
            'vname' => 'LBL_API_KEY_SECRET',
            'type' => 'varchar',
            'len' => 255,
        ),
        'twiml_app_sid' => array(
            'name' => 'twiml_app_sid',
            'vname' => 'LBL_TWIML_APP_SID',
            'type' => 'varchar',
            'len' => 255,
        ),
        // S-027: Last Validation Message
        'last_validation_status' => array(
            'name' => 'last_validation_status',
            'vname' => 'LBL_LAST_VALIDATION_STATUS',
            'type' => 'enum',
            'options' => 'twilio_validation_status_list',
            'dbType' => 'enum',
            'len' => 30,
            'default' => '',
        ),
        'last_validation_message' => array(
            'name' => 'last_validation_message',
            'vname' => 'LBL_LAST_VALIDATION_MESSAGE',
            'type' => 'text',
        ),
        'last_validation_date' => array(
            'name' => 'last_validation_date',
            'vname' => 'LBL_LAST_VALIDATION_DATE',
            'type' => 'datetime',
        ),
        // S-031: Ring Timeout
        'ring_timeout' => array(
            'name' => 'ring_timeout',
            'vname' => 'LBL_RING_TIMEOUT',
            'type' => 'int',
            'default' => 30,
            'len' => 3,
        ),
        // Audit Fields
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
        // S-028: Multi-agent/multi-number uniqueness
        array('name' => 'idx_phone_agent_unique', 'type' => 'unique', 'fields' => array('agent_phone_number', 'outbound_inbound_agent_id', 'deleted')),
    ),
    'optimistic_locking' => true,
    'unified_search' => true,
);
