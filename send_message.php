<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    $conversation_id = $_POST['conversation_id'];
    $message = trim($_POST['message'] ?? '');

    // Verify user is part of conversation
    $verify_query = "SELECT 1 FROM conversation_participants 
                    WHERE conversation_id = ? AND user_id = ? AND user_type = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("iis", $conversation_id, $user_id, $user_type);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows > 0 && (!empty($message) || !empty($_FILES['attachment']['name']))) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert message (even if empty when there's an attachment)
            $insert_query = "INSERT INTO messages (conversation_id, sender_id, sender_type, content, status)
                            VALUES (?, ?, ?, ?, 'sent')";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iiss", $conversation_id, $user_id, $user_type, $message);
            $insert_stmt->execute();
            $message_id = $conn->insert_id;

            // Handle file attachment
            if (!empty($_FILES['attachment']['name'])) {
                $upload_dir = 'uploads/attachments/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Generate unique filename
                $file_ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                $file_size = $_FILES['attachment']['size'];
                $mime_type = $_FILES['attachment']['type'];

                // Validate file (example checks)
                $allowed_types = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'text/plain'];
                $max_size = 10 * 1024 * 1024; // 10MB

                if (in_array($mime_type, $allowed_types)) {
                    if ($file_size <= $max_size) {
                        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
                            // Insert attachment record
                            $attachment_query = "INSERT INTO attachments 
                                              (message_id, file_name, file_path, file_size, mime_type)
                                              VALUES (?, ?, ?, ?, ?)";
                            $attachment_stmt = $conn->prepare($attachment_query);
                            $attachment_stmt->bind_param("issis", $message_id, 
                                                       $_FILES['attachment']['name'],
                                                       $file_path, $file_size, $mime_type);
                            $attachment_stmt->execute();
                        } else {
                            throw new Exception("File upload failed");
                        }
                    } else {
                        throw new Exception("File size too large (max 10MB)");
                    }
                } else {
                    throw new Exception("Invalid file type. Allowed: JPG, PNG, PDF, DOC, TXT");
                }
            }

            // Update conversation last message time
            $update_query = "UPDATE conversations SET last_message_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $conversation_id);
            $update_stmt->execute();

            // Get other participants
            $participants_query = "SELECT user_id, user_type FROM conversation_participants
                                 WHERE conversation_id = ? AND (user_id != ? OR user_type != ?)";
            $participants_stmt = $conn->prepare($participants_query);
            $participants_stmt->bind_param("iis", $conversation_id, $user_id, $user_type);
            $participants_stmt->execute();
            $participants_result = $participants_stmt->get_result();

            // Add message status for each participant
            while ($participant = $participants_result->fetch_assoc()) {
                $status_query = "INSERT INTO message_status (message_id, user_id, user_type, status)
                               VALUES (?, ?, ?, 'delivered')";
                $status_stmt = $conn->prepare($status_query);
                $status_stmt->bind_param("iis", $message_id, $participant['user_id'], $participant['user_type']);
                $status_stmt->execute();
            }

            $conn->commit();
            header("Location: messages.php?conversation=" . $conversation_id);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: messages.php?conversation=" . $conversation_id . "&error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

header("Location: messages.php");
exit();
?>