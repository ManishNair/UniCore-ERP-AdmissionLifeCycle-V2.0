<?php
// role_permissions.php - V2.0 Dynamic Access Matrix
include_once 'includes/header.php';
require_once 'config/db.php';

// SECURITY: Only Superadmins should access this
if ($user_role !== 'Superadmin') {
    header("Location: index.php?error=unauthorized_capability");
    exit;
}

// Fetch all roles and all permissions
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
$all_perms = $pdo->query("SELECT * FROM permissions")->fetchAll();

// Fetch current mapping
$mapping = $pdo->query("SELECT role_id, perm_id FROM role_permissions")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Matrix | UniCore ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="flex min-h-screen">
    <?php include_once 'includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen bg-[#f8fafc] flex flex-col">
        <header class="w-full px-12 pt-12 pb-6">
            <h1 class="text-3xl font-[900] uppercase italic text-slate-900">Access <span class="text-blue-600">Matrix</span></h1>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Global System Capability Governance</p>
        </header>

        <main class="p-12">
            <div class="bg-white rounded-[40px] border border-slate-100 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="p-8 text-[10px] font-black uppercase text-slate-400">System Capability</th>
                            <?php foreach($roles as $role): ?>
                                <th class="p-8 text-center text-[10px] font-black uppercase text-slate-900"><?= $role['role_name'] ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach($all_perms as $p): ?>
                        <tr class="hover:bg-slate-50/50 transition-all">
                            <td class="p-8">
                                <p class="text-xs font-black text-slate-800 uppercase italic leading-none"><?= $p['perm_key'] ?></p>
                                <p class="text-[9px] text-slate-400 font-bold mt-1"><?= $p['perm_desc'] ?></p>
                            </td>
                            <?php foreach($roles as $role): ?>
                                <td class="p-8 text-center">
                                    <input type="checkbox" 
                                           class="w-5 h-5 accent-blue-600 cursor-pointer"
                                           onchange="updatePerm(<?= $role['id'] ?>, <?= $p['id'] ?>, this.checked)"
                                           <?= (isset($mapping[$role['id']]) && in_array($p['id'], $mapping[$role['id']])) ? 'checked' : '' ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    async function updatePerm(roleId, permId, isChecked) {
        const formData = new FormData();
        formData.append('role_id', roleId);
        formData.append('perm_id', permId);
        formData.append('action', isChecked ? 'grant' : 'revoke');

        try {
            const res = await fetch('api/toggle_permission.php', { method: 'POST', body: formData });
            if (!res.ok) alert("Sync Error: Could not update database.");
        } catch (e) { alert("Network Error"); }
    }
    </script>
</body>
</html>