<?php
require_once 'config.php';

$department_id = $_GET['department_id'] ?? 0;
if ($department_id) {
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
    
    echo $dept_abbrev . '-' . str_pad($max_num + 1, 3, '0', STR_PAD_LEFT);
}
?>