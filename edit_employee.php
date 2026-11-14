<?php
// edit_employee.php
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
    
    // Basic validation
    if (empty($name) || empty($email) || empty($department) || empty($status)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: edit_employee.php?id=$id");
        exit();
    }
    
    try {
        // First get department ID from department name
        $dept_query = "SELECT department_id FROM departments WHERE department_name = ?";
        $dept_stmt = $conn->prepare($dept_query);
        $dept_stmt->bind_param("s", $department);
        $dept_stmt->execute();
        $dept_result = $dept_stmt->get_result();
        $dept = $dept_result->fetch_assoc();
        
        if (!$dept) {
            $_SESSION['error'] = "Invalid department selected";
            header("Location: edit_employee.php?id=$id");
            exit();
        }
        
        $dept_id = $dept['department_id'];
        
        // Update employee
        $query = "UPDATE employee_user SET name = ?, email_address = ?, department_id = ?, user_status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssisi", $name, $email, $dept_id, $status, $id);
        $stmt->execute();
        
        $_SESSION['success'] = "Employee updated successfully";
        header("Location: users.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating employee: " . $e->getMessage();
        header("Location: edit_employee.php?id=$id");
        exit();
    }
}

// If GET request, show edit form
$id = $_GET['id'] ?? 0;
if (empty($id)) {
    $_SESSION['error'] = "No employee ID specified";
    header("Location: users.php");
    exit();
}

// Get employee data
$query = "SELECT e.*, d.department_name 
          FROM employee_user e 
          LEFT JOIN departments d ON e.department_id = d.department_id 
          WHERE e.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    $_SESSION['error'] = "Employee not found";
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
    <title>Edit Employee</title>
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
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="bi bi-person-gear me-2"></i>Edit Employee</h3>
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
                            <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" 
                                           value="<?php echo htmlspecialchars($employee['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($employee['email_address']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Department <span class="text-danger">*</span></label>
                                    <select class="form-select" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php while($dept = $dept_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($dept['department_name']); ?>" 
                                                <?php echo ($dept['department_name'] == $employee['department_name']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['department_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" required>
                                        <option value="Active" <?php echo ($employee['user_status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo ($employee['user_status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="password" 
                                           placeholder="Leave blank to keep current password">
                                    <small class="text-muted">Only enter if you want to change the password</small>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
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
</body>
</html>