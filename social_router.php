<?php
// social_router.php - V2.0 Dynamic RBAC Implementation
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once 'config/db.php';

/**
 * 1. PRE-HEADER SECURITY GATE
 * Prevents unauthorized access and "Headers already sent" errors.
 */
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

// Check session array directly for the specific 'social_router' permission
$user_perms = $_SESSION['permissions'] ?? [];
if (!in_array('social_router', $user_perms)) {
    header("Location: index.php?error=unauthorized_capability");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

/**
 * 2. FETCH WEBHOOK LOGS (WhatsApp/Social Ingestion)
 * Filters leads where the source indicates external API or social intake.
 */
$query = "SELECT l.*, c.name as college_name 
          FROM leads l 
          LEFT JOIN colleges c ON l.college_id = c.id 
          WHERE l.source IN ('WhatsApp', 'Social', 'API')
          ORDER BY l.created_at DESC 
          LIMIT 50";

$stmt = $pdo->prepare($query);
$stmt->execute();
$inboundLeads = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * 3. AGGREGATE STATS FOR MONITORING
 */
$stats = $pdo->query("SELECT 
    COUNT(*) as total_social,
    SUM(CASE WHEN source = 'WhatsApp' THEN 1 ELSE 0 END) as wa_count,
    SUM(CASE WHEN source = 'API' THEN 1 ELSE 0 END) as api_count
    FROM leads WHERE source IN ('WhatsApp', 'Social', 'API')")->fetch();

/**
 * 4. UI LOADING
 */
include_once 'includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Social Intake Router | UniCore ERP V2.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .pulse-node { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden">

    <?php include_once 'includes/sidebar.php'; ?>

    <div class="flex-1 min-h-screen flex flex-col bg-[#f8fafc] p-12 overflow-y-auto">
        
        <header class="mb-12 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-[900] uppercase italic tracking-tighter">
                    Social <span class="text-blue-600">Router</span>
                </h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                    System Node: <span class="text-slate-900">Inbound Webhook Monitor</span> | RBAC Verified
                </p>
            </div>
            
            <div class="flex gap-4">
                <div class="bg-white px-6 py-4 rounded-3xl shadow-sm border border-slate-100 text-center">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Global Inbound</p>
                    <p class="text-xl font-black text-slate-900"><?= number_format($stats['total_social'] ?? 0) ?></p>
                </div>
                <div class="bg-blue-600 px-6 py-4 rounded-3xl shadow-xl shadow-blue-500/20 text-center text-white">
                    <p class="text-[8px] font-black text-blue-200 uppercase tracking-widest">WhatsApp Nodes</p>
                    <p class="text-xl font-black"><?= number_format($stats['wa_count'] ?? 0) ?></p>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-12 gap-8">
            
            <div class="col-span-12 bg-slate-900 rounded-[45px] p-10 shadow-2xl border border-slate-800">
                <div class="flex items-center justify-between mb-10">
                    <div class="flex items-center gap-4">
                        <div class="w-3 h-3 bg-emerald-500 rounded-full pulse-node"></div>
                        <h3 class="text-xs font-black text-white uppercase tracking-[0.3em] italic">Live Ingestion Stream</h3>
                    </div>
                    <a href="api/log_viewer.php" class="text-[9px] font-black text-slate-500 uppercase hover:text-white transition-all">
                        View Raw Payload Ledger <i class="fas fa-external-link-alt ml-1"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] font-black text-slate-600 uppercase tracking-widest border-b border-slate-800">
                                <th class="pb-6">Timestamp</th>
                                <th class="pb-6">Origin Source</th>
                                <th class="pb-6">Lead Identity</th>
                                <th class="pb-6">Target Institution</th>
                                <th class="pb-6 text-right">Integrity Token</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/50">
                            <?php if (empty($inboundLeads)): ?>
                                <tr><td colspan="5" class="py-20 text-center text-slate-600 font-black uppercase text-xs italic">No inbound social traffic detected</td></tr>
                            <?php else: ?>
                                <?php foreach ($inboundLeads as $l): ?>
                                <tr class="group hover:bg-white/5 transition-all">
                                    <td class="py-6 text-[10px] font-bold text-slate-500 mono"><?= date('H:i:s | d-M', strtotime($l['created_at'])) ?></td>
                                    <td class="py-6">
                                        <span class="px-3 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-widest border 
                                            <?= $l['source'] === 'WhatsApp' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-blue-500/10 text-blue-500 border-blue-500/20' ?>">
                                            <i class="fab <?= $l['source'] === 'WhatsApp' ? 'fa-whatsapp' : 'fa-uncharted' ?> mr-1"></i> <?= $l['source'] ?>
                                        </span>
                                    </td>
                                    <td class="py-6">
                                        <p class="text-xs font-black text-white uppercase italic"><?= htmlspecialchars($l['full_name']) ?></p>
                                        <p class="text-[9px] font-bold text-slate-500 mono"><?= htmlspecialchars($l['phone']) ?></p>
                                    </td>
                                    <td class="py-6">
                                        <p class="text-[10px] font-black text-slate-400 uppercase italic">
                                            <?= htmlspecialchars($l['college_name'] ?? 'Inbound Buffer') ?>
                                        </p>
                                    </td>
                                    <td class="py-6 text-right">
                                        <code class="text-[9px] bg-slate-800 text-rose-400 px-3 py-1.5 rounded-lg mono">
                                            <?= substr(md5($l['id']), 0, 12) ?>
                                        </code>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-span-6 bg-white rounded-[40px] p-10 border border-slate-100 shadow-sm">
                <div class="flex items-center gap-4 mb-6">
                    <i class="fab fa-whatsapp text-emerald-500 text-2xl"></i>
                    <h4 class="text-xs font-black text-slate-900 uppercase italic">WhatsApp API Gateway</h4>
                </div>
                <p class="text-[10px] text-slate-500 font-medium leading-relaxed mb-6">
                    Webhook listener is currently active. All messages hitting the endpoint are parsed for <span class="text-slate-900 font-bold">FullName</span> and <span class="text-slate-900 font-bold">Contact</span>.
                </p>
                <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl">
                    <span class="text-[9px] font-black text-slate-400 uppercase">Endpoint Status</span>
                    <span class="text-[9px] font-black text-emerald-600 uppercase italic">Active / Listening</span>
                </div>
            </div>

            <div class="col-span-6 bg-white rounded-[40px] p-10 border border-slate-100 shadow-sm">
                <div class="flex items-center gap-4 mb-6">
                    <i class="fas fa-project-diagram text-blue-500 text-2xl"></i>
                    <h4 class="text-xs font-black text-slate-900 uppercase italic">Institutional Routing</h4>
                </div>
                <p class="text-[10px] text-slate-500 font-medium leading-relaxed mb-6">
                    Leads without an institutional tag are placed in the <span class="text-slate-900 font-bold">General Buffer</span>.
                </p>
                <div class="flex gap-3">
                    <a href="bulk_assign.php" class="text-[9px] font-black text-blue-600 uppercase underline italic">Open Lead Hub</a>
                </div>
            </div>

        </div>
    </div>
</body>
</html>