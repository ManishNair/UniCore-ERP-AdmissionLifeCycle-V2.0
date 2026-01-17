<?php
// api/public_receiver.php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;
    $doc_name = $_POST['doc_name'] ?? null;
    
    // 1. VALIDATE STUDENT TOKEN
    $stmt = $pdo->prepare("SELECT id, full_name FROM leads WHERE upload_token = ?");
    $stmt->execute([$token]);
    $lead = $stmt->fetch();

    if (!$lead) {
        die("Invalid security token.");
    }

    $lead_id = $lead['id'];

    // 2. FILE HANDLING
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = '../uploads/docs/';
        
        // Ensure directory exists - CRITICAL FIX
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        
        // SANITIZATION FIX: Remove slashes and special chars from doc name
        // This changes "National ID / Passport" to "National_ID_Passport"
        $clean_doc_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $doc_name);
        
        $new_filename = "lead_" . $lead_id . "_" . $clean_doc_name . "_" . time() . "." . $file_ext;
        $destination = $upload_dir . $new_filename;

        // Try to move the file
        if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
            
            try {
                $pdo->beginTransaction();

                // 3. UPDATE OR INSERT DOCUMENT RECORD
                $sql = "INSERT INTO lead_documents (lead_id, doc_name, file_path, status, source) 
                        VALUES (?, ?, ?, 'pending', 'Student Portal')
                        ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), status = 'pending'";
                $pdo->prepare($sql)->execute([$lead_id, $doc_name, $new_filename]);

                // 4. LOG SYSTEM EVENT
                $log_msg = "System: Student uploaded [" . $doc_name . "]. Verification Required.";
                $log_sql = "INSERT INTO lead_engagement_log (lead_id, user_id, note, temp_status, channel) 
                            VALUES (?, 1, ?, 'Uploaded', 'system')"; 
                $pdo->prepare($log_sql)->execute([$lead_id, $log_msg]);

                $pdo->commit();

                header("Location: ../public_upload.php?id=" . $token . "&success=1");
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                die("Database Error: " . $e->getMessage());
            }
        } else {
            die("Upload Error: Could not move file to $destination. Check folder permissions.");
        }
    } else {
        die("No file received or error code: " . ($_FILES['file']['error'] ?? 'No File'));
    }
}