<?php
// api/finalize_enrollment.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$lead_id = $data['lead_id'] ?? null;

if ($lead_id) {
    try {
        // 1. Mark as Enrolled and lock probability at 100%
        $stmt = $pdo->prepare("UPDATE leads SET current_stage = 'Enrolled', conversion_probability = 100, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$lead_id]);

        // 2. Fetch data for WhatsApp
        $stmtL = $pdo->prepare("SELECT full_name, phone FROM leads WHERE id = ?");
        $stmtL->execute([$lead_id]);
        $lead = $stmtL->fetch();

        // 3. Construct Receipt URL
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $receipt_url = $protocol . $_SERVER['HTTP_HOST'] . "/unicore_erp/view_receipt.php?id=" . $lead_id;

        // 4. Log the Final Step
        $logNote = "Payment verified. Receipt issued. Student officially enrolled.";
        $stmtLog = $pdo->prepare("INSERT INTO lead_engagement_log (lead_id, user_id, channel, note) VALUES (?, ?, 'system', ?)");
        $stmtLog->execute([$lead_id, $_SESSION['user_id'], $logNote]);

        // 5. Generate the WhatsApp payload
        $wa_msg = "Hello " . $lead['full_name'] . ", your payment is verified! You are officially enrolled. Download your receipt here: " . $receipt_url;
        $wa_url = "https://wa.me/" . str_replace(['+', ' ', '-'], '', $lead['phone']) . "?text=" . urlencode($wa_msg);

        echo json_encode([
            'success' => true, 
            'whatsapp_url' => $wa_url
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}