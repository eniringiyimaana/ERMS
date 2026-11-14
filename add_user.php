<?php
session_start();
require_once 'config.php';

// Check admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $user_type = trim($_POST['user_type']);
    $name = trim($conn->real_escape_string($_POST['name']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $department_id = (int)($_POST['department'] ?? 0);
    $password = $_POST['password'];
    $status = 'Active';
    $role_id = (int)($_POST['role_id'] ?? 4);

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($department_id <= 0) $errors[] = "Valid department is required";

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: users.php");
        exit();
    }

    // Check email uniqueness
    $check_query = "SELECT email FROM admin WHERE email = ? 
                    UNION SELECT email_address FROM employee_user WHERE email_address = ?";
    $check_stmt = $conn->prepare($check_query);
    if (!$check_stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: users.php");
        exit();
    }
    
    $check_stmt->bind_param("ss", $email, $email);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Email already exists";
        header("Location: users.php");
        exit();
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Verify department exists
        $dept_check = $conn->prepare("SELECT department_id FROM departments WHERE department_id = ?");
        if (!$dept_check) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $dept_check->bind_param("i", $department_id);
        $dept_check->execute();
        
        if ($dept_check->get_result()->num_rows == 0) {
            throw new Exception("Invalid department selected");
        }

        // Insert user based on type
        if ($user_type == 'admin') {
            $admin_user = explode('@', $email)[0];
            $query = "INSERT INTO admin (name, admin_user, email, admin_password, admin_status, department_id, role_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $bind_result = $stmt->bind_param(
                "sssssii", 
                $name, 
                $admin_user, 
                $email, 
                $password_hash, 
                $status, 
                $department_id,
                $role_id
            );
        } else {
            $query = "INSERT INTO employee_user (name, email_address, user_password, user_status, department_id, role_id) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $bind_result = $stmt->bind_param(
                "ssssii", 
                $name, 
                $email, 
                $password_hash, 
                $status, 
                $department_id,
                $role_id
            );
        }

        if (!$bind_result) {
            throw new Exception("Bind failed: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to create user: " . $stmt->error);
        }

        // Log the action
        $log_query = "INSERT INTO audit_log (user_id, action_type, ip_address, timestamp) 
                      VALUES (?, 'Create User', ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        if (!$log_stmt) {
            throw new Exception("Log prepare failed: " . $conn->error);
        }
        
        $log_stmt->bind_param("is", $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']);
        
        if (!$log_stmt->execute()) {
            throw new Exception("Failed to log action: " . $log_stmt->error);
        }

        $_SESSION['success'] = ucfirst($user_type) . " user created successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: users.php");
    exit();
}

header("Location: users.php");
exit();
?>