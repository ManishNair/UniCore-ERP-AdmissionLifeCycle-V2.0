<?php
// api/assign_lead.php
session_start();
require_once '../config/db.php';

// Security: Only Teamleaders or Chancellor can assign leads
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Teamleader', 'Chancellor'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized Access']));
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

$lead_id = $data['lead_id'] ?? null;
$counsellor_id = $data['counsellor_id'] ?? null;

if ($lead_id && $counsellor_id) {
    try {
        $stmt = $pdo->prepare("UPDATE leads SET assigned_to = ?, assigned_by = ?, assignment_date = NOW() WHERE id = ?");
        $stmt->execute([$counsellor_id, $_SESSION['user_id'], $lead_id]);
	
    	$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
		$stmt->execute([$counsellor_id]);
		$c_name = $stmt->fetchColumn();
		
		// 2. Insert into Activity Log
         $log_sql = "INSERT INTO lead_activity_log (lead_id, performed_by, activity_type, description) 
            VALUES (?, ?, 'Assignment', ?)";
         $stmt = $pdo->prepare($log_sql);
         $stmt->execute([
			$lead_id, 
			$_SESSION['user_id'], 
			"Lead reassigned to " . $c_name
		]);

        
        echo json_encode(['status' => 'success', 'message' => 'Lead successfully reassigned.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Data Provided']);
}

