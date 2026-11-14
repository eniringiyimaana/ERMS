<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$record_id = intval($_GET['id']);

// Get record details with department info
$query = "SELECT 
    uf.*, 
    COALESCE(adm.name, emp.name) as uploaded_by_name,
    d.department_name, 
    d.location as department_location,
    d.contact_email as department_email,
    (SELECT status FROM document_detentions WHERE document_id = uf.id AND status = 'Active' LIMIT 1) as detention_status
    FROM upload_files uf
    LEFT JOIN admin adm ON uf.uploaded_by_admin = adm.id
    LEFT JOIN employee_user emp ON uf.uploaded_by_employee = emp.id
    LEFT JOIN departments d ON uf.department_id = d.department_id
    WHERE uf.id = ?";

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

// Check if document is detained and user is not admin
if ($record['status'] == 'Detained' && $_SESSION['user_type'] != 'admin') {
    $_SESSION['error'] = "You don't have permission to view this detained document";
    header("Location: records.php");
    exit();
}

// For employees: Check if they belong to the document's department
if ($_SESSION['user_type'] == 'employee') {
    $employee_dept = $conn->query("SELECT department_id FROM employee_user WHERE id = {$_SESSION['user_id']}")->fetch_assoc()['department_id'];
    if ($record['department_id'] != $employee_dept) {
        $_SESSION['error'] = "You don't have permission to view this document";
        header("Location: records.php");
        exit();
    }
}

// Log this view
$log_query = "INSERT INTO audit_log (user_id, action_type, document_id, ip_address, timestamp) 
              VALUES (?, 'View', ?, ?, NOW())";
$log_stmt = $conn->prepare($log_query);
$log_stmt->bind_param("iis", $_SESSION['user_id'], $record_id, $_SERVER['REMOTE_ADDR']);
$log_stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Record - MOWT ERMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .document-preview {
            min-height: 500px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .badge-detained {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar position-fixed" id="sidebar">
            <!-- Your sidebar content -->
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">View Record: <?php echo htmlspecialchars($record['title']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="records.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Records
                    </a>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Record Details
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Document Number</th>
                                    <td><?php echo htmlspecialchars($record['doc_no']); ?></td>
                                </tr>
                                <tr>
                                    <th>Title</th>
                                    <td><?php echo htmlspecialchars($record['title']); ?></td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td>
                                        <?php if (!empty($record['department_name'])): ?>
                                            <?php echo htmlspecialchars($record['department_name']); ?>
                                            <?php if (!empty($record['department_location'])): ?>
                                                <br><small class="text-muted">Location: <?php echo htmlspecialchars($record['department_location']); ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($record['department_email'])): ?>
                                                <br><small class="text-muted">Email: <?php echo htmlspecialchars($record['department_email']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Not specified
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Author</th>
                                    <td><?php echo htmlspecialchars($record['author']); ?></td>
                                </tr>
                                <tr>
                                    <th>Creation Date</th>
                                    <td><?php echo date('M d, Y', strtotime($record['creation_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Upload Date</th>
                                    <td><?php echo date('M d, Y H:i', strtotime($record['upload_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Uploaded By</th>
                                    <td><?php echo htmlspecialchars($record['uploaded_by_name'] ?? 'System'); ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php if($record['detention_status'] == 'Active' || $record['status'] == 'Detained'): ?>
                                            <span class="badge badge-detained">Detained</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Retention Years</th>
                                    <td><?php echo $record['retention_years']; ?> years</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Actions
                        </div>
                        <div class="card-body">
                            <a href="<?php echo $record['file_path']; ?>" class="btn btn-primary mb-2" download>
                                <i class="bi bi-download"></i> Download File
                            </a>
                            <?php if($_SESSION['user_type'] == 'admin' || $_SESSION['user_id'] == $record['uploaded_by_admin'] || $_SESSION['user_id'] == $record['uploaded_by_employee']): ?>
                                <?php if($record['detention_status'] == 'Active' || $record['status'] == 'Detained'): ?>
                                    <a href="#" class="btn btn-success mb-2" data-bs-toggle="modal" 
                                       data-bs-target="#releaseModal<?php echo $record['id']; ?>">
                                        <i class="bi bi-lock"></i> Release Document
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="btn btn-warning mb-2" data-bs-toggle="modal" 
                                       data-bs-target="#detainModal<?php echo $record['id']; ?>">
                                        <i class="bi bi-unlock"></i> Detain Document
                                    </a>
                                <?php endif; ?>
                                <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-warning mb-2">
                                    <i class="bi bi-pencil"></i> Edit Record
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Preview Section -->
            <div class="card mb-4">
                <div class="card-header">
                    Document Preview
                </div>
                <div class="card-body">
                    <?php
                    $file_ext = strtolower(pathinfo($record['file_path'], PATHINFO_EXTENSION));
                    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <img src="<?php echo $record['file_path']; ?>" class="img-fluid" alt="Document Preview">
                    <?php elseif ($file_ext == 'pdf'): ?>
                        <iframe src="<?php echo $record['file_path']; ?>" class="document-preview w-100"></iframe>
                    <?php else: ?>
                        <div class="alert alert-info">
                            This file type cannot be previewed. Please download the file to view it.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detain Modal -->
    <div class="modal fade" id="detainModal<?php echo $record['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Detain Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="records.php">
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
                <form method="POST" action="records.php">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>