<?php
// compliance_queue.php - V2.0 Dynamic RBAC Implementation
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once 'config/db.php';

/**
 * 1. PRE-HEADER SECURITY GATE
 * Prevents "Headers already sent" and "Undefined function" errors by checking
 * the session directly before any HTML (including sidebar.php) is loaded.
 */
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_perms = $_SESSION['permissions'] ?? [];
// We use 'view_compliance' as the gatekeeper for this entire page
if (!in_array('view_compliance', $user_perms)) {
    header("Location: index.php?error=unauthorized_capability");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$role_name = $_SESSION['role_name'] ?? 'Staff';

/**
 * 2. DYNAMIC ACCESS FILTER (V2.0 RBAC)
 * Users with 'access_all_colleges' see the full pipeline.
 * Others are restricted to their assigned leads.
 */
$has_global_view = in_array('access_all_colleges', $user_perms);
$access_filter = $has_global_view ? "1=1" : "l.counselor_id = :uid";

// 3. FETCH ACTIVE PIPELINE (Compliance + Financial Gate)
$qActive = "SELECT l.*, c.name as college_name 
            FROM leads l 
            LEFT JOIN colleges c ON l.college_id = c.id 
            WHERE l.current_stage IN ('Compliance', 'Financial Gate') 
            AND $access_filter 
            ORDER BY l.updated_at DESC";

$stmtA = $pdo->prepare($qActive);
if (!$has_global_view) { 
    $stmtA->bindParam(':uid', $user_id); 
}
$stmtA->execute();
$activeLeads = $stmtA->fetchAll(PDO::FETCH_ASSOC);

// 4. FETCH ENROLLED ARCHIVE
$qEnrolled = "SELECT l.*, c.name as college_name 
              FROM leads l 
              LEFT JOIN colleges c ON l.college_id = c.id 
              WHERE l.current_stage = 'Enrolled' 
              AND $access_filter 
              ORDER BY l.updated_at DESC LIMIT 15";

$stmtE = $pdo->prepare($qEnrolled);
if (!$has_global_view) { 
    $stmtE->bindParam(':uid', $user_id); 
}
$stmtE->execute();
$enrolledLeads = $stmtE->fetchAll(PDO::FETCH_ASSOC);

/**
 * 5. UI LOADING
 */
include_once 'includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lead Pipeline | UniCore ERP V2.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 0; }
        .row-hover:hover { background-color: #f8fafc; transform: translateX(4px); }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden">

    <?php include_once 'includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen flex flex-col bg-[#f8fafc] p-12 overflow-y-auto">
        
        <header class="mb-12 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-[900] uppercase italic tracking-tighter">
                    Lead <span class="text-blue-600">Pipeline</span>
                </h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                    System Role: <span class="text-slate-900"><?= htmlspecialchars($role_name) ?></span> | User: <?= htmlspecialchars($user_name) ?>
                </p>
            </div>
            
            <div class="flex gap-6 bg-white p-6 rounded-[30px] shadow-sm border border-slate-100">
                <div class="text-center border-r border-slate-100 pr-6">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Active Queue</p>
                    <p class="text-xl font-black text-slate-900"><?= count($activeLeads) ?></p>
                </div>
                <div class="text-center">
                    <p class="text-[8px] font-black text-emerald-500 uppercase tracking-widest">Enrolled</p>
                    <p class="text-xl font-black text-emerald-600"><?= count($enrolledLeads) ?></p>
                </div>
            </div>
        </header>

        <div class="mb-16">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 italic flex items-center gap-4">
                Operational Queue <span class="h-[1px] flex-1 bg-slate-200/50"></span>
            </h3>
            
            <div class="bg-white rounded-[40px] shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] font-black text-slate-300 uppercase tracking-widest border-b border-slate-50">
                            <th class="py-6 px-10">Student Identity</th>
                            <th class="py-6">Status & Integrity</th>
                            <th class="py-6 text-right px-10">Verification Gate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (empty($activeLeads)): ?>
                            <tr><td colspan="3" class="py-20 text-center text-slate-300 font-black uppercase text-xs italic">No Active Records</td></tr>
                        <?php else: ?>
                            <?php foreach ($activeLeads as $l): ?>
                            <tr class="row-hover transition-all">
                                <td class="py-6 px-10">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-black text-xs">
                                            <?= strtoupper(substr($l['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-800 italic uppercase text-xs"><?= htmlspecialchars($l['full_name']) ?></p>
                                            <p class="text-[9px] font-bold text-blue-600 uppercase tracking-widest italic"><?= htmlspecialchars($l['college_name'] ?? 'General Admissions') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-6">
                                    <div class="flex flex-col gap-2">
                                        <?php if ($l['current_stage'] === 'Financial Gate'): ?>
                                            <span class="inline-flex items-center w-fit gap-2 px-3 py-1.5 bg-amber-50 text-amber-600 rounded-lg border border-amber-100 text-[8px] font-black uppercase italic">
                                                Finance Gate
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center w-fit gap-2 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg border border-blue-100 text-[8px] font-black uppercase italic">
                                                Compliance Desk
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-6 text-right px-10">
                                    <a href="compliance_desk.php?id=<?= $l['id'] ?>" class="bg-slate-900 text-white px-8 py-2.5 rounded-xl text-[9px] font-black uppercase hover:bg-blue-600 transition-all shadow-lg shadow-slate-200">
                                        <?= $has_global_view ? 'Supervise' : 'Process' ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($enrolledLeads)): ?>
        <div class="mt-20">
            <h3 class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.3em] mb-6 italic flex items-center gap-4">
                Successful Enrollments <span class="h-[1px] flex-1 bg-emerald-100/50"></span>
            </h3>
            
            <div class="bg-white rounded-[40px] border border-emerald-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <tbody class="divide-y divide-emerald-50">
                        <?php foreach ($enrolledLeads as $l): ?>
                        <tr class="hover:bg-emerald-50/30 transition-all">
                            <td class="py-6 px-10">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-xs">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-700 italic uppercase text-xs"><?= htmlspecialchars($l['full_name']) ?></p>
                                        <p class="text-[9px] font-bold text-emerald-600 uppercase tracking-widest italic"><?= htmlspecialchars($l['college_name']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-6 text-right px-10">
                                <a href="compliance_desk.php?id=<?= $l['id'] ?>" class="bg-emerald-50 text-emerald-600 px-6 py-2 rounded-xl text-[9px] font-black uppercase hover:bg-emerald-600 hover:text-white transition-all">
                                    View Profile
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>