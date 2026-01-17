<?php
// api/get_eligible_counselors.php
session_start();
require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$selectedColleges = $data['college_ids'] ?? [];

if (empty($selectedColleges)) {
    echo json_encode([]);
    exit;
}

// Fetch all counselors
$stmt = $pdo->query("SELECT id, full_name, college_id FROM users WHERE LOWER(role) = 'counselor'");
$allCounselors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eligible = [];

foreach ($allCounselors as $c) {
    $cPermissions = explode(',', $c['college_id']); // "1,2,5" -> [1,2,5]
    
    // Check if counselor has EVERY college requested
    $hasAll = true;
    foreach ($selectedColleges as $id) {
        if (!in_array($id, $cPermissions)) {
            $hasAll = false;
            break;
        }
    }
    
    if ($hasAll) {
        $eligible[] = [
            'id' => $c['id'],
            'full_name' => $c['full_name']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($eligible);