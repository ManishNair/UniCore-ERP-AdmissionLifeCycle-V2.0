<?php
// index.php - Final Unified V2.0 (Dynamic RBAC Integration)
include 'includes/header.php'; 

/**
 * 1. VARIABLE MAPPING
 * These variables are now fed directly from the updated header.php session data.
 */
$user_id = $u_id; 
$user_permitted_colleges = $raw_ids; 
$filter_college = $_GET['college_filter'] ?? '';

// 2. ANALYTICS QUERIES
$stats_params = [];
$stats_where = " WHERE 1=1";

// Use the dynamic permission check instead of hardcoded role names
if (!has_perm('access_all_colleges')) {
    $ids = array_filter(explode(',', $user_permitted_colleges));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stats_where .= " AND l.college_id IN ($placeholders)";
        $stats_params = $ids;
    } else { 
        $stats_where .= " AND 1=0"; 
    }
}

// 1. Total Leads
$total_leads = $pdo->prepare("SELECT COUNT(*) FROM leads l $stats_where");
$total_leads->execute($stats_params);
$count_all = $total_leads->fetchColumn();

// 2. Compliance Gate Stats (Only calculated if user has permission)
$count_compliance = 0;
if (has_perm('view_compliance')) {
    $compliance = $pdo->prepare("SELECT COUNT(*) FROM leads l $stats_where AND l.current_stage IN ('Compliance', 'Verification')");
    $compliance->execute($stats_params);
    $count_compliance = $compliance->fetchColumn();
}

// 3. Financial Gate Stats (Only calculated if user has permission)
$count_financial = 0;
if (has_perm('view_finance_gate')) {
    $financial = $pdo->prepare("SELECT COUNT(*) FROM leads l $stats_where AND l.current_stage = 'Financial'");
    $financial->execute($stats_params);
    $count_financial = $financial->fetchColumn();
}

// 4. Enrolled
$enrolled = $pdo->prepare("SELECT COUNT(*) FROM leads l $stats_where AND l.current_stage = 'Enrolled'");
$enrolled->execute($stats_params);
$count_enrolled = $enrolled->fetchColumn();

// 5. Revenue Logic (Only calculated if user has permission)
$total_revenue = 0;
$revenue_30d = 0;
if (has_perm('view_revenue')) {
    try {
        $rev_query = "SELECT SUM(f.paid_amt) FROM student_finances f JOIN leads l ON f.lead_id = l.id $stats_where";
        $revenue_stmt = $pdo->prepare($rev_query);
        $revenue_stmt->execute($stats_params);
        $total_revenue = $revenue_stmt->fetchColumn() ?? 0;

        $rev_30_query = "SELECT SUM(f.paid_amt) FROM student_finances f JOIN leads l ON f.lead_id = l.id $stats_where AND f.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $revenue_30_stmt = $pdo->prepare($rev_30_query);
        $revenue_30_stmt->execute($stats_params);
        $revenue_30d = $revenue_30_stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) { $total_revenue = 0; $revenue_30d = 0; }
}

// 3. MAIN REGISTRY TABLE QUERY
$query = "SELECT l.*, c.name as college_name, crs.course_name 
          FROM leads l 
          LEFT JOIN colleges c ON l.college_id = c.id 
          LEFT JOIN courses crs ON l.course_id = crs.id 
          $stats_where";

$main_params = $stats_params;
if (!empty($filter_college)) {
    $query .= " AND l.college_id = ?";
    $main_params[] = $filter_college;
}
$query .= " ORDER BY l.created_at DESC LIMIT 50";
$stmt = $pdo->prepare($query);
$stmt->execute($main_params);
$leads = $stmt->fetchAll();

// 4. DROPDOWN OPTIONS
$dropdown_colleges = [];
if (has_perm('access_all_colleges')) {
    $dropdown_colleges = $pdo->query("SELECT id, name FROM colleges")->fetchAll();
} elseif (!empty($user_permitted_colleges)) {
    $safe_ids = preg_replace('/[^0-9,]/', '', $user_permitted_colleges);
    if (!empty($safe_ids)) { $dropdown_colleges = $pdo->query("SELECT id, name FROM colleges WHERE id IN ($safe_ids)")->fetchAll(); }
}
?>

<main class="p-12">
    <header class="mb-10 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-[900] uppercase italic tracking-tighter text-slate-900 leading-none">
                System <span class="text-blue-600">Overview</span>
            </h1>
            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-2 italic">
                Real-time Pipeline Intelligence
            </p>
        </div>
        
        <form method="GET" class="flex items-center gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
            <select name="college_filter" onchange="this.form.submit()" class="bg-transparent px-4 py-2 text-[10px] font-black uppercase tracking-widest outline-none cursor-pointer">
                <option value="">All Institutions</option>
                <?php foreach ($dropdown_colleges as $dc): ?>
                    <option value="<?= $dc['id'] ?>" <?= $filter_college == $dc['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dc['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-12">
        
        <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Leads</p>
            <h2 class="text-2xl font-[900] text-slate-900 italic"><?= number_format($count_all) ?></h2>
        </div>

        <?php if (has_perm('view_compliance')): ?>
        <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm border-l-4 border-l-amber-400">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Compliance Gate</p>
            <h2 class="text-2xl font-[900] text-slate-900 italic"><?= number_format($count_compliance) ?></h2>
        </div>
        <?php endif; ?>

        <?php if (has_perm('view_finance_gate')): ?>
        <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm border-l-4 border-l-blue-400">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Financial Gate</p>
            <h2 class="text-2xl font-[900] text-slate-900 italic"><?= number_format($count_financial) ?></h2>
        </div>
        <?php endif; ?>

        <div class="bg-blue-600 p-6 rounded-[32px] shadow-lg shadow-blue-200 text-white">
            <p class="text-[9px] font-black text-blue-100 uppercase tracking-widest mb-2">Total Enrolled</p>
            <h2 class="text-2xl font-[900] italic"><?= number_format($count_enrolled) ?></h2>
        </div>

        <?php if (has_perm('view_revenue')): ?>
        <div class="bg-emerald-600 p-6 rounded-[32px] shadow-lg shadow-emerald-200 text-white">
            <div class="flex justify-between items-start mb-1">
                <p class="text-[9px] font-black text-emerald-100 uppercase tracking-widest">Revenue</p>
                <span class="text-[7px] font-black bg-white/20 px-1.5 py-0.5 rounded italic">30D: ₹<?= number_format($revenue_30d) ?></span>
            </div>
            <h2 class="text-2xl font-[900] italic">₹<?= number_format($total_revenue) ?></h2>
        </div>
        <?php endif; ?>

    </div>

    <div class="bg-white rounded-[45px] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
            <h3 class="text-xs font-black uppercase italic tracking-widest text-slate-400">Intake Pipeline (Top 50)</h3>
            <div class="flex gap-4">
                <?php if (has_perm('view_compliance')): ?>
                    <a href="compliance_queue.php" class="text-[9px] font-black text-amber-600 uppercase border border-amber-200 px-3 py-2 rounded-xl hover:bg-amber-50 transition-all">Compliance Desk</a>
                <?php endif; ?>
                <?php if (has_perm('view_finance_gate')): ?>
                    <a href="financial_gate.php" class="text-[9px] font-black text-blue-600 uppercase border border-blue-200 px-3 py-2 rounded-xl hover:bg-blue-50 transition-all">Financial Desk</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[9px] font-black text-slate-300 uppercase tracking-widest">
                    <tr>
                        <th class="py-6 px-10">Lead Identity</th>
                        <th class="py-6">Course Specialty</th>
                        <th class="py-6">Institution</th>
                        <th class="py-6 text-right px-10">Current Stage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($leads)): ?>
                        <tr><td colspan="4" class="py-12 text-center text-slate-400 uppercase font-black text-[10px] italic">No active leads in scope</td></tr>
                    <?php else: ?>
                        <?php foreach ($leads as $l): ?>
                        <tr class="hover:bg-slate-50/50 transition-all">
                            <td class="py-6 px-10">
                                <p class="font-black text-slate-900 uppercase italic text-xs leading-none mb-1"><?= htmlspecialchars($l['full_name']) ?></p>
                                <p class="text-[9px] text-slate-400 font-bold tracking-tight"><?= htmlspecialchars($l['phone']) ?></p>
                            </td>
                            <td class="py-6"><span class="text-[10px] font-black text-blue-600 uppercase italic"><?= htmlspecialchars($l['course_name'] ?? 'General Inquiry') ?></span></td>
                            <td class="py-6"><p class="text-[10px] font-bold text-slate-500 uppercase italic"><?= htmlspecialchars($l['college_name'] ?? 'Global Scope') ?></p></td>
                            <td class="py-6 text-right px-10"><span class="px-3 py-1 bg-slate-100 rounded-lg text-[8px] font-black uppercase text-slate-500 border border-slate-200"><?= htmlspecialchars($l['current_stage']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>