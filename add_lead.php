<?php
// add_lead.php - V2.0 Relational Lead Ingestion (Dynamic RBAC)
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once 'config/db.php';

/**
 * 1. PRE-HEADER SECURITY GATE
 * This MUST run before including header.php to avoid "Headers already sent" error.
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check session permissions directly before HTML starts
$user_perms = $_SESSION['permissions'] ?? [];
if (!in_array('add_leads', $user_perms)) {
    header("Location: index.php?error=unauthorized_capability");
    exit;
}

/**
 * 2. INCLUDE VIEW COMPONENTS
 * Now it is safe to load the header and sidebar.
 */
include_once 'includes/header.php'; 

// Inherit values from the already loaded header.php
$user_permitted_colleges = $raw_ids; 

/**
 * 3. FETCH PERMITTED COLLEGES
 */
$colleges = [];
if (has_perm('access_all_colleges')) {
    $colleges = $pdo->query("SELECT id, name FROM colleges")->fetchAll();
} else {
    $allowed_ids = array_filter(explode(',', $user_permitted_colleges));
    if (!empty($allowed_ids)) {
        $placeholders = implode(',', array_fill(0, count($allowed_ids), '?'));
        $stmtC = $pdo->prepare("SELECT id, name FROM colleges WHERE id IN ($placeholders)");
        $stmtC->execute($allowed_ids);
        $colleges = $stmtC->fetchAll();
    }
}

/**
 * 4. FETCH COURSES FOR JS MAPPING
 */
$courses = $pdo->query("SELECT id, course_name, college_id FROM courses")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provision Lead | UniCore ERP V2.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
        .glass-card { background: white; border-radius: 40px; border: 1px solid #f1f5f9; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden">

    <?php include_once 'includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen flex flex-col bg-[#f8fafc]">
        
        <header class="w-full h-auto px-12 pt-12 pb-6 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-[900] uppercase italic tracking-tighter text-slate-900 leading-none">
                    Provision <span class="text-blue-600">Lead</span>
                </h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2 italic">
                    Authorized Personnel Entry • Dynamic V2.0 RBAC
                </p>
            </div>
            <a href="index.php" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-900 transition-all">
                <i class="fas fa-times-circle mr-1"></i> Discard
            </a>
        </header>

        <main class="w-full p-12">
            <?php if (empty($colleges) && !has_perm('access_all_colleges')): ?>
                <div class="w-full bg-rose-50 border border-rose-100 p-12 rounded-[40px] text-center">
                    <i class="fas fa-shield-alt text-rose-200 text-5xl mb-4"></i>
                    <h2 class="text-rose-600 font-black uppercase italic text-sm tracking-widest">Access Restricted</h2>
                    <p class="text-[10px] text-rose-400 font-bold uppercase mt-2">No institutional permissions found for your profile.</p>
                </div>
            <?php else: ?>

                <form action="api/process_lead.php" method="POST" class="glass-card p-12 max-w-5xl">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
                        
                        <div class="space-y-8">
                            <div>
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-3 block tracking-widest italic">Full Legal Name</label>
                                <input type="text" name="full_name" required placeholder="Student Name" 
                                       class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            </div>
                            <div>
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-3 block tracking-widest italic">Contact Number</label>
                                <input type="text" name="phone" required placeholder="+91 00000 00000" 
                                       class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div>
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-3 block tracking-widest italic">Target Institution</label>
                                <select id="college_select" name="college_id" required onchange="filterCourses()" 
                                        class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer">
                                    <option value="">Select College...</option>
                                    <?php foreach ($colleges as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-[9px] font-black uppercase text-slate-400 mb-3 block tracking-widest italic">Course Specialty</label>
                                <select id="course_select" name="course_id" required 
                                        class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer">
                                    <option value="">Select College First...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-slate-900 text-white py-6 rounded-3xl font-black uppercase italic tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-200">
                        Register Lead into Pipeline
                    </button>
                </form>

            <?php endif; ?>
        </main>
    </div>

    <script>
        const allCourses = <?= json_encode($courses) ?>;

        function filterCourses() {
            const collegeId = document.getElementById('college_select').value;
            const courseSelect = document.getElementById('course_select');
            courseSelect.innerHTML = '<option value="">Select Specialty...</option>';
            if (!collegeId) return;

            const filtered = allCourses.filter(c => c.college_id == collegeId);
            filtered.forEach(course => {
                const opt = document.createElement('option');
                opt.value = course.id;
                opt.textContent = course.course_name;
                courseSelect.appendChild(opt);
            });
        }
    </script>
</body>
</html>