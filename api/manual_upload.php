<?php
// api/manual_upload.php
session_start();
require_once '../config/db.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lead_id = $_POST['lead_id'];
    $doc_name = $_POST['doc_name'];
    $user_id = $_SESSION['user_id'];

    // 2. FILE VALIDATION
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        header("Location: ../compliance_desk.php?id=$lead_id&error=upload_failed");
        exit;
    }

    $file = $_FILES['file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($file_ext, $allowed_exts)) {
        header("Location: ../compliance_desk.php?id=$lead_id&error=invalid_format");
        exit;
    }

    // 3. TARGET PATH DEFINITION
    $upload_dir = '../uploads/docs/';
    // Unique naming convention: LEADID_DOCNAME_TIMESTAMP.EXTENSION
    $clean_doc_name = str_replace(' ', '_', strtolower($doc_name));
    $new_file_name = "lead_{$lead_id}_{$clean_doc_name}_" . time() . "." . $file_ext;
    $target_path = $upload_dir . $new_file_name;

    // 4. EXECUTE UPLOAD
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        
        try {
            $pdo->beginTransaction();

            // A. Update or Insert the document record
            $stmt = $pdo->prepare("INSERT INTO lead_documents (lead_id, doc_name, file_path, status, source) 
                                   VALUES (?, ?, ?, 'pending', 'Staff-Manual') 
                                   ON DUPLICATE KEY UPDATE 
                                   file_path = VALUES(file_path), 
                                   status = 'pending', 
                                   source = 'Staff-Manual'");
            $stmt->execute([$lead_id, $doc_name, $new_file_name]);

            // B. Log to Lifecycle Audit Trail
            $log_desc = "Manual upload of $doc_name by counselor.";
            $log = $pdo->prepare("INSERT INTO lead_activity_log (lead_id, user_id, action_type, description) 
                                  VALUES (?, ?, 'DOCUMENT_UPLOAD', ?)");
            $log->execute([$lead_id, $user_id, $log_desc]);

            $pdo->commit();
            
            // Redirect back to the desk to see the new blue icon/file
            header("Location: ../compliance_desk.php?id=$lead_id&msg=upload_success");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Database Error: " . $e->getMessage());
        }

    } else {
        header("Location: ../compliance_desk.php?id=$lead_id&error=move_failed");
        exit;
    }
}