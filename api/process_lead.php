<?php
// api/process_lead.php - V2.0 Relational Lead Ingestion Engine
session_start();
require_once '../config/db.php';

// 1. SECURITY & METHOD CHECK
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Unauthorized Access");
}

/** * DYNAMIC PERMISSION CHECK
 * Replaced Security::guard('ADD_LEADS') with session-based check
 */
$user_perms = $_SESSION['permissions'] ?? [];
if (!in_array('add_leads', $user_perms)) {
    header("Location: ../add_lead.php?error=unauthorized_capability");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../add_lead.php");
    exit;
}

// 2. DATA CAPTURE & VALIDATION
$full_name  = trim($_POST['full_name'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$college_id = filter_var($_POST['college_id'], FILTER_VALIDATE_INT);
$course_id  = filter_var($_POST['course_id'], FILTER_VALIDATE_INT);
$source     = "Direct Manual";
// Standardized to 'Compliance' to match index.php filters
$stage      = "Compliance"; 

if (!$full_name || !$phone || !$college_id || !$course_id) {
    header("Location: ../add_lead.php?error=missing_fields");
    exit;
}

try {
    // 3. DATABASE INSERTION
    // Added counselor_id to link the lead to the staff member
    $sql = "INSERT INTO leads (
                full_name, 
                phone, 
                college_id, 
                course_id, 
                source, 
                current_stage, 
                counselor_id,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([
        $full_name, 
        $phone, 
        $college_id, 
        $course_id, 
        $source, 
        $stage,
        $_SESSION['user_id']
    ]);

    if ($success) {
        // Redirect to dashboard with provisioned success status
        header("Location: ../index.php?success=lead_provisioned");
        exit;
    }

} catch (PDOException $e) {
    // Log the error and redirect back with failure message
    error_log("Lead Ingestion Error: " . $e->getMessage());
    
    // Check for foreign key constraint errors
    if ($e->getCode() == 23000) {
        header("Location: ../add_lead.php?error=invalid_mapping");
    } else {
        header("Location: ../add_lead.php?error=db_failure&detail=" . urlencode($e->getMessage()));
    }
    exit;
}