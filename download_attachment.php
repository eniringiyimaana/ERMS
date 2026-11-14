<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$attachment_id = $_GET['id'] ?? 0;

// Get attachment info and verify user has access
$query = "SELECT a.* FROM attachments a
          JOIN messages m ON a.message_id = m.id
          JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
          WHERE a.id = ? AND cp.user_id = ? AND cp.user_type = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $attachment_id, $_SESSION['user_id'], $_SESSION['user_type']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $attachment = $result->fetch_assoc();
    
    if (file_exists($attachment['file_path'])) {
        // Log the download
        $log_query = "INSERT INTO attachment_downloads (attachment_id, user_id, user_type, downloaded_at)
                     VALUES (?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("iis", $attachment_id, $_SESSION['user_id'], $_SESSION['user_type']);
        $log_stmt->execute();
        
        // Send the file
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($attachment['file_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($attachment['file_path']));
        readfile($attachment['file_path']);
        exit;
    }
}

header("Location: messages.php?error=file_not_found");
exit();
?>