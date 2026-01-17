<?php
// api/complete_enrollment.php
session_start();
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['lead_id'])) {
    // Update stage to 'Enrolled'
    $stmt = $pdo->prepare("UPDATE leads SET current_stage = 'Enrolled', updated_at = NOW() WHERE id = ?");
    $success = $stmt->execute([$data['lead_id']]);

    if($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}