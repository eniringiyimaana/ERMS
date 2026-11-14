<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user details
if ($user_type == 'admin') {
    $query = "SELECT * FROM admin WHERE id = ?";
} else {
    $query = "SELECT * FROM employee_user WHERE id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get conversations for the user
$conversations_query = "SELECT c.* FROM conversations c
                       JOIN conversation_participants cp ON c.id = cp.conversation_id
                       WHERE cp.user_id = ? AND cp.user_type = ?
                       ORDER BY c.last_message_at DESC";
$conversations_stmt = $conn->prepare($conversations_query);
$conversations_stmt->bind_param("is", $user_id, $user_type);
$conversations_stmt->execute();
$conversations_result = $conversations_stmt->get_result();

// Get all users for new conversation
$users_query = "SELECT 'admin' as user_type, id, name, email FROM admin WHERE id != ? AND admin_status = 'active'
                UNION ALL
                SELECT 'employee' as user_type, id, name, email_address as email FROM employee_user 
                WHERE id != ? AND user_status = 'active'";
$users_stmt = $conn->prepare($users_query);
$users_stmt->bind_param("ii", $user_id, $user_id);
$users_stmt->execute();
$users_result = $users_stmt->get_result();

// Helper functions
function getFileIcon($mime_type) {
    $icons = [
        'image/' => 'bi-file-image',
        'application/pdf' => 'bi-file-pdf',
        'application/msword' => 'bi-file-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'bi-file-word',
        'text/' => 'bi-file-text'
    ];
    
    foreach ($icons as $prefix => $icon) {
        if (strpos($mime_type, $prefix) === 0) {
            return $icon;
        }
    }
    
    return 'bi-file-earmark';
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - MOWT ERMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .message-container {
            height: calc(100vh - 200px);
            overflow-y: auto;
        }
        .conversation-list {
            height: calc(100vh - 150px);
            overflow-y: auto;
        }
        .message-bubble {
            max-width: 70%;
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
        }
        .sent-message {
            background-color: #4e73df;
            color: white;
            margin-left: auto;
        }
        .received-message {
            background-color: #f8f9fa;
            margin-right: auto;
        }
        .unread-badge {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            background-color: #e74a3b;
        }
        .attachment {
            background-color: rgba(255,255,255,0.2);
            padding: 0.5rem;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        

        <!-- Main Content -->
        <div class="main-content w-100">
            <!-- Top Navigation -->
            <!-- Top Navigation -->
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <button class="btn btn-link d-md-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        
        <div class="d-flex align-items-center">
            <!-- Back to Dashboard Button -->
            <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            
            <h1 class="h4 mb-0 text-gray-800">Messages</h1>
        </div>
        
        <div class="d-flex align-items-center">
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newConversationModal">
                <i class="bi bi-plus-lg me-1"></i> New Conversation
            </button>
        </div>
    </div>
</nav>

            <div class="container-fluid">
                <div class="row">
                    <!-- Conversation List -->
                    <div class="col-md-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">Conversations</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush conversation-list">
                                    <?php while($conversation = $conversations_result->fetch_assoc()): ?>
                                    <?php
                                    // Get conversation participants
                                    $participants_query = "SELECT cp.user_id, cp.user_type, 
                                                         CASE WHEN cp.user_type = 'admin' THEN a.name ELSE e.name END as name
                                                         FROM conversation_participants cp
                                                         LEFT JOIN admin a ON cp.user_type = 'admin' AND cp.user_id = a.id
                                                         LEFT JOIN employee_user e ON cp.user_type = 'employee' AND cp.user_id = e.id
                                                         WHERE cp.conversation_id = ? AND (cp.user_id != ? OR cp.user_type != ?)";
                                    $participants_stmt = $conn->prepare($participants_query);
                                    $participants_stmt->bind_param("iis", $conversation['id'], $user_id, $user_type);
                                    $participants_stmt->execute();
                                    $participants_result = $participants_stmt->get_result();
                                    $participants = $participants_result->fetch_all(MYSQLI_ASSOC);
                                    
                                    // Get last message
                                    $last_msg_query = "SELECT m.* FROM messages m WHERE m.conversation_id = ? ORDER BY m.sent_at DESC LIMIT 1";
                                    $last_msg_stmt = $conn->prepare($last_msg_query);
                                    $last_msg_stmt->bind_param("i", $conversation['id']);
                                    $last_msg_stmt->execute();
                                    $last_msg_result = $last_msg_stmt->get_result();
                                    $last_msg = $last_msg_result->fetch_assoc();
                                    
                                    // Check if unread
                                    $unread_query = "SELECT COUNT(*) as unread FROM message_status 
                                                    WHERE message_id IN (SELECT id FROM messages WHERE conversation_id = ?)
                                                    AND user_id = ? AND user_type = ? AND status = 'delivered'";
                                    $unread_stmt = $conn->prepare($unread_query);
                                    $unread_stmt->bind_param("iis", $conversation['id'], $user_id, $user_type);
                                    $unread_stmt->execute();
                                    $unread_result = $unread_stmt->get_result();
                                    $unread = $unread_result->fetch_assoc();
                                    ?>
                                    <a href="messages.php?conversation=<?php echo $conversation['id']; ?>" 
                                       class="list-group-item list-group-item-action <?php echo isset($_GET['conversation']) && $_GET['conversation'] == $conversation['id'] ? 'active' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php 
                                                if ($conversation['is_group']) {
                                                    echo htmlspecialchars($conversation['group_name']);
                                                } else {
                                                    echo htmlspecialchars($participants[0]['name']);
                                                }
                                                ?>
                                            </h6>
                                            <small><?php echo date('M j', strtotime($last_msg['sent_at'])); ?></small>
                                        </div>
                                        <p class="mb-1 text-truncate">
                                            <?php if ($unread['unread'] > 0): ?>
                                            <span class="unread-badge"></span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars(substr($last_msg['content'] ?? '[Attachment]', 0, 50)); ?>
                                        </p>
                                    </a>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message Area -->
                    <div class="col-md-8">
                        <?php if (isset($_GET['conversation'])): ?>
                        <?php
                        $conversation_id = $_GET['conversation'];
                        // Verify user is part of conversation
                        $verify_query = "SELECT 1 FROM conversation_participants 
                                        WHERE conversation_id = ? AND user_id = ? AND user_type = ?";
                        $verify_stmt = $conn->prepare($verify_query);
                        $verify_stmt->bind_param("iis", $conversation_id, $user_id, $user_type);
                        $verify_stmt->execute();
                        $verify_result = $verify_stmt->get_result();
                        
                        if ($verify_result->num_rows > 0) {
                            // Get conversation details
                            $conv_query = "SELECT * FROM conversations WHERE id = ?";
                            $conv_stmt = $conn->prepare($conv_query);
                            $conv_stmt->bind_param("i", $conversation_id);
                            $conv_stmt->execute();
                            $conv_result = $conv_stmt->get_result();
                            $conversation = $conv_result->fetch_assoc();
                            
                            // Get messages with attachments
                            $messages_query = "SELECT m.*, 
                                             CASE WHEN m.sender_type = 'admin' THEN a.name ELSE e.name END as sender_name,
                                             GROUP_CONCAT(CONCAT_WS('||', at.id, at.file_name, at.file_path, at.file_size, at.mime_type)) as attachments
                                             FROM messages m
                                             LEFT JOIN admin a ON m.sender_type = 'admin' AND m.sender_id = a.id
                                             LEFT JOIN employee_user e ON m.sender_type = 'employee' AND m.sender_id = e.id
                                             LEFT JOIN attachments at ON at.message_id = m.id
                                             WHERE m.conversation_id = ?
                                             GROUP BY m.id
                                             ORDER BY m.sent_at ASC";
                            $messages_stmt = $conn->prepare($messages_query);
                            $messages_stmt->bind_param("i", $conversation_id);
                            $messages_stmt->execute();
                            $messages_result = $messages_stmt->get_result();
                            
                            // Mark messages as read
                            $mark_read_query = "UPDATE message_status SET status = 'read', updated_at = NOW()
                                              WHERE message_id IN (SELECT id FROM messages WHERE conversation_id = ?)
                                              AND user_id = ? AND user_type = ? AND status = 'delivered'";
                            $mark_read_stmt = $conn->prepare($mark_read_query);
                            $mark_read_stmt->bind_param("iis", $conversation_id, $user_id, $user_type);
                            $mark_read_stmt->execute();
                            ?>
                            <div class="card shadow">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <?php 
                                        if ($conversation['is_group']) {
                                            echo htmlspecialchars($conversation['group_name']);
                                        } else {
                                            // Get other participant's name
                                            $other_query = "SELECT CASE WHEN user_type = 'admin' THEN a.name ELSE e.name END as name
                                                          FROM conversation_participants cp
                                                          LEFT JOIN admin a ON cp.user_type = 'admin' AND cp.user_id = a.id
                                                          LEFT JOIN employee_user e ON cp.user_type = 'employee' AND cp.user_id = e.id
                                                          WHERE cp.conversation_id = ? AND (cp.user_id != ? OR cp.user_type != ?)";
                                            $other_stmt = $conn->prepare($other_query);
                                            $other_stmt->bind_param("iis", $conversation_id, $user_id, $user_type);
                                            $other_stmt->execute();
                                            $other_result = $other_stmt->get_result();
                                            $other = $other_result->fetch_assoc();
                                            echo htmlspecialchars($other['name']);
                                        }
                                        ?>
                                    </h5>
                                </div>
                                <div class="card-body message-container" id="messageContainer">
                                    <?php while($message = $messages_result->fetch_assoc()): ?>
                                    <?php
                                    // Process attachments
                                    $attachments = [];
                                    if (!empty($message['attachments'])) {
                                        $attachment_rows = explode(',', $message['attachments']);
                                        foreach ($attachment_rows as $row) {
                                            if (!empty($row)) {
                                                list($id, $file_name, $file_path, $file_size, $mime_type) = explode('||', $row);
                                                $attachments[] = [
                                                    'id' => $id,
                                                    'file_name' => $file_name,
                                                    'file_path' => $file_path,
                                                    'file_size' => $file_size,
                                                    'mime_type' => $mime_type
                                                ];
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="d-flex mb-3 <?php echo $message['sender_id'] == $user_id && $message['sender_type'] == $user_type ? 'justify-content-end' : 'justify-content-start'; ?>">
                                        <div class="message-bubble <?php echo $message['sender_id'] == $user_id && $message['sender_type'] == $user_type ? 'sent-message' : 'received-message'; ?>">
                                            <?php if ($message['sender_id'] != $user_id || $message['sender_type'] != $user_type): ?>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars($message['sender_name']); ?></small>
                                            <?php endif; ?>
                                            
                                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                            
                                            <?php foreach ($attachments as $attachment): ?>
                                            <div class="mt-2 attachment">
                                                <a href="download_attachment.php?id=<?php echo $attachment['id']; ?>" 
                                                   class="text-decoration-none" target="_blank">
                                                    <i class="bi <?php echo getFileIcon($attachment['mime_type']); ?> me-1"></i>
                                                    <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                    <small class="text-muted">(<?php echo formatFileSize($attachment['file_size']); ?>)</small>
                                                </a>
                                            </div>
                                            <?php endforeach; ?>
                                            
                                            <small class="d-block text-end <?php echo $message['sender_id'] == $user_id && $message['sender_type'] == $user_type ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo date('g:i A', strtotime($message['sent_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <div class="card-footer">
                                    <form id="messageForm" method="POST" action="send_message.php" enctype="multipart/form-data">
                                        <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
                                        <div class="input-group">
                                            <input type="text" name="message" class="form-control" placeholder="Type your message...">
                                            <button type="button" class="btn btn-outline-secondary" id="attachButton">
                                                <i class="bi bi-paperclip"></i>
                                            </button>
                                            <input type="file" name="attachment" id="attachmentInput" style="display: none;">
                                            <button type="submit" class="btn btn-primary">Send</button>
                                        </div>
                                        <div id="attachmentPreview" class="mt-2 d-none">
                                            <small class="text-muted">Attachment: </small>
                                            <span id="attachmentName"></span>
                                            <button type="button" class="btn btn-sm btn-link text-danger" id="removeAttachment">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php
                        } else {
                            echo '<div class="alert alert-danger">You do not have access to this conversation</div>';
                        }
                        ?>
                        <?php else: ?>
                        <div class="card shadow h-100">
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <div class="text-center">
                                    <i class="bi bi-chat-square-text" style="font-size: 3rem; color: #ddd;"></i>
                                    <h5 class="mt-3 text-muted">Select a conversation</h5>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- New Conversation Modal -->
            <div class="modal fade" id="newConversationModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">New Conversation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="create_conversation.php">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Select Recipient</label>
                                    <select name="recipient_id" class="form-select" required>
                                        <option value="">-- Select User --</option>
                                        <?php while($user = $users_result->fetch_assoc()): ?>
                                        <option value="<?php echo $user['id']; ?>" data-type="<?php echo $user['user_type']; ?>">
                                            <?php echo htmlspecialchars($user['name']); ?> (<?php echo ucfirst($user['user_type']); ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <input type="hidden" name="recipient_type" id="recipientType">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea name="message" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Start Conversation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set recipient type when selecting a user
        document.querySelector('select[name="recipient_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('recipientType').value = selectedOption.getAttribute('data-type');
        });
        
        // Attachment handling
        document.getElementById('attachButton').addEventListener('click', function() {
            document.getElementById('attachmentInput').click();
        });

        document.getElementById('attachmentInput').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                document.getElementById('attachmentName').textContent = file.name;
                document.getElementById('attachmentPreview').classList.remove('d-none');
            }
        });

        document.getElementById('removeAttachment').addEventListener('click', function() {
            document.getElementById('attachmentInput').value = '';
            document.getElementById('attachmentPreview').classList.add('d-none');
        });
        
        // Auto-scroll to bottom of messages
        const messageContainer = document.getElementById('messageContainer');
        if (messageContainer) {
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
        
        // Real-time message updates with EventSource
        if (document.getElementById('messageContainer')) {
            const conversationId = <?php echo isset($_GET['conversation']) ? $_GET['conversation'] : 'null'; ?>;
            const userId = <?php echo $user_id; ?>;
            const userType = '<?php echo $user_type; ?>';
            
            if (conversationId) {
                const eventSource = new EventSource(`message_updates.php?conversation=${conversationId}&user_id=${userId}&user_type=${userType}`);
                
                eventSource.onmessage = function(e) {
                    const data = JSON.parse(e.data);
                    if (data.newMessage) {
                        const message = data.newMessage;
                        const isSender = message.sender_id == userId && message.sender_type == userType;
                        
                        // Build attachment HTML if present
                        let attachmentHtml = '';
                        if (message.attachments && message.attachments.length > 0) {
                            message.attachments.forEach(attach => {
                                const icon = getFileIcon(attach.mime_type);
                                const size = formatFileSize(attach.file_size);
                                attachmentHtml += `
                                <div class="mt-2 attachment">
                                    <a href="download_attachment.php?id=${attach.id}" 
                                       class="text-decoration-none" target="_blank">
                                        <i class="bi ${icon} me-1"></i>
                                        ${attach.file_name}
                                        <small class="text-muted">(${size})</small>
                                    </a>
                                </div>`;
                            });
                        }
                        
                        const messageHtml = `
                        <div class="d-flex mb-3 ${isSender ? 'justify-content-end' : 'justify-content-start'}">
                            <div class="message-bubble ${isSender ? 'sent-message' : 'received-message'}">
                                ${!isSender ? `<small class="d-block text-muted">${message.sender_name}</small>` : ''}
                                ${message.content ? message.content.replace(/\n/g, '<br>') : ''}
                                ${attachmentHtml}
                                <small class="d-block text-end ${isSender ? 'text-white-50' : 'text-muted'}">
                                    ${new Date(message.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                </small>
                            </div>
                        </div>`;
                        
                        document.getElementById('messageContainer').insertAdjacentHTML('beforeend', messageHtml);
                        document.getElementById('messageContainer').scrollTop = document.getElementById('messageContainer').scrollHeight;
                    }
                };
                
                // Clean up when leaving the page
                window.addEventListener('beforeunload', () => {
                    eventSource.close();
                });
            }
        }
        
        // Helper functions for frontend
        function getFileIcon(mimeType) {
            const icons = {
                'image/': 'bi-file-image',
                'application/pdf': 'bi-file-pdf',
                'application/msword': 'bi-file-word',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'bi-file-word',
                'text/': 'bi-file-text'
            };
            
            for (const [prefix, icon] of Object.entries(icons)) {
                if (mimeType.startsWith(prefix)) {
                    return icon;
                }
            }
            
            return 'bi-file-earmark';
        }

        function formatFileSize(bytes) {
            if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(1) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(1) + ' KB';
            } else {
                return bytes + ' bytes';
            }
        }
    </script>
</body>
</html>