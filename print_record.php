<?php
// print_record.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$record_id = intval($_GET['id']);

// Get record details with all necessary fields including department
$query = "SELECT 
            uf.*, 
            COALESCE(adm.name, emp.name) as uploaded_by_name,
            d.department_name,
            d.location as department_location,
            d.contact_email as department_email
          FROM upload_files uf
          LEFT JOIN admin adm ON uf.uploaded_by_admin = adm.id
          LEFT JOIN employee_user emp ON uf.uploaded_by_employee = emp.id
          LEFT JOIN departments d ON uf.department_id = d.department_id
          WHERE uf.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: records.php");
    exit();
}

$record = $result->fetch_assoc();

// For employees: Check if they belong to the document's department
if ($_SESSION['user_type'] == 'employee') {
    $employee_dept = $conn->query("SELECT department_id FROM employee_user WHERE id = {$_SESSION['user_id']}")->fetch_assoc()['department_id'];
    if ($record['department_id'] != $employee_dept) {
        $_SESSION['error'] = "You don't have permission to view this document";
        header("Location: records.php");
        exit();
    }
}

// Get user details for the header
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($user_type == 'admin') {
    $query = "SELECT * FROM admin WHERE id = ?";
} else {
    $query = "SELECT * FROM employee_user WHERE id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Helper function to format file size
function formatFileSize($bytes) {
    if (!isset($bytes) || $bytes === null) return '0 bytes';
    
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

// Get file size if file exists
$file_size = file_exists($record['file_path']) ? filesize($record['file_path']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Record - MOWT ERMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fff;
            color: #000;
        }
        .print-header {
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .print-footer {
            border-top: 2px solid #000;
            margin-top: 20px;
            padding-top: 10px;
            font-size: 0.8em;
        }
        .record-details {
            margin-bottom: 30px;
        }
        .detail-row {
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            width: 150px;
            display: inline-block;
        }
        .document-info {
            margin-top: 30px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-end no-print mb-3">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
            <button class="btn btn-secondary" onclick="window.close()">
                <i class="bi bi-x"></i> Close
            </button>
        </div>

        <div class="print-header">
            <h2>MINISTRY OF WORKS AND TRANSPORT</h2>
            <h3>Electronic Records Management System</h3>
            <p>Printed by: <?php echo htmlspecialchars($user['name']); ?> (<?php echo strtoupper($user_type); ?>)</p>
            <p>Print Date: <?php echo date('M d, Y H:i:s'); ?></p>
        </div>

        <div class="record-details">
            <h4 class="mb-4">RECORD DETAILS</h4>
            
            <div class="detail-row">
                <span class="detail-label">Document Number:</span>
                <span><?php echo htmlspecialchars($record['doc_no'] ?? 'N/A'); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Title:</span>
                <span><?php echo htmlspecialchars($record['title']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Department:</span>
                <span>
                    <?php if (!empty($record['department_name'])): ?>
                        <?php echo htmlspecialchars($record['department_name']); ?>
                        <?php if (!empty($record['department_location'])): ?>
                            <br><small>(<?php echo htmlspecialchars($record['department_location']); ?>)</small>
                        <?php endif; ?>
                    <?php else: ?>
                        Not specified
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Author:</span>
                <span><?php echo htmlspecialchars($record['author']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Creation Date:</span>
                <span><?php echo date('M d, Y', strtotime($record['creation_date'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Upload Date:</span>
                <span><?php echo date('M d, Y H:i', strtotime($record['upload_date'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Uploaded By:</span>
                <span><?php echo htmlspecialchars($record['uploaded_by_name'] ?? 'System'); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Retention Period:</span>
                <span><?php echo $record['retention_years'] . ' years'; ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">File Path:</span>
                <span><?php echo htmlspecialchars(basename($record['file_path'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">File Size:</span>
                <span><?php echo formatFileSize($file_size); ?></span>
            </div>
        </div>

        <div class="document-info">
            <h5 class="mb-3">DOCUMENT INFORMATION</h5>
            <div class="border p-3">
                <?php 
                $file_ext = strtolower(pathinfo($record['file_path'], PATHINFO_EXTENSION));
                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <p>This is an image file. View the original file for full details.</p>
                <?php elseif ($file_ext == 'pdf'): ?>
                    <p>This is a PDF document. View the original file for complete content.</p>
                <?php else: ?>
                    <p>This document has been archived in the Electronic Records Management System.</p>
                    <p>For the complete document content, please refer to the original file.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="print-footer">
            <p>This is an official document from the Ministry of Works and Transport ERMS</p>
            <p>Printed on: <?php echo date('M d, Y H:i:s'); ?></p>
            <p>Document ID: <?php echo $record['id']; ?></p>
            <p>Document Number: <?php echo htmlspecialchars($record['doc_no'] ?? 'N/A'); ?></p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        window.onload = function() {
            // Uncomment the next line if you want the print dialog to open automatically
            // window.print();
        };
    </script>
</body>
</html>