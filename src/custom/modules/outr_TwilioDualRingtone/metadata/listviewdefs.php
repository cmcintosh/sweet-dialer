<?php
/**
 * S-098: Dual Ringtone List View
 */

$viewdefs['outr_TwilioDualRingtone']['ListView'] = array(
    'NAME' => array(
        'width' => '30%',
        'label' => 'LBL_NAME',
        'link' => true,
        'default' => true,
    ),
    'CATEGORY' => array(
        'width' => '15%',
        'label' => 'LBL_CATEGORY',
        'default' => true,
    ),
    'SUB_CATEGORY' => array(
        'width' => '15%',
        'label' => 'LBL_SUB_CATEGORY',
        'default' => true,
    ),
    'STATUS' => array(
        'width' => '10%',
        'label' => 'LBL_STATUS',
        'default' => true,
    ),
    'ASSIGNED_USER_NAME' => array(
        'width' => '15%',
        'label' => 'LBL_ASSIGNED_TO',
        'default' => true,
    ),
    'DATE_CREATED' => array(
        'width' => '15%',
        'label' => 'LBL_DATE_CREATED',
        'default' => true,
    ),
);
