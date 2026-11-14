<?php
require_once 'config.php';

$hash = $_GET['hash'] ?? '';
$response = ['exists' => false, 'doc_no' => ''];

if ($hash) {
    $stmt = $conn->prepare("SELECT doc_no FROM upload_files WHERE file_hash = ?");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['exists'] = true;
        $response['doc_no'] = $result->fetch_assoc()['doc_no'];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>