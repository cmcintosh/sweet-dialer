<?php
$dictionary['outr_TwilioErrorLogs'] = array(
    'table' => 'outr_twilio_error_logs',
    'fields' => array(
        'id' => array('name' => 'id', 'vname' => 'LBL_ID', 'type' => 'id', 'required' => true),
        'error_code' => array('name' => 'error_code', 'vname' => 'LBL_ERROR_CODE', 'type' => 'varchar', 'len' => 50),
        'error_message' => array('name' => 'error_message', 'vname' => 'LBL_ERROR_MESSAGE', 'type' => 'text'),
        'call_sid' => array('name' => 'call_sid', 'vname' => 'LBL_CALL_SID', 'type' => 'varchar', 'len' => 255),
        'endpoint' => array('name' => 'endpoint', 'vname' => 'LBL_ENDPOINT', 'type' => 'varchar', 'len' => 255),
        'request_body' => array('name' => 'request_body', 'vname' => 'LBL_REQUEST_BODY', 'type' => 'text'),
        'response_body' => array('name' => 'response_body', 'vname' => 'LBL_RESPONSE_BODY', 'type' => 'text'),
        'date_created' => array('name' => 'date_created', 'vname' => 'LBL_DATE_CREATED', 'type' => 'datetime'),
    ),
);
