<?php
/**
 * S-121-S-123: Report Module Setup - Reporting Vardefs Extension
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

// Define reporting module related fields and relationships
$dictionary['Administration']['fields']['outr_reporting_enabled'] = array(
    'name' => 'outr_reporting_enabled',
    'vname' => 'LBL_OUTR_REPORTING_ENABLED',
    'type' => 'bool',
    'default' => true,
    'required' => false,
    'comment' => 'Enable/disable reporting module',
);

$dictionary['Administration']['fields']['outr_dashboard_refresh'] = array(
    'name' => 'outr_dashboard_refresh',
    'vname' => 'LBL_OUTR_DASHBOARD_REFRESH',
    'type' => 'int',
    'len' => 5,
    'default' => 60,
    'required' => false,
    'comment' => 'Dashboard auto-refresh interval in seconds',
);

$dictionary['Administration']['fields']['outr_report_retention'] = array(
    'name' => 'outr_report_retention',
    'vname' => 'LBL_OUTR_REPORT_RETENTION',
    'type' => 'int',
    'len' => 5,
    'default' => 90,
    'required' => false,
    'comment' => 'Number of days to retain report data',
);

$dictionary['Administration']['fields']['outr_analytics_default_range'] = array(
    'name' => 'outr_analytics_default_range',
    'vname' => 'LBL_OUTR_ANALYTICS_DEFAULT_RANGE',
    'type' => 'enum',
    'options' => 'outr_date_range_list',
    'default' => 'last_30_days',
    'required' => false,
    'comment' => 'Default date range for analytics views',
);
