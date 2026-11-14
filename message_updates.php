<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

session_start();
require_once 'config.php';

$conversation_id = $_GET['conversation'] ?? null;
$user_id = $_GET['user_id'] ?? null;
$user_type = $_GET['user_type'] ?? null;

if (!$conversation_id || !$user_id || !$user_type) {
    exit();
}

// Verify user is part of conversation
$verify_query = "SELECT 1 FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ? AND user_type = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("iis", $conversation_id, $user_id, $user_type);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    exit();
}

// Get last message ID the client has seen
$last_id = $_GET['last_id'] ?? 0;

while (true) {
    // Check for new messages
    $new_msg_query = "SELECT m.*, 
                     CASE WHEN m.sender_type = 'admin' THEN a.name ELSE e.name END as sender_name,
                     GROUP_CONCAT(CONCAT_WS('||', at.id, at.file_name, at.file_path, at.file_size, at.mime_type)) as attachments_raw
                     FROM messages m
                     LEFT JOIN admin a ON m.sender_type = 'admin' AND m.sender_id = a.id
                     LEFT JOIN employee_user e ON m.sender_type = 'employee' AND m.sender_id = e.id
                     LEFT JOIN attachments at ON at.message_id = m.id
                     WHERE m.conversation_id = ? AND m.id > ?
                     GROUP BY m.id
                     ORDER BY m.sent_at ASC";
    $new_msg_stmt = $conn->prepare($new_msg_query);
    $new_msg_stmt->bind_param("ii", $conversation_id, $last_id);
    $new_msg_stmt->execute();
    $new_msg_result = $new_msg_stmt->get_result();
    
    if ($new_msg_result->num_rows > 0) {
        while ($message = $new_msg_result->fetch_assoc()) {
            $last_id = $message['id'];
            
            // Process attachments
            $message['attachments'] = [];
            if (!empty($message['attachments_raw'])) {
                $attachment_rows = explode(',', $message['attachments_raw']);
                foreach ($attachment_rows as $row) {
                    if (!empty($row)) {
                        list($id, $file_name, $file_path, $file_size, $mime_type) = explode('||', $row);
                        $message['attachments'][] = [
                            'id' => $id,
                            'file_name' => $file_name,
                            'file_path' => $file_path,
                            'file_size' => $file_size,
                            'mime_type' => $mime_type
                        ];
                    }
                }
            }
            unset($message['attachments_raw']);
            
            // Mark as delivered if recipient
            if ($message['sender_id'] != $user_id || $message['sender_type'] != $user_type) {
                $status_query = "INSERT INTO message_status (message_id, user_id, user_type, status)
                                VALUES (?, ?, ?, 'delivered')
                                ON DUPLICATE KEY UPDATE status = 'delivered', updated_at = NOW()";
                $status_stmt = $conn->prepare($status_query);
                $status_stmt->bind_param("iis", $message['id'], $user_id, $user_type);
                $status_stmt->execute();
            }
            
            echo "data: " . json_encode(['newMessage' => $message]) . "\n\n";
            ob_flush();
            flush();
        }
    }
    
    // Check if client disconnected
    if (connection_aborted()) {
        exit();
    }
    
    sleep(1); // Wait 1 second before checking again
}
?>