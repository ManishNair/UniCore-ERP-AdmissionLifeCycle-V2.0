<?php
// manage_users.php - V2.0 Dynamic RBAC Implementation
include_once 'includes/header.php'; 
require_once 'config/db.php';

/**
 * 1. DYNAMIC SECURITY GATE
 * Replaced Security::guard with our new has_perm() system.
 */
if (!has_perm('manage_staff')) {
    header("Location: index.php?error=unauthorized_capability");
    exit;
}

// 2. FETCH STAFF REGISTRY WITH ROLE NAMES
$query = "
    SELECT u.*, r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. FETCH COLLEGES FOR UI LABELS
$colleges = $pdo->query("SELECT id, name FROM colleges")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Governance | UniCore ERP V2.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden">

    <?php include_once 'includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen flex flex-col">
        
        <header class="w-full h-auto px-12 pt-12 pb-6 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-[900] uppercase italic tracking-tighter">
                    Staff <span class="text-blue-600">Accounts</span>
                </h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                    Authorized Personnel Registry • System Governance V2.0
                </p>
            </div>
            
            <a href="add_user.php" class="bg-slate-900 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-200">
                <i class="fas fa-user-plus mr-2"></i> Provision New Staff
            </a>
        </header>

        <main class="w-full p-12">
            <div class="bg-white rounded-[45px] shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 text-[9px] font-black text-slate-300 uppercase tracking-widest border-b border-slate-50">
                        <tr>
                            <th class="py-6 px-10">Employee Identity</th>
                            <th class="py-6">System Role</th>
                            <th class="py-6">Institutional Access</th>
                            <th class="py-6 text-right px-10">Operations</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50/50 transition-all group">
                            <td class="py-7 px-10">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-[10px] text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                        <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-900 uppercase italic leading-none mb-1"><?= htmlspecialchars($u['full_name']) ?></p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">@<?= htmlspecialchars($u['username']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-7">
                                <span class="px-3 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-widest border border-blue-100 bg-blue-50 text-blue-600">
                                    <?= htmlspecialchars($u['role_name']) ?>
                                </span>
                            </td>
                            <td class="py-7">
                                <div class="flex flex-wrap gap-1">
                                    <?php 
                                    // Use 'access_all_colleges' permission to check global access
                                    if ($u['college_id'] === 'ALL' || $u['college_id'] === '1,2') {
                                        echo '<span class="text-[9px] font-black text-emerald-600 uppercase italic">Global Access</span>';
                                    } else {
                                        $assigned = explode(',', $u['college_id']);
                                        foreach ($assigned as $cid) {
                                            $cname = $colleges[$cid] ?? 'Unknown';
                                            echo '<span class="bg-slate-100 text-slate-500 text-[8px] font-bold px-2 py-0.5 rounded uppercase">' . htmlspecialchars($cname) . '</span> ';
                                        }
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="py-7 text-right px-10">
                                <div class="flex justify-end gap-2">
                                    <a href="edit_user.php?id=<?= $u['id'] ?>" class="w-8 h-8 rounded-lg bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all">
                                        <i class="fas fa-pen text-[10px]"></i>
                                    </a>
                                    <?php if ($u['id'] != $u_id): ?>
                                    <button onclick="confirmDelete(<?= $u['id'] ?>)" class="w-8 h-8 rounded-lg bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all">
                                        <i class="fas fa-trash text-[10px]"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function confirmDelete(id) {
            if(confirm("CRITICAL: Are you sure you want to revoke all access for this staff member? This action is permanent.")) {
                window.location.href = "api/delete_user.php?id=" + id;
            }
        }
    </script>
</body>
</html>