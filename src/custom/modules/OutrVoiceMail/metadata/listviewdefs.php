<?php
/**
 * S-092: Voicemail List View Definition
 * Configures columns for voicemail list view
 */

$listViewDefs['OutrVoiceMail'] = [
    'NAME' => [
        'width' => '25%',
        'label' => 'LBL_NAME',
        'link' => true,
        'default' => true,
        'related_fields' => ['name'],
    ],
    'ASSIGNED_USER_NAME' => [
        'width' => '15%',
        'label' => 'LBL_ASSIGNED_TO_NAME',
        'module' => 'Users',
        'id' => 'ASSIGNED_USER_ID',
        'default' => true,
        'sortable' => true,
    ],
    'DURATION' => [
        'width' => '10%',
        'label' => 'LBL_DURATION',
        'default' => true,
        'sortable' => true,
    ],
    'STATUS' => [
        'type' => 'enum',
        'default' => true,
        'studio' => 'visible',
        'label' => 'LBL_STATUS',
        'width' => '10%',
        'sortable' => true,
    ],
    'DATE_ENTERED' => [
        'width' => '15%',
        'label' => 'LBL_DATE_ENTERED',
        'default' => true,
        'sortable' => true,
    ],
    'SOURCE' => [
        'type' => 'enum',
        'default' => false,
        'studio' => 'visible',
        'label' => 'LBL_SOURCE',
        'width' => '10%',
    ],
    'TWILIO_CALL_SID' => [
        'type' => 'varchar',
        'label' => 'LBL_TWILIO_CALL_SID',
        'width' => '15%',
        'default' => false,
    ],
    'TWILIO_RECORDING_SID' => [
        'type' => 'varchar',
        'label' => 'LBL_TWILIO_RECORDING_SID',
        'width' => '15%',
        'default' => false,
    ],
    'DATE_MODIFIED' => [
        'type' => 'datetime',
        'label' => 'LBL_DATE_MODIFIED',
        'width' => '15%',
        'default' => false,
    ],
    'CREATED_BY_NAME' => [
        'type' => 'relate',
        'link' => 'created_by_link',
        'label' => 'LBL_CREATED',
        'width' => '10%',
        'default' => false,
    ],
    'MODIFIED_BY_NAME' => [
        'type' => 'relate',
        'link' => 'modified_user_link',
        'label' => 'LBL_MODIFIED',
        'width' => '10%',
        'default' => false,
    ],
];
