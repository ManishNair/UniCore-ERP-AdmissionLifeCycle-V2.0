<?php
// api/promote_to_finance.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$lead_id = $data['lead_id'] ?? null;

if ($lead_id) {
    try {
        // 1. Update Lead Stage to 'Financial Gate'
        $stmt = $pdo->prepare("UPDATE leads SET current_stage = 'Financial Gate', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$lead_id]);

        // 2. Fetch Lead Details for WhatsApp
        $stmtLead = $pdo->prepare("SELECT full_name, phone, upload_token FROM leads WHERE id = ?");
        $stmtLead->execute([$lead_id]);
        $lead = $stmtLead->fetch();

        // 3. Construct the Student Payment Link
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $payment_url = $protocol . $_SERVER['HTTP_HOST'] . "/unicore_erp/student_payment.php?token=" . $lead['upload_token'];

        // 4. Log the System Action
        $msg = "System: Documents cleared. Sent Payment Link to student.";
        $stmtLog = $pdo->prepare("INSERT INTO lead_engagement_log (lead_id, user_id, channel, note) VALUES (?, ?, 'system', ?)");
        $stmtLog->execute([$lead_id, $_SESSION['user_id'], $msg]);

        // 5. Return the URL so JavaScript can open WhatsApp automatically
        echo json_encode([
            'success' => true, 
            'whatsapp_url' => "https://wa.me/" . str_replace(['+', ' ', '-'], '', $lead['phone']) . "?text=" . urlencode("Congratulations " . $lead['full_name'] . "! Your docs are verified. Please complete your enrollment payment here: " . $payment_url)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}