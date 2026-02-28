<?php
/**
 * S-015: Polymorphic relationship fields
 * CTI Calls --> Contacts/Leads/Targets/Cases
 */

$dictionary['SweetDialerCalls']['fields']['parent_type'] = array(
    'name' => 'parent_type',
    'vname' => 'LBL_PARENT_TYPE',
    'type' => 'parent_type',
    'dbType' => 'varchar',
    'len' => 255,
    'comment' => 'The module type of the parent record',
    'options' => 'parent_type_display',
);

$dictionary['SweetDialerCalls']['fields']['parent_id'] = array(
    'name' => 'parent_id',
    'vname' => 'LBL_PARENT_ID',
    'type' => 'id',
    'group' => 'parent_name',
    'comment' => 'The ID of the parent record',
);

$dictionary['SweetDialerCalls']['fields']['parent_name'] = array(
    'name' => 'parent_name',
    'vname' => 'LBL_PARENT',
    'type' => 'parent',
    'dbType' => 'varchar',
    'len' => 255,
    'parent_type' => 'record_type_display',
    'parent_id' => 'parent_id',
    'source' => 'non-db',
    'options' => 'parent_type_display',
);

$dictionary['SweetDialerCalls']['fields']['contacts'] = array(
    'name' => 'contacts',
    'type' => 'link',
    'relationship' => 'twilio_calls_contacts',
    'source' => 'non-db',
    'module' => 'Contacts',
);

$dictionary['SweetDialerCalls']['fields']['leads'] = array(
    'name' => 'leads',
    'type' => 'link',
    'relationship' => 'twilio_calls_leads',
    'source' => 'non-db',
    'module' => 'Leads',
);

$dictionary['SweetDialerCalls']['fields']['prospects'] = array(
    'name' => 'prospects',
    'type' => 'link',
    'relationship' => 'twilio_calls_prospects',
    'source' => 'non-db',
    'module' => 'Prospects',
);

$dictionary['SweetDialerCalls']['fields']['cases'] = array(
    'name' => 'cases',
    'type' => 'link',
    'relationship' => 'twilio_calls_cases',
    'source' => 'non-db',
    'module' => 'Cases',
);
