<?php
// api/process_assignment.php
session_start();
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$lead_ids = $data['lead_ids'] ?? [];
$counselor_id = $data['counselor_id'] ?? null;

if (!empty($lead_ids) && $counselor_id) {
    try {
        $pdo->beginTransaction();

        $sql = "UPDATE leads SET counselor_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);

        $logSql = "INSERT INTO lead_engagement_log (lead_id, user_id, note, channel) VALUES (?, ?, ?, 'system')";
        $logStmt = $pdo->prepare($logSql);

        foreach ($lead_ids as $id) {
            $stmt->execute([$counselor_id, $id]);
            $logStmt->execute([$id, $_SESSION['user_id'], "Lead assigned/transferred by Chancellor."]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}