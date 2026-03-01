<?php
/**
 * S-133-S-135: Export Report Endpoint (CSV/PDF)
 *
 * @package SweetDialer
 * @subpackage Reporting
 * @entryPoint exportReport
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

// Verify user access
if (!isset($current_user) || empty($current_user->id)) {
    die('Not authenticated');
}

// Check ACL
if (!ACLController::checkAccess('OutrReports', 'export', $current_user->id)) {
    die('Access denied');
}

// Get request parameters
$reportId = $_GET['reportId'] ?? null;
$format = $_GET['format'] ?? 'csv';
$dateRange = $_GET['range'] ?? 'last_30_days';
$savedReportId = $_GET['savedReportId'] ?? null;

// Validate format
$validFormats = array('csv', 'pdf');
if (!in_array($format, $validFormats)) {
    die('Invalid format. Supported formats: csv, pdf');
}

// Get database instance
$db = DBManagerFactory::getInstance();

// Calculate date range
$dateRangeData = getExportDateRange($dateRange);
$dateFrom = $dateRangeData['from'];
$dateTo = $dateRangeData['to'];

try {
    // Get report data
    $reportData = getReportData($db, $reportId, $dateFrom, $dateTo, $savedReportId);
    
    // Generate export
    switch ($format) {
        case 'csv':
            exportAsCSV($reportData);
            break;
        case 'pdf':
            exportAsPDF($reportData);
            break;
    }
    
} catch (Exception $e) {
    die('Export error: ' . $e->getMessage());
}

/**
 * Get report data based on report type
 */
function getReportData($db, $reportId, $dateFrom, $dateTo, $savedReportId = null)
{
    // Determine report type and parameters
    $reportType = 'calls_summary';
    $reportName = 'Call Report - ' . date('Y-m-d');
    $filters = array();
    
    // If saved report exists, load its parameters
    if ($savedReportId) {
        $sql = "SELECT name, report_type, filters FROM outrreports 
                WHERE id = '" . $db->quote($savedReportId) . "' 
                AND deleted = 0";
        $result = $db->query($sql);
        $savedReport = $db->fetchByAssoc($result);
        
        if ($savedReport) {
            $reportName = $savedReport['name'];
            $reportType = $savedReport['report_type'] ?? 'calls_summary';
            $filters = json_decode($savedReport['filters'], true) ?? array();
        }
    }
    
    // Get data based on report type
    switch ($reportType) {
        case 'calls_summary':
            return getCallsSummaryReport($db, $dateFrom, $dateTo, $filters, $reportName);
        case 'calls_by_agent':
            return getCallsByAgentReport($db, $dateFrom, $dateTo, $filters, $reportName);
        case 'calls_by_contact':
            return getCallsByContactReport($db, $dateFrom, $dateTo, $filters, $reportName);
        case 'missed_calls':
            return getMissedCallsReport($db, $dateFrom, $dateTo, $filters, $reportName);
        case 'call_outcomes':
            return getCallOutcomesReport($db, $dateFrom, $dateTo, $filters, $reportName);
        case 'detailed_log':
            return getDetailedCallLog($db, $dateFrom, $dateTo, $filters, $reportName);
        default:
            return getCallsSummaryReport($db, $dateFrom, $dateTo, $filters, $reportName);
    }
}

/**
 * Get calls summary report
 */
function getCallsSummaryReport($db, $dateFrom, $dateTo, $filters, $reportName)
{
    $columns = array(
        'Date' => 'date',
        'Time' => 'time',
        'Direction' => 'direction',
        'Phone Number' => 'phone_number',
        'Contact Name' => 'contact_name',
        'Status' => 'status',
        'Duration' => 'duration_formatted',
        'Agent' => 'agent_name',
        'Notes' => 'description',
    );
    
    $sql = "SELECT 
                c.id,
                c.date_entered,
                DATE(c.date_entered) as call_date,
                TIME(c.date_entered) as call_time,
                c.direction,
                c.phone_number,
                c.status,
                c.duration,
                c.description,
                c.assigned_user_id,
                CONCAT(u.first_name, ' ', u.last_name) as agent_name,
                COALESCE(l.first_name, con.first_name, 'Unknown') as contact_first_name,
                COALESCE(l.last_name, con.last_name, '') as contact_last_name
            FROM outrtwiliocalls c
            LEFT JOIN users u ON c.assigned_user_id = u.id AND u.deleted = 0
            LEFT JOIN leads l ON c.contact_type = 'Leads' AND c.contact_id = l.id AND l.deleted = 0
            LEFT JOIN contacts con ON c.contact_type = 'Contacts' AND c.contact_id = con.id AND con.deleted = 0
            WHERE c.date_entered >= '" . $db->quote($dateFrom) . "'
            AND c.date_entered <= '" . $db->quote($dateTo) . "'
            AND c.deleted = 0";
    
    // Apply filters
    if (!empty($filters['direction'])) {
        $sql .= " AND c.direction = '" . $db->quote($filters['direction']) . "'";
    }
    if (!empty($filters['status'])) {
        $sql .= " AND c.status = '" . $db->quote($filters['status']) . "'";
    }
    if (!empty($filters['assigned_user_id'])) {
        $sql .= " AND c.assigned_user_id = '" . $db->quote($filters['assigned_user_id']) . "'";
    }
    
    $sql .= " ORDER BY c.date_entered DESC";
    
    $result = $db->query($sql);
    $rows = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $contactName = trim($row['contact_first_name'] . ' ' . $row['contact_last_name']);
        
        $rows[] = array(
            'date' => $row['call_date'],
            'time' => $row['call_time'],
            'direction' => ucfirst($row['direction'] ?? 'unknown'),
            'phone_number' => $row['phone_number'],
            'contact_name' => $contactName ?: 'Unknown',
            'status' => ucfirst(str_replace('-', ' ', $row['status'] ?? 'unknown')),
            'duration_formatted' => formatDuration($row['duration']),
            'agent_name' => $row['agent_name'] ?? 'Unassigned',
            'description' => strip_tags($row['description'] ?? ''),
        );
    }
    
    return array(
        'name' => $reportName,
        'type' => 'Calls Summary Report',
        'generatedAt' => date('Y-m-d H:i:s'),
        'dateRange' => "{$dateFrom} to {$dateTo}",
        'totalRows' => count($rows),
        'columns' => $columns,
        'data' => $rows,
        'summary' => getReportSummary($rows),
    );
}

/**
 * Get calls by agent report
 */
function getCallsByAgentReport($db, $dateFrom, $dateTo, $filters, $reportName)
{
    $columns = array(
        'Agent Name' => 'agent_name',
        'Total Calls' => 'total_calls',
        'Inbound Calls' => 'inbound_calls',
        'Outbound Calls' => 'outbound_calls',
        'Completed' => 'completed_calls',
        'Missed' => 'missed_calls',
        'Avg Duration' => 'avg_duration',
        'Success Rate' => 'success_rate',
    );
    
    $sql = "SELECT 
                c.assigned_user_id,
                CONCAT(u.first_name, ' ', u.last_name) as agent_name,
                COUNT(*) as total_calls,
                SUM(CASE WHEN c.direction = 'inbound' THEN 1 ELSE 0 END) as inbound_calls,
                SUM(CASE WHEN c.direction = 'outbound' THEN 1 ELSE 0 END) as outbound_calls,
                SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as completed_calls,
                SUM(CASE WHEN c.status IN ('no-answer', 'failed', 'busy') THEN 1 ELSE 0 END) as missed_calls,
                AVG(CASE WHEN c.status = 'completed' THEN c.duration END) as avg_duration
            FROM outrtwiliocalls c
            LEFT JOIN users u ON c.assigned_user_id = u.id AND u.deleted = 0
            WHERE c.date_entered >= '" . $db->quote($dateFrom) . "'
            AND c.date_entered <= '" . $db->quote($dateTo) . "'
            AND c.deleted = 0";
    
    if (!empty($filters['direction'])) {
        $sql .= " AND c.direction = '" . $db->quote($filters['direction']) . "'";
    }
    
    $sql .= " GROUP BY c.assigned_user_id ORDER BY total_calls DESC";
    
    $result = $db->query($sql);
    $rows = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $total = $row['total_calls'];
        $completed = $row['completed_calls'];
        
        $rows[] = array(
            'agent_name' => $row['agent_name'] ?? 'Unassigned',
            'total_calls' => $total,
            'inbound_calls' => $row['inbound_calls'],
            'outbound_calls' => $row['outbound_calls'],
            'completed_calls' => $completed,
            'missed_calls' => $row['missed_calls'],
            'avg_duration' => formatDuration($row['avg_duration']),
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 1) . '%' : '0%',
        );
    }
    
    return array(
        'name' => $reportName,
        'type' => 'Calls by Agent Report',
        'generatedAt' => date('Y-m-d H:i:s'),
        'dateRange' => "{$dateFrom} to {$dateTo}",
        'totalRows' => count($rows),
        'columns' => $columns,
        'data' => $rows,
        'summary' => getReportSummary($rows),
    );
}

/**
 * Get calls by contact report
 */
function getCallsByContactReport($db, $dateFrom, $dateTo, $filters, $reportName)
{
    $columns = array(
        'Contact Name' => 'contact_name',
        'Contact Type' => 'contact_type',
        'Phone Number' => 'phone_number',
        'Total Calls' => 'total_calls',
        'Last Called' => 'last_called',
        'Avg Duration' => 'avg_duration',
    );
    
    $sql = "SELECT 
                c.phone_number,
                c.contact_id,
                c.contact_type,
                COALESCE(l.first_name, con.first_name, a.name) as contact_first_name,
                COALESCE(l.last_name, con.last_name) as contact_last_name,
                COUNT(*) as total_calls,
                MAX(c.date_entered) as last_called,
                AVG(c.duration) as avg_duration
            FROM outrtwiliocalls c
            LEFT JOIN leads l ON c.contact_type = 'Leads' AND c.contact_id = l.id AND l.deleted = 0
            LEFT JOIN contacts con ON c.contact_type = 'Contacts' AND c.contact_id = con.id AND con.deleted = 0
            LEFT JOIN accounts a ON c.contact_type = 'Accounts' AND c.contact_id = a.id AND a.deleted = 0
            WHERE c.date_entered >= '" . $db->quote($dateFrom) . "'
            AND c.date_entered <= '" . $db->quote($dateTo) . "'
            AND c.deleted = 0
            GROUP BY c.phone_number, c.contact_id, c.contact_type
            ORDER BY total_calls DESC, last_called DESC";
    
    $result = $db->query($sql);
    $rows = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $contactName = trim($row['contact_first_name'] . ' ' . $row['contact_last_name']);
        
        $rows[] = array(
            'contact_name' => $contactName ?: $row['phone_number'],
            'contact_type' => $row['contact_type'] ?? 'Unknown',
            'phone_number' => $row['phone_number'],
            'total_calls' => $row['total_calls'],
            'last_called' => $row['last_called'],
            'avg_duration' => formatDuration($row['avg_duration']),
        );
    }
    
    return array(
        'name' => $reportName,
        'type' => 'Calls by Contact Report',
        'generatedAt' => date('Y-m-d H:i:s'),
        'dateRange' => "{$dateFrom} to {$dateTo}",
        'totalRows' => count($rows),
        'columns' => $columns,
        'data' => $rows,
        'summary' => getReportSummary($rows),
    );
}

/**
 * Get missed calls report
 */
function getMissedCallsReport($db, $dateFrom, $dateTo, $filters, $reportName)
{
    $columns = array(
        'Date' => 'date',
        'Time' => 'time',
        'Phone Number' => 'phone_number',
        'Contact Name' => 'contact_name',
        'Status' => 'status',
        'Attempts' => 'attempts',
        'Last Attempt' => 'last_attempt',
    );
    
    $sql = "SELECT 
                c.date_entered,
                DATE(c.date_entered) as call_date,
                TIME(c.date_entered) as call_time,
                c.phone_number,
                c.status,
                c.call_count as attempts,
                c.last_call_date as last_attempt,
                COALESCE(l.first_name, con.first_name) as contact_first_name,
                COALESCE(l.last_name, con.last_name) as contact_last_name
            FROM outrtwiliocalls c
            LEFT JOIN leads l ON c.contact_type = 'Leads' AND c.contact_id = l.id AND l.deleted = 0
            LEFT JOIN contacts con ON c.contact_type = 'Contacts' AND c.contact_id = con.id AND con.deleted = 0
            WHERE c.date_entered >= '" . $db->quote($dateFrom) . "'
            AND c.date_entered <= '" . $db->quote($dateTo) . "'
            AND c.status IN ('no-answer', 'failed', 'busy', 'canceled')
            AND c.deleted = 0
            ORDER BY c.date_entered DESC";
    
    $result = $db->query($sql);
    $rows = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $contactName = trim($row['contact_first_name'] . ' ' . $row['contact_last_name']);
        
        $rows[] = array(
            'date' => $row['call_date'],
            'time' => $row['call_time'],
            'phone_number' => $row['phone_number'],
            'contact_name' => $contactName ?: 'Unknown',
            'status' => ucfirst(str_replace('-', ' ', $row['status'])),
            'attempts' => $row['attempts'] ?? 1,
            'last_attempt' => $row['last_attempt'] ?? $row['date_entered'],
        );
    }
    
    return array(
        'name' => $reportName,
        'type' => 'Missed Calls Report',
        'generatedAt' => date('Y-m-d H:i:s'),
        'dateRange' => "{$dateFrom} to {$dateTo}",
        'totalRows' => count($rows),
        'columns' => $columns,
        'data' => $rows,
        'summary' => getReportSummary($rows),
    );
}

/**
 * Get call outcomes report
 */
function getCallOutcomesReport($db, $dateFrom, $dateTo, $filters, $reportName)
{
    $columns = array(
        'Outcome' => 'outcome',
        'Count' => 'count',
        'Percentage' => 'percentage',
    );
    
    $sql = "SELECT 
                status as outcome,
                COUNT(*) as count
            FROM outrtwiliocalls
            WHERE date_entered >= '" . $db->quote($dateFrom) . "'
            AND date_entered <= '" . $db->quote($dateTo) . "'
            AND deleted = 0
            GROUP BY status
            ORDER BY count DESC";
    
    $result = $db->query($sql);
    
    // Get total for percentage calculation
    $totalSql = "SELECT COUNT(*) as total FROM outrtwiliocalls 
                 WHERE date_entered >= '" . $db->quote($dateFrom) . "'
                 AND date_entered <= '" . $db->quote($dateTo) . "'
                 AND deleted = 0";
    $totalResult = $db->query($totalSql);
    $totalRow = $db->fetchByAssoc($totalResult);
    $total = $totalRow['total'];
    
    $rows = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $rows[] = array(
            'outcome' => ucfirst(str_replace('-', ' ', $row['outcome'])),
            'count' => $row['count'],
            'percentage' => $total > 0 ? round(($row['count'] / $total) * 100, 1) . '%' : '0%',
        );
    }
    
    return array(
        'name' => $reportName,
        'type' => 'Call Outcomes Report',
        'generatedAt' => date('Y-m-d H:i:s'),
        'dateRange' => "{$dateFrom} to {$dateTo}",
        'totalRows' => count($rows),
        'columns' => $columns,
        'data' => $rows,
        'summary' => getReportSummary($rows),
    );
}

/**
 * Get detailed call log
 */
function getDetailedCallLog($db, $dateFrom, $dateTo, $filters, $reportName)
{
    $columns = array(
        'Call ID' => 'call_id',
        'Date/Time' => 'datetime',
        'Direction' => 'direction',
        'From' => 'from_number',
        'To' => 'to_number',
        'Contact Name' => 'contact_name',
        'Contact Type' => 'contact_type',
        'Status' => 'status',
        'Duration' => 'duration_formatted',
        'Recording' => 'recording_url',
        'Agent' => 'agent_name',
        'Notes' => 'description',
    );
    
    $sql = "SELECT 
                c.*,
                DATE_FORMAT(c.date_entered, '%Y-%m-%d %H:%i:%s') as datetime,
                CONCAT(u.first_name, ' ', u.last_name) as agent_name,
                COALESCE(l.first_name, con.first_name, a.name, '') as contact_first_name,
                COALESCE(l.last_name, con.last_name, u2.last_name, '') as contact_last_name
            FROM outrtwiliocalls c
            LEFT JOIN users u ON c.assigned_user_id = u.id AND u.deleted = 0
            LEFT JOIN leads l ON c.contact_type = 'Leads' AND c.contact_id = l.id AND l.deleted = 0
            LEFT JOIN contacts con ON c.contact_type = 'Contacts' AND c.contact_id = con.id AND con.deleted = 0
            LEFT JOIN accounts a ON c.contact_type = 'Accounts' AND c.contact_id = a.id AND a.deleted = 0
            LEFT JOIN users u2 ON c.contact_type = 'Users' AND c.contact_id = u2.id AND u2.deleted = 0
            WHERE c.date_entered >= '" . $db->quote($dateFrom) . "'
            AND c.date_entered <= '" . $db->quote($dateTo) . "'
            AND c.deleted = 0
            ORDER BY c.date_entered DESC";
    
    $result = $db->query($sql);
    $rows = array();
    
    while ($row = $db->fetchByAssoc($result)) {
        $contactName = trim($row['contact_first_name'] . ' ' . $row['contact_last_name']);
        
        $rows[] = array(
            'call_id' => $row['id'],
            'datetime' => $row['datetime'],
            'direction' => ucfirst($row['direction'] ?? 'unknown'),
            'from_number' => $row['from_number'] ?? $row['phone_number'],
            'to_number' => $row['to_number'] ?? $row['phone_number'],
            'contact_name' => $contactName ?: 'Unknown',
            'contact_type' => $row['contact_type'] ?? 'N/A',
            'status' => ucfirst(str_replace('-', ' ', $row['status'] ?? 'unknown')),
            'duration_formatted' => formatDuration($row['duration']),
            'recording_url' => $row['recording_url'] ?: 'No Recording',
            'agent_name' => $row['agent_name'] ?? 'Unassigned',
            'description' => strip_tags($row['description'] ?? ''),
        );
    }
    
    return array(
        'name' => $reportName,
        'type' => 'Detailed Call Log',
        'generatedAt' => date('Y-m-d H:i:s'),
        'dateRange' => "{$dateFrom} to {$dateTo}",
        'totalRows' => count($rows),
        'columns' => $columns,
        'data' => $rows,
        'summary' => getReportSummary($rows),
    );
}

/**
 * Get report summary statistics
 */
function getReportSummary($rows)
{
    return array(
        'Total Records' => count($rows),
        'Generated At' => date('Y-m-d H:i:s'),
    );
}

/**
 * Export data as CSV
 */
function exportAsCSV($data)
{
    $filename = sanitizeFilename($data['name']) . '_' . date('Y-m-d') . '.csv';
    
    // Set headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Report metadata
    fputcsv($output, array('Report Name:', $data['name']));
    fputcsv($output, array('Report Type:', $data['type']));
    fputcsv($output, array('Generated At:', $data['generatedAt']));
    fputcsv($output, array('Date Range:', $data['dateRange']));
    fputcsv($output, array());
    
    // Headers
    fputcsv($output, array_keys($data['columns']));
    
    // Data rows
    foreach ($data['data'] as $row) {
        $rowData = array();
        foreach ($data['columns'] as $key => $field) {
            $rowData[] = $row[$field] ?? '';
        }
        fputcsv($output, $rowData);
    }
    
    fclose($output);
    exit;
}

/**
 * Export data as PDF (simplified - generates HTML for print/PDF)
 */
function exportAsPDF($data)
{
    $filename = sanitizeFilename($data['name']) . '_' . date('Y-m-d') . '.pdf';
    
    // For PDF, we'll generate HTML that can be printed to PDF
    header('Content-Type: text/html');
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>' . htmlspecialchars($data['name']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
        .meta { margin: 20px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #1976d2; color: white; padding: 10px; text-align: left; }
        td { padding: 8px 10px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f5f5f5; }
        .summary { margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 4px; }
        .print-btn { margin: 20px 0; padding: 10px 20px; background: #1976d2; color: white; border: none; cursor: pointer; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print to PDF</button>
    <h1>' . htmlspecialchars($data['name']) . '</h1>
    <div class="meta">
        <p><strong>Type:</strong> ' . htmlspecialchars($data['type']) . '</p>
        <p><strong>Generated:</strong> ' . htmlspecialchars($data['generatedAt']) . '</p>
        <p><strong>Date Range:</strong> ' . htmlspecialchars($data['dateRange']) . '</p>
        <p><strong>Total Records:</strong> ' . number_format($data['totalRows']) . '</p>
    </div>
    <table>
        <thead>
            <tr>';
    
    foreach ($data['columns'] as $label => $field) {
        $html .= '<th>' . htmlspecialchars($label) . '</th>';
    }
    
    $html .= '</tr>
        </thead>
        <tbody>';
    
    foreach ($data['data'] as $row) {
        $html .= '<tr>';
        foreach ($data['columns'] as $key => $field) {
            $html .= '<td>' . htmlspecialchars($row[$field] ?? '') . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</tbody>
    </table>
</body>
</html>';
    
    echo $html;
    exit;
}

/**
 * Format duration in seconds to readable string
 */
function formatDuration($seconds)
{
    if (!$seconds) return '0:00';
    
    $seconds = (int)$seconds;
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }
    
    return sprintf('%d:%02d', $minutes, $secs);
}

/**
 * Get date range for export
 */
function getExportDateRange($range)
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
 * Sanitize filename
 */
function sanitizeFilename($filename)
{
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
}
