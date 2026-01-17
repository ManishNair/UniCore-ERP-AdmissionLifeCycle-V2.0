<?php
// audit_trail.php - V2.0 Relational Activity Audit (RBAC Compliant)
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once 'config/db.php';

/**
 * 1. RBAC SECURITY GATE
 * Checks the session directly for 'view_audit' permission before rendering output.
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_perms = $_SESSION['permissions'] ?? [];
if (!in_array('view_audit', $user_perms)) {
    header("Location: index.php?error=unauthorized_capability");
    exit;
}

/**
 * 2. DATA LOGIC (Preserving your specific V1.0 Join Logic)
 * Fetches activity logs joined with Lead (Student) and User (Staff) names.
 */
$sql = "SELECT lal.*, l.full_name as student_name, u.full_name as staff_name 
        FROM lead_activity_log lal
        JOIN leads l ON lal.lead_id = l.id
        JOIN users u ON lal.user_id = u.id
        ORDER BY lal.created_at DESC 
        LIMIT 100";
$logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/**
 * 3. INCLUDE UI COMPONENTS
 */
include_once 'includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lifecycle Audit Trail | UniCore Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden">

    <?php include_once 'includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen flex flex-col bg-[#f8fafc]">
        
        <header class="p-8 flex items-center justify-between border-b bg-white sticky top-0 z-50">
            <div class="flex items-center gap-2 text-slate-400 text-sm">
                <span>Governance & Audit</span> 
                <i class="fas fa-chevron-right text-[10px]"></i> 
                <span class="text-slate-900 font-bold italic">Lifecycle Audit Trail</span>
            </div>
            <div class="flex gap-3">
                <button class="px-4 py-2 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 hover:bg-slate-50 transition-all">
                    <i class="fas fa-download mr-2"></i> Export Logs
                </button>
            </div>
        </header>

        <main class="p-10 w-full max-w-6xl">
            <div class="grid grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-[32px] border border-slate-200 shadow-sm">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Total System Events</p>
                    <p class="text-2xl font-black text-slate-900"><?= count($logs) ?></p>
                </div>
            </div>

            <div class="bg-white rounded-[45px] border border-slate-200 p-12 shadow-sm relative overflow-hidden">
                <h2 class="text-lg font-black text-slate-800 uppercase tracking-tighter mb-12 italic">University Lifecycle Activity</h2>
                
                <div class="space-y-12 relative before:content-[''] before:absolute before:left-6 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">
                    
                    <?php if (empty($logs)): ?>
                        <div class="text-center py-20">
                            <i class="fas fa-fingerprint text-slate-200 text-6xl mb-4"></i>
                            <p class="text-slate-400 font-bold">No activity logs found in the system.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                            <div class="flex items-start gap-10 relative z-10 group">
                                <?php 
                                    $icon_color = "bg-blue-600";
                                    $icon = "fa-fingerprint";
                                    if (strpos($log['action_type'], 'GATE') !== false) { $icon_color = "bg-purple-600"; $icon = "fa-door-open"; }
                                    if ($log['action_type'] == 'CREATION') { $icon_color = "bg-emerald-500"; $icon = "fa-plus"; }
                                ?>
                                <div class="w-12 h-12 <?= $icon_color ?> text-white rounded-full flex items-center justify-center shadow-lg transition-all group-hover:scale-110">
                                    <i class="fas <?= $icon ?> text-sm"></i>
                                </div>

                                <div class="flex-1">
                                    <div class="flex justify-between items-start mb-1">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                            <?= date('M d, Y • h:i A', strtotime($log['created_at'])) ?>
                                        </p>
                                        <span class="bg-slate-100 text-slate-500 text-[8px] font-black px-2 py-1 rounded-md uppercase">
                                            <?= htmlspecialchars($log['action_type']) ?>
                                        </span>
                                    </div>
                                    
                                    <p class="text-base font-black text-slate-900 tracking-tight">
                                        <?= htmlspecialchars($log['description']) ?>
                                    </p>
                                    
                                    <div class="mt-3 flex items-center gap-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 bg-slate-100 rounded-md flex items-center justify-center text-[10px] text-slate-500"><i class="fas fa-user-graduate"></i></div>
                                            <span class="text-[10px] font-bold text-slate-600 uppercase italic"><?= htmlspecialchars($log['student_name']) ?></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 bg-blue-50 rounded-md flex items-center justify-center text-[10px] text-blue-500"><i class="fas fa-user-tie"></i></div>
                                            <span class="text-[10px] font-bold text-blue-600 uppercase tracking-tighter">By: <?= htmlspecialchars($log['staff_name']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>