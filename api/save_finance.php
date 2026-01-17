<?php
// api/save_finance.php
session_start();
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['lead_id'])) {
    try {
        $pdo->beginTransaction();

        // Use UPSERT (Insert or Update if exists)
        $sql = "INSERT INTO student_finances (lead_id, tuition_fee, scholarship_amt, paid_amt) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                tuition_fee = VALUES(tuition_fee), 
                scholarship_amt = VALUES(scholarship_amt), 
                paid_amt = VALUES(paid_amt)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['lead_id'],
            $data['tuition_fee'] ?? 0,
            $data['scholarship_amt'] ?? 0,
            $data['paid_amt'] ?? 0
        ]);

        // Log the financial update in the journey log
        $logNote = "Financials Updated: Tuition ₹" . $data['tuition_fee'] . " | Paid ₹" . $data['paid_amt'];
        $log = $pdo->prepare("INSERT INTO lead_engagement_log (lead_id, user_id, note, channel) VALUES (?, ?, ?, 'system')");
        $log->execute([$data['lead_id'], $_SESSION['user_id'], $logNote]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}