<?php
/**
 * Transfer Fields Vardefs
 * Epic 10: Transfer - S-096 (2 pts)
 */

\$dictionary['OutrTwilioCalls']['fields']['transfer_type'] = array(
    'name' => 'transfer_type',
    'vname' => 'LBL_TRANSFER_TYPE',
    'type' => 'enum',
    'options' => 'transfer_type_list',
    'default' => '',
    'len' => 20,
    'comment' => 'Type of transfer: warm or cold',
);

\$dictionary['OutrTwilioCalls']['fields']['transfer_status'] = array(
    'name' => 'transfer_status',
    'vname' => 'LBL_TRANSFER_STATUS',
    'type' => 'enum',
    'options' => 'transfer_status_list',
    'default' => '',
    'len' => 20,
    'comment' => 'Status of the transfer',
);

\$dictionary['OutrTwilioCalls']['fields']['transfer_to'] = array(
    'name' => 'transfer_to',
    'vname' => 'LBL_TRANSFER_TO',
    'type' => 'relate',
    'id_name' => 'transfer_to_id',
    ' module' => 'Users',
    'rname' => 'user_name',
    'dbType' => 'varchar',
    'len' => 255,
    'comment' => 'User the call was transferred to',
);

\$dictionary['OutrTwilioCalls']['fields']['transfer_to_id'] = array(
    'name' => 'transfer_to_id',
    'type' => 'id',
    'vname' => 'LBL_TRANSFER_TO_ID',
);

\$dictionary['OutrTwilioCalls']['fields']['transfer_from'] = array(
    'name' => 'transfer_from',
    'vname' => 'LBL_TRANSFER_FROM',
    'type' => 'relate',
    'id_name' => 'transfer_from_id',
    'module' => 'Users',
    'rname' => 'user_name',
    'dbType' => 'varchar',
    'len' => 255,
    'comment' => 'User who transferred the call',
);

\$dictionary['OutrTwilioCalls']['fields']['transfer_from_id'] = array(
    'name' => 'transfer_from_id',
    'type' => 'id',
    'vname' => 'LBL_TRANSFER_FROM_ID',
);

// Enum lists
\$GLOBALS['app_list_strings']['transfer_type_list'] = array(
    '' => '',
    'warm' => 'Warm Transfer',
    'cold' => 'Cold Transfer',
);

\$GLOBALS['app_list_strings']['transfer_status_list'] = array(
    '' => '',
    'pending' => 'Pending',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'failed' => 'Failed',
    'cancelled' => 'Cancelled',
);
