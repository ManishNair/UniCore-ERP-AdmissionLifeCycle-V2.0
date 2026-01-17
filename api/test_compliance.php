<?php
// api/test_compliance.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { die("Unauthorized Access"); }

// 1. Find the most recent student currently in the Compliance Gate
$stmt = $pdo->prepare("SELECT id, full_name FROM leads WHERE current_stage = 'Compliance Gate' ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$lead = $stmt->fetch();

if (!$lead) {
    die("<script>alert('No students found in Compliance Gate to test.'); window.location.href='../social_router.php';</script>");
}

$lead_id = $lead['id'];

// 2. V1.0 logic: Update Status to 'interested' which triggers V2.0 'Financial Gate'
$new_status = 'interested';
$new_stage  = 'Financial Gate';

$update = $pdo->prepare("UPDATE leads SET status = ?, current_stage = ? WHERE id = ?");
$update->execute([$new_status, $new_stage, $lead_id]);

// 3. Log the "Movement" for the Audit Trail menu
$log_sql = "INSERT INTO lead_activity_log (lead_id, user_id, action_type, description) VALUES (?, ?, ?, ?)";
$log_stmt = $pdo->prepare($log_sql);
$log_stmt->execute([
    $lead_id, 
    $_SESSION['user_id'], 
    'GATE_MOVEMENT', 
    "AI Automated Verification: All documents accepted for " . $lead['full_name']
]);

// 4. Redirect to the Financial Gate to verify UI
header("Location: ../financial_gate.php?id=" . $lead_id);
exit;