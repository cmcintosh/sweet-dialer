<?php
/**
 * ListViewDefs for SweetDialerCTI (Epic 3)
 * S-026: "See CTI Settings" list view
 */

$viewdefs['outr_TwilioSettings']['ListView'] = array(
    'NAME' => array(
        'width' => '25%',
        'label' => 'LBL_NAME',
        'link' => true,
        'default' => true,
        'name' => 'name',
    ),
    'AGENT_PHONE_NUMBER' => array(
        'width' => '15%',
        'label' => 'LBL_AGENT_PHONE_NUMBER',
        'default' => true,
        'name' => 'agent_phone_number',
    ),
    'OUTBOUND_INBOUND_AGENT' => array(
        'width' => '15%',
        'label' => 'LBL_OUTBOUND_INBOUND_AGENT',
        'default' => true,
        'name' => 'outbound_inbound_agent',
    ),
    'STATUS' => array(
        'width' => '10%',
        'label' => 'LBL_STATUS',
        'default' => true,
        'name' => 'status',
    ),
    'DATE_CREATED' => array(
        'width' => '12%',
        'label' => 'LBL_DATE_CREATED',
        'default' => true,
        'name' => 'date_created',
    ),
    'ASSIGNED_USER_NAME' => array(
        'width' => '12%',
        'label' => 'LBL_ASSIGNED_TO',
        'default' => true,
        'name' => 'outbound_inbound_agent',
    ),
);
