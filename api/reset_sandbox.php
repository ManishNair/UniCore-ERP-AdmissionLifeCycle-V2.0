<?php
// api/reset_sandbox.php
session_start();
require_once '../config/db.php';

// 1. SECURITY
if (!isset($_SESSION['user_id'])) { 
    die("Unauthorized access."); 
}

try {
    // 2. DISABLE FOREIGN KEY CONSTRAINTS
    // This allows us to wipe tables regardless of their relationships
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 3. WIPE DATA TABLES
    // Truncate is faster and automatically resets Auto-Increment IDs
    $tables = [
        'financial_tickets',
        'lead_engagement_log',
        'lead_documents',
        'lead_activity_log',
        'leads'
    ];

    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE $table");
    }

    // 4. RE-ENABLE FOREIGN KEY CONSTRAINTS
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 5. REDIRECT WITH SUCCESS
    header("Location: ../index.php?msg=sandbox_reset_success");
    exit;

} catch (Exception $e) {
    // Safety: Always try to re-enable FK checks if something fails
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    die("Reset Failed: " . $e->getMessage());
}