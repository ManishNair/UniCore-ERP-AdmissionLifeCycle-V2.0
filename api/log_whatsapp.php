<?php
// api/log_whatsapp.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['lead_id'], $data['message'])) {
    try {
        // Insert into the history log
        $stmt = $pdo->prepare("INSERT INTO lead_engagement_log (lead_id, user_id, channel, note, created_at) VALUES (?, ?, 'whatsapp', ?, NOW())");
        
        $success = $stmt->execute([
            $data['lead_id'],
            $_SESSION['user_id'], // The Counselor sending the message
            $data['message']
        ]);

        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
}