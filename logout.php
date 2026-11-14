<?php
// logout.php
session_start();

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    require_once 'config.php';
    
    // Get user email
    if ($_SESSION['user_type'] == 'admin') {
        $query = "SELECT admin_user FROM ADMIN WHERE id = ?";
    } else {
        $query = "SELECT email_address FROM EMPLOYEE_USER WHERE id = ?";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Record logout
    $log_query = "UPDATE HISTORY_LOG SET logout_time = NOW() 
                  WHERE user_id = ? AND logout_time IS NULL 
                  ORDER BY log_id DESC LIMIT 1";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("i", $_SESSION['user_id']);
    $log_stmt->execute();
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>