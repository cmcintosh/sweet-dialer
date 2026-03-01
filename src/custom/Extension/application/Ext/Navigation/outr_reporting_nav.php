<?php
/**
 * S-121-S-123: Report Module Setup - Reporting Navigation Tab
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

$extension_navigation['Reporting'] = array(
    'title' => 'LBL_OUTR_REPORTING_NAV',
    'order' => 130,
    'module' => 'OutrReports',
    'action' => 'index',
    'icon' => 'fa-chart-bar',
    'label' => 'LBL_OUTR_REPORTING_NAV',
    'submenu' => array(
        array(
            'key' => 'call_reports',
            'module' => 'OutrReports',
            'action' => 'ListView',
            'label' => 'LBL_OUTR_CALL_REPORTS',
        ),
        array(
            'key' => 'dashboard',
            'module' => 'OutrReports',
            'action' => 'Dashboard',
            'label' => 'LBL_OUTR_DASHBOARD',
        ),
        array(
            'key' => 'analytics',
            'module' => 'OutrReports',
            'action' => 'Analytics',
            'label' => 'LBL_OUTR_ANALYTICS',
        ),
        array(
            'key' => 'export',
            'module' => 'OutrReports',
            'action' => 'Export',
            'label' => 'LBL_OUTR_EXPORT',
        ),
    ),
);
