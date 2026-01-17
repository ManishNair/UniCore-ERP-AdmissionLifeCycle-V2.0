<?php
// audit_logs.php - Master Activity Ledger
session_start();
require_once 'config/db.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// 2. CAPTURE FILTERS
$filter_type = $_GET['type'] ?? 'all';
$filter_user = $_GET['user'] ?? 'all';
$search = $_GET['search'] ?? '';

// 3. BUILD DYNAMIC SQL
$where = ["1=1"];
$params = [];

if ($filter_type !== 'all') {
    $where[] = "l.channel = ?";
    $params[] = $filter_type;
}

if ($filter_user !== 'all') {
    $where[] = "l.user_id = ?";
    $params[] = $filter_user;
}

if (!empty($search)) {
    $where[] = "(ld.full_name LIKE ? OR u.full_name LIKE ? OR l.note LIKE ?)";
    $st = "%$search%";
    array_push($params, $st, $st, $st);
}

$where_sql = "WHERE " . implode(" AND ", $where);

// 4. FETCH DATA
$query = "SELECT l.*, ld.full_name as lead_name, u.full_name as actor_name 
          FROM lead_engagement_log l
          LEFT JOIN leads ld ON l.lead_id = ld.id
          LEFT JOIN users u ON l.user_id = u.id
          $where_sql
          ORDER BY l.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. FETCH USERS FOR FILTER DROPDOWN
$users = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Vault | UniCore ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar { width: 12px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #2563eb; border: 3px solid #f1f5f9; border-radius: 10px; }
    </style>
</head>
<body class="flex min-h-screen text-slate-900">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 ml-72 p-12">
        
        <header class="flex justify-between items-start mb-12">
            <div>
                <h1 class="text-3xl font-[900] uppercase italic tracking-tighter">
                    Audit <span class="text-blue-600">Vault</span>
                </h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                    Full System Trace & Connectivity Logs
                </p>
            </div>

            <form method="GET" class="flex items-center gap-4 bg-white p-4 rounded-[30px] shadow-sm border border-slate-100">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search Logs..." 
                           class="pl-10 pr-4 py-2 bg-slate-50 border-none rounded-2xl text-[10px] font-bold focus:ring-2 focus:ring-blue-500 outline-none w-48">
                </div>

                <select name="type" onchange="this.form.submit()" class="bg-slate-50 border-none rounded-xl px-4 py-2 text-[10px] font-black uppercase outline-none">
                    <option value="all">All Channels</option>
                    <option value="call" <?= $filter_type == 'call' ? 'selected' : '' ?>>Voice Calls</option>
                    <option value="whatsapp" <?= $filter_type == 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                    <option value="verification" <?= $filter_type == 'verification' ? 'selected' : '' ?>>Verification</option>
                    <option value="system" <?= $filter_type == 'system' ? 'selected' : '' ?>>System/Integrity</option>
                </select>

                <select name="user" onchange="this.form.submit()" class="bg-slate-50 border-none rounded-xl px-4 py-2 text-[10px] font-black uppercase outline-none">
                    <option value="all">All Actors</option>
                    <?php foreach($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>><?= $u['full_name'] ?></option>
                    <?php endforeach; ?>
                </select>

                <?php if($filter_type !== 'all' || $filter_user !== 'all' || !empty($search)): ?>
                    <a href="audit_logs.php" class="text-rose-500 px-2 hover:text-rose-700 transition-colors"><i class="fas fa-times-circle"></i></a>
                <?php endif; ?>
            </form>
        </header>

        <div class="bg-white rounded-[40px] shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] font-black text-slate-300 uppercase tracking-widest border-b border-slate-50">
                            <th class="py-8 px-10">Time & Date</th>
                            <th class="py-8">Actor / Counselor</th>
                            <th class="py-8">Activity Detail</th>
                            <th class="py-8">Student Link</th>
                            <th class="py-8 text-right px-10">Channel</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($logs as $log): 
                            // STYLE LOGIC
                            $badgeColor = match($log['channel']) {
                                'system' => 'bg-amber-50 text-amber-600 border-amber-100',
                                'whatsapp' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                'verification' => 'bg-blue-50 text-blue-600 border-blue-100',
                                'call' => 'bg-rose-50 text-rose-600 border-rose-100',
                                default => 'bg-slate-50 text-slate-400 border-slate-100',
                            };
                            
                            $icon = match($log['channel']) {
                                'system' => 'fa-robot',
                                'whatsapp' => 'fa-whatsapp',
                                'verification' => 'fa-check-double',
                                'call' => 'fa-phone-alt',
                                default => 'fa-info-circle',
                            };
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-all group">
                            <td class="py-7 px-10">
                                <p class="text-[10px] font-black text-slate-900"><?= date('H:i:s', strtotime($log['created_at'])) ?></p>
                                <p class="text-[8px] font-bold text-slate-400 uppercase"><?= date('M d, Y', strtotime($log['created_at'])) ?></p>
                            </td>

                            <td class="py-7">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                        <?= strtoupper(substr($log['actor_name'] ?? 'S', 0, 1)) ?>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-700 uppercase italic">
                                        <?= htmlspecialchars($log['actor_name'] ?? 'UniCore System') ?>
                                    </span>
                                </div>
                            </td>

                            <td class="py-7">
                                <div class="max-w-md">
                                    <p class="text-[11px] text-slate-600 font-medium leading-relaxed">
                                        <?= htmlspecialchars($log['note']) ?>
                                    </p>
                                    <?php if($log['channel'] === 'call' && $log['call_duration'] > 0): ?>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="text-[8px] font-black bg-rose-500 text-white px-2 py-0.5 rounded uppercase tracking-widest">
                                                <i class="fas fa-clock mr-1"></i> 
                                                <?= floor($log['call_duration'] / 60) ?>m <?= ($log['call_duration'] % 60) ?>s
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="py-7">
                                <?php if($log['lead_name']): ?>
                                    <a href="compliance_desk.php?id=<?= $log['lead_id'] ?>" class="text-[10px] font-black text-blue-600 uppercase italic hover:underline decoration-2">
                                        <?= htmlspecialchars($log['lead_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-[10px] font-bold text-slate-300 uppercase italic">General</span>
                                <?php endif; ?>
                            </td>

                            <td class="py-7 text-right px-10">
                                <span class="px-3 py-1.5 rounded-xl border text-[8px] font-black uppercase tracking-widest <?= $badgeColor ?> inline-flex items-center gap-2">
                                    <i class="fas <?= $icon ?>"></i>
                                    <?= $log['channel'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="mt-12 flex justify-between items-center text-slate-400">
            <p class="text-[9px] font-bold uppercase tracking-widest italic">Showing latest 200 security events</p>
            <div class="flex gap-4">
                <button onclick="window.print()" class="text-[9px] font-black text-slate-600 uppercase tracking-widest hover:text-blue-600">
                    <i class="fas fa-print mr-2"></i> Print Ledger
                </button>
            </div>
        </footer>

    </div>

</body>
</html>