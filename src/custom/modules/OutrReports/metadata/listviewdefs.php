<?php
/**
 * S-124-S-126: Call Reports ListView Definition
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

$listViewDefs['OutrReports'] = array(
    'NAME' => array(
        'width' => '25%',
        'label' => 'LBL_NAME',
        'default' => true,
        'link' => true,
    ),
    'REPORT_TYPE' => array(
        'width' => '15%',
        'label' => 'LBL_REPORT_TYPE',
        'default' => true,
    ),
    'DATE_START' => array(
        'width' => '15%',
        'label' => 'LBL_DATE_START',
        'default' => true,
    ),
    'DATE_END' => array(
        'width' => '15%',
        'label' => 'LBL_DATE_END',
        'default' => true,
    ),
    'TOTAL_CALLS' => array(
        'width' => '10%',
        'label' => 'LBL_TOTAL_CALLS',
        'default' => true,
        'align' => 'center',
    ),
    'CREATED_BY_NAME' => array(
        'width' => '10%',
        'label' => 'LBL_CREATED_BY',
        'default' => true,
    ),
    'DATE_ENTERED' => array(
        'width' => '10%',
        'label' => 'LBL_DATE_ENTERED',
        'default' => true,
    ),
    'ASSIGNED_USER_NAME' => array(
        'width' => '10%',
        'label' => 'LBL_ASSIGNED_TO',
        'default' => false,
    ),
    'MODIFIED_BY_NAME' => array(
        'width' => '10%',
        'label' => 'LBL_MODIFIED_BY',
        'default' => false,
    ),
);
