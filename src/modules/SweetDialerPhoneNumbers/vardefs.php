<?php
$dictionary['outr_TwilioPhoneNumbers'] = array(
    'table' => 'outr_twilio_phone_numbers',
    'fields' => array(
        'id' => array('name' => 'id', 'vname' => 'LBL_ID', 'type' => 'id', 'required' => true),
        'phone_number' => array('name' => 'phone_number', 'vname' => 'LBL_PHONE_NUMBER', 'type' => 'phone', 'dbType' => 'varchar', 'len' => 50, 'required' => true),
        'friendly_name' => array('name' => 'friendly_name', 'vname' => 'LBL_FRIENDLY_NAME', 'type' => 'varchar', 'len' => 255),
        'phone_sid' => array('name' => 'phone_sid', 'vname' => 'LBL_PHONE_SID', 'type' => 'varchar', 'len' => 255),
        'capabilities_voice' => array('name' => 'capabilities_voice', 'vname' => 'LBL_CAPABILITIES_VOICE', 'type' => 'bool', 'default' => 1),
        'capabilities_sms' => array('name' => 'capabilities_sms', 'vname' => 'LBL_CAPABILITIES_SMS', 'type' => 'bool', 'default' => 1),
        'assignment_status' => array('name' => 'assignment_status', 'vname' => 'LBL_ASSIGNMENT_STATUS', 'type' => 'enum', 'options' => 'phonenum_assignment_list', 'len' => 30, 'default' => 'Available'),
        'cti_setting_id' => array('name' => 'cti_setting_id', 'vname' => 'LBL_CTI_SETTING_ID', 'type' => 'relate', 'rname' => 'name', 'id_name' => 'cti_setting_id', 'table' => 'outr_twilio_settings', 'module' => 'outr_TwilioSettings'),
        'date_created' => array('name' => 'date_created', 'vname' => 'LBL_DATE_CREATED', 'type' => 'datetime'),
        'date_modified' => array('name' => 'date_modified', 'vname' => 'LBL_DATE_MODIFIED', 'type' => 'datetime'),
        'deleted' => array('name' => 'deleted', 'vname' => 'LBL_DELETED', 'type' => 'bool', 'default' => 0),
    ),
);
