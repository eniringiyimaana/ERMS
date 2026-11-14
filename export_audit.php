<?php

// Add at the beginning of the script
set_time_limit(0);
ini_set('memory_limit', '512M');

// export_audit.php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Get filter parameters from URL
$action_type = $_GET['action_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$user_filter = $_GET['user_id'] ?? '';

// Build base query
$query = "SELECT al.timestamp, 
          COALESCE(a.name, eu.name) AS user_name,
          al.action_type,
          uf.title AS document_title,
          al.ip_address
          FROM AUDIT_LOG al
          LEFT JOIN admin a ON al.user_id = a.id
          LEFT JOIN employee_user eu ON al.user_id = eu.id
          LEFT JOIN UPLOAD_FILES uf ON al.document_id = uf.id";

// Build where conditions
$conditions = [];
$params = [];
$types = '';

if (!empty($action_type)) {
    $conditions[] = "al.action_type = ?";
    $params[] = $action_type;
    $types .= 's';
}

if (!empty($date_from)) {
    $conditions[] = "al.timestamp >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $conditions[] = "al.timestamp <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

if (!empty($user_filter)) {
    $conditions[] = "al.user_id = ?";
    $params[] = $user_filter;
    $types .= 'i';
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY al.timestamp DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=audit_log_export_' . date('Y-m-d') . '.csv');

// Create output file pointer
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Timestamp',
    'User',
    'Action Type',
    'Document',
    'IP Address'
]);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['timestamp'],
        $row['user_name'] ?? 'System',
        $row['action_type'],
        $row['document_title'] ?? 'N/A',
        $row['ip_address'] ?? ''
    ]);
}

fclose($output);
exit();
?>