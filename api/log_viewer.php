<?php
// api/log_viewer.php - V2.0 RBAC Ledger
session_start();
require_once '../config/db.php';
require_once '../core/Security.php';

// V2.0 Check: Use the permission key defined in your setup_demo
Security::guard('VIEW_SYSTEM_LOGS');

$root = dirname(__DIR__);
$wa_log_path = $root . '/api/wa_log.txt';

// Action: Clear log
if (isset($_GET['clear_logs']) && Security::can('MANAGE_STAFF')) {
    file_put_contents($wa_log_path, "[" . date('Y-m-d H:i:s') . "] Ledger reset by authorized admin." . PHP_EOL);
    header("Location: log_viewer.php?status=cleared");
    exit;
}

$wa_logs = file_exists($wa_log_path) ? file_get_contents($wa_log_path) : "No ledger data found.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Governance Ledger | UniCore V2.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style> @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono&display=swap'); </style>
</head>
<body class="bg-slate-950 text-slate-200 p-12 font-mono text-xs">
    <div class="max-w-5xl mx-auto">
        <header class="flex justify-between mb-8 border-b border-slate-800 pb-4">
            <h1 class="font-black uppercase tracking-tighter text-blue-500 text-xl">System_Ledger_v2.0</h1>
            <a href="../social_router.php" class="text-slate-500 hover:text-white uppercase font-bold">Close Terminal</a>
        </header>
        <pre class="bg-slate-900 p-10 rounded-3xl border border-slate-800 overflow-y-auto h-[70vh] leading-relaxed text-emerald-500/80"><?= Security::clean($wa_logs) ?></pre>
    </div>
</body>
</html>