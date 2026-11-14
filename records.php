<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}



$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user details
if ($user_type == 'admin') {
    $query = "SELECT * FROM admin WHERE id = ?";
} else {
    $query = "SELECT * FROM employee_user WHERE id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();




// Function to generate document number based on department
function generateDepartmentDocNo($conn, $department_id) {
    // Get department abbreviation
    $dept_query = $conn->prepare("SELECT SUBSTRING(department_name, 1, 2) as abbrev FROM departments WHERE department_id = ?");
    $dept_query->bind_param("i", $department_id);
    $dept_query->execute();
    $dept_result = $dept_query->get_result();
    $dept_abbrev = strtoupper($dept_result->fetch_assoc()['abbrev']);
    
    // Get next number for this department
    $num_query = $conn->prepare("SELECT MAX(CAST(SUBSTRING(doc_no, 4) AS UNSIGNED)) 
                                FROM upload_files 
                                WHERE doc_no LIKE ?");
    $like_prefix = $dept_abbrev . '-%';
    $num_query->bind_param("s", $like_prefix);
    $num_query->execute();
    $num_result = $num_query->get_result();
    $max_num = $num_result->fetch_row()[0] ?? 0;
    
    return $dept_abbrev . '-' . str_pad($max_num + 1, 3, '0', STR_PAD_LEFT);
}

// Handle document actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new record with enhanced duplicate check
    if (isset($_POST['add_record'])) {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $retention_years = $_POST['retention_years'];
        $creation_date = $_POST['creation_date'];
        $file_name = $_FILES['file']['name'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_size = $_FILES['file']['size'];
        
        // Set department - admin can choose, employee is restricted to their department
        if ($user_type == 'admin') {
            $department_id = $_POST['department_id'];
        } else {
            // Get employee's department
            $dept_stmt = $conn->prepare("SELECT department_id FROM employee_user WHERE id = ?");
            $dept_stmt->bind_param("i", $user_id);
            $dept_stmt->execute();
            $dept_result = $dept_stmt->get_result();
            $dept_data = $dept_result->fetch_assoc();
            $department_id = $dept_data ? $dept_data['department_id'] : null;
            
            if (!$department_id) {
                $_SESSION['error'] = "Your account is not assigned to any department. Please contact administrator.";
                header("Location: records.php");
                exit();
            }
        }
        
        // Enhanced duplicate check - compare file content
        $file_hash = md5_file($file_tmp);
        
        // Check for duplicate by content hash
        $check_hash = $conn->prepare("SELECT id, doc_no FROM upload_files WHERE file_hash = ?");
        $check_hash->bind_param("s", $file_hash);
        $check_hash->execute();
        $hash_result = $check_hash->get_result();
        
        if ($hash_result->num_rows > 0) {
            $existing = $hash_result->fetch_assoc();
            $_SESSION['error'] = "This exact file already exists in the system (Document #" . $existing['doc_no'] . ")";
            header("Location: records.php");
            exit();
        }
        
        // Check for similar metadata (title, author, department)
        $check_meta = $conn->prepare("SELECT id, doc_no FROM upload_files 
                                    WHERE title = ? AND author = ? AND department_id = ?");
        $check_meta->bind_param("ssi", $title, $author, $department_id);
        $check_meta->execute();
        $meta_result = $check_meta->get_result();
        
        if ($meta_result->num_rows > 0) {
            $existing = $meta_result->fetch_assoc();
            $_SESSION['error'] = "A document with similar metadata already exists (Document #" . $existing['doc_no'] . ")";
            header("Location: records.php");
            exit();
        }
        
        // Process file upload
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($file_ext, $allowed_ext)) {
            $_SESSION['error'] = "Invalid file type. Allowed types: PDF, DOC, XLS, JPG, PNG";
            header("Location: records.php");
            exit();
        }
        
        if ($file_size > 10 * 1024 * 1024) { // 10MB
            $_SESSION['error'] = "File size exceeds maximum limit of 10MB";
            header("Location: records.php");
            exit();
        }
        
        $new_filename = uniqid() . '.' . $file_ext;
        $upload_path = 'uploads/' . $new_filename;
        
        if (move_uploaded_file($file_tmp, $upload_path)) {
            $doc_no = generateDepartmentDocNo($conn, $department_id);
            
            $stmt = $conn->prepare("INSERT INTO upload_files 
                                  (doc_no, title, author, retention_years, 
                                  creation_date, file_path, uploaded_by, upload_date, file_hash, department_id)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->bind_param("sssissisi", 
                $doc_no, 
                $title, $author, 
                $retention_years, $creation_date, $upload_path, 
                $user_id, $file_hash, $department_id);
            $stmt->execute();
            
            $_SESSION['success'] = "Document added successfully with document number: " . $doc_no;
            header("Location: records.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to upload file";
            header("Location: records.php");
            exit();
        }
    }
    
    // Handle detention actions
    if (isset($_POST['detain_document'])) {
        $document_id = $_POST['document_id'];
        $reason = $_POST['detention_reason'];
        $expected_date = $_POST['expected_release_date'];
        
        $stmt = $conn->prepare("INSERT INTO document_detentions 
                              (document_id, detained_by, detention_date, reason, expected_release_date, status) 
                              VALUES (?, ?, CURDATE(), ?, ?, 'Active')");
        $stmt->bind_param("iiss", $document_id, $user_id, $reason, $expected_date);
        $stmt->execute();
        
        $conn->query("UPDATE upload_files SET status = 'Detained' WHERE id = $document_id");
        
        $_SESSION['success'] = "Document detained successfully";
        header("Location: records.php");
        exit();
    }
    
    if (isset($_POST['release_document'])) {
        $detention_id = $_POST['detention_id'];
        
        $stmt = $conn->prepare("UPDATE document_detentions 
                              SET actual_release_date = CURDATE(), status = 'Released'
                              WHERE id = ?");
        $stmt->bind_param("i", $detention_id);
        $stmt->execute();
        
        $doc_id = $conn->query("SELECT document_id FROM document_detentions WHERE id = $detention_id")->fetch_assoc()['document_id'];
        $conn->query("UPDATE upload_files SET status = 'Active' WHERE id = $doc_id");
        
        $_SESSION['success'] = "Document released successfully";
        header("Location: records.php");
        exit();
    }
    
    // Handle disposal actions - only for admin users
    if (isset($_POST['initiate_disposal']) && $user_type == 'admin') {
        $document_id = $_POST['disposal_document_id'];
        $disposition_type = $_POST['disposition_type'];
        $method = $_POST['disposal_method'];
        $notes = $_POST['disposal_notes'];
        
        $stmt = $conn->prepare("INSERT INTO document_disposals 
                              (document_id, disposition_type_id, disposed_by, disposal_date, method, notes) 
                              VALUES (?, ?, ?, CURDATE(), ?, ?)");
        $stmt->bind_param("iiiss", $document_id, $disposition_type, $user_id, $method, $notes);
        $stmt->execute();
        
        $conn->query("UPDATE upload_files SET status = 'Pending Disposal' WHERE id = $document_id");
        
        $_SESSION['success'] = "Disposal process initiated successfully";
        header("Location: records.php");
        exit();
    }
}

// Get records with status information
// Get records with status information
// Add this near the top of the file after getting user details
if ($user_type == 'employee') {
    // Get employee's department
    $dept_query = $conn->prepare("SELECT department_id FROM employee_user WHERE id = ?");
    $dept_query->bind_param("i", $user_id);
    $dept_query->execute();
    $dept_result = $dept_query->get_result();
    $dept_data = $dept_result->fetch_assoc();
    
    if (!$dept_data || !isset($dept_data['department_id'])) {
        $_SESSION['error'] = "Your account is not assigned to any department";
        header("Location: dashboard.php");
        exit();
    }
    
    $employee_dept = $dept_data['department_id'];
}

// Modify the records query section (replace the existing query)
if ($user_type == 'admin') {
    $records_query = "SELECT 
        uf.*, 
        COALESCE(adm.name, emp.name) as uploaded_by_name, 
        d.department_name,
        (SELECT status FROM document_detentions WHERE document_id = uf.id AND status = 'Active' LIMIT 1) as detention_status,
        (SELECT status FROM document_disposals WHERE document_id = uf.id AND verification_date IS NULL LIMIT 1) as disposal_status
        FROM upload_files uf
        LEFT JOIN admin adm ON uf.uploaded_by_admin = adm.id
        LEFT JOIN employee_user emp ON uf.uploaded_by_employee = emp.id
        LEFT JOIN departments d ON uf.department_id = d.department_id
        ORDER BY uf.upload_date DESC";
    $records_result = $conn->query($records_query);
} else {
    // Employee can only see their department's documents
    $records_query = "SELECT 
        uf.*, 
        COALESCE(adm.name, emp.name) as uploaded_by_name, 
        d.department_name,
        (SELECT status FROM document_detentions WHERE document_id = uf.id AND status = 'Active' LIMIT 1) as detention_status,
        (SELECT status FROM document_disposals WHERE document_id = uf.id AND verification_date IS NULL LIMIT 1) as disposal_status
        FROM upload_files uf
        LEFT JOIN admin adm ON uf.uploaded_by_admin = adm.id
        LEFT JOIN employee_user emp ON uf.uploaded_by_employee = emp.id
        LEFT JOIN departments d ON uf.department_id = d.department_id
        WHERE uf.department_id = ?
        ORDER BY uf.upload_date DESC";
    
    $stmt = $conn->prepare($records_query);
    $stmt->bind_param("i", $employee_dept);
    $stmt->execute();
    $records_result = $stmt->get_result();
}
// Get disposition types for disposal
$disposition_types = $conn->query("SELECT * FROM disposition_types WHERE is_active = TRUE");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOWT ERMS - Records Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>
        /* Your existing CSS styles remain the same */
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
        
        .badge-detained {
            background-color: #f6c23e;
            color: #000;
        }
        
        .badge-pending-disposal {
            background-color: #e74a3b;
            color: #fff;
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
        <!-- Sidebar (Dashboard Style) -->
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
                        <a class="nav-link active" href="records.php">
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
                        <a class="nav-link" href="audit_log.php">
                            <i class="bi bi-journal-text"></i> Audit Log
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Reports
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
                    
                    <h1 class="h4 mb-0 text-gray-800">Records Management</h1>
                    
                    <div class="d-flex align-items-center">
                        <?php if($user_type == 'admin' || $user_type == 'employee'): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                            <i class="bi bi-plus-circle me-1"></i> Add New Record
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Records Table -->
            <div class="table-responsive">
                <table id="recordsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-dark">
    <tr>
        <th>Doc No</th>
        <th>Title</th>
        <th>Department</th> <!-- Changed from Type -->
        <th>Upload Date</th>
        <th>Status</th>
        <th>Uploaded By</th>
        <th>Actions</th>
    </tr>
</thead>
                    <tbody>
                        <?php while($record = $records_result->fetch_assoc()): 
                            $status = 'Active';
                            $status_badge = 'bg-success';
                            
                            if ($record['detention_status'] == 'Active') {
                                $status = 'Detained';
                                $status_badge = 'badge-detained';
                            } elseif ($record['disposal_status'] != NULL) {
                                $status = 'Pending Disposal';
                                $status_badge = 'badge-pending-disposal';
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['doc_no']); ?></td>
                            <td><?php echo htmlspecialchars($record['title']); ?></td>
                            <td><?php echo htmlspecialchars($record['department_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['upload_date'])); ?></td>
                            <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status; ?></span></td>
                            <td><?php echo htmlspecialchars($record['uploaded_by_name'] ?? 'System'); ?></td>
                      <td>
    <div class="btn-group" role="group">
        <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info" title="View">
            <i class="bi bi-eye"></i>
        </a>
        <a href="print_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-secondary" title="Print" target="_blank">
            <i class="bi bi-printer"></i>
        </a>
        
        <?php if($user_type == 'admin'): ?>
            <?php if($status == 'Active'): ?>
                <button class="btn btn-sm btn-warning" title="Detain" data-bs-toggle="modal" 
                        data-bs-target="#detainModal<?php echo $record['id']; ?>">
                    <i class="bi bi-unlock"></i>
                </button>
            <?php elseif($status == 'Detained'): ?>
                <button class="btn btn-sm btn-success" title="Release" data-bs-toggle="modal" 
                        data-bs-target="#releaseModal<?php echo $record['id']; ?>">
                    <i class="bi bi-lock"></i>
                </button>
            <?php endif; ?>
            
            <?php if($status == 'Active'): ?>
                <button class="btn btn-sm btn-danger" title="Initiate Disposal" data-bs-toggle="modal" 
                        data-bs-target="#disposeModal<?php echo $record['id']; ?>">
                    <i class="bi bi-trash"></i>
                </button>
            <?php elseif($status == 'Pending Disposal'): ?>
                <form method="POST" action="approve_disposal.php" style="display: inline;">
                    <input type="hidden" name="document_id" value="<?php echo $record['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger" title="Approve Disposal" onclick="return confirm('Are you sure you want to permanently delete this document?')">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </form>
            <?php endif; ?>
            
            <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>
        <?php endif; ?>
    </div>
                                
                                <!-- Detain Modal -->
                                <div class="modal fade" id="detainModal<?php echo $record['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title">Detain Document</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="document_id" value="<?php echo $record['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Reason for Detention</label>
                                                        <textarea name="detention_reason" class="form-control" rows="3" required></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Expected Release Date</label>
                                                        <input type="date" name="expected_release_date" class="form-control" required 
                                                               min="<?php echo date('Y-m-d'); ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="detain_document" class="btn btn-warning">
                                                        <i class="bi bi-unlock"></i> Detain Document
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Release Modal -->
                                <div class="modal fade" id="releaseModal<?php echo $record['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title">Release Document</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <?php 
                                                    $detention = $conn->query("SELECT id FROM document_detentions 
                                                                             WHERE document_id = {$record['id']} AND status = 'Active'")->fetch_assoc();
                                                    ?>
                                                    <input type="hidden" name="detention_id" value="<?php echo $detention['id']; ?>">
                                                    <p>Are you sure you want to release this document?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="release_document" class="btn btn-success">
                                                        <i class="bi bi-lock"></i> Release Document
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Dispose Modal -->
                                <div class="modal fade" id="disposeModal<?php echo $record['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Initiate Document Disposal</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="disposal_document_id" value="<?php echo $record['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Disposition Type</label>
                                                        <select name="disposition_type" class="form-select" required>
                                                            <option value="">Select Disposition Type</option>
                                                            <?php while ($type = $disposition_types->fetch_assoc()): ?>
                                                                <option value="<?php echo $type['id']; ?>">
                                                                    <?php echo htmlspecialchars($type['name']); ?>
                                                                </option>
                                                            <?php endwhile; 
                                                            $disposition_types->data_seek(0); // Reset pointer for next row
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Disposal Method</label>
                                                        <select name="disposal_method" class="form-select" required>
                                                            <option value="">Select Method</option>
                                                            <option value="Shredding">Shredding</option>
                                                            <option value="Digital Erasure">Digital Erasure</option>
                                                            <option value="Physical Destruction">Physical Destruction</option>
                                                            <option value="Transfer">Transfer</option>
                                                            <option value="Other">Other</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Notes</label>
                                                        <textarea name="disposal_notes" class="form-control" rows="3"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="initiate_disposal" class="btn btn-danger">
                                                        <i class="bi bi-trash"></i> Initiate Disposal
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Record Modal -->
    <!-- Add Record Modal -->
<!-- Add Record Modal -->
<div class="modal fade" id="addRecordModal" tabindex="-1" aria-labelledby="addRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addRecordModalLabel">Add New Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="add_record.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="docNo" class="form-label">Document Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="docNo" name="doc_no" readonly required>
                        <div class="form-text">Auto-generated based on department</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="recordTitle" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="recordTitle" name="title" required>
                    </div>
                    
                    <!-- Department Field (different for admin vs employee) -->
                    <?php if($_SESSION['user_type'] == 'admin'): ?>
                    <div class="mb-3">
                        <label for="recordDepartment" class="form-label">Department <span class="text-danger">*</span></label>
                        <select class="form-select" id="recordDepartment" name="department_id" required onchange="updateDocNo()">
                            <option value="">Select Department</option>
                            <?php 
                            $depts = $conn->query("SELECT * FROM departments ORDER BY department_name");
                            while($dept = $depts->fetch_assoc()): ?>
                                <option value="<?php echo $dept['department_id']; ?>">
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <?php
                        // Get employee's department name
                        $dept_query = $conn->prepare("SELECT d.department_name 
                                                     FROM departments d
                                                     JOIN employee_user eu ON d.department_id = eu.department_id
                                                     WHERE eu.id = ?");
                        $dept_query->bind_param("i", $_SESSION['user_id']);
                        $dept_query->execute();
                        $dept_result = $dept_query->get_result();
                        $dept_name = $dept_result->fetch_assoc()['department_name'];
                        ?>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($dept_name); ?>" readonly>
                        <input type="hidden" name="department_id" value="<?php echo $employee_dept; ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="recordAuthor" class="form-label">Creator <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="recordAuthor" name="author" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="retentionYears" class="form-label">Retention Years <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="retentionYears" name="retention_years" value="5" min="1" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="creationDate" class="form-label">Creation Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="creationDate" name="creation_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="recordFile" class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="recordFile" name="file" required 
                               onchange="checkFileDuplicate(this)">
                        <div class="form-text">Maximum file size: 10MB. Allowed types: PDF, DOC, XLS, JPG, PNG</div>
                        <div id="fileDuplicateWarning" class="text-danger d-none mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_record" class="btn btn-primary">Save Record</button>
                </div>
            </form>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle sidebar
            $('#sidebarToggle').click(function() {
                $('#sidebar').toggleClass('show');
            });
            
            // Initialize DataTable
            $('#recordsTable').DataTable({
                responsive: true,
                dom: '<"top"lf>rt<"bottom"ip>',
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search records...",
                    lengthMenu: "Show _MENU_ records per page",
                    zeroRecords: "No records found",
                    info: "Showing _START_ to _END_ of _TOTAL_ records",
                    infoEmpty: "No records available",
                    infoFiltered: "(filtered from _MAX_ total records)"
                },
                columnDefs: [
                    { orderable: false, targets: -1 } // Disable sorting for actions column
                ]
            });
            
            // Set creation date to today by default
            document.getElementById('creationDate').valueAsDate = new Date();
        });

        // Function to update document number based on selected type
        function updateDocNo() {
            const docType = document.getElementById('recordType').value;
            if (docType) {
                const prefix = docType.substring(0, 2).toUpperCase();
                document.getElementById('docNo').value = prefix + '-001';
            }
        }

        // Function to check for duplicate files
        function checkFileDuplicate(input) {
            const file = input.files[0];
            if (!file) return;
            
            const warningDiv = document.getElementById('fileDuplicateWarning');
            warningDiv.classList.add('d-none');
            
            // Check file size first
            if (file.size > 10 * 1024 * 1024) {
                warningDiv.textContent = "File size exceeds maximum limit of 10MB";
                warningDiv.classList.remove('d-none');
                return;
            }
            
            // Check file extension
            const validExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
            const fileExt = file.name.split('.').pop().toLowerCase();
            if (!validExtensions.includes(fileExt)) {
                warningDiv.textContent = "Invalid file type. Allowed types: PDF, DOC, XLS, JPG, PNG";
                warningDiv.classList.remove('d-none');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                // Calculate MD5 hash of the file
                const hash = CryptoJS.MD5(CryptoJS.lib.WordArray.create(e.target.result)).toString();
                
                // Check with server
                fetch('check_duplicate.php?hash=' + hash)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            warningDiv.textContent = `Warning: This file already exists as Document #${data.doc_no}`;
                            warningDiv.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking file duplicate:', error);
                    });
            };
            reader.readAsArrayBuffer(file);
        }
    </script>

    <script>
// Function to update document number based on selected department
function updateDocNo() {
    const deptSelect = document.getElementById('recordDepartment');
    const deptId = deptSelect.value;
    if (deptId) {
        // AJAX call to get next document number for this department
        fetch('get_next_docno.php?department_id=' + deptId)
            .then(response => response.text())
            .then(docNo => {
                document.getElementById('docNo').value = docNo;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    } else {
        document.getElementById('docNo').value = '';
    }
}

// Function to check for duplicate files
function checkFileDuplicate(input) {
    const file = input.files[0];
    if (!file) return;
    
    const warningDiv = document.getElementById('fileDuplicateWarning');
    warningDiv.classList.add('d-none');
    
    // Check file size first
    if (file.size > 10 * 1024 * 1024) {
        warningDiv.textContent = "File size exceeds maximum limit of 10MB";
        warningDiv.classList.remove('d-none');
        return;
    }
    
    // Check file extension
    const validExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
    const fileExt = file.name.split('.').pop().toLowerCase();
    if (!validExtensions.includes(fileExt)) {
        warningDiv.textContent = "Invalid file type. Allowed types: PDF, DOC, XLS, JPG, PNG";
        warningDiv.classList.remove('d-none');
        return;
    }
    
    // Calculate file hash for duplicate checking
    const reader = new FileReader();
    reader.onload = function(e) {
        const hash = CryptoJS.MD5(CryptoJS.lib.WordArray.create(e.target.result)).toString();
        
        fetch('check_duplicate.php?hash=' + hash)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    warningDiv.textContent = `Warning: This file already exists as Document #${data.doc_no}`;
                    warningDiv.classList.remove('d-none');
                }
            })
            .catch(error => {
                console.error('Error checking file duplicate:', error);
            });
    };
    reader.readAsArrayBuffer(file);
}

// Set creation date to today by default
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('creationDate').valueAsDate = new Date();
    
    // For employees, auto-set their department's doc number
    <?php if($_SESSION['user_type'] != 'admin'): ?>
        fetch('get_next_docno.php?department_id=<?php echo $employee_dept; ?>')
            .then(response => response.text())
            .then(docNo => {
                document.getElementById('docNo').value = docNo;
            });
    <?php endif; ?>
});
</script>

</body>
</html>