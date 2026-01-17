<?php
// add_user.php - V1.0 (Superadmin Edition)
session_start();
require_once 'config/db.php';

// 1. SECURITY: Only Superadmin level can provision new staff (V1.0 Update)
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Superadmin') !== 0) {
    header("Location: index.php");
    exit;
}

$message = "";

// 2. FETCH COLLEGES FOR ASSIGNMENT
$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC")->fetchAll();

// 3. PROCESS FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role      = $_POST['role'];
    
    // LOGIC: If Superadmin, they get 'ALL'. If Counselor, they get the selected IDs (V1.0 Update)
    if ($role === 'Superadmin') {
        $college_assignment = 'ALL';
    } else {
        $college_assignment = isset($_POST['college_ids']) ? implode(',', $_POST['college_ids']) : '1';
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, role, college_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$full_name, $username, $password, $role, $college_assignment])) {
            $message = "<div class='bg-emerald-50 border border-emerald-200 text-emerald-600 p-4 rounded-2xl font-bold text-xs uppercase tracking-widest mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> Staff Account Provisioned Successfully!</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='bg-rose-50 border border-rose-200 text-rose-600 p-4 rounded-2xl font-bold text-xs uppercase tracking-widest mb-6 shadow-sm'><i class='fas fa-times-circle mr-2'></i> Error: Username already exists in the system.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provision Staff | UniCore ERP V1.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .custom-select:focus { border-color: #2563eb; ring: 2px; ring-color: #2563eb; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="flex min-h-screen">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 ml-72 transition-all duration-300 ease-in-out">
        
        <header class="h-[100px] px-12 bg-white border-b flex justify-between items-center sticky top-0 z-40 shadow-sm">
            <div>
                <h1 class="text-2xl font-[900] uppercase italic tracking-tighter text-slate-900">Provision <span class="text-blue-600">Staff</span></h1>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1 italic">Role-Based Access Control & Multi-College Assignment</p>
            </div>
            <a href="manage_users.php" class="text-slate-400 hover:text-slate-900 font-bold text-[10px] uppercase tracking-widest flex items-center gap-2 transition-all">
                <i class="fas fa-chevron-left"></i> Return to Staff List
            </a>
        </header>

        <main class="p-12 flex justify-center">
            <div class="max-w-2xl w-full bg-white rounded-[40px] p-10 border border-slate-200 shadow-2xl shadow-slate-200/50">
                
                <?= $message ?>

                <form method="POST" class="space-y-8">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">Full Legal Name</label>
                            <input type="text" name="full_name" required placeholder="e.g. Sarah Jenkins" 
                                   class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl py-4 px-6 font-bold text-slate-900 outline-none focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">System Username</label>
                            <input type="text" name="username" required placeholder="sarah_admin" 
                                   class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl py-4 px-6 font-bold text-slate-900 outline-none focus:border-blue-500 transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">Initial Password</label>
                            <input type="password" name="password" required placeholder="••••••••" 
                                   class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl py-4 px-6 font-bold text-slate-900 outline-none focus:border-blue-500 transition-all">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">Access Role</label>
                            <select name="role" id="roleSelect" onchange="toggleCollegeSelector()" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl py-4 px-6 font-bold text-slate-900 outline-none focus:border-blue-500 appearance-none">
                                <option value="Counselor">Counselor (Standard)</option>
                                <option value="Superadmin">Superadmin (Global Admin)</option>
                            </select>
                        </div>
                    </div>

                    <div id="collegeSelectorSection">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">College Assignment (Hold Ctrl/Cmd to select multiple)</label>
                        <select name="college_ids[]" multiple class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl p-4 font-bold text-slate-900 outline-none focus:border-blue-500 min-h-[150px] transition-all scrollbar-hide">
                            <?php foreach ($colleges as $c): ?>
                                <option value="<?= $c['id'] ?>" class="p-2 rounded-lg mb-1 hover:bg-blue-100 cursor-pointer"><?= strtoupper($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[8px] font-bold text-slate-400 mt-2 ml-4 uppercase tracking-widest italic">Note: Staff can only see leads belonging to their assigned institutions.</p>
                    </div>

                    <div id="globalAccessNotice" class="hidden bg-blue-50 p-6 rounded-3xl border border-blue-100">
                        <div class="flex gap-4">
                            <i class="fas fa-globe text-blue-500 text-xl"></i>
                            <div>
                                <h4 class="text-[11px] font-black text-blue-900 uppercase tracking-widest">Global Administrator Privileges</h4>
                                <p class="text-[10px] text-blue-700 font-medium mt-1 leading-relaxed">Superadmins bypass institutional restrictions. This user will have visibility over all colleges and global system logs.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6">
                        <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-[25px] font-[900] text-xs uppercase tracking-[0.2em] shadow-xl hover:bg-blue-600 transition-all active:scale-95">
                            Authorize & Create Account
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleCollegeSelector() {
            const role = document.getElementById('roleSelect').value;
            const selector = document.getElementById('collegeSelectorSection');
            const notice = document.getElementById('globalAccessNotice');

            if (role === 'Superadmin') {
                // Hide multi-select for Superadmins as they get 'ALL' access (V1.0 Update)
                selector.classList.add('hidden');
                notice.classList.remove('hidden');
            } else {
                // Show multi-select for Counselors
                selector.classList.remove('hidden');
                notice.classList.add('hidden');
            }
        }
        
        // Run once on load to ensure UI state matches default value
        window.onload = toggleCollegeSelector;
    </script>
</body>
</html>