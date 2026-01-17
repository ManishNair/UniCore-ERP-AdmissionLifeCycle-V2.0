<?php
// api/add_note.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);
$lead_id = $data['lead_id'] ?? null;
$note = $data['note'] ?? null;

if ($lead_id && $note) {
    try {
        $log_sql = "INSERT INTO lead_activity_log (lead_id, performed_by, activity_type, description) 
                    VALUES (?, ?, 'Note', ?)";
        $stmt = $pdo->prepare($log_sql);
        $stmt->execute([$lead_id, $_SESSION['user_id'], $note]);

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}