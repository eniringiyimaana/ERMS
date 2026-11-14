<?php
// profile.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user details
// Get user details with department name
if ($user_type == 'admin') {
    $query = "SELECT a.*, d.department_name 
              FROM admin a 
              LEFT JOIN departments d ON a.department_id = d.department_id
              WHERE a.id = ?";
} else {
    $query = "SELECT e.*, d.department_name 
              FROM employee_user e 
              LEFT JOIN departments d ON e.department_id = d.department_id 
              WHERE e.id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = ($user_type == 'admin') ? $_POST['email'] : $_POST['email_address'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['admin_password'] ?? $user['user_password'])) {
            $error = "Current password is incorrect";
        } elseif ($new_password != $confirm_password) {
            $error = "New passwords do not match";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = ($user_type == 'admin') 
                ? "UPDATE admin SET name = ?, email = ?, admin_password = ? WHERE id = ?"
                : "UPDATE employee_user SET name = ?, email_address = ?, user_password = ? WHERE id = ?";
            
            $update_stmt = $conn->prepare($update_query);
            $params = ($user_type == 'admin')
                ? [$name, $email, $hashed_password, $user_id]
                : [$name, $email, $hashed_password, $user_id];
            
            $update_stmt->bind_param(str_repeat('s', count($params)-1) . 'i', ...$params);
            $update_stmt->execute();
            
            $success = "Profile updated successfully";
            // Refresh user data
            $user = $result->fetch_assoc();
        }
    } else {
        // Update without password change
        $update_query = ($user_type == 'admin') 
            ? "UPDATE admin SET name = ?, email = ? WHERE id = ?"
            : "UPDATE employee_user SET name = ?, email_address = ? WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_query);
        $params = ($user_type == 'admin')
            ? [$name, $email, $user_id]
            : [$name, $email, $user_id];
        
        $update_stmt->bind_param(str_repeat('s', count($params)-1) . 'i', ...$params);
        $update_stmt->execute();
        
        $success = "Profile updated successfully";
        // Refresh user data
        $user = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOWT ERMS - My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --accent-color: #f6c23e;
            --dark-color: #5a5c69;
            --sidebar-width: 14rem;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color), #224abe);
            transition: all 0.3s;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            margin: 0 0.5rem;
            border-radius: 0.35rem;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        
        .user-profile {
            padding: 1.5rem 1rem;
            text-align: center;
            background: rgba(0, 0, 0, 0.2);
        }
        
        .user-profile img {
            width: 7rem;
            height: 7rem;
            border: 0.3rem solid rgba(255, 255, 255, 0.2);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .profile-card {
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -var(--sidebar-width);
                position: fixed;
                z-index: 1040;
            }
            .sidebar.show {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }

        .footer {
            background-color: #f8f9fa;
            padding: 1.5rem;
            margin-left: var(--sidebar-width);
            border-top: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-text {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .footer {
                margin-left: 0;
            }
        }  
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar position-fixed" id="sidebar">
            <div class="position-sticky pt-3">
                <div class="user-profile">
                    <img src="logo.png" alt="User" class="rounded-circle mb-3 shadow">
                    <h5 class="text-white mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <small class="text-white-50">
                        <?php echo strtoupper($user_type); ?> • 
                        <span class="badge bg-light text-dark"></span>
                    </small>
                </div>
                
                <ul class="nav flex-column mt-4">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="records.php">
                            <i class="bi bi-files"></i> Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="bi bi-chat-dots"></i> Chat
                        </a>
                    </li>
                    <?php if($user_type == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="audit_log.php">
                            <i class="bi bi-journal-text"></i> Audit Log
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content w-100">
            <nav class="navbar navbar-expand navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid">
                    <button class="btn btn-link d-md-none" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="h4 mb-0 text-gray-800">My Profile</h1>
                </div>
            </nav>

            <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card profile-card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="profile.php">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" 
                                               name="<?php echo $user_type == 'admin' ? 'email' : 'email_address'; ?>" 
                                               value="<?php echo htmlspecialchars($user_type == 'admin' ? $user['email'] : $user['email_address']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">User Type</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo ucfirst($user_type); ?>" readonly>
                                    </div>
<div class="col-md-6">
    <label class="form-label">Department</label>
    <input type="text" class="form-control" 
           value="<?php echo !empty($user['department_name']) ? htmlspecialchars($user['department_name']) : 'Not assigned'; ?>" readonly>
</div>
                                </div>
                                <hr>
                                
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card profile-card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Account Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <img src="logo.png" alt="User" class="rounded-circle" width="80">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo ucfirst($user_type); ?></p>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <p class="mb-1"><strong>Last Login:</strong></p>
                                <p><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never logged in'; ?></p>
                            </div>
                            <div class="mb-3">
                                <p class="mb-1"><strong>Account Status:</strong></p>
                                <span class="badge <?php echo ($user['admin_status'] ?? $user['user_status']) == 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $user['admin_status'] ?? $user['user_status']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#sidebarToggle').click(function() {
                $('#sidebar').toggleClass('show');
            });
            
            // Show alert if password fields are filled but don't match
            $('form').submit(function() {
                const current = $('input[name="current_password"]').val();
                const newPass = $('input[name="new_password"]').val();
                const confirm = $('input[name="confirm_password"]').val();
                
                if (current || newPass || confirm) {
                    if (!current) {
                        alert('Please enter your current password to change it');
                        return false;
                    }
                    if (newPass !== confirm) {
                        alert('New passwords do not match');
                        return false;
                    }
                }
                return true;
            });
        });
    </script>
</body>
</html>