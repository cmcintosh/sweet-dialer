<?php
$dictionary['outr_TwilioLogger'] = array(
    'table' => 'outr_twilio_logger',
    'fields' => array(
        'id' => array('name' => 'id', 'vname' => 'LBL_ID', 'type' => 'id', 'required' => true),
        'log_level' => array('name' => 'log_level', 'vname' => 'LBL_LOG_LEVEL', 'type' => 'varchar', 'len' => 20, 'required' => true),
        'message' => array('name' => 'message', 'vname' => 'LBL_MESSAGE', 'type' => 'text', 'required' => true),
        'context' => array('name' => 'context', 'vname' => 'LBL_CONTEXT', 'type' => 'text'),
        'date_created' => array('name' => 'date_created', 'vname' => 'LBL_DATE_CREATED', 'type' => 'datetime'),
    ),
);
