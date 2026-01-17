<?php
/**
 * includes/header.php - Global Identity & Permission Engine
 */
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once 'config/db.php';

// 1. AUTHENTICATION CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. IDENTITY MAPPING
// These variables are used by index.php to filter data
$u_id = $_SESSION['user_id'] ?? 0;
$u_name = $_SESSION['full_name'] ?? 'System User';
$user_role = $_SESSION['role_name'] ?? 'Counselor'; 
$role_id = $_SESSION['role_id'] ?? 1;

// Bridge: Fixes "Undefined variable $user_permitted_colleges"
$user_permitted_colleges = $_SESSION['college_id'] ?? '1'; 
$raw_ids = $user_permitted_colleges; 

/**
 * 3. THE PERMISSION CHECKER (Fixes the Fatal Error)
 * This function checks the session array created during login.
 */
function has_perm($key) {
    // Access the permissions array we stored in login.php
    $permissions = $_SESSION['permissions'] ?? [];
    return in_array($key, $permissions);
}

// 4. ASSIGNED SCOPE DISPLAY LOGIC
// Checks if the user has the 'access_all_colleges' permission key
$has_global_access = has_perm('access_all_colleges');

if ($has_global_access) {
    $assigned_display = "Global Access (All Colleges)";
} else {
    if (!empty($raw_ids)) {
        // Fetch college names for the assigned scope
        $stmt_names = $pdo->prepare("SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM colleges WHERE FIND_IN_SET(id, ?)");
        $stmt_names->execute([$raw_ids]);
        $assigned_display = $stmt_names->fetchColumn() ?: "Scope Restricted";
    } else {
        $assigned_display = "No Colleges Assigned";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UniCore ERP | Pipeline Intelligence</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="flex min-h-screen">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 ml-72 min-h-screen flex flex-col">
        
        <header class="w-full flex justify-between items-center py-4 px-12 bg-white border-b border-slate-100 sticky top-0 z-40 shadow-sm">
            
            <div class="flex items-center gap-3">
                <div class="bg-slate-50 px-4 py-2 rounded-2xl border border-slate-100 flex items-center gap-3">
                    <i class="fas fa-building text-slate-400 text-xs"></i>
                    <div>
                        <p class="text-[7px] font-black text-slate-400 uppercase tracking-[0.2em] leading-none mb-1">Assigned Scope</p>
                        <p class="text-[10px] font-bold text-slate-700 italic"><?= htmlspecialchars($assigned_display) ?></p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-8">
                <div class="flex items-center gap-4 border-r border-slate-100 pr-8">
                    <div class="text-right">
                        <p class="text-[11px] font-[900] text-slate-900 uppercase italic leading-none mb-1"><?= htmlspecialchars($u_name) ?></p>
                        <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest"><?= htmlspecialchars($user_role) ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-2xl bg-[#0f172a] flex items-center justify-center text-[12px] font-black text-white shadow-lg shadow-slate-200">
                        <?= strtoupper(substr($u_name, 0, 1)) ?>
                    </div>
                </div>

                <a href="logout.php" class="flex items-center gap-2 text-rose-500 hover:text-rose-600 transition-all group">
                    <span class="text-[10px] font-black uppercase tracking-widest">Sign Out</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center group-hover:bg-rose-500 group-hover:text-white transition-all">
                        <i class="fas fa-sign-out-alt text-[10px]"></i>
                    </div>
                </a>
            </div>
        </header>