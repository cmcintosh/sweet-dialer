<?php
/**
 * S-127-S-129: Dialer Dashboard Data Endpoint
 *
 * @package SweetDialer
 * @subpackage Reporting
 * @entryPoint dialerDashboard
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

// Get database instance
$db = DBManagerFactory::getInstance();

// Calculate date ranges
$dateRange = $_GET['range'] ?? 'today';
$dateFrom = getDateFromRange($dateRange);
$dateTo = date('Y-m-d 23:59:59');

try {
    // Get today's call metrics
    $metrics = array(
        'totalCalls' => getTotalCalls($db, $dateFrom, $dateTo),
        'inboundCalls' => getInboundCalls($db, $dateFrom, $dateTo),
        'outboundCalls' => getOutboundCalls($db, $dateFrom, $dateTo),
        'missedCalls' => getMissedCalls($db, $dateFrom, $dateTo),
        'avgDuration' => getAvgDuration($db, $dateFrom, $dateTo),
        'successRate' => getSuccessRate($db, $dateFrom, $dateTo),
    );
    
    // Get recent calls
    $recentCalls = getRecentCalls($db, 10);
    
    $response = array(
        'success' => true,
        'data' => array_merge($metrics, array('recentCalls' => $recentCalls)),
        'timestamp' => time(),
    );
    
    jsonResponse($response);
    
} catch (Exception $e) {
    jsonResponse(array('success' => false, 'error' => $e->getMessage()), 500);
}

/**
 * Get total calls count
 */
function getTotalCalls($db, $dateFrom, $dateTo)
{
    $sql = "SELECT COUNT(*) as total FROM outrtwiliocalls 
            WHERE date_entered >= '" . $db->quote($dateFrom) . "' 
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND deleted = 0";
    
    $result = $db->query($sql);
    $row = $db->fetchByAssoc($result);
    
    return (int)($row['total'] ?? 0);
}

/**
 * Get inbound calls count
 */
function getInboundCalls($db, $dateFrom, $dateTo)
{
    $sql = "SELECT COUNT(*) as total FROM outrtwiliocalls 
            WHERE date_entered >= '" . $db->quote($dateFrom) . "' 
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND direction = 'inbound'
            AND deleted = 0";
    
    $result = $db->query($sql);
    $row = $db->fetchByAssoc($result);
    
    return (int)($row['total'] ?? 0);
}

/**
 * Get outbound calls count
 */
function getOutboundCalls($db, $dateFrom, $dateTo)
{
    $sql = "SELECT COUNT(*) as total FROM outrtwiliocalls 
            WHERE date_entered >= '" . $db->quote($dateFrom) . "' 
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND direction = 'outbound'
            AND deleted = 0";
    
    $result = $db->query($sql);
    $row = $db->fetchByAssoc($result);
    
    return (int)($row['total'] ?? 0);
}

/**
 * Get missed calls count
 */
function getMissedCalls($db, $dateFrom, $dateTo)
{
    $sql = "SELECT COUNT(*) as total FROM outrtwiliocalls 
            WHERE date_entered >= '" . $db->quote($dateFrom) . "' 
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND status IN ('no-answer', 'failed', 'busy', 'canceled')
            AND deleted = 0";
    
    $result = $db->query($sql);
    $row = $db->fetchByAssoc($result);
    
    return (int)($row['total'] ?? 0);
}

/**
 * Get average call duration
 */
function getAvgDuration($db, $dateFrom, $dateTo)
{
    $sql = "SELECT AVG(duration) as avg_duration FROM outrtwiliocalls 
            WHERE date_entered >= '" . $db->quote($dateFrom) . "' 
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND status = 'completed'
            AND duration > 0
            AND deleted = 0";
    
    $result = $db->query($sql);
    $row = $db->fetchByAssoc($result);
    
    $avgSeconds = (int)($row['avg_duration'] ?? 0);
    
    // Format as MM:SS
    $minutes = floor($avgSeconds / 60);
    $seconds = $avgSeconds % 60;
    
    return sprintf('%d:%02d', $minutes, $seconds);
}

/**
 * Get success rate percentage
 */
function getSuccessRate($db, $dateFrom, $dateTo)
{
    $sql = "SELECT 
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(*) as total 
            FROM outrtwiliocalls 
            WHERE date_entered >= '" . $db->quote($dateFrom) . "' 
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND deleted = 0";
    
    $result = $db->query($sql);
    $row = $db->fetchByAssoc($result);
    
    $completed = (int)($row['completed'] ?? 0);
    $total = (int)($row['total'] ?? 0);
    
    if ($total === 0) {
        return 0;
    }
    
    return round(($completed / $total) * 100, 1);
}

/**
 * Get recent calls
 */
function getRecentCalls($db, $limit = 10)
{
    $sql = "SELECT 
                c.id,
                c.phone_number,
                c.direction,
                c.status,
                c.duration,
                c.date_entered,
                c.contact_id,
                c.contact_type,
                COALESCE(l.first_name, con.first_name, a.name, u.first_name) as contact_first_name,
                COALESCE(l.last_name, con.last_name, u.last_name) as contact_last_name
            FROM outrtwiliocalls c
            LEFT JOIN leads l ON c.contact_type = 'Leads' AND c.contact_id = l.id AND l.deleted = 0
            LEFT JOIN contacts con ON c.contact_type = 'Contacts' AND c.contact_id = con.id AND con.deleted = 0
            LEFT JOIN accounts a ON c.contact_type = 'Accounts' AND c.contact_id = a.id AND a.deleted = 0
            LEFT JOIN users u ON c.contact_type = 'Users' AND c.contact_id = u.id AND u.deleted = 0
            WHERE c.deleted = 0
            ORDER BY c.date_entered DESC
            LIMIT " . intval($limit);
    
    $result = $db->query($sql);
    $calls = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $contactName = trim(($row['contact_first_name'] ?? '') . ' ' . ($row['contact_last_name'] ?? ''));
        
        $calls[] = array(
            'id' => $row['id'],
            'phoneNumber' => $row['phone_number'],
            'direction' => $row['direction'],
            'status' => $row['status'],
            'duration' => (int)($row['duration'] ?? 0),
            'contactName' => $contactName ?: null,
            'contactId' => $row['contact_id'],
            'contactType' => $row['contact_type'],
            'timeAgo' => getTimeAgo($row['date_entered']),
        );
    }
    
    return $calls;
}

/**
 * Get date from range string
 */
function getDateFromRange($range)
{
    switch ($range) {
        case 'today':
            return date('Y-m-d 00:00:00');
        case 'yesterday':
            return date('Y-m-d 00:00:00', strtotime('-1 day'));
        case 'last_7_days':
            return date('Y-m-d 00:00:00', strtotime('-7 days'));
        case 'last_30_days':
            return date('Y-m-d 00:00:00', strtotime('-30 days'));
        case 'this_month':
            return date('Y-m-01 00:00:00');
        case 'last_month':
            return date('Y-m-01 00:00:00', strtotime('first day of last month'));
        default:
            return date('Y-m-d 00:00:00');
    }
}

/**
 * Format time as "X minutes ago"
 */
function getTimeAgo($datetime)
{
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}

/**
 * Send JSON response
 */
function jsonResponse($data, $httpCode = 200)
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
