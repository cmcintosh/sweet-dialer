<?php
/**
 * Dual and Hold Ringtones
 */

$dictionary['outr_TwilioDualRingtone'] = array(
    'table' => 'outr_twilio_dual_ringtone',
    'fields' => array(
        'id' => array('name' => 'id', 'vname' => 'LBL_ID', 'type' => 'id', 'required' => true),
        'name' => array('name' => 'name', 'vname' => 'LBL_NAME', 'type' => 'name', 'dbType' => 'varchar', 'len' => 255, 'required' => true),
        'file' => array('name' => 'file', 'vname' => 'LBL_FILE', 'type' => 'text'),
        'category' => array('name' => 'category', 'vname' => 'LBL_CATEGORY', 'type' => 'varchar', 'len' => 100),
        'sub_category' => array('name' => 'sub_category', 'vname' => 'LBL_SUB_CATEGORY', 'type' => 'varchar', 'len' => 100),
        'assigned_to' => array('name' => 'assigned_to', 'vname' => 'LBL_ASSIGNED_TO', 'type' => 'relate', 'rname' => 'user_name', 'id_name' => 'assigned_to', 'table' => 'users', 'module' => 'Users'),
        'publish_date' => array('name' => 'publish_date', 'vname' => 'LBL_PUBLISH_DATE', 'type' => 'datetime'),
        'expiration_date' => array('name' => 'expiration_date', 'vname' => 'LBL_EXPIRATION_DATE', 'type' => 'datetime'),
        'status' => array('name' => 'status', 'vname' => 'LBL_STATUS', 'type' => 'enum', 'options' => 'ringtone_status_list', 'len' => 30, 'default' => 'Active'),
        'description' => array('name' => 'description', 'vname' => 'LBL_DESCRIPTION', 'type' => 'text'),
        'date_created' => array('name' => 'date_created', 'vname' => 'LBL_DATE_CREATED', 'type' => 'datetime'),
        'date_modified' => array('name' => 'date_modified', 'vname' => 'LBL_DATE_MODIFIED', 'type' => 'datetime'),
        'deleted' => array('name' => 'deleted', 'vname' => 'LBL_DELETED', 'type' => 'bool', 'default' => 0),
    ),
);

$dictionary['outr_TwilioHoldRingtone'] = array(
    'table' => 'outr_twilio_hold_ringtone',
    'fields' => array(
        'id' => array('name' => 'id', 'vname' => 'LBL_ID', 'type' => 'id', 'required' => true),
        'name' => array('name' => 'name', 'vname' => 'LBL_NAME', 'type' => 'name', 'dbType' => 'varchar', 'len' => 255, 'required' => true),
        'file' => array('name' => 'file', 'vname' => 'LBL_FILE', 'type' => 'text'),
        'category' => array('name' => 'category', 'vname' => 'LBL_CATEGORY', 'type' => 'varchar', 'len' => 100),
        'sub_category' => array('name' => 'sub_category', 'vname' => 'LBL_SUB_CATEGORY', 'type' => 'varchar', 'len' => 100),
        'assigned_to' => array('name' => 'assigned_to', 'vname' => 'LBL_ASSIGNED_TO', 'type' => 'relate', 'rname' => 'user_name', 'id_name' => 'assigned_to', 'table' => 'users', 'module' => 'Users'),
        'publish_date' => array('name' => 'publish_date', 'vname' => 'LBL_PUBLISH_DATE', 'type' => 'datetime'),
        'expiration_date' => array('name' => 'expiration_date', 'vname' => 'LBL_EXPIRATION_DATE', 'type' => 'datetime'),
        'status' => array('name' => 'status', 'vname' => 'LBL_STATUS', 'type' => 'enum', 'options' => 'ringtone_status_list', 'len' => 30, 'default' => 'Active'),
        'description' => array('name' => 'description', 'vname' => 'LBL_DESCRIPTION', 'type' => 'text'),
        'date_created' => array('name' => 'date_created', 'vname' => 'LBL_DATE_CREATED', 'type' => 'datetime'),
        'date_modified' => array('name' => 'date_modified', 'vname' => 'LBL_DATE_MODIFIED', 'type' => 'datetime'),
        'deleted' => array('name' => 'deleted', 'vname' => 'LBL_DELETED', 'type' => 'bool', 'default' => 0),
    ),
);
