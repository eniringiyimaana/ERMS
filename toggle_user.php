<?php
// toggle_user.php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    $_SESSION['error'] = "Unauthorized access";
    header("Location: users.php");
    exit();
}

// Get parameters
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;
$current_status = $_GET['status'] ?? '';

// Validate inputs
if (empty($type) || empty($id) || empty($current_status)) {
    $_SESSION['error'] = "Invalid parameters";
    header("Location: users.php");
    exit();
}

try {
    // Determine which table to update
    $table = ($type == 'admin') ? 'admin' : 'employee_user';
    $statusColumn = ($type == 'admin') ? 'admin_status' : 'user_status';
    $newStatus = ($current_status == 'Active') ? 'Inactive' : 'Active';
    
    // Update the status
    $query = "UPDATE $table SET $statusColumn = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $newStatus, $id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $message = "User " . ($newStatus == 'Active' ? 'activated' : 'deactivated') . " successfully";
        $_SESSION['success'] = $message;
    } else {
        $_SESSION['error'] = "No changes made or user not found";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

header("Location: users.php");
exit();
?>