<?php
// delete_record.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$record_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// First, get the record details to check permissions and file path
$query = "SELECT * FROM UPLOAD_FILES WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Record not found";
    header("Location: records.php");
    exit();
}

$record = $result->fetch_assoc();

// Check if user has permission to delete (admin or owner)
if ($user_type != 'admin' && $user_id != $record['uploaded_by']) {
    $_SESSION['error'] = "You don't have permission to delete this record";
    header("Location: records.php");
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // First, log the deletion in audit log
    $log_query = "INSERT INTO AUDIT_LOG (user_id, action_type, document_id, ip_address, timestamp) 
                  VALUES (?, 'Delete', ?, ?, NOW())";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("iis", $user_id, $record_id, $_SERVER['REMOTE_ADDR']);
    $log_stmt->execute();

    // Then delete the record from database
    $delete_query = "DELETE FROM UPLOAD_FILES WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $record_id);
    $delete_stmt->execute();

    // If database operations were successful, delete the physical file
    if (file_exists($record['file_path'])) {
        unlink($record['file_path']);
    }

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = "Record deleted successfully";
} catch (Exception $e) {
    // Rollback transaction if any error occurs
    $conn->rollback();
    $_SESSION['error'] = "Error deleting record: " . $e->getMessage();
}

header("Location: records.php");
exit();
?>