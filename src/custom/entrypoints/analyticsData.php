<?php
/**
 * S-130-S-132: Analytics Data Endpoint (AJAX)
 *
 * @package SweetDialer
 * @subpackage Reporting
 * @entryPoint analyticsData
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

// Verify user access
if (!isset($current_user) || empty($current_user->id)) {
    jsonResponse(array('success' => false, 'error' => 'Not authenticated'), 401);
}

// Check ACL
if (!ACLController::checkAccess('OutrReports', 'view', $current_user->id)) {
    jsonResponse(array('success' => false, 'error' => 'Access denied'), 403);
}

// Get request parameters
$chartType = $_GET['chart'] ?? 'calls_over_time';
$dateRange = $_GET['range'] ?? 'last_30_days';
$groupBy = $_GET['groupBy'] ?? 'day';
$userId = $_GET['userId'] ?? null;

// Validate parameters
$validCharts = array('calls_over_time', 'calls_by_direction', 'calls_by_status', 'calls_by_hour', 'agent_performance', 'call_duration', 'conversion_rate');
if (!in_array($chartType, $validCharts)) {
    jsonResponse(array('success' => false, 'error' => 'Invalid chart type'), 400);
}

// Get database instance
$db = DBManagerFactory::getInstance();

// Calculate date range
$dateRangeData = getDateRangeBounds($dateRange);
$dateFrom = $dateRangeData['from'];
$dateTo = $dateRangeData['to'];

try {
    $response = array(
        'success' => true,
        'chartType' => $chartType,
        'dateRange' => $dateRange,
        'from' => $dateFrom,
        'to' => $dateTo,
    );
    
    // Get chart data based on type
    switch ($chartType) {
        case 'calls_over_time':
            $response['data'] = getCallsOverTime($db, $dateFrom, $dateTo, $groupBy, $userId);
            break;
        case 'calls_by_direction':
            $response['data'] = getCallsByDirection($db, $dateFrom, $dateTo, $userId);
            break;
        case 'calls_by_status':
            $response['data'] = getCallsByStatus($db, $dateFrom, $dateTo, $userId);
            break;
        case 'calls_by_hour':
            $response['data'] = getCallsByHour($db, $dateFrom, $dateTo, $userId);
            break;
        case 'agent_performance':
            $response['data'] = getAgentPerformance($db, $dateFrom, $dateTo);
            break;
        case 'call_duration':
            $response['data'] = getCallDurationDistribution($db, $dateFrom, $dateTo, $userId);
            break;
        case 'conversion_rate':
            $response['data'] = getConversionRate($db, $dateFrom, $dateTo, $userId);
            break;
    }
    
    jsonResponse($response);
    
} catch (Exception $e) {
    jsonResponse(array('success' => false, 'error' => $e->getMessage()), 500);
}

/**
 * Get calls over time grouped by day/week/month
 */
function getCallsOverTime($db, $dateFrom, $dateTo, $groupBy, $userId = null)
{
    $format = '';
    switch ($groupBy) {
        case 'hour':
            $format = '%Y-%m-%d %H:00';
            break;
        case 'week':
            $format = '%Y-%u';
            break;
        case 'month':
            $format = '%Y-%m';
            break;
        case 'day':
        default:
            $format = '%Y-%m-%d';
            break;
    }
    
    $sql = "SELECT 
                DATE_FORMAT(date_entered, '{$format}') as period,
                COUNT(*) as total,
                SUM(CASE WHEN direction = 'inbound' THEN 1 ELSE 0 END) as inbound,
                SUM(CASE WHEN direction = 'outbound' THEN 1 ELSE 0 END) as outbound
            FROM outrtwiliocalls
            WHERE date_entered >= '" . $db->quote($dateFrom) . "'
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND deleted = 0";
    
    if ($userId) {
        $sql .= " AND assigned_user_id = '" . $db->quote($userId) . "'";
    }
    
    $sql .= " GROUP BY period ORDER BY period ASC";
    
    $result = $db->query($sql);
    $data = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $data[] = array(
            'period' => $row['period'],
            'total' => (int)$row['total'],
            'inbound' => (int)$row['inbound'],
            'outbound' => (int)$row['outbound'],
        );
    }
    
    return $data;
}

/**
 * Get calls grouped by direction
 */
function getCallsByDirection($db, $dateFrom, $dateTo, $userId = null)
{
    $sql = "SELECT 
                direction,
                COUNT(*) as count,
                AVG(duration) as avg_duration
            FROM outrtwiliocalls
            WHERE date_entered >= '" . $db->quote($dateFrom) . "'
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND deleted = 0";
    
    if ($userId) {
        $sql .= " AND assigned_user_id = '" . $db->quote($userId) . "'";
    }
    
    $sql .= " GROUP BY direction ORDER BY count DESC";
    
    $result = $db->query($sql);
    $data = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $data[] = array(
            'direction' => $row['direction'] ?? 'unknown',
            'count' => (int)$row['count'],
            'avgDuration' => round((float)$row['avg_duration'], 2),
        );
    }
    
    return $data;
}

/**
 * Get calls grouped by status
 */
function getCallsByStatus($db, $dateFrom, $dateTo, $userId = null)
{
    $sql = "SELECT 
                status,
                COUNT(*) as count,
                AVG(duration) as avg_duration
            FROM outrtwiliocalls
            WHERE date_entered >= '" . $db->quote($dateFrom) . "'
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND deleted = 0";
    
    if ($userId) {
        $sql .= " AND assigned_user_id = '" . $db->quote($userId) . "'";
    }
    
    $sql .= " GROUP BY status ORDER BY count DESC";
    
    $result = $db->query($sql);
    $data = array();
    
    $statusLabels = array(
        'completed' => 'Completed',
        'no-answer' => 'No Answer',
        'busy' => 'Busy',
        'failed' => 'Failed',
        'canceled' => 'Canceled',
        'voicemail' => 'Voicemail',
    );
    
    while ($row = $db->fetchByAssoc($result)) {
        $status = $row['status'] ?? 'unknown';
        $data[] = array(
            'status' => $status,
            'label' => $statusLabels[$status] ?? ucfirst($status),
            'count' => (int)$row['count'],
            'avgDuration' => round((float)$row['avg_duration'], 2),
        );
    }
    
    return $data;
}

/**
 * Get calls grouped by hour of day
 */
function getCallsByHour($db, $dateFrom, $dateTo, $userId = null)
{
    $sql = "SELECT 
                HOUR(date_entered) as hour,
                COUNT(*) as count
            FROM outrtwiliocalls
            WHERE date_entered >= '" . $db->quote($dateFrom) . "'
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND deleted = 0";
    
    if ($userId) {
        $sql .= " AND assigned_user_id = '" . $db->quote($userId) . "'";
    }
    
    $sql .= " GROUP BY hour ORDER BY hour ASC";
    
    $result = $db->query($sql);
    $data = array();
    
    // Initialize all hours with 0
    for ($i = 0; $i < 24; $i++) {
        $data[$i] = array(
            'hour' => $i,
            'hourLabel' => sprintf('%02d:00', $i),
            'count' => 0,
        );
    }
    
    while ($row = $db->fetchByAssoc($result)) {
        $hour = (int)$row['hour'];
        $data[$hour]['count'] = (int)$row['count'];
    }
    
    return array_values($data);
}

/**
 * Get performance by agent
 */
function getAgentPerformance($db, $dateFrom, $dateTo)
{
    // Get user info for agent names
    $sql = "SELECT 
                c.assigned_user_id,
                u.first_name,
                u.last_name,
                COUNT(*) as total_calls,
                SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as completed_calls,
                SUM(CASE WHEN c.direction = 'outbound' THEN 1 ELSE 0 END) as outbound_calls,
                AVG(c.duration) as avg_call_duration,
                MAX(c.duration) as max_call_duration
            FROM outrtwiliocalls c
            LEFT JOIN users u ON c.assigned_user_id = u.id AND u.deleted = 0
            WHERE c.date_entered >= '" . $db->quote($dateFrom) . "'
            AND c.date_entered <= '" . $db->quote($dateTo) . "'
            AND c.deleted = 0
            GROUP BY c.assigned_user_id
            ORDER BY total_calls DESC
            LIMIT 20";
    
    $result = $db->query($sql);
    $data = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $data[] = array(
            'agentId' => $row['assigned_user_id'] ?: 'unassigned',
            'agentName' => $name ?: 'Unknown',
            'totalCalls' => (int)$row['total_calls'],
            'completedCalls' => (int)$row['completed_calls'],
            'outboundCalls' => (int)$row['outbound_calls'],
            'successRate' => $row['total_calls'] > 0 
                ? round(($row['completed_calls'] / $row['total_calls']) * 100, 1) 
                : 0,
            'avgDuration' => round((float)$row['avg_call_duration'], 2),
            'maxDuration' => (int)$row['max_call_duration'],
        );
    }
    
    return $data;
}

/**
 * Get call duration distribution
 */
function getCallDurationDistribution($db, $dateFrom, $dateTo, $userId = null)
{
    $sql = "SELECT 
                CASE 
                    WHEN duration < 60 THEN '0-1 min'
                    WHEN duration < 180 THEN '1-3 min'
                    WHEN duration < 300 THEN '3-5 min'
                    WHEN duration < 600 THEN '5-10 min'
                    WHEN duration < 1800 THEN '10-30 min'
                    ELSE '30+ min'
                END as duration_range,
                COUNT(*) as count
            FROM outrtwiliocalls
            WHERE date_entered >= '" . $db->quote($dateFrom) . "'
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND status = 'completed'
            AND deleted = 0";
    
    if ($userId) {
        $sql .= " AND assigned_user_id = '" . $db->quote($userId) . "'";
    }
    
    $sql .= " GROUP BY duration_range ORDER BY MIN(duration) ASC";
    
    $result = $db->query($sql);
    $data = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $data[] = array(
            'range' => $row['duration_range'],
            'count' => (int)$row['count'],
        );
    }
    
    return $data;
}

/**
 * Get conversion rate for calls to outcomes
 */
function getConversionRate($db, $dateFrom, $dateTo, $userId = null)
{
    // Count calls with related records
    $sql = "SELECT 
                COUNT(DISTINCT c.id) as calls_with_outcome,
                COUNT(DISTINCT CASE WHEN c.contact_id IS NOT NULL THEN c.id END) as calls_with_contact,
                COUNT(DISTINCT CASE WHEN c.call_result IS NOT NULL THEN c.id END) as calls_with_result,
                COUNT(DISTINCT l.id) as related_leads,
                COUNT(DISTINCT con.id) as related_contacts
            FROM outrtwiliocalls c
            LEFT JOIN leads l ON c.contact_type = 'Leads' AND c.contact_id = l.id AND l.deleted = 0
            LEFT JOIN contacts con ON c.contact_type = 'Contacts' AND c.contact_id = con.id AND con.deleted = 0
            WHERE c.date_entered >= '" . $db->quote($dateFrom) . "'
            AND c.date_entered <= '" . $db->quote($dateTo) . "'
            AND c.deleted = 0";
    
    if ($userId) {
        $sql .= " AND c.assigned_user_id = '" . $db->quote($userId) . "'";
    }
    
    $result = $db->query($sql);
    $row = $db->fetchByAssoc($result);
    
    // Get total calls for rate calculation
    $totalSql = "SELECT COUNT(*) as total FROM outrtwiliocalls 
                 WHERE date_entered >= '" . $db->quote($dateFrom) . "'
                 AND date_entered <= '" . $db->quote($dateTo) . "'
                 AND deleted = 0";
    
    if ($userId) {
        $totalSql .= " AND assigned_user_id = '" . $db->quote($userId) . "'";
    }
    
    $totalResult = $db->query($totalSql);
    $totalRow = $db->fetchByAssoc($totalResult);
    $totalCalls = (int)$totalRow['total'];
    
    $callsWithOutcome = (int)$row['calls_with_outcome'];
    
    return array(
        'totalCalls' => $totalCalls,
        'callsWithContact' => (int)$row['calls_with_contact'],
        'callsWithResult' => (int)$row['calls_with_result'],
        'relatedLeads' => (int)$row['related_leads'],
        'relatedContacts' => (int)$row['related_contacts'],
        'conversionRate' => $totalCalls > 0 
            ? round((($row['calls_with_contact'] ?? 0) / $totalCalls) * 100, 1) 
            : 0,
    );
}

/**
 * Get date range bounds
 */
function getDateRangeBounds($range)
{
    switch ($range) {
        case 'today':
            return array('from' => date('Y-m-d 00:00:00'), 'to' => date('Y-m-d 23:59:59'));
        case 'yesterday':
            return array('from' => date('Y-m-d 00:00:00', strtotime('-1 day')), 'to' => date('Y-m-d 23:59:59', strtotime('-1 day')));
        case 'last_7_days':
            return array('from' => date('Y-m-d 00:00:00', strtotime('-7 days')), 'to' => date('Y-m-d 23:59:59'));
        case 'last_30_days':
            return array('from' => date('Y-m-d 00:00:00', strtotime('-30 days')), 'to' => date('Y-m-d 23:59:59'));
        case 'this_month':
            return array('from' => date('Y-m-01 00:00:00'), 'to' => date('Y-m-d 23:59:59', strtotime('last day of this month')));
        case 'last_month':
            return array(
                'from' => date('Y-m-01 00:00:00', strtotime('first day of last month')),
                'to' => date('Y-m-d 23:59:59', strtotime('last day of last month'))
            );
        default:
            return array('from' => date('Y-m-d 00:00:00', strtotime('-30 days')), 'to' => date('Y-m-d 23:59:59'));
    }
}

/**
 * Send JSON response
 */
function jsonResponse($data, $httpCode = 200)
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($data);
    exit;
}
