<?php
require_once 'config/db.php';
$lead_id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT l.*, c.name as college_name FROM leads l JOIN colleges c ON l.college_id = c.id WHERE l.id = ?");
$stmt->execute([$lead_id]);
$data = $stmt->fetch();

if (!$data) die("Receipt not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Receipt - <?= htmlspecialchars($data['full_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print { .no-print { display: none; } body { background: white; } }
    </style>
</head>
<body class="bg-slate-100 p-10">
    <div class="max-w-2xl mx-auto bg-white shadow-2xl rounded-[40px] overflow-hidden border border-slate-200">
        <div class="bg-slate-900 p-12 text-white flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-black italic tracking-tighter uppercase">UniCore <span class="text-blue-500">ERP</span></h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Official Enrollment Receipt</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold text-slate-400 uppercase">Receipt No:</p>
                <p class="text-sm font-black italic">UC-<?= date('Y') ?>-00<?= $data['id'] ?></p>
            </div>
        </div>

        <div class="p-12">
            <div class="flex justify-between mb-10">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Student Details</p>
                    <p class="text-lg font-black text-slate-900 italic"><?= htmlspecialchars($data['full_name']) ?></p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($data['email']) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Date Issued</p>
                    <p class="text-xs font-bold text-slate-900"><?= date('F d, Y') ?></p>
                </div>
            </div>

            <table class="w-full mb-10">
                <thead>
                    <tr class="border-b-2 border-slate-100 text-[10px] font-black text-slate-400 uppercase text-left">
                        <th class="pb-4">Description</th>
                        <th class="pb-4 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr>
                        <td class="py-6">
                            <p class="text-sm font-black text-slate-800 italic">Tuition & Enrollment Fee</p>
                            <p class="text-[10px] text-slate-400 uppercase font-bold"><?= htmlspecialchars($data['college_name']) ?></p>
                        </td>
                        <td class="py-6 text-right font-black text-slate-900">$2,500.00</td>
                    </tr>
                </tbody>
            </table>

            <div class="bg-slate-50 p-8 rounded-3xl flex justify-between items-center border border-slate-100">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Payment Method</p>
                    <p class="text-xs font-black text-slate-700">Ref ID: <?= htmlspecialchars($data['payment_ref']) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-blue-500 uppercase mb-1">Total Paid</p>
                    <p class="text-2xl font-black text-slate-900">$2,500.00</p>
                </div>
            </div>

            <div class="mt-12 text-center">
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4">Digitally Verified by UniCore Cloud ERP</p>
                <div class="flex justify-center gap-4 no-print">
                    <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all">Print Receipt</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>