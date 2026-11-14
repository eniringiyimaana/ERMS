<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$record_id = $_GET['id'] ?? null;
if (!$record_id) {
    header("Location: records.php");
    exit();
}

// Get current record data with department information
$query = "SELECT uf.*, d.department_name 
          FROM upload_files uf
          LEFT JOIN departments d ON uf.department_id = d.department_id
          WHERE uf.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    header("Location: records.php");
    exit();
}

// Check if user has permission to edit (admin or original uploader)
$is_admin = ($_SESSION['user_type'] == 'admin');
$is_uploader = ($_SESSION['user_id'] == $record['uploaded_by_admin'] || $_SESSION['user_id'] == $record['uploaded_by_employee']);

if (!$is_admin && !$is_uploader) {
    $_SESSION['error'] = "You don't have permission to edit this record";
    header("Location: records.php");
    exit();
}

// Get all departments for dropdown
$departments = [];
$dept_query = "SELECT * FROM departments ORDER BY department_name";
$dept_result = $conn->query($dept_query);
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $department_id = $_POST['department_id'];
    $author = $_POST['author'];
    $retention_years = isset($_POST['retention_years']) ? (int)$_POST['retention_years'] : $record['retention_years'];
    $creation_date = $_POST['creation_date'] ?? $record['creation_date'];
    $doc_no = $_POST['doc_no'] ?? $record['doc_no'];

    // Check if document number is being changed to one that already exists
    if ($doc_no != $record['doc_no']) {
        $check_query = "SELECT id FROM upload_files WHERE doc_no = ? AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $doc_no, $record_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = "This document number already exists in the system";
            header("Location: edit_record.php?id=$record_id");
            exit();
        }
    }

    // Update document information
    $update_query = "UPDATE upload_files SET 
                    title = ?, 
                    author = ?, 
                    retention_years = ?,
                    creation_date = ?,
                    doc_no = ?,
                    department_id = ?
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sissiii", 
        $title, 
        $author, 
        $retention_years,
        $creation_date,
        $doc_no,
        $department_id,
        $record_id
    );
    
    if ($update_stmt->execute()) {
        // Log this action
        $log_query = "INSERT INTO audit_log (user_id, action_type, document_id, ip_address, timestamp) 
                     VALUES (?, 'Edit', ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("iis", $_SESSION['user_id'], $record_id, $_SERVER['REMOTE_ADDR']);
        $log_stmt->execute();

        $_SESSION['success'] = "Record updated successfully!";
        header("Location: records.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating record: " . $update_stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fc;
        }
        .container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 2rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Edit Record</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="doc_no" class="form-label">Document Number</label>
                <input type="text" class="form-control" id="doc_no" name="doc_no" 
                       value="<?php echo htmlspecialchars($record['doc_no'] ?? ''); ?>" required>
                <div class="form-text">Format: XX-NNN (e.g., HR-001 for Human Resources documents)</div>
            </div>
            
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo htmlspecialchars($record['title']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="department_id" class="form-label">Department</label>
                <select class="form-select" id="department_id" name="department_id" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_id']; ?>"
                            <?php echo ($dept['department_id'] == $record['department_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="author" class="form-label">Creator</label>
                <input type="text" class="form-control" id="author" name="author" 
                       value="<?php echo htmlspecialchars($record['author']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="retention_years" class="form-label">Retention Years</label>
                <input type="number" class="form-control" id="retention_years" name="retention_years" 
                       value="<?php echo htmlspecialchars($record['retention_years']); ?>" min="1" required>
            </div>
            
            <div class="mb-3">
                <label for="creation_date" class="form-label">Creation Date</label>
                <input type="date" class="form-control" id="creation_date" name="creation_date" 
                       value="<?php echo htmlspecialchars($record['creation_date']); ?>" required>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Record
                </button>
                <a href="records.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>