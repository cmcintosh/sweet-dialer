<?php
$dictionary['outr_TwilioOptedOut'] = array(
    'table' => 'outr_twilio_opted_out',
    'fields' => array(
        'id' => array('name' => 'id', 'vname' => 'LBL_ID', 'type' => 'id', 'required' => true),
        'phone_number' => array('name' => 'phone_number', 'vname' => 'LBL_PHONE_NUMBER', 'type' => 'phone', 'dbType' => 'varchar', 'len' => 50, 'required' => true),
        'reason' => array('name' => 'reason', 'vname' => 'LBL_REASON', 'type' => 'text'),
        'date_opted_out' => array('name' => 'date_opted_out', 'vname' => 'LBL_DATE_OPTED_OUT', 'type' => 'datetime', 'required' => true),
        'date_created' => array('name' => 'date_created', 'vname' => 'LBL_DATE_CREATED', 'type' => 'datetime'),
        'date_modified' => array('name' => 'date_modified', 'vname' => 'LBL_DATE_MODIFIED', 'type' => 'datetime'),
        'deleted' => array('name' => 'deleted', 'vname' => 'LBL_DELETED', 'type' => 'bool', 'default' => 0),
    ),
    'indices' => array(
        array('name' => 'idx_optedout_phone', 'type' => 'unique', 'fields' => array('phone_number')),
    ),
);
