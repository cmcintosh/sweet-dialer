<?php
/**
 * S-127-S-129: Dashboard Widget Injection Hook
 *
 * @package SweetDialer
 * @subpackage Reporting
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Inject dashboard widget assets into the page
 *
 * @param SugarBean $bean
 * @param array $args
 * @param string $event
 */
function injectDashboardWidget($bean, $event, $args)
{
    global $current_user;
    
    // Only inject on dashboard or home page
    $module = $_GET['module'] ?? '';
    $action = $_GET['action'] ?? '';
    
    $isDashboard = (
        $module === 'Home' ||
        $module === 'Dashboard' ||
        ($module === 'OutrReports' && $action === 'Dashboard') ||
        $action === 'index'
    );
    
    if (!$isDashboard) {
        return;
    }
    
    // Check if user has permission to view dashboard
    if (!ACLController::checkAccess('OutrReports', 'view', $current_user->id)) {
        return;
    }
    
    // Get configuration
    $config = getDialerDashboardConfig();
    
    // Inject CSS
    $cssUrl = 'custom/include/TwilioDialer/css/dashboardWidget.css';
    if (file_exists($cssUrl)) {
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '"/>\n';
    }
    
    // Inject inline styles for widget
    echo '<style>
        .outr-dashboard-panel {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .outr-dashboard-header {
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .outr-dashboard-header h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .outr-dashboard-header h3 i {
            color: #5cb85c;
            margin-right: 10px;
        }
        .outr-dashboard-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .outr-last-updated {
            font-size: 12px;
            color: #666;
        }
        .outr-refresh-btn {
            background: #fff;
            border: 1px solid #ccc;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .outr-refresh-btn:hover {
            background: #e6e6e6;
        }
        .outr-metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 20px;
        }
        .outr-metric-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 4px;
            background: #f9f9f9;
        }
        .outr-metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
        }
        .outr-metric-total .outr-metric-icon { background: #e3f2fd; color: #1976d2; }
        .outr-metric-inbound .outr-metric-icon { background: #e8f5e9; color: #388e3c; }
        .outr-metric-outbound .outr-metric-icon { background: #fff3e0; color: #f57c00; }
        .outr-metric-missed .outr-metric-icon { background: #ffebee; color: #d32f2f; }
        .outr-metric-avg-duration .outr-metric-icon { background: #f3e5f5; color: #7b1fa2; }
        .outr-metric-success-rate .outr-metric-icon { background: #e0f2f1; color: #00796b; }
        .outr-metric-data {
            display: flex;
            flex-direction: column;
        }
        .outr-metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .outr-metric-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .outr-recent-calls {
            padding: 0 20px 20px;
            border-top: 1px solid #eee;
        }
        .outr-recent-calls h4 {
            margin: 15px 0;
            color: #333;
        }
        .outr-calls-list {
            max-height: 200px;
            overflow-y: auto;
        }
        .outr-call-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .outr-call-item:last-child {
            border-bottom: none;
        }
        .outr-call-direction {
            width: 30px;
            text-align: center;
            color: #666;
        }
        .outr-call-info {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .outr-call-number {
            font-weight: 500;
        }
        .outr-call-contact {
            font-size: 12px;
            color: #666;
        }
        .outr-call-meta {
            text-align: right;
        }
        .outr-call-status {
            display: block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-bottom: 3px;
        }
        .outr-status-completed .outr-call-status { background: #e8f5e9; color: #388e3c; }
        .outr-status-missed .outr-call-status { background: #ffebee; color: #d32f2f; }
        .outr-status-voicemail .outr-call-status { background: #fff3e0; color: #f57c00; }
        .outr-status-other .outr-call-status { background: #f5f5f5; color: #666; }
        .outr-call-time {
            font-size: 11px;
            color: #999;
        }
        .outr-no-calls {
            text-align: center;
            padding: 20px;
            color: #999;
        }
    </style>\n';
    
    // Inject configuration script
    echo '<script>\n';
    echo 'window.outrDashboardConfig = ' . json_encode($config) . ';\n';
    echo '</script>\n';
    
    // Inject JavaScript
    $jsUrl = 'custom/include/TwilioDialer/js/dashboardWidget.js';
    if (file_exists($jsUrl)) {
        echo '<script src="' . $jsUrl . '" type="text/javascript"></script>\n';
    }
}

/**
 * Get dashboard configuration
 *
 * @return array
 */
function getDialerDashboardConfig()
{
    global $sugar_config;
    
    return array(
        'refreshInterval' => isset($sugar_config['outr_dashboard_refresh']) 
            ? intval($sugar_config['outr_dashboard_refresh']) 
            : 60,
        'enabled' => isset($sugar_config['outr_reporting_enabled']) 
            ? (bool)$sugar_config['outr_reporting_enabled'] 
            : true,
        'defaultRange' => isset($sugar_config['outr_analytics_default_range']) 
            ? $sugar_config['outr_analytics_default_range'] 
            : 'last_30_days',
    );
}
