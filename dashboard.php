<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['access_error'] = "Please login to access this page";
    header("Location: index.php");
    exit();
}

// Store session values
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user details with permissions
if ($user_type == 'admin') {
    $query = "SELECT a.*, r.permissions FROM admin a 
              JOIN user_roles r ON a.role_id = r.role_id 
              WHERE a.id = ?";
} else {
    $query = "SELECT e.*, r.permissions FROM employee_user e 
              JOIN user_roles r ON e.role_id = r.role_id 
              WHERE e.id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['access_error'] = "User account not found or inactive";
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();
$permissions = json_decode($user['permissions'], true);

// Permission check function
function checkPermission($requiredPermission, $permissions) {
    if (!isset($permissions[$requiredPermission])) {
        $_SESSION['access_error'] = "You don't have permission to access this feature";
        return false;
    }
    return true;
}

// Log access
$action = "Dashboard Access";
$ip = $_SERVER['REMOTE_ADDR'];
$host = gethostbyaddr($ip);
$email = ($user_type == 'admin') ? $user['email'] : $user['email_address'];

$log_query = "INSERT INTO history_log (user_id, user_type, email_address, action, ip, host, login_time) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
$log_stmt = $conn->prepare($log_query);
$log_stmt->bind_param("isssss", $user_id, $user_type, $email, $action, $ip, $host);
$log_stmt->execute();

// Get records statistics
// First get all departments
$depts_query = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$depts_result = $conn->query($depts_query);
$departments = $depts_result->fetch_all(MYSQLI_ASSOC);

// Build query dynamically
$stats_query = "SELECT COUNT(*) as total_records, ";
foreach ($departments as $dept) {
    $alias = strtolower(preg_replace('/[^a-z]/i', '_', $dept['department_name']));
    $stats_query .= "SUM(CASE WHEN d.department_id = {$dept['department_id']} THEN 1 ELSE 0 END) as {$alias}_documents, ";
}
$stats_query .= "SUM(CASE WHEN uf.upload_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as recent_uploads,
                SUM(CASE WHEN d.department_id IS NULL THEN 1 ELSE 0 END) as uncategorized
                FROM upload_files uf
                LEFT JOIN departments d ON uf.department_id = d.department_id";

// Execute query
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
// Get unread message count
$unread_query = "SELECT COUNT(*) as unread FROM message_status ms
                JOIN messages m ON ms.message_id = m.id
                JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
                WHERE cp.user_id = ? AND cp.user_type = ?
                AND ms.user_id = ? AND ms.user_type = ?
                AND ms.status = 'delivered'";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("isis", $user_id, $user_type, $user_id, $user_type);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread = $unread_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOWT ERMS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
        
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 700;
        }
        
        .sidebar .nav-link.text-muted {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .sidebar .nav-link.text-muted:hover {
            background-color: transparent !important;
            transform: none !important;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
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
            transition: all 0.3s;
        }
        
        .user-profile img:hover {
            transform: scale(1.05);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .dashboard-card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: all 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .card-primary {
            background: linear-gradient(45deg, var(--primary-color), #2e59d9);
            border-left: 0.25rem solid var(--primary-color);
        }
        
        .card-success {
            background: linear-gradient(45deg, var(--secondary-color), #17a673);
            border-left: 0.25rem solid var(--secondary-color);
        }
        
        .card-warning {
            background: linear-gradient(45deg, var(--accent-color), #da9e01);
            border-left: 0.25rem solid var(--accent-color);
        }
        
        .card-icon {
            font-size: 2rem;
            opacity: 0.3;
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
        }
        
        .activity-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .activity-item {
            position: relative;
            padding-left: 1.5rem;
            border-left: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }
        
        .activity-item:before {
            content: "";
            position: absolute;
            left: -0.5rem;
            top: 0;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        
        .chart-area {
            position: relative;
            height: 10rem;
            width: 100%;
        }
        
        .notification-container {
            max-width: 400px;
        }
        
        .alert {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        @media (min-width: 768px) {
            .chart-area {
                height: 20rem;
            }
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
            
            .mobile-menu-btn {
                display: block !important;
            }
        }
        
        .animate-pop-in {
            animation: popIn 0.5s ease-out forwards;
            opacity: 0;
            transform: scale(0.8);
        }
        
        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

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
    <!-- Notification System -->
    <div class="notification-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <?php if (isset($_SESSION['access_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['access_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['access_error']); ?>
        <?php endif; ?>
    </div>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar position-fixed" id="sidebar">
            <div class="position-sticky pt-3">
                <div class="user-profile">
                    <img src="logo.png" alt="User" class="rounded-circle mb-3 shadow">
                    <h5 class="text-white mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <small class="text-white-50">
                        <?php echo strtoupper($user_type); ?> • 
                        <?php echo date('M Y'); ?>
                    </small>
                </div>
                
                <ul class="nav flex-column mt-4">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if (checkPermission('records', $permissions)): ?>
                            <a class="nav-link" href="records.php">
                                <i class="bi bi-files"></i> Records
                            </a>
                        <?php else: ?>
                            <a class="nav-link text-muted" href="#" 
                               onclick="showAccessError()" data-bs-toggle="tooltip" 
                               title="You don't have permission to access records">
                                <i class="bi bi-files"></i> Records
                            </a>
                        <?php endif; ?>
                    </li>
                    
                    <!-- Messages Link -->
                    <?php if (checkPermission('messages', $permissions)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="bi bi-chat-dots"></i> Chat
                            <?php if ($unread['unread'] > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $unread['unread']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-muted" href="#" 
                           onclick="showAccessError()" data-bs-toggle="tooltip" 
                           title="You don't have permission to access messages">
                            <i class="bi bi-chat-dots"></i> System Email
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- User Management -->
                    <?php if($user_type == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> User Management
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-muted" href="#" 
                           onclick="showAccessError()" data-bs-toggle="tooltip" 
                           title="Admin access required">
                            <i class="bi bi-people"></i> User Management
                        </a>
                    </li>
                    
                    <?php endif; ?>
                    
                    <!-- Reports - Always disabled for employees -->
                    <?php if($user_type == 'admin' && checkPermission('reports', $permissions)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-muted" href="#" 
                           onclick="showAccessError()" data-bs-toggle="tooltip" 
                           title="Reports access requires admin privileges">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Audit Log - Only for admins -->
<?php if($user_type == 'admin'): ?>
<li class="nav-item">
    <a class="nav-link" href="audit_log.php">
        <i class="bi bi-journal-text"></i> Audit Log
    </a>
</li>
<?php else: ?>
<li class="nav-item">
    <a class="nav-link text-muted" href="#" 
       onclick="showAccessError()" data-bs-toggle="tooltip" 
       title="Audit Log access requires admin privileges">
        <i class="bi bi-journal-text"></i> Audit Log
    </a>
</li>
<?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
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
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid">
                    <button class="btn btn-link d-md-none" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <h1 class="h4 mb-0 text-gray-800">Dashboard</h1>
                    
                    <div class="d-flex align-items-center">
                        <div class="dropdown me-3">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-calendar3 me-1"></i> This Week
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Today</a></li>
                                <li><a class="dropdown-item" href="#">This Week</a></li>
                                <li><a class="dropdown-item" href="#">This Month</a></li>
                            </ul>
                        </div>
                        
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                Quick Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (checkPermission('records', $permissions)): ?>
                                <li><a class="dropdown-item" href="records.php?action=add">
                                    <i class="bi bi-plus-circle me-2"></i>Add Record
                                </a></li>
                                <?php endif; ?>
                                <?php if($user_type == 'admin'): ?>
                                <li><a class="dropdown-item" href="users.php?action=add">
                                    <i class="bi bi-person-plus me-2"></i>Add User
                                </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <?php if($user_type == 'admin' && checkPermission('reports', $permissions)): ?>
                                <li><a class="dropdown-item" href="reports.php">
                                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Report
                                </a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Cards -->
            <div class="container-fluid">
                <div class="row g-4">
                    <!-- Total Records Card -->
                    <div class="col-xl-4 col-md-6 animate-pop-in delay-1">
                        <div class="card dashboard-card card-primary text-white h-100">
                            <div class="card-body position-relative">
                                <i class="bi bi-archive card-icon"></i>
                                <h5 class="card-title text-uppercase mb-3">Total Records</h5>
                                <h2 class="card-text display-4 fw-bold mb-0"><?php echo $stats['total_records']; ?></h2>
                                <div class="mt-2">
                                    <span class="text-white-50 small">All system records</span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <?php if (checkPermission('records', $permissions)): ?>
                                <a href="records.php" class="text-white stretched-link small">
                                    View all records <i class="bi bi-arrow-right"></i>
                                </a>
                                <?php else: ?>
                                <a href="#" onclick="showAccessError()" class="text-white stretched-link small">
                                    View all records <i class="bi bi-arrow-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Users Card -->
                    <div class="col-xl-4 col-md-6 animate-pop-in delay-2">
                        <div class="card dashboard-card card-success text-white h-100">
                            <div class="card-body position-relative">
                                <i class="bi bi-people card-icon"></i>
                                <h5 class="card-title text-uppercase mb-3">Active Users</h5>
                                <?php
                                $user_query = "SELECT COUNT(*) as total FROM employee_user WHERE user_status = 'Active'";
                                $user_result = $conn->query($user_query);
                                $user_count = $user_result->fetch_assoc();
                                ?>
                                <h2 class="card-text display-4 fw-bold mb-0"><?php echo $user_count['total']; ?></h2>
                                <div class="mt-2">
                                    <span class="text-white-50 small">Currently active users</span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <?php if ($user_type == 'admin'): ?>
                                <a href="users.php" class="text-white stretched-link small">
                                    Manage users <i class="bi bi-arrow-right"></i>
                                </a>
                                <?php else: ?>
                                <a href="#" onclick="showAccessError()" class="text-white stretched-link small">
                                    Manage users <i class="bi bi-arrow-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activities Card - Always visible to admins -->
                    <div class="col-xl-4 col-md-6 animate-pop-in delay-3">
                        <div class="card dashboard-card card-warning text-white h-100">
                            <div class="card-body position-relative">
                                <i class="bi bi-clock-history card-icon"></i>
                                <h5 class="card-title text-uppercase mb-3">Recent Activities</h5>
                                <div class="activity-list">
                                    <?php
                                    $activity_query = "SELECT * FROM AUDIT_LOG ORDER BY timestamp DESC LIMIT 5";
                                    $activity_result = $conn->query($activity_query);
                                    ?>
                                    <?php while($activity = $activity_result->fetch_assoc()): ?>
                                    <div class="activity-item text-white">
                                        <div class="small"><?php echo htmlspecialchars($activity['action_type']); ?></div>
                                        <div class="small text-white-50">
                                            <?php echo date('M j, g:i A', strtotime($activity['timestamp'])); ?>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <?php if ($user_type == 'admin'): ?>
                                <a href="audit_log.php" class="text-white stretched-link small">
                                    View full log <i class="bi bi-arrow-right"></i>
                                </a>
                                <?php else: ?>
                                <a href="#" onclick="showAccessError()" class="text-white stretched-link small">
                                    View full log <i class="bi bi-arrow-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Records Overview Section -->
                <div class="row mt-4">
                    <div class="col-lg-12 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Records Overview</h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        Filter
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" onclick="filterRecords('week')">This Week</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="filterRecords('month')">This Month</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="filterRecords('year')">This Year</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="filterRecords('all')">All Time</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Statistics Cards -->
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-left-primary shadow-sm h-100 py-2">
                                                    <div class="card-body">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                                    Total Records</div>
                                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                    <?php echo $stats['total_records']; ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-auto">
                                                                <i class="bi bi-archive fa-2x text-gray-300"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-left-success shadow-sm h-100 py-2">
                                                    <div class="card-body">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                                    Recent Uploads (7 days)</div>
                                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                    <?php echo $stats['recent_uploads']; ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-auto">
                                                                <i class="bi bi-upload fa-2x text-gray-300"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-12 mb-3">
    <div class="card shadow-sm h-100">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Records by Department</h6>
        </div>
        <div class="card-body">
            <div class="chart-pie pt-4 pb-2">
                <canvas id="departmentChart"></canvas>
            </div>
            <div class="mt-4 text-center small">
                <span class="mr-2">
                    <i class="fas fa-circle text-primary"></i> Human Resources (<?php echo $stats['hr_documents'] ?? 0; ?>)
                </span>
                <span class="mr-2">
                    <i class="fas fa-circle text-success"></i> Accounts (<?php echo $stats['accounts_documents'] ?? 0; ?>)
                </span>
                <span class="mr-2">
                    <i class="fas fa-circle text-info"></i> UNRA (<?php echo $stats['unra_documents'] ?? 0; ?>)
                </span>
                <span class="mr-2">
                    <i class="fas fa-circle text-warning"></i> Registry (<?php echo $stats['registry_documents'] ?? 0; ?>)
                </span>
                <span class="mr-2">
                    <i class="fas fa-circle text-danger"></i> Uncategorized (<?php echo $stats['uncategorized'] ?? 0; ?>)
                </span>
            </div>
        </div>
    </div>
</div>


                                        </div>
                                    </div>
                                    
                                    <!-- Recent Records -->
                                    <!-- Recent Records -->
<div class="col-md-4">
    <div class="card shadow-sm h-100">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recently Added</h6>
        </div>
        <div class="card-body">
            <?php
            // Initialize with empty array
            $recent_uploads = [];
            
            try {
                // Get recent records
                $recent_query = "SELECT uf.id, uf.title, uf.document_type, uf.upload_date, 
                                COALESCE(eu.name, a.name) as uploaded_by 
                                FROM upload_files uf
                                LEFT JOIN employee_user eu ON uf.uploaded_by = eu.id
                                LEFT JOIN admin a ON uf.uploaded_by = a.id
                                ORDER BY uf.upload_date DESC LIMIT 5";
                
                $recent_result = $conn->query($recent_query);
                
                if ($recent_result) {
                    $recent_uploads = $recent_result->fetch_all(MYSQLI_ASSOC);
                }
            } catch (Exception $e) {
                error_log("Error fetching recent uploads: " . $e->getMessage());
            }
            ?>
            
            <div class="list-group list-group-flush">
                <?php if (!empty($recent_uploads)): ?>
                    <?php foreach ($recent_uploads as $recent): ?>
                        <?php if (checkPermission('records', $permissions)): ?>
                            <a href="view_record.php?id=<?php echo htmlspecialchars($recent['id'] ?? ''); ?>" 
                               class="list-group-item list-group-item-action flex-column align-items-start">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($recent['title'] ?? 'Untitled'); ?></h6>
                                    <small><?php echo isset($recent['upload_date']) ? date('M d', strtotime($recent['upload_date'])) : 'N/A'; ?></small>
                                </div>
                                <p class="mb-1 small text-muted">
                                    <?php echo htmlspecialchars($recent['document_type'] ?? 'No type specified'); ?>
                                </p>
                                <small class="text-muted">Uploaded by <?php echo htmlspecialchars($recent['uploaded_by'] ?? 'System'); ?></small>
                            </a>
                        <?php else: ?>
                            <div class="list-group-item flex-column align-items-start">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($recent['title'] ?? 'Untitled'); ?></h6>
                                    <small><?php echo isset($recent['upload_date']) ? date('M d', strtotime($recent['upload_date'])) : 'N/A'; ?></small>
                                </div>
                                <p class="mb-1 small text-muted">
                                    <?php echo htmlspecialchars($recent['document_type'] ?? 'No type specified'); ?>
                                </p>
                                <small class="text-muted">Uploaded by <?php echo htmlspecialchars($recent['uploaded_by'] ?? 'System'); ?></small>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">No recent uploads available or unable to load data.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer bg-transparent">
            <?php if (checkPermission('records', $permissions)): ?>
                <a href="records.php" class="btn btn-sm btn-primary float-end">View All Records</a>
            <?php else: ?>
                <button class="btn btn-sm btn-secondary float-end" onclick="showAccessError()">View All Records</button>
            <?php endif; ?>
        </div>
    </div>
</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Dashboard Content -->
                <div class="row mt-4">
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Records Activity</h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        Filter
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#">This Week</a></li>
                                        <li><a class="dropdown-item" href="#">This Month</a></li>
                                        <li><a class="dropdown-item" href="#">This Year</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="recordsActivityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <?php if (checkPermission('records', $permissions)): ?>
                                    <a href="records.php?action=add" class="btn btn-primary btn-block">
                                        <i class="bi bi-plus-circle me-2"></i> Add New Record
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-primary btn-block" onclick="showAccessError()">
                                        <i class="bi bi-plus-circle me-2"></i> Add New Record
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if($user_type == 'admin'): ?>
                                    <a href="users.php?action=add" class="btn btn-success btn-block">
                                        <i class="bi bi-person-plus me-2"></i> Add New User
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-success btn-block" onclick="showAccessError()">
                                        <i class="bi bi-person-plus me-2"></i> Add New User
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if($user_type == 'admin' && checkPermission('reports', $permissions)): ?>
                                    <a href="reports.php" class="btn btn-info btn-block">
                                        <i class="bi bi-file-earmark-bar-graph me-2"></i> Generate Report
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-info btn-block" onclick="showAccessError()">
                                        <i class="bi bi-file-earmark-bar-graph me-2"></i> Generate Report
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="profile.php" class="btn btn-warning btn-block">
                                        <i class="bi bi-gear me-2"></i> Update Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="footer-content">
                <div class="footer-text">
                    &copy; <?php echo date('Y'); ?> Ministry of Works and Transport — All Rights Reserved.
                </div>
                <div class="footer-text">
                    Designed and Developed with Excellence for Government e-Records Modernization.
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Show access error notification
        function showAccessError() {
            // Create and show a Bootstrap alert
            const notificationContainer = document.querySelector('.notification-container');
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.setAttribute('role', 'alert');
            alertDiv.innerHTML = `
                You don't have permission to access this feature
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            notificationContainer.appendChild(alertDiv);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
            
            return false;
        }

        // Records by Type Chart
       document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('departmentChart').getContext('2d');
    const departmentChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Human Resources', 'Accounts', 'UNRA', 'Registry', 'Uncategorized'],
            datasets: [{
                data: [
                    <?php echo $stats['hr_documents'] ?? 0; ?>,
                    <?php echo $stats['accounts_documents'] ?? 0; ?>,
                    <?php echo $stats['unra_documents'] ?? 0; ?>,
                    <?php echo $stats['registry_documents'] ?? 0; ?>,
                    <?php echo $stats['uncategorized'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#4e73df',  // HR - blue
                    '#1cc88a',  // Accounts - green
                    '#36b9cc',  // UNRA - teal
                    '#f6c23e',  // Registry - yellow
                    '#e74a3b'   // Uncategorized - red
                ],
                hoverBackgroundColor: [
                    '#2e59d9',
                    '#17a673',
                    '#2c9faf',
                    '#dda20a',
                    '#be2617'
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '70%',
        },
    });
});
        // Records Activity Chart (Monthly Uploads)
        const activityCtx = document.getElementById('recordsActivityChart').getContext('2d');
        const recordsActivityChart = new Chart(activityCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Records Uploaded',
                    data: [12, 19, 15, 27, 24, 18],
                    backgroundColor: 'rgba(78, 115, 223, 0.5)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' records';
                            }
                        }
                    }
                }
            }
        });

        // Filter function
        function filterRecords(timeframe) {
            // This would typically make an AJAX call to update the data
            console.log('Filtering for:', timeframe);
            // Implement your filtering logic here
        }

        // Animate elements when they come into view
        const animateOnScroll = function() {
            const elements = document.querySelectorAll('.animate-pop-in');
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.3;
                
                if(elementPosition < screenPosition) {
                    element.style.opacity = '1';
                    element.style.transform = 'scale(1)';
                }
            });
        };
        
        window.addEventListener('scroll', animateOnScroll);
        document.addEventListener('DOMContentLoaded', animateOnScroll);
    </script>

    




</body>
</html>