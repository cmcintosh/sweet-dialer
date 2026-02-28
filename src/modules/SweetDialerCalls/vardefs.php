<?php
/**
 * SweetDialerCalls Vardefs (S-010)
 * outr_twilio_calls table
 */

$dictionary['outr_TwilioCalls'] = array(
    'table' => 'outr_twilio_calls',
    'audited' => true,
    'fields' => array(
        'id' => array(
            'name' => 'id',
            'vname' => 'LBL_ID',
            'type' => 'id',
            'required' => true,
        ),
        'call_type' => array(
            'name' => 'call_type',
            'vname' => 'LBL_CALL_TYPE',
            'type' => 'enum',
            'options' => 'twilio_call_type_list',
            'dbType' => 'enum',
            'len' => 30,
            'required' => true,
        ),
        'agent_id' => array(
            'name' => 'agent_id',
            'vname' => 'LBL_AGENT_ID',
            'type' => 'relate',
            'rname' => 'user_name',
            'id_name' => 'agent_id',
            'table' => 'users',
            'module' => 'Users',
        ),
        'from_number' => array(
            'name' => 'from_number',
            'vname' => 'LBL_FROM_NUMBER',
            'type' => 'phone',
            'dbType' => 'varchar',
            'len' => 50,
        ),
        'to_number' => array(
            'name' => 'to_number',
            'vname' => 'LBL_TO_NUMBER',
            'type' => 'phone',
            'dbType' => 'varchar',
            'len' => 50,
        ),
        'status' => array(
            'name' => 'status',
            'vname' => 'LBL_STATUS',
            'type' => 'varchar',
            'len' => 50,
        ),
        'call_sid' => array(
            'name' => 'call_sid',
            'vname' => 'LBL_CALL_SID',
            'type' => 'varchar',
            'len' => 255,
            'required' => true,
        ),
        'duration' => array(
            'name' => 'duration',
            'vname' => 'LBL_DURATION',
            'type' => 'int',
            'default' => 0,
        ),
        'recording_url' => array(
            'name' => 'recording_url',
            'vname' => 'LBL_RECORDING_URL',
            'type' => 'text',
        ),
        'recording_sid' => array(
            'name' => 'recording_sid',
            'vname' => 'LBL_RECORDING_SID',
            'type' => 'varchar',
            'len' => 255,
        ),
        'parent_type' => array(
            'name' => 'parent_type',
            'vname' => 'LBL_PARENT_TYPE',
            'type' => 'parent_type',
            'dbType' => 'varchar',
            'len' => 100,
        ),
        'parent_id' => array(
            'name' => 'parent_id',
            'vname' => 'LBL_PARENT_ID',
            'type' => 'id',
        ),
        'company_id' => array(
            'name' => 'company_id',
            'vname' => 'LBL_COMPANY_ID',
            'type' => 'relate',
            'rname' => 'name',
            'id_name' => 'company_id',
            'table' => 'accounts',
            'module' => 'Accounts',
        ),
        'notes' => array(
            'name' => 'notes',
            'vname' => 'LBL_NOTES',
            'type' => 'text',
        ),
        'cti_setting_id' => array(
            'name' => 'cti_setting_id',
            'vname' => 'LBL_CTI_SETTING_ID',
            'type' => 'relate',
            'rname' => 'name',
            'id_name' => 'cti_setting_id',
            'table' => 'outr_twilio_settings',
            'module' => 'outr_TwilioSettings',
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
    ),
    'indices' => array(
        array('name' => 'idx_twilio_calls_call_sid', 'type' => 'index', 'fields' => array('call_sid')),
        array('name' => 'idx_twilio_calls_call_type', 'type' => 'index', 'fields' => array('call_type')),
        array('name' => 'idx_twilio_calls_agent', 'type' => 'index', 'fields' => array('agent_id')),
        array('name' => 'idx_twilio_calls_parent', 'type' => 'index', 'fields' => array('parent_type', 'parent_id')),
        array('name' => 'idx_twilio_calls_company', 'type' => 'index', 'fields' => array('company_id')),
        array('name' => 'idx_twilio_calls_deleted', 'type' => 'index', 'fields' => array('deleted')),
    ),
);
