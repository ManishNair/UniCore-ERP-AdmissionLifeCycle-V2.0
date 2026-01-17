<?php
// api/process_merge.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$child_id = $data['child_id'];
$master_id = $data['master_id'];

if ($child_id && $master_id) {
    // Hide duplicate from queue
    $pdo->prepare("UPDATE leads SET current_stage = 'Merged/Archived' WHERE id = ?")->execute([$child_id]);
    
    // Transfer notes to master for visibility
    $pdo->prepare("UPDATE lead_engagement_log SET lead_id = ? WHERE lead_id = ?")->execute([$master_id, $child_id]);
    
    // Log the action
    $pdo->prepare("INSERT INTO lead_engagement_log (lead_id, user_id, channel, note) VALUES (?, ?, 'system', ?)")
        ->execute([$master_id, $_SESSION['user_id'], "System: Duplicate ID #$child_id merged into this record."]);

    echo json_encode(['success' => true]);
}