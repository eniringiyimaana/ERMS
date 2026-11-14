<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['user_type'] != 'admin') {
    $_SESSION['access_error'] = "You don't have permission to access the Audit Log";
    header("Location: dashboard.php");
    exit();
}


$user_id = $_SESSION['user_id'];

// Get admin details
$query = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Handle filters
$action_type = $_GET['action_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$user_filter = $_GET['user_id'] ?? '';

// Build base query
$query = "SELECT al.*, 
          COALESCE(a.name, eu.name) AS user_name,
          uf.title AS document_title
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

// Get users for filter dropdown
$users_query = "SELECT id, name FROM admin
                UNION
                SELECT id, name FROM employee_user
                ORDER BY name";
$users_result = $conn->query($users_query);

// Get action types for filter dropdown
$actions_result = $conn->query("SELECT DISTINCT action_type FROM AUDIT_LOG ORDER BY action_type");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOWT ERMS - Audit Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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
        
        .log-card {
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .badge-login {
            background-color: var(--secondary-color);
        }
        .badge-create {
            background-color: #36b9cc;
        }
        .badge-update {
            background-color: var(--accent-color);
        }
        .badge-delete {
            background-color: #e74a3b;
        }
        .badge-system {
            background-color: #6c757d;
        }

        .badge {
    color: orange;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 600;
    border-radius: 0.25rem;
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
                    <h5 class="text-white mb-1"><?php echo htmlspecialchars($admin['name']); ?></h5>
                    <small class="text-white-50">
                        ADMIN • 
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
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="bi bi-chat-dots"></i> Chat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="audit_log.php">
                            <i class="bi bi-journal-text"></i> Audit Log
                        </a>
                    </li>
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
            <nav class="navbar navbar-expand navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid">
                    <button class="btn btn-link d-md-none" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="h4 mb-0 text-gray-800">Audit Log</h1>
                </div>
            </nav>

            <!-- Filters -->
            <div class="card log-card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Action Type</label>
                            <select class="form-select" name="action_type">
                                <option value="">All Actions</option>
                                <?php while($action = $actions_result->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($action['action_type']) ?>" 
                                    <?= $action_type == $action['action_type'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($action['action_type']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <select class="form-select" name="user_id">
                                <option value="">All Users</option>
                                <?php while($user = $users_result->fetch_assoc()): ?>
                                <option value="<?= $user['id'] ?>" 
                                    <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="audit_log.php" class="btn btn-secondary">Reset</a>
                            <a href="export_audit.php?<?= http_build_query($_GET) ?>" class="btn btn-success float-end">
                                <i class="bi bi-download"></i> Export
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Audit Log Table -->
            <div class="card log-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="auditLogTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Document</th>
                                    <th>IP Address</th>
                                   
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($log = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('M d, Y H:i', strtotime($log['timestamp'])) ?></td>
                                    <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= strpos($log['action_type'], 'Login') !== false ? 'badge-login' : '' ?>
                                            <?= strpos($log['action_type'], 'Create') !== false ? 'badge-create' : '' ?>
                                            <?= strpos($log['action_type'], 'Update') !== false ? 'badge-update' : '' ?>
                                            <?= strpos($log['action_type'], 'Delete') !== false ? 'badge-delete' : '' ?>
                                            <?= $log['user_id'] == 0 ? 'badge-system' : '' ?>">
                                            <?= htmlspecialchars($log['action_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($log['document_id']): ?>
                                            <a href="view_record.php?id=<?= $log['document_id'] ?>">
                                                <?= htmlspecialchars($log['document_title'] ?? 'Document #' . $log['document_id']) ?>
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                    
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

   
    

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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#sidebarToggle').click(function() {
                $('#sidebar').toggleClass('show');
            });

            // Initialize DataTable
            $('#auditLogTable').DataTable({
                responsive: true,
                order: [[0, 'desc']],
                dom: '<"top"lf>rt<"bottom"ip>',
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search audit log...",
                    lengthMenu: "Show _MENU_ entries per page",
                    zeroRecords: "No matching records found",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No entries available",
                    infoFiltered: "(filtered from _MAX_ total entries)"
                }
            });

            
        });
    </script>
</body>
</html>