<?php
session_start();
require_once 'config/db.php';

$lead_id = $_GET['id'] ?? null;

// Fetch Lead Info
$stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
$stmt->execute([$lead_id]);
$lead = $stmt->fetch();

// Fetch Activity Log with User Names
$stmt = $pdo->prepare("
    SELECT log.*, u.full_name as staff_name, u.role 
    FROM lead_activity_log log 
    JOIN users u ON log.performed_by = u.id 
    WHERE log.lead_id = ? 
    ORDER BY log.created_at DESC
");
$stmt->execute([$lead_id]);
$activities = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Log | <?= $lead['full_name'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 p-8">
    <div class="max-w-3xl mx-auto">
        <a href="index.php" class="text-xs font-bold text-slate-400 uppercase tracking-widest hover:text-slate-900 transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>

        <div class="mt-8 bg-white rounded-[40px] shadow-sm border border-slate-100 overflow-hidden">
            <div class="bg-slate-900 p-10 text-white">
                <h1 class="text-3xl font-black italic"><?= $lead['full_name'] ?></h1>
                <p class="text-slate-400 text-xs mt-2 uppercase tracking-widest font-bold">Activity Timeline</p>
            </div>

            <div class="p-10">
                <?php foreach($activities as $act): ?>
                <div class="relative pl-8 pb-10 border-l-2 border-slate-100 last:border-0">
                    <div class="absolute -left-[9px] top-0 w-4 h-4 bg-white border-4 border-blue-500 rounded-full"></div>
                    
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-black uppercase text-blue-500 tracking-widest mb-1">
                                <?= $act['activity_type'] ?>
                            </p>
                            <p class="text-sm font-bold text-slate-800"><?= $act['description'] ?></p>
                            <p class="text-xs text-slate-400 mt-2 italic">
                                Performed by: <?= $act['staff_name'] ?> (<?= $act['role'] ?>)
                            </p>
                        </div>
                        <span class="text-[10px] font-bold text-slate-300 uppercase">
                            <?= date('d M, H:i', strtotime($act['created_at'])) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>