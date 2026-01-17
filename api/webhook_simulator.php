<?php
// api/webhook_simulator.php
session_start();
require_once '../config/db.php';
require_once '../services/LeadService.php';

if (!isset($_SESSION['user_id'])) { die("Unauthorized"); }

$service = new LeadService($pdo);

// Mock Data representing a Social Lead
$testData = [
    'full_name' => 'Test Applicant ' . rand(100, 999),
    'phone' => '9198' . rand(100000, 999999),
    'email' => 'test' . rand(1, 100) . '@example.com',
    'source' => 'WhatsApp',
    'college_id' => 1
];

$result = $service->ingestLead($testData);

// Redirect back to the Social Router to see the new lead
header("Location: ../social_router.php?simulated=" . $result['status']);
exit;