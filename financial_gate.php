<?php
// financial_gate.php - V2.0 Dynamic RBAC Implementation
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once 'config/db.php';

/**
 * 1. PRE-HEADER SECURITY GATE
 * This MUST run before including sidebar.php to avoid "Fatal error: has_perm()".
 */
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

// Check session permissions directly for the 'view_finance' key
$user_perms = $_SESSION['permissions'] ?? [];
if (!in_array('view_finance', $user_perms)) {
    header("Location: index.php?error=unauthorized_capability");
    exit;
}

/**
 * 2. FETCH STUDENTS IN THE FINANCIAL STAGE
 * Only leads in 'Financial Gate' stage appear here for audit.
 */
$query = "SELECT l.*, c.name as college_name 
          FROM leads l 
          LEFT JOIN colleges c ON l.college_id = c.id 
          WHERE l.current_stage = 'Financial Gate'
          ORDER BY l.updated_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$financial_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * 3. UI LOADING
 */
include_once 'includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Gate | UniCore ERP V2.0</title>
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
        
        <header class="h-[120px] px-12 bg-white border-b flex justify-between items-center sticky top-0 z-40 shadow-sm">
            <div>
                <h1 class="text-3xl font-[900] uppercase italic tracking-tighter text-slate-900">
                    Financial <span class="text-blue-600">Gate</span>
                </h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1 italic">
                    Fee Verification & Official Enrollment
                </p>
            </div>
            
            <div class="bg-amber-50 border border-amber-100 px-6 py-3 rounded-2xl">
                <p class="text-[8px] font-black text-amber-500 uppercase tracking-widest text-center">Pending Audits</p>
                <p class="text-xl font-black text-amber-600 text-center"><?= count($financial_leads) ?></p>
            </div>
        </header>

        <main class="p-12">
            <div class="bg-white rounded-[40px] shadow-sm border border-slate-100 p-10">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-300 uppercase tracking-widest border-b border-slate-50">
                            <th class="pb-6">Student & Institution</th>
                            <th class="pb-6">Payment Reference</th>
                            <th class="pb-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (empty($financial_leads)): ?>
                            <tr>
                                <td colspan="3" class="py-24 text-center">
                                    <div class="opacity-20">
                                        <i class="fas fa-file-invoice-dollar text-5xl mb-4"></i>
                                        <p class="text-xs font-black uppercase tracking-widest">No pending financial clearances</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($financial_leads as $l): 
                                $pRef = isset($l['payment_ref']) && !empty($l['payment_ref']) ? $l['payment_ref'] : null;
                            ?>
                            <tr class="group">
                                <td class="py-8">
                                    <p class="font-black text-slate-800 italic uppercase text-sm"><?= htmlspecialchars($l['full_name']) ?></p>
                                    <p class="text-[9px] font-bold text-blue-600 uppercase mt-1"><?= htmlspecialchars($l['college_name'] ?? 'General') ?></p>
                                </td>
                                <td class="py-8">
                                    <?php if ($pRef): ?>
                                        <span class="bg-slate-100 px-4 py-2 rounded-xl text-[10px] font-mono font-black text-slate-600 border border-slate-200">
                                            <?= htmlspecialchars($pRef) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[9px] font-black text-rose-500 bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-100 uppercase italic animate-pulse">
                                            Awaiting Student Input
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-8 text-right action-cell">
                                    <button onclick="finalizeEnrollment(<?= $l['id'] ?>, this)" 
                                            <?= !$pRef ? 'disabled' : '' ?>
                                            class="bg-[#101424] text-white px-8 py-4 rounded-[20px] text-[10px] font-black uppercase tracking-widest transition-all <?= $pRef ? 'hover:bg-emerald-600 shadow-xl' : 'opacity-20 cursor-not-allowed' ?>">
                                        Verify & Enroll
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    function finalizeEnrollment(id, btn) {
        if(!confirm("Are you sure? This officially enrolls the student.")) return;
        
        const cell = btn.closest('.action-cell');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch('api/finalize_enrollment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lead_id: id })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                cell.innerHTML = `
                    <div class="flex flex-col items-end gap-2 animate-in fade-in slide-in-from-right-4 duration-500">
                        <span class="text-[10px] font-black text-emerald-600 uppercase italic mb-1">
                            <i class="fas fa-check-double mr-1"></i> Enrolled Successfully
                        </span>
                        <div class="flex gap-2">
                            <button onclick="window.open('${data.whatsapp_url}', '_blank')" 
                                    class="bg-emerald-600 text-white px-6 py-3 rounded-xl text-[9px] font-black uppercase tracking-widest shadow-lg hover:bg-emerald-700 transition-all">
                                <i class="fab fa-whatsapp mr-1"></i> Send Receipt
                            </button>
                            <button onclick="location.reload()" 
                                    class="bg-slate-100 text-slate-400 px-4 py-3 rounded-xl text-[9px] font-black uppercase hover:bg-slate-200 transition-all">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            } else {
                alert("Error: " + data.message);
                btn.innerHTML = "Verify & Enroll";
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert("Network Error.");
        });
    }
    </script>
</body>
</html>