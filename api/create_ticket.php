<?php
// api/create_ticket.php
session_start();
require_once '../config/db.php';

// 1. SECURITY & INPUT CAPTURE
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data['lead_id'] || !isset($data['tuition'])) {
    die(json_encode(['success' => false, 'message' => 'Missing financial data']));
}

$lead_id = $data['lead_id'];
$tuition = floatval($data['tuition']);
$scholarship = floatval($data['scholarship'] ?? 0);
$net_payable = $tuition - $scholarship;
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // 2. CREATE THE FINANCIAL TICKET
    $stmtTicket = $pdo->prepare("INSERT INTO financial_tickets (lead_id, tuition_fee, scholarship_amount, net_payable, due_date) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
    $stmtTicket->execute([$lead_id, $tuition, $scholarship, $net_payable]);

    // 3. ADVANCE THE LEAD STAGE
    // We move them to 'Interested' and set probability to 100% as they are now 'Converted'
    $stmtLead = $pdo->prepare("UPDATE leads SET current_stage = 'Interested', status = 'closed', conversion_probability = 100 WHERE id = ?");
    $stmtLead->execute([$lead_id]);

    // 4. LOG THE CONVERSION IN AUDIT TRAIL
    $logDesc = "Financial Ticket Generated: Total $" . number_format($net_payable, 2) . " (Scholarship: $" . number_format($scholarship, 2) . ")";
    $stmtLog = $pdo->prepare("INSERT INTO lead_activity_log (lead_id, user_id, action_type, description) VALUES (?, ?, 'REVENUE_GENERATED', ?)");
    $stmtLog->execute([$lead_id, $user_id, $logDesc]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}