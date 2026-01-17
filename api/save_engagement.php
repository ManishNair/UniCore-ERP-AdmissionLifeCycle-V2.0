<?php
// api/save_engagement.php
session_start();
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['lead_id'], $data['probability'], $data['note'])) {
    try {
        $pdo->beginTransaction();

        // 1. Update the Main Lead Record
        // We update conversion_probability and set the timestamp for 'Last Modified'
        $updateLead = $pdo->prepare("UPDATE leads SET conversion_probability = ?, updated_at = NOW() WHERE id = ?");
        $updateLead->execute([$data['probability'], $data['lead_id']]);

        // 2. Log the Strategy Note to Engagement History
        $logNote = $pdo->prepare("INSERT INTO lead_engagement_log (lead_id, user_id, note, temp_status, channel) VALUES (?, ?, ?, 'Strategy Refined', 'note')");
        $logNote->execute([
            $data['lead_id'],
            $_SESSION['user_id'],
            $data['note']
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}