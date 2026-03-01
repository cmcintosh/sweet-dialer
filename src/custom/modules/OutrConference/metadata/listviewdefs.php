<?php
/**
 * OutrConference List View
 * S-110-S-112: Conference Room List
 */

\$module_name = 'OutrConference';

\$listViewDefs[$module_name] = array(
    'NAME' => array(
        'width' => '25%',
        'label' => 'LBL_NAME',
        'default' => true,
        'link' => true
    ),
    'STATUS' => array(
        'width' => '10%',
        'label' => 'LBL_STATUS',
        'default' => true
    ),
    'PARTICIPANT_COUNT' => array(
        'width' => '10%',
        'label' => 'LBL_PARTICIPANT_COUNT',
        'default' => true,
        'sortable' => false
    ),
    'MAX_PARTICIPANTS' => array(
        'width' => '10%',
        'label' => 'LBL_MAX_PARTICIPANTS',
        'default' => true
    ),
    'MODERATOR_NAME' => array(
        'width' => '15%',
        'label' => 'LBL_MODERATOR',
        'default' => true
    ),
    'DATE_ENTERED' => array(
        'width' => '15%',
        'label' => 'LBL_DATE_ENTERED',
        'default' => true
    ),
    'ASSIGNED_USER_NAME' => array(
        'width' => '15%',
        'label' => 'LBL_ASSIGNED_TO',
        'default' => true
    )
);
