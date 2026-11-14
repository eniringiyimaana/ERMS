<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['document_id'])) {
    $document_id = $_POST['document_id'];
    $user_id = $_SESSION['user_id'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // 1. Verify pending disposal exists
        $disposal_query = "SELECT * FROM document_disposals 
                          WHERE document_id = ? AND verification_date IS NULL";
        $stmt = $conn->prepare($disposal_query);
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $disposal = $stmt->get_result()->fetch_assoc();

        if (!$disposal) {
            throw new Exception("No pending disposal found for this document");
        }

        // 2. Get file path before deletion
        $file_query = "SELECT file_path FROM upload_files WHERE id = ?";
        $stmt = $conn->prepare($file_query);
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $file_path = $stmt->get_result()->fetch_assoc()['file_path'];

        // 3. Handle all foreign key constraints:
        // a) Update AUDIT_LOG entries
        $update_audit_query = "UPDATE AUDIT_LOG SET document_id = NULL WHERE document_id = ?";
        $stmt = $conn->prepare($update_audit_query);
        $stmt->bind_param("i", $document_id);
        $stmt->execute();

        // b) Update document_detentions entries
        $update_detentions_query = "UPDATE document_detentions SET document_id = NULL WHERE document_id = ?";
        $stmt = $conn->prepare($update_detentions_query);
        $stmt->bind_param("i", $document_id);
        $stmt->execute();

        // c) Delete from document_disposals (since it's a disposal)
        $delete_disposals_query = "DELETE FROM document_disposals WHERE document_id = ?";
        $stmt = $conn->prepare($delete_disposals_query);
        $stmt->bind_param("i", $document_id);
        $stmt->execute();

        // 4. Now delete the document record
        $delete_query = "DELETE FROM upload_files WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $document_id);
        $stmt->execute();

        // 5. Delete the physical file
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // 6. Log the deletion action
        $log_query = "INSERT INTO AUDIT_LOG 
                     (user_id, action_type, document_id, ip_address, timestamp) 
                     VALUES (?, 'Delete', NULL, ?, NOW())";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("is", $user_id, $_SERVER['REMOTE_ADDR']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['success'] = "Document disposal approved and record deleted successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error approving disposal: " . $e->getMessage();
    }

    header("Location: records.php");
    exit();
}

header("Location: records.php");
exit();
?>