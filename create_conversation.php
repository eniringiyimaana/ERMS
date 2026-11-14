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
    $recipient_id = $_POST['recipient_id'];
    $recipient_type = $_POST['recipient_type'];
    $message = trim($_POST['message']);

    // Check if conversation already exists between these users
    $check_query = "SELECT c.id FROM conversations c
                   JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
                   JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
                   WHERE cp1.user_id = ? AND cp1.user_type = ?
                   AND cp2.user_id = ? AND cp2.user_type = ?
                   AND c.is_group = FALSE";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("isis", $user_id, $user_type, $recipient_id, $recipient_type);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Existing conversation found
        $existing = $check_result->fetch_assoc();
        $_SESSION['message'] = $message;
        header("Location: messages.php?conversation=" . $existing['id']);
        exit();
    } elseif (!empty($message)) {
        // Create new conversation
        $conn->begin_transaction();

        try {
            // Insert conversation
            $conv_query = "INSERT INTO conversations (is_group, last_message_at) VALUES (FALSE, NOW())";
            $conn->query($conv_query);
            $conversation_id = $conn->insert_id;

            // Add participants
            $participants = [
                ['user_id' => $user_id, 'user_type' => $user_type],
                ['user_id' => $recipient_id, 'user_type' => $recipient_type]
            ];

            foreach ($participants as $participant) {
                $part_query = "INSERT INTO conversation_participants (conversation_id, user_id, user_type)
                             VALUES (?, ?, ?)";
                $part_stmt = $conn->prepare($part_query);
                $part_stmt->bind_param("iis", $conversation_id, $participant['user_id'], $participant['user_type']);
                $part_stmt->execute();
            }

            // Insert initial message
            $msg_query = "INSERT INTO messages (conversation_id, sender_id, sender_type, content, status)
                         VALUES (?, ?, ?, ?, 'sent')";
            $msg_stmt = $conn->prepare($msg_query);
            $msg_stmt->bind_param("iiss", $conversation_id, $user_id, $user_type, $message);
            $msg_stmt->execute();
            $message_id = $conn->insert_id;

            // Add message status for recipient
            $status_query = "INSERT INTO message_status (message_id, user_id, user_type, status)
                           VALUES (?, ?, ?, 'delivered')";
            $status_stmt = $conn->prepare($status_query);
            $status_stmt->bind_param("iis", $message_id, $recipient_id, $recipient_type);
            $status_stmt->execute();

            $conn->commit();
            header("Location: messages.php?conversation=" . $conversation_id);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: messages.php?error=create_failed");
            exit();
        }
    }
}

header("Location: messages.php");
exit();
?>