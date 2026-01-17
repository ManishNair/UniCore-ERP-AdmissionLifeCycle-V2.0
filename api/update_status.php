<?php
// api/update_status.php
session_start();
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$lead_id = $data['lead_id'];

// 1. Advance the stage and lock probability to 100%
$sql = "UPDATE leads SET 
        current_stage = 'Financial Gate', 
        conversion_probability = 100, 
        status = 'closed' 
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
if($stmt->execute([$lead_id])) {
    
    // 2. Create the Financial Ticket Audit Log
    $log = $pdo->prepare("INSERT INTO lead_activity_log (lead_id, user_id, action_type, description) 
                          VALUES (?, ?, 'FINANCE', 'Compliance Cleared: Financial Ticket Generated')");
    $log->execute([$lead_id, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
}