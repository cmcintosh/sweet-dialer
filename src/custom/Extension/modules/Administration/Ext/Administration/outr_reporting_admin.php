<?php
/**
 * S-121-S-123: Report Module Setup - Add Reporting section to Administration
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

// Add Reporting Settings section to Admin panel
$admin_group_header['outr_reporting'] = array(
    'LBL_OUTR_REPORTING_TITLE',
    '',
    false,
    array(
        // Dashboard Settings
        array(
            'LBL_OUTR_DASHBOARD_SETTINGS',
            'index.php?module=Administration&action=outrDashboardConfig',
            'LBL_OUTR_DASHBOARD_SETTINGS_DESC',
        ),
        // Report Configuration
        array(
            'LBL_OUTR_REPORT_CONFIG',
            'index.php?module=Administration&action=outrReportConfig',
            'LBL_OUTR_REPORT_CONFIG_DESC',
        ),
        // Analytics Settings
        array(
            'LBL_OUTR_ANALYTICS_SETTINGS',
            'index.php?module=Administration&action=outrAnalyticsConfig',
            'LBL_OUTR_ANALYTICS_SETTINGS_DESC',
        ),
        // Data Retention
        array(
            'LBL_OUTR_DATA_RETENTION',
            'index.php?module=Administration&action=outrDataRetention',
            'LBL_OUTR_DATA_RETENTION_DESC',
        ),
    ),
);
