<?php
// permission_manager.php - V2.0 Visual RBAC Manager
session_start();
require_once 'config/db.php';
require_once 'core/Security.php';

// 1. SECURITY: Only users with 'MANAGE_STAFF' permission can access this
Security::guard('MANAGE_STAFF'); 

$selected_role_id = $_GET['role_id'] ?? null;
$message = "";

// 2. HANDLE PERMISSION UPDATES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $role_id = $_POST['role_id'];
    $new_perms = $_POST['perms'] ?? []; // Array of perm_ids

    try {
        $pdo->beginTransaction();
        
        // Remove existing mappings for this role
        $stmtDelete = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmtDelete->execute([$role_id]);

        // Insert new mappings
        if (!empty($new_perms)) {
            $stmtInsert = $pdo->prepare("INSERT INTO role_permissions (role_id, perm_id) VALUES (?, ?)");
            foreach ($new_perms as $p_id) {
                $stmtInsert->execute([$role_id, $p_id]);
            }
        }

        $pdo->commit();
        $message = "Permissions updated successfully for Role ID: #$role_id";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

// 3. FETCH DATA
$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
$all_permissions = $pdo->query("SELECT * FROM permissions ORDER BY perm_key ASC")->fetchAll();

// Get active permissions for selected role
$active_perms = [];
if ($selected_role_id) {
    $stmt = $pdo->prepare("SELECT perm_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$selected_role_id]);
    $active_perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Permission Manager | UniCore ERP V2.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 flex min-h-screen">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 ml-72 p-12">
        <header class="mb-12">
            <h1 class="text-3xl font-[900] uppercase italic tracking-tighter">Security <span class="text-blue-600">Governance</span></h1>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">V2.0 Visual Role-Based Access Control</p>
        </header>

        <?php if($message): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-600 rounded-2xl text-[10px] font-black uppercase tracking-widest italic">
                <i class="fas fa-check-circle mr-2"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-12 gap-10">
            <div class="col-span-4">
                <div class="bg-white rounded-[40px] p-8 shadow-sm border border-slate-100">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 italic">Select Role to Edit</h3>
                    <div class="space-y-3">
                        <?php foreach($roles as $r): ?>
                            <a href="?role_id=<?= $r['id'] ?>" 
                               class="block p-5 rounded-2xl border-2 transition-all <?= ($selected_role_id == $r['id']) ? 'border-blue-500 bg-blue-50' : 'border-slate-50 hover:border-slate-200 bg-slate-50' ?>">
                                <div class="flex justify-between items-center">
                                    <span class="font-black uppercase italic text-xs <?= ($selected_role_id == $r['id']) ? 'text-blue-700' : 'text-slate-600' ?>">
                                        <?= htmlspecialchars($r['role_name']) ?>
                                    </span>
                                    <i class="fas fa-chevron-right text-[10px] opacity-30"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-span-8">
                <?php if($selected_role_id): ?>
                    <form method="POST" class="bg-white rounded-[40px] p-10 shadow-sm border border-slate-100">
                        <input type="hidden" name="role_id" value="<?= $selected_role_id ?>">
                        <input type="hidden" name="update_permissions" value="1">

                        <div class="flex justify-between items-center mb-10 pb-6 border-b border-slate-50">
                            <h3 class="text-sm font-black text-slate-900 uppercase italic">Managing Capabilities</h3>
                            <button type="submit" class="bg-slate-900 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg">
                                Save Matrix
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <?php foreach($all_permissions as $p): ?>
                                <label class="flex items-center justify-between p-6 bg-slate-50 rounded-3xl border border-slate-100 cursor-pointer hover:bg-slate-100 transition-all group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-slate-400 group-hover:text-blue-500">
                                            <i class="fas fa-key text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-black text-slate-800 uppercase italic leading-none mb-1"><?= $p['perm_key'] ?></p>
                                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest"><?= $p['perm_desc'] ?></p>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="perms[]" value="<?= $p['id'] ?>" 
                                           class="w-6 h-6 rounded-lg accent-blue-600 cursor-pointer" 
                                           <?= in_array($p['id'], $active_perms) ? 'checked' : '' ?>>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="h-full flex flex-col items-center justify-center bg-slate-100/50 border-2 border-dashed border-slate-200 rounded-[40px] p-20 text-center">
                        <i class="fas fa-user-shield text-5xl text-slate-200 mb-6"></i>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest italic">Please select a role from the left to manage system access</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>