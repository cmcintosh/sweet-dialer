<?php
/**
 * S-016: CTI Calls -> Accounts Relationship
 */

$dictionary['SweetDialerCalls']['fields']['account_id'] = array(
    'name' => 'account_id',
    'vname' => 'LBL_ACCOUNT_ID',
    'type' => 'id',
    'reportable' => true,
);

$dictionary['SweetDialerCalls']['fields']['account_name'] = array(
    'name' => 'account_name',
    'vname' => 'LBL_ACCOUNT_NAME',
    'type' => 'relate',
    'rname' => 'name',
    'id_name' => 'account_id',
    'module' => 'Accounts',
    'source' => 'non-db',
);

$dictionary['SweetDialerCalls']['fields']['accounts'] = array(
    'name' => 'accounts',
    'type' => 'link',
    'relationship' => 'twilio_calls_accounts',
    'source' => 'non-db',
    'module' => 'Accounts',
);
