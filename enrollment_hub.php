<?php
// enrollment_hub.php
session_start();
require_once 'config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$lead_id = $_GET['id'] ?? null;

// V1.0 Logic: Fetch final student record
$stmt = $pdo->prepare("SELECT l.*, c.name as college_name FROM leads l LEFT JOIN colleges c ON l.college_id = c.id WHERE l.id = ?");
$stmt->execute([$lead_id]);
$lead = $stmt->fetch();

// Generate dynamic Enrollment IDs if they don't exist
$enrollment_no = "UNIV-2025-IOT-" . str_pad($lead['id'], 4, '0', STR_PAD_LEFT);
$batch = "CS-ALPHA-2025";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Hub | UniCore Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-[#f8fafc] flex">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 ml-72">
        <header class="p-8 flex items-center justify-between bg-white border-b sticky top-0 z-50">
            <div class="flex items-center gap-3">
                <a href="index.php" class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-900 hover:text-white transition-all">
                    <i class="fas fa-home text-xs"></i>
                </a>
                <div>
                    <h1 class="text-sm font-black text-slate-900 uppercase tracking-tighter">Enrollment Hub</h1>
                    <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest">Status: Successfully Enrolled</p>
                </div>
            </div>
            <button class="bg-[#0f172a] text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-slate-900/20">
                <i class="fas fa-print"></i> Print Admission Kit
            </button>
        </header>

        <div class="p-10">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
                
                <div class="lg:col-span-3 space-y-8">
                    <div class="bg-white rounded-[45px] p-12 border border-slate-100 shadow-sm text-center">
                        <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-8 text-3xl shadow-inner">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900 tracking-tighter mb-4">Conversion Complete!</h2>
                        <p class="text-slate-400 font-medium mb-12">The applicant has been successfully moved to the <strong class="text-slate-700">Classroom-Ready</strong> state.</p>
                        
                        <div class="grid grid-cols-2 gap-6 max-w-xl mx-auto">
                            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-2 tracking-widest">Enrollment Number</p>
                                <p class="text-lg font-black text-blue-600"><?= $enrollment_no ?></p>
                            </div>
                            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-2 tracking-widest">Batch Allocated</p>
                                <p class="text-lg font-black text-slate-800"><?= $batch ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-[45px] border border-slate-100 p-10 shadow-sm">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em] mb-10">Lifecycle Audit Trail</h3>
                        <div class="space-y-10 relative before:content-[''] before:absolute before:left-5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-50">
                            <div class="flex items-start gap-8 relative z-10">
                                <div class="w-10 h-10 bg-emerald-500 text-white rounded-full flex items-center justify-center text-xs shadow-lg shadow-emerald-500/20"><i class="fas fa-money-bill-wave"></i></div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Today, 10:45 AM</p>
                                    <p class="text-sm font-black text-slate-800">Fee Received - $700.00</p>
                                    <p class="text-[10px] text-slate-400 font-medium">Transaction ID: TXN_882910 via Razorpay</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-8 relative z-10">
                                <div class="w-10 h-10 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs shadow-lg shadow-blue-500/20"><i class="fas fa-user-check"></i></div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Jan 02, 2025</p>
                                    <p class="text-sm font-black text-slate-800">Compliance Verification Passed</p>
                                    <p class="text-[10px] text-slate-400 font-medium">Verified by: John Doe (Admin)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-10">
                    
                    <div class="bg-gradient-to-br from-[#1e293b] to-[#0f172a] rounded-[40px] p-10 text-white shadow-2xl relative overflow-hidden ring-8 ring-white">
                        <div class="flex justify-between items-start mb-10">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-university text-blue-400"></i>
                                <span class="text-[10px] font-black uppercase tracking-tighter italic">UniCore IOT</span>
                            </div>
                            <span class="text-[8px] font-bold text-slate-500 uppercase tracking-widest border border-slate-700 px-2 py-1 rounded">Digital Passport</span>
                        </div>

                        <div class="flex items-center gap-6 mb-12">
                            <div class="w-24 h-24 bg-white rounded-3xl overflow-hidden shadow-2xl border-4 border-slate-800">
                                <img src="https://i.pravatar.cc/150?u=<?= $lead['id'] ?>" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <h4 class="text-2xl font-black tracking-tight leading-none mb-2 uppercase"><?= htmlspecialchars($lead['full_name']) ?></h4>
                                <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest">B.Tech - Comp. Science</p>
                                <p class="text-[9px] text-slate-500 font-medium mt-1 uppercase">Academic Year 2025-2029</p>
                            </div>
                        </div>

                        <div class="flex justify-between items-end border-t border-slate-800 pt-8">
                            <div>
                                <p class="text-[8px] text-slate-500 font-bold uppercase mb-1">Enrollment No</p>
                                <p class="text-xs font-black tracking-widest">U25-IOT-0842</p>
                                <p class="text-[8px] text-slate-500 font-bold uppercase mt-4 mb-1">Valid Thru</p>
                                <p class="text-xs font-black tracking-widest">07 / 2029</p>
                            </div>
                            <div class="w-20 h-20 bg-white p-2 rounded-xl">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $enrollment_no ?>" class="w-full h-full">
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-600 rounded-[40px] p-10 text-white shadow-2xl shadow-blue-500/20">
                        <div class="flex items-center gap-3 mb-6">
                            <i class="fas fa-paper-plane text-xl"></i>
                            <h3 class="text-sm font-black uppercase tracking-widest">Post-Admission Automation</h3>
                        </div>
                        <p class="text-xs text-blue-100 leading-relaxed mb-8">
                            The system has automatically sent the orientation kit and login credentials to the student's registered WhatsApp and Email.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-3 text-[10px] font-bold bg-blue-700/50 p-3 rounded-xl">
                                <i class="fas fa-check-circle text-emerald-400"></i> LMS Account Created
                            </li>
                            <li class="flex items-center gap-3 text-[10px] font-bold bg-blue-700/50 p-3 rounded-xl">
                                <i class="fas fa-check-circle text-emerald-400"></i> Library Access Granted
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>