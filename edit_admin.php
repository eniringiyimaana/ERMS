<?php
// edit_admin.php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $department = $_POST['department'] ?? '';
    $status = $_POST['status'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($department) || empty($status)) {
        $_SESSION['error'] = "All required fields must be filled";
        header("Location: edit_admin.php?id=$id");
        exit();
    }
    
    try {
        // Prepare base query
        if (!empty($password)) {
    // Update with password change
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $query = "UPDATE admin SET name = ?, email = ?, department_id = ?, admin_status = ?, admin_password = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssi", $name, $email, $department, $status, $hashed_password, $id);
} else {
    // Update without password change
    $query = "UPDATE admin SET name = ?, email = ?, department_id = ?, admin_status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssi", $name, $email, $department, $status, $id);
}
        
        $stmt->execute();
        
        $_SESSION['success'] = "Admin updated successfully";
        header("Location: users.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating admin: " . $e->getMessage();
        header("Location: edit_admin.php?id=$id");
        exit();
    }
}

// If GET request, show edit form
$id = $_GET['id'] ?? 0;
if (empty($id)) {
    $_SESSION['error'] = "No admin ID specified";
    header("Location: users.php");
    exit();
}

// Get admin data
$query = "SELECT a.*, d.department_name 
          FROM admin a
          LEFT JOIN departments d ON a.department_id = d.department_id
          WHERE a.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    $_SESSION['error'] = "Admin not found";
    header("Location: users.php");
    exit();
}

// Get departments for dropdown
$dept_query = "SELECT department_id, department_name FROM departments ORDER BY department_name ASC";
$dept_result = $conn->query($dept_query) or die("Error loading departments: " . $conn->error);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
        }
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .card-header {
            background-color: var(--primary-color);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="bi bi-person-gear me-2"></i>Edit Admin</h3>
                            <a href="users.php" class="btn btn-sm btn-light">
                                <i class="bi bi-arrow-left"></i> Back to Users
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label required-field">Full Name</label>
                                    <input type="text" class="form-control" name="name" 
                                           value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required-field">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label required-field">Department</label>
                                    <select class="form-select" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php while($dept = $dept_result->fetch_assoc()): ?>
                                            <option value="<?php echo $dept['department_id']; ?>" 
    <?php echo ($dept['department_id'] == $admin['department_id']) ? 'selected' : ''; ?>>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required-field">Status</label>
                                    <select class="form-select" name="status" required>
                                        <option value="Active" <?php echo ($admin['admin_status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo ($admin['admin_status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="password" 
                                           placeholder="Leave blank to keep current password">
                                    <small class="text-muted">Only enter if you want to change the password</small>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-text">
                                        Last updated: <?php echo date('M d, Y H:i', strtotime($admin['updated_at'] ?? 'now')); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end border-top pt-3">
                                <button type="submit" class="btn btn-primary me-md-2">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                                <a href="users.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = document.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    </script>
</body>
</html>