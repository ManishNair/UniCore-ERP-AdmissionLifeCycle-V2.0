<?php
// api/toggle_permission.php
session_start();
require_once '../config/db.php';

if ($_SESSION['role_name'] !== 'Superadmin') { exit('Denied'); }

$role_id = $_POST['role_id'];
$perm_id = $_POST['perm_id'];
$action  = $_POST['action'];

if ($action === 'grant') {
    $sql = "INSERT IGNORE INTO role_permissions (role_id, perm_id) VALUES (?, ?)";
} else {
    $sql = "DELETE FROM role_permissions WHERE role_id = ? AND perm_id = ?";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$role_id, $perm_id]);
echo json_encode(['status' => 'success']);