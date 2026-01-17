<?php
// api/verify_document.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['lead_id'], $data['doc_name'], $data['status'])) {
    try {
        // Update document status
        $stmt = $pdo->prepare("UPDATE lead_documents SET status = ? WHERE lead_id = ? AND doc_name = ?");
        $stmt->execute([$data['status'], $data['lead_id'], $data['doc_name']]);

        // Insert log entry for the audit trail
        $logNote = "Verified Document: " . $data['doc_name'];
        $stmtLog = $pdo->prepare("INSERT INTO lead_engagement_log (lead_id, user_id, channel, note) VALUES (?, ?, 'system', ?)");
        $stmtLog->execute([$data['lead_id'], $_SESSION['user_id'], $logNote]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}