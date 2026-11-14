<?php
session_start();
require_once 'config.php';

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['login_error'] = "Security token mismatch";
        header("Location: index.php");
        exit();
    }

    // Sanitize and validate input
    $username = trim($conn->real_escape_string($_POST['admin_user']));
    $password = $_POST['admin_password'];

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Username/email and password are required";
        header("Location: index.php");
        exit();
    }

    // Check in ADMIN table first (using email)
    $query = "SELECT 
                id, 
                name, 
                email, 
                admin_password AS password, 
                admin_status AS status, 
                'admin' AS user_type,
                department_id,
                role_id
              FROM admin 
              WHERE email = ? 
              AND admin_status = 'Active' 
              LIMIT 1";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $_SESSION['login_error'] = "System error. Please try again later.";
        header("Location: index.php");
        exit();
    }
    
    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $_SESSION['login_error'] = "System error. Please try again later.";
        header("Location: index.php");
        exit();
    }
    
    $result = $stmt->get_result();
    $user = null;
    $user_type = null;

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $user_type = 'admin';
    } else {
        // If not found in ADMIN, check EMPLOYEE_USER
        $query = "SELECT 
                    id, 
                    name, 
                    email_address AS email, 
                    user_password AS password, 
                    user_status AS status, 
                    'employee' AS user_type,
                    department_id,
                    role_id
                  FROM employee_user 
                  WHERE email_address = ? 
                  AND user_status = 'Active' 
                  LIMIT 1";
                  
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            $_SESSION['login_error'] = "System error. Please try again later.";
            header("Location: index.php");
            exit();
        }
        
        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $_SESSION['login_error'] = "System error. Please try again later.";
            header("Location: index.php");
            exit();
        }
        
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $user_type = 'employee';
        }
    }

    // Rest of your login logic...
    // [Keep all the remaining code the same]

    if ($user && password_verify($password, $user['password'])) {
        // Check if password needs rehash
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE ".($user_type == 'admin' ? 'admin' : 'employee_user')." SET ".($user_type == 'admin' ? 'admin_password' : 'user_password')." = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newHash, $user['id']);
            $updateStmt->execute();
        }

        // Regenerate session ID
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user_type;
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['department'] = $user['department'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();

        // Update last login
        $updateStmt = $conn->prepare("UPDATE ".($user_type == 'admin' ? 'admin' : 'employee_user')." SET last_login = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $user['id']);
        $updateStmt->execute();

        // Log login in history_log
        $host = gethostname(); // Store in variable first
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $log_query = "INSERT INTO history_log (
                        user_id, 
                        user_type, 
                        email_address, 
                        action, 
                        ip, 
                        host, 
                        login_time
                      ) VALUES (?, ?, ?, 'Login', ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        
        // Bind variables, not direct function calls
        $log_stmt->bind_param("issss", 
            $user['id'], 
            $user_type, 
            $user['email'],
            $ip,
            $host
        );
        
        if (!$log_stmt->execute()) {
            error_log("Failed to log login: ".$log_stmt->error);
        }

        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['login_error'] = "Invalid username/email or password";
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>