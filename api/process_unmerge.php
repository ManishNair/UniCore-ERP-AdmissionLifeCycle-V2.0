<?php
// api/process_unmerge.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$child_id = $data['child_id'];

if ($child_id) {
    // Restore as independent lead
    $pdo->prepare("UPDATE leads SET current_stage = 'Compliance Gate', is_duplicate = 0, master_lead_id = NULL WHERE id = ?")
        ->execute([$child_id]);
    
    // Optional: Log on both records that an unmerge happened
    echo json_encode(['success' => true]);
}