<?php
// api/whatsapp_webhook.php - V2.0 Final Debug Edition
require_once '../config/db.php';

$log_file = 'wa_log.txt';
$raw_payload = file_get_contents('php://input');
$data = json_decode($raw_payload, true);
$timestamp = date('Y-m-d H:i:s');

// 1. LOG EVERYTHING
file_put_contents($log_file, "[$timestamp] INCOMING: " . ($raw_payload ?: "BROWSER_GET_REQUEST") . PHP_EOL, FILE_APPEND);

// 2. FORCE DATA IF BROWSER TESTING
if (!$data) {
    $data = [
        'name' => 'Force Test Lead ' . rand(10, 99),
        'phone' => '9999999999',
        'source' => 'WhatsApp'
    ];
}

$full_name = $data['name'] ?? 'Social Lead';
$phone     = $data['phone'] ?? '000-000';
$source    = $data['source'] ?? 'WhatsApp';

try {
    /** * NOTE: We use college_id = 0 for unassigned leads. 
     * Ensure your 'leads' table has 'source' and 'current_stage' columns.
     */
    $sql = "INSERT INTO leads (full_name, phone, source, current_stage, college_id, created_at) 
            VALUES (?, ?, ?, 'Compliance Gate', 0, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $res = $stmt->execute([$full_name, $phone, $source]);

    if($res) {
        file_put_contents($log_file, "[$timestamp] SUCCESS: Lead created: $full_name" . PHP_EOL, FILE_APPEND);
        echo "✅ SUCCESS: Lead Created. Check Social Router Now.";
    }

} catch (PDOException $e) {
    // This will print the EXACT reason it is failing in the Ledger
    file_put_contents($log_file, "[$timestamp] CRITICAL SQL ERROR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo "❌ SQL ERROR: " . $e->getMessage();
}