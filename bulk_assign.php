<?php
// bulk_assign.php - V2.0 Dynamic RBAC Implementation
include_once 'includes/header.php'; 
require_once 'config/db.php';

/**
 * 1. DYNAMIC SECURITY GATE
 * Replaced Security::guard with our new has_perm() system.
 */
if (!has_perm('bulk_assign')) {
    header("Location: index.php?error=unauthorized_capability");
    exit;
}

$user_id = $u_id; // Inherited from header.php
$role_name = $user_role; // Inherited from header.php

/**
 * 2. FETCH LEADS FOR DISTRIBUTION
 * Use 'access_all_colleges' permission to determine data scope.
 */
$access_filter = has_perm('access_all_colleges') ? "1=1" : "l.counselor_id = :uid";

$query = "
    SELECT l.*, c.name as college_name, u.full_name as current_counselor
    FROM leads l 
    LEFT JOIN colleges c ON l.college_id = c.id 
    LEFT JOIN users u ON l.counselor_id = u.id
    WHERE l.current_stage != 'Enrolled'
    AND $access_filter
    ORDER BY l.counselor_id ASC, l.created_at DESC
";

$stmtLeads = $pdo->prepare($query);
if (!has_perm('access_all_colleges')) { 
    $stmtLeads->bindParam(':uid', $user_id); 
}
$stmtLeads->execute();
$leads = $stmtLeads->fetchAll(PDO::FETCH_ASSOC);

/**
 * 3. FETCH TARGET COUNSELORS
 */
$counselors = $pdo->query("
    SELECT u.id, u.full_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.role_name = 'Counselor' OR r.id = 1
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lead Distribution | UniCore ERP V2.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden">

    <?php include_once 'includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen bg-[#f8fafc] flex flex-col">
        
        <header class="w-full h-[120px] px-12 bg-white border-b border-slate-100 flex justify-between items-center sticky top-0 z-40 shadow-sm">
            <div>
                <h1 class="text-3xl font-[900] uppercase italic tracking-tighter text-slate-900">
                    Lead <span class="text-blue-600">Distribution</span>
                </h1>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1 italic">
                    Authorized Personnel Only • Dynamic V2.0 RBAC
                </p>
            </div>
            
            <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <div class="text-right">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest leading-none">Selected Volume</p>
                    <p id="counterDisplay" class="text-lg font-black text-blue-600">0</p>
                </div>
            </div>
        </header>

        <main class="w-full p-12">
            
            <div id="assignBar" class="fixed bottom-10 left-[calc(50%+144px)] -translate-x-1/2 bg-slate-900 text-white px-10 py-6 rounded-[40px] shadow-2xl z-[100] flex items-center gap-10 border border-white/10 opacity-0 translate-y-20 pointer-events-none transition-all duration-500">
                <div class="flex flex-col border-r border-white/10 pr-10">
                    <span class="text-[9px] font-black uppercase tracking-widest opacity-50">Transfer to</span>
                    <select id="targetCounselor" class="bg-transparent text-blue-400 font-black uppercase text-xs outline-none cursor-pointer mt-1">
                        <option value="" class="bg-slate-900">Select Counselor...</option>
                        <?php foreach($counselors as $c): ?>
                            <option value="<?= $c['id'] ?>" class="bg-slate-900"><?= htmlspecialchars($c['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button onclick="executeAssignment()" class="bg-blue-600 hover:bg-blue-500 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95 shadow-xl shadow-blue-500/20">
                    Confirm Batch Move
                </button>
            </div>

            <div class="w-full space-y-4 pb-32">
                <?php if(empty($leads)): ?>
                    <div class="w-full bg-white rounded-[40px] p-20 text-center border-2 border-dashed border-slate-200">
                        <p class="text-xs font-black text-slate-300 uppercase italic">No active leads available for distribution</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                    <div class="w-full bg-white rounded-[40px] p-6 px-10 border border-slate-100 flex items-center justify-between hover:border-blue-200 transition-all shadow-sm group">
                        <div class="flex items-center gap-8">
                            <input type="checkbox" 
                                   class="lead-check w-6 h-6 accent-blue-600 rounded-lg cursor-pointer transition-transform group-hover:scale-110" 
                                   value="<?= $lead['id'] ?>" 
                                   onclick="updateSelection()">
                            
                            <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-blue-50 group-hover:text-blue-500 transition-colors">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            
                            <div>
                                <h3 class="font-[900] text-slate-900 uppercase text-xs italic tracking-tight mb-0.5"><?= htmlspecialchars($lead['full_name']) ?></h3>
                                <div class="flex gap-3 items-center">
                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($lead['college_name'] ?? 'General') ?></span>
                                    <span class="w-1 h-1 bg-slate-200 rounded-full"></span>
                                    <span class="text-[8px] font-black text-blue-500 uppercase tracking-widest italic"><?= $lead['current_stage'] ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 italic">Current Handled By</p>
                            <span class="text-[10px] font-black uppercase text-slate-900">
                                <?= htmlspecialchars($lead['current_counselor'] ?? 'UNASSIGNED') ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function updateSelection() {
            const selected = document.querySelectorAll('.lead-check:checked');
            const bar = document.getElementById('assignBar');
            const counter = document.getElementById('counterDisplay');
            
            counter.innerText = selected.length;
            
            if (selected.length > 0) {
                bar.classList.remove('opacity-0', 'translate-y-20', 'pointer-events-none');
                bar.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
            } else {
                bar.classList.add('opacity-0', 'translate-y-20', 'pointer-events-none');
                bar.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
            }
        }

        async function executeAssignment() {
            const targetId = document.getElementById('targetCounselor').value;
            if(!targetId) return alert("Please select a target counselor.");

            const leadIds = Array.from(document.querySelectorAll('.lead-check:checked')).map(cb => cb.value);

            if(!confirm(`Transfer ${leadIds.length} leads to selected staff?`)) return;

            try {
                const response = await fetch('api/process_assignment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lead_ids: leadIds, counselor_id: targetId })
                });

                const result = await response.json();
                if(result.success) {
                    location.reload();
                } else {
                    alert("Transfer Error: " + result.message);
                }
            } catch (error) {
                alert("Network communication failure.");
            }
        }
    </script>
</body>
</html>