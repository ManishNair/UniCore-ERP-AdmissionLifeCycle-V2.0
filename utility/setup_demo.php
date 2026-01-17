<?php
/**
 * setup_demo.php - V2.0 UniCore ERP Rapid Deployment
 * Provisions database, directories, and seeds data for immediate demonstration.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$host = 'localhost';
$user = 'root';
$pass = 'root'; 
$db_name = 'unicore_db';

try {
    // 1. DIRECTORY INITIALIZATION
    $upload_path = 'uploads/docs/';
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0777, true);
        echo "<p style='color:green;'>📁 Created directory: $upload_path</p>";
    }

    // 2. DATABASE CONNECTION
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
    $pdo->exec("USE `$db_name` ");

    echo "<body style='font-family:sans-serif; background:#f8fafc; padding:40px;'>";
    echo "<h1 style='color:#2563eb;'>🚀 UniCore ERP Demo Setup</h1><hr>";

    // 3. SCHEMA BUILD
    $pdo->exec("DROP TABLE IF EXISTS `role_permissions`, `permissions`, `roles`, `lead_activity_log`, 
                           `lead_documents`, `lead_engagement_log`, `financial_tickets`, 
                           `leads`, `courses`, `colleges`, `users` ");

    $pdo->exec("CREATE TABLE `roles` (
      `id` int NOT NULL AUTO_INCREMENT,
      `role_name` varchar(50) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE `permissions` (
      `id` int NOT NULL AUTO_INCREMENT,
      `perm_desc` varchar(100) NOT NULL,
      `perm_key` varchar(50) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE `role_permissions` (
      `role_id` int NOT NULL,
      `perm_id` int NOT NULL,
      PRIMARY KEY (`role_id`,`perm_id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE `users` (
      `id` int NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL,
      `password` varchar(255) DEFAULT NULL,
      `full_name` varchar(100) DEFAULT NULL,
      `role_id` int DEFAULT '1',
      PRIMARY KEY (`id`),
      UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE `colleges` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE `leads` (
      `id` int NOT NULL AUTO_INCREMENT,
      `full_name` varchar(255) NOT NULL,
      `phone` varchar(20) NOT NULL,
      `college_id` int DEFAULT NULL,
      `source` varchar(50) DEFAULT 'Manual',
      `current_stage` varchar(50) DEFAULT 'Compliance Gate',
      `group_tag` varchar(100) DEFAULT NULL,
      `access_token` varchar(100) DEFAULT NULL,
      `upload_token` varchar(64) DEFAULT NULL,
      `visit_count` int DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `group_tag` (`group_tag`),
      UNIQUE KEY `upload_token` (`upload_token`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE `lead_documents` (
      `id` int NOT NULL AUTO_INCREMENT,
      `lead_id` int NOT NULL,
      `doc_name` varchar(150) NOT NULL,
      `file_path` varchar(255) DEFAULT NULL,
      `status` enum('pending','verified','rejected') DEFAULT 'pending',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB");

    // 4. PDF FILE SEEDING
    $dummy_pdf_base64 = "JVBERi0xLjAKMSAwIG9iajw8L1R5cGUvQ2F0YWxvZy9QYWdlcyAyIDAgUj4+ZW5kb2JqMiAwIG9iajw8L1R5cGUvUGFnZXMvS2lkc1szIDAgUl0vQ291bnQgMT4+ZW5kb2JqMyAwIG9iajw8L1R5cGUvUGFnZS9QYXJlbnQgMiAwIFIvUmVzb3VyY2VzPDwvRm9udDw8L0YxPDwvVHlwZS9Gb250L1N1YnR5cGUvVHlwZTEvQmFzZUZvbnQvSGVsdmV0aWNhPj4+Pj4vQ29udGVudHMgNCAwIFI+PmVuZG9iaiA0IDAgb2JqPDwvTGVuZ3RoIDQzPj5zdHJlYW0KQlQKL0YxIDI0IFRmCjEwMCA3MDAgVGQKKEhpZ2ggU2Nob29sIE1hcmtzaGVldCAtIERFTU8pIFRqCkVUCmVuZHN0cmVhbQplbmRvYmoKeHJlZgowIDUKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAwMDE4IDAwMDAwIG4gCjAwMDAwMDAwNzcgMDAwMDAgbiAKMDAwMDAwMDE1OCAwMDAwMCBuIAowMDAwMDAwMzM1IDAwMDAwIG4gCnRyYWlsZXI8PC9TaXplIDUvUm9vdCAxIDAgUj4+CnN0YXJ0eHJlZgowNDIKJSVFT0Y=";
    $pdf_name = "sample_marksheet.pdf";
    file_put_contents($upload_path . $pdf_name, base64_decode($dummy_pdf_base64));

    // 5. DATA SEEDING
    $pdo->exec("INSERT INTO roles (id, role_name) VALUES (1, 'Counselor'), (2, 'Teamleader'), (3, 'Superadmin')");
    $pdo->exec("INSERT INTO permissions (perm_key, perm_desc) VALUES 
        ('view_compliance', 'Verification Terminal'),
        ('view_finance', 'Financial Gate'),
        ('social_router', 'Social Inbound Tracker'),
        ('view_audit', 'Audit Logs')");
    $pdo->exec("INSERT INTO role_permissions (role_id, perm_id) SELECT 3, id FROM permissions");

    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (username, password, full_name, role_id) VALUES ('admin', '$admin_pass', 'Demo Superadmin', 3)");
    $pdo->exec("INSERT INTO colleges (id, name) VALUES (1, 'School of Engineering'), (2, 'School of Business')");

    // Grouped Leads
    $pdo->exec("INSERT INTO leads (id, full_name, phone, college_id, source, group_tag, upload_token) VALUES 
        (1, 'John Doe', '+919876543210', 1, 'WhatsApp', 'GRP-9876543210', 'token1'),
        (2, 'John Doe', '+919876543210', 2, 'API', 'GRP-9876543210', 'token2')");
    
    // Seed Document Relation
    $pdo->exec("INSERT INTO lead_documents (lead_id, doc_name, file_path, status) VALUES 
        (1, 'High School Marksheet', '$pdf_name', 'pending')");

    echo "<div style='background:white; border:1px solid #e2e8f0; padding:25px; border-radius:15px; box-shadow:0 4px 6px -1px rgb(0 0 0 / 0.1);'>";
    echo "<h2 style='margin-top:0; color:#10b981;'>✅ Setup Successful</h2>";
    echo "<p>Database <b>$db_name</b> is ready. Directory <b>$upload_path</b> is initialized.</p>";
    echo "<h3>🔑 Authentication</h3>";
    echo "<ul><li><b>User:</b> admin</li><li><b>Pass:</b> admin123</li></ul>";
    echo "<h3>📂 Demo Content</h3>";
    echo "<ul><li><b>Multi-Lead Tracking:</b> 'John Doe' has 2 linked applications.</li><li><b>PDF Preview:</b> One document is pre-attached to test the terminal viewer.</li></ul>";
    echo "<a href='login.php' style='display:inline-block; margin-top:10px; padding:10px 20px; background:#2563eb; color:white; text-decoration:none; border-radius:10px;'>Go to Login</a>";
    echo "</div>";

} catch (Exception $e) {
    die("<div style='color:red; font-weight:bold;'>❌ ERROR: " . $e->getMessage() . "</div>");
}