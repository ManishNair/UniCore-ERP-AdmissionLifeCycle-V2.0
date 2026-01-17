<?php
// global_analytics.php
require_once 'config/db.php';
require_once 'core/Security.php';

session_start();

// --- AUTH PROTECT ---
// In a real app, only 'admin' or 'team_lead' roles should access this.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // For learning V1.0, we will let it slide, but in V2.0 we'd redirect to login.
}

// 1. FETCH TOTALS (The Big Numbers)
$totals = $pdo->query("
    SELECT 
        COUNT(*) as total_leads,
        SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as total_enrolled,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as total_rejected,
        SUM(CASE WHEN status = 'paid' OR status = 'enrolled' THEN 1 ELSE 0 END) as revenue_generating
    FROM leads
")->fetch();

// 2. FETCH COLLEGE PERFORMANCE (Group By)
$college_stats = $pdo->query("
    SELECT c.name, COUNT(l.id) as count, SUM(c.base_fee) as potential_rev
    FROM colleges c
    LEFT JOIN leads l ON c.id = l.college_id
    GROUP BY c.id
")->fetchAll();

// 3. FETCH SOURCE EFFECTIVENESS
$source_stats = $pdo->query("
    SELECT source, COUNT(*) as count 
    FROM leads 
    GROUP BY source 
    ORDER BY count DESC
")->fetchAll();

// 4. CALCULATE CONVERSION RATE
$conversion_rate = ($totals['total_leads'] > 0) 
    ? round(($totals['total_enrolled'] / $totals['total_leads']) * 100, 1) 
    : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Analytics | UniCore ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen p-12">

    <div class="max-w-6xl mx-auto">
        <header class="flex justify-between items-center mb-12">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">University Analytics</h1>
                <p class="text-slate-500 text-sm font-bold uppercase tracking-widest mt-1">Real-time Performance Metrics</p>
            </div>
            <a href="index.php" class="bg-white border border-slate-200 px-6 py-3 rounded-2xl font-bold text-xs uppercase text-slate-600 hover:bg-slate-50">
                <i class="fas fa-arrow-left mr-2"></i> Dashboard
            </a>
        </header>

        <div class="grid grid-cols-4 gap-6 mb-12">
            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Total Inquiries</p>
                <h3 class="text-3xl font-black text-slate-800"><?= $totals['total_leads'] ?></h3>
            </div>
            <div class="bg-blue-600 p-8 rounded-[40px] shadow-lg shadow-blue-200 text-white">
                <p class="text-[10px] font-black text-blue-200 uppercase mb-2">Total Enrolled</p>
                <h3 class="text-3xl font-black"><?= $totals['total_enrolled'] ?></h3>
            </div>
            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Conversion Rate</p>
                <h3 class="text-3xl font-black text-emerald-500"><?= $conversion_rate ?>%</h3>
            </div>
            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Blacklisted/Fake</p>
                <h3 class="text-3xl font-black text-red-400"><?= $totals['total_rejected'] ?></h3>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-8">
            <div class="col-span-8 bg-white rounded-[40px] shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-8 border-b border-slate-50">
                    <h4 class="font-black text-slate-800 uppercase text-xs tracking-widest">Campus Distribution</h4>
                </div>
                <table class="w-full text-left">
                    <thead class="bg-slate-50">
                        <tr class="text-[10px] font-black text-slate-400 uppercase">
                            <th class="p-6">College Name</th>
                            <th class="p-6">Lead Count</th>
                            <th class="p-6">Potential Fee</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach($college_stats as $stat): ?>
                        <tr class="text-sm font-bold text-slate-700">
                            <td class="p-6"><?= $stat['name'] ?></td>
                            <td class="p-6"><?= $stat['count'] ?></td>
                            <td class="p-6 text-emerald-600">₹<?= number_format($stat['potential_rev'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="col-span-4 bg-white rounded-[40px] shadow-sm border border-slate-200 p-8">
                <h4 class="font-black text-slate-800 uppercase text-xs tracking-widest mb-8">Lead Sources</h4>
                <div class="space-y-6">
                    <?php foreach($source_stats as $s): 
                        $width = ($totals['total_leads'] > 0) ? ($s['count'] / $totals['total_leads']) * 100 : 0;
                    ?>
                    <div>
                        <div class="flex justify-between text-[10px] font-black uppercase mb-2">
                            <span class="text-slate-500"><?= $s['source'] ?></span>
                            <span class="text-slate-900"><?= $s['count'] ?></span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-blue-500 h-full" style="width: <?= $width ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>