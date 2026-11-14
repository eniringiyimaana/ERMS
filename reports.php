<?php
// reports.php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
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

// Get report data
$records_count = $conn->query("SELECT COUNT(*) as total FROM upload_files")->fetch_assoc();
$users_count = $conn->query("SELECT COUNT(*) as total FROM employee_user WHERE user_status = 'Active'")->fetch_assoc();
$recent_uploads = $conn->query("SELECT uf.*, d.department_name FROM upload_files uf LEFT JOIN departments d ON uf.department_id = d.department_id ORDER BY upload_date DESC LIMIT 5");
$user_activity = $conn->query("SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT 10");

// Filter handling
$filter_type = $_GET['type'] ?? 'all';
$filter_date = $_GET['date'] ?? 'month';

// Get filtered data
$filter_conditions = "";
if ($filter_type != 'all') {
    $filter_conditions .= " AND d.department_id = " . intval($filter_type);
}

switch ($filter_date) {
    case 'day':
        $filter_conditions .= " AND uf.upload_date >= CURDATE()";
        break;
    case 'week':
        $filter_conditions .= " AND uf.upload_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $filter_conditions .= " AND uf.upload_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
    case 'year':
        $filter_conditions .= " AND uf.upload_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        break;
}

$filtered_data = $conn->query("SELECT d.department_name, COUNT(*) as count 
                              FROM upload_files uf
                              LEFT JOIN departments d ON uf.department_id = d.department_id
                              WHERE 1=1 $filter_conditions 
                              GROUP BY d.department_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOWT ERMS - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .report-card {
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
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
                        <a class="nav-link" href="messages.php">
                            <i class="bi bi-chat-dots"></i> Chat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="audit_log.php">
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
                    <h1 class="h4 mb-0 text-gray-800">System Reports</h1>
                </div>
            </nav>

            <!-- Filters -->
            <div class="card report-card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="type">
                                <option value="all" <?= $filter_type == 'all' ? 'selected' : '' ?>>All Departments</option>
                                <?php
                                $depts = $conn->query("SELECT * FROM departments ORDER BY department_name");
                                while($dept = $depts->fetch_assoc()): ?>
                                    <option value="<?= $dept['department_id'] ?>" <?= $filter_type == $dept['department_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Time Period</label>
                            <select class="form-select" name="date">
                                <option value="day" <?= $filter_date == 'day' ? 'selected' : '' ?>>Today</option>
                                <option value="week" <?= $filter_date == 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                                <option value="month" <?= $filter_date == 'month' ? 'selected' : '' ?>>Last Month</option>
                                <option value="year" <?= $filter_date == 'year' ? 'selected' : '' ?>>Last Year</option>
                                <option value="all" <?= $filter_date == 'all' ? 'selected' : '' ?>>All Time</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card report-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Records</h6>
                                    <h3 class="mb-0"><?= $records_count['total'] ?></h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-archive text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card report-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Active Users</h6>
                                    <h3 class="mb-0"><?= $users_count['total'] ?></h3>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-people-fill text-success" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card report-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Recent Activity</h6>
                                    <h3 class="mb-0"><?= $user_activity->num_rows ?></h3>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-clock-history text-warning" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card report-card h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Documents by Department</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="docTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card report-card h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Recent Uploads</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php while($upload = $recent_uploads->fetch_assoc()): ?>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($upload['title']) ?></h6>
                                        <small class="text-muted"><?= date('M d', strtotime($upload['upload_date'])) ?></small>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars($upload['department_name'] ?? 'No Department') ?></small>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="card report-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Activity Log</h5>
                    <a href="#" class="btn btn-sm btn-outline-primary">Export</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($activity = $user_activity->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('M d, Y H:i', strtotime($activity['timestamp'])) ?></td>
                                    <td>
                                        <?php 
                                        $user_query = "SELECT name FROM admin WHERE id = ? 
                                                      UNION 
                                                      SELECT name FROM employee_user WHERE id = ?";
                                        $user_stmt = $conn->prepare($user_query);
                                        $user_stmt->bind_param("ii", $activity['user_id'], $activity['user_id']);
                                        $user_stmt->execute();
                                        $user_result = $user_stmt->get_result();
                                        $user = $user_result->fetch_assoc();
                                        echo htmlspecialchars($user['name'] ?? 'System');
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($activity['action_type']) ?></td>
                                    <td>
                                        <?php 
                                        if ($activity['document_id']) {
                                            $doc_query = "SELECT title FROM upload_files WHERE id = ?";
                                            $doc_stmt = $conn->prepare($doc_query);
                                            $doc_stmt->bind_param("i", $activity['document_id']);
                                            $doc_stmt->execute();
                                            $doc_result = $doc_stmt->get_result();
                                            $doc = $doc_result->fetch_assoc();
                                            echo htmlspecialchars($doc['title'] ?? 'N/A');
                                        } else {
                                            echo 'System Action';
                                        }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($activity['ip_address']) ?></td>
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
            $('table').DataTable({
                responsive: true,
                order: [[0, 'desc']]
            });

            // Document Type Chart
            const docTypeCtx = document.getElementById('docTypeChart').getContext('2d');
            const docTypeChart = new Chart(docTypeCtx, {
                type: 'bar',
                data: {
                    labels: [<?php 
                        $filtered_data->data_seek(0);
                        while($row = $filtered_data->fetch_assoc()) {
                            echo "'" . $row['department_name'] . "',";
                        }
                    ?>],
                    datasets: [{
                        label: 'Documents by Department',
                        data: [<?php 
                            $filtered_data->data_seek(0);
                            while($row = $filtered_data->fetch_assoc()) {
                                echo $row['count'] . ",";
                            }
                        ?>],
                        backgroundColor: [
                            'rgba(78, 115, 223, 0.7)',
                            'rgba(28, 200, 138, 0.7)',
                            'rgba(246, 194, 62, 0.7)',
                            'rgba(231, 74, 59, 0.7)',
                            'rgba(102, 16, 242, 0.7)'
                        ],
                        borderColor: [
                            'rgba(78, 115, 223, 1)',
                            'rgba(28, 200, 138, 1)',
                            'rgba(246, 194, 62, 1)',
                            'rgba(231, 74, 59, 1)',
                            'rgba(102, 16, 242, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>