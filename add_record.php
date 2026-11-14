<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check user type (only admin and employee allowed)
if ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'employee') {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        $required = ['title', 'author', 'retention_years', 'creation_date'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Get basic form data
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $retention_years = (int)$_POST['retention_years'];
        $creation_date = $_POST['creation_date'];
        $uploaded_by = $_SESSION['user_id'];
        $user_type = $_SESSION['user_type'];

        // Handle department assignment based on user type
        if ($user_type == 'admin') {
            if (empty($_POST['department_id'])) {
                throw new Exception("Department is required");
            }
            $department_id = (int)$_POST['department_id'];
        } else {
            // For employees, get their assigned department
            $dept_stmt = $conn->prepare("SELECT department_id FROM employee_user WHERE id = ?");
            $dept_stmt->bind_param("i", $_SESSION['user_id']);
            $dept_stmt->execute();
            $dept_result = $dept_stmt->get_result();
            $dept_data = $dept_result->fetch_assoc();
            
            if (!$dept_data || !isset($dept_data['department_id'])) {
                throw new Exception("Your account is not assigned to any department");
            }
            $department_id = $dept_data['department_id'];
        }

        // File upload handling
        if (!isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK) {
            throw new Exception("File upload is required");
        }

        // Create uploads directory if it doesn't exist
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }

        // Process file upload
        $file_name = basename($_FILES["file"]["name"]);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;

        // File validations
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_types)) {
            throw new Exception("Invalid file type. Allowed types: PDF, DOC, XLS, JPG, PNG");
        }

        if ($_FILES["file"]["size"] > 10 * 1024 * 1024) { // 10MB
            throw new Exception("File too large (max 10MB)");
        }

        // Generate document number
        $doc_no = generateDepartmentDocNo($conn, $department_id);

        // Move uploaded file
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $file_hash = md5_file($target_file);

            // Check for duplicate files
            $check_hash = $conn->prepare("SELECT id, doc_no FROM upload_files WHERE file_hash = ?");
            $check_hash->bind_param("s", $file_hash);
            $check_hash->execute();
            $hash_result = $check_hash->get_result();

            if ($hash_result->num_rows > 0) {
                unlink($target_file);
                $existing = $hash_result->fetch_assoc();
                throw new Exception("This file already exists in the system (Document #{$existing['doc_no']})");
            }

            // Insert record
            // [Previous code remains the same until the INSERT query]

// Corrected INSERT query
// In the insert section of add_record.php:
$query = "INSERT INTO upload_files (
    doc_no, title, author, retention_years, 
    creation_date, file_path, 
    upload_date, file_hash, department_id, 
    uploaded_by_type, " . 
    ($user_type == 'admin' ? 'uploaded_by_admin' : 'uploaded_by_employee') . "
) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param(
    "sssissisii",
    $doc_no, 
    $title, 
    $author, 
    $retention_years,
    $creation_date,
    $target_file,
    $file_hash,
    $department_id,
    $user_type,
    $uploaded_by  // This will go to either uploaded_by_admin or uploaded_by_employee
);

// [Rest of the code remains the same]

            if ($stmt->execute()) {
                $_SESSION['success'] = "Document added successfully with document number: $doc_no";
                header("Location: records.php");
                exit();
            } else {
                unlink($target_file);
                throw new Exception("Database error: " . $stmt->error);
            }
        } else {
            throw new Exception("Failed to upload file");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: records.php");
        exit();
    }
}

function generateDepartmentDocNo($conn, $department_id) {
    // Get department abbreviation
    $dept_query = $conn->prepare("SELECT SUBSTRING(department_name, 1, 2) as abbrev FROM departments WHERE department_id = ?");
    $dept_query->bind_param("i", $department_id);
    $dept_query->execute();
    $dept_result = $dept_query->get_result();
    
    if ($dept_result->num_rows === 0) {
        throw new Exception("Invalid department selected");
    }
    
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

// If not POST request, redirect
header("Location: records.php");
exit();
?>