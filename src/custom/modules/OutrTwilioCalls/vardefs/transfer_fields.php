<?php

$dictionary['OutrTwilioCalls']['fields']['transfer_type'] = [
    'name' => 'transfer_type',
    'vname' => 'LBL_TRANSFER_TYPE',
    'type' => 'enum',
    'options' => 'transfer_type_dom',
    'default' => '',
    'len' => 50,
    'audited' => true,
    'comment' => 'Type of transfer performed: warm or cold',
];

$dictionary['OutrTwilioCalls']['fields']['transfer_status'] = [
    'name' => 'transfer_status',
    'vname' => 'LBL_TRANSFER_STATUS',
    'type' => 'enum',
    'options' => 'transfer_status_dom',
    'default' => 'pending',
    'len' => 50,
    'audited' => true,
    'comment' => 'Status of the transfer: pending, completed, or failed',
];

$dictionary['OutrTwilioCalls']['fields']['transfer_to'] = [
    'name' => 'transfer_to',
    'vname' => 'LBL_TRANSFER_TO',
    'type' => 'relate',
    'link' => 'user_link',
    'module' => 'Users',
    'rname' => 'name',
    'id_name' => 'transfer_to_user_id',
    'audited' => true,
    'comment' => 'User the call was transferred to',
];

$dictionary['OutrTwilioCalls']['fields']['transfer_to_user_id'] = [
    'name' => 'transfer_to_user_id',
    'vname' => 'LBL_TRANSFER_TO_USER_ID',
    'type' => 'id',
    'audited' => true,
    'comment' => 'ID of user the call was transferred to',
];

$dictionary['OutrTwilioCalls']['fields']['user_link'] = [
    'name' => 'user_link',
    'vname' => 'LBL_USER_LINK',
    'type' => 'link',
    'relationship' => 'outrtwiliocalls_users',
    'module' => 'Users',
    'bean_name' => 'User',
    'source' => 'non-db',
];
