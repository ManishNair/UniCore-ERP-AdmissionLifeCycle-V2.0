<?php
// finance_desk.php
require_once 'config/db.php';
require_once 'core/Security.php';

session_start();

$student_id = Security::sanitizeInt($_GET['id'] ?? null);
if (!$student_id) die("Error: Student ID required.");

// 1. Fetch Student and College Fee Details
$stmt = $pdo->prepare("
    SELECT l.*, c.name as college_name, c.base_fee 
    FROM leads l 
    JOIN colleges c ON l.college_id = c.id 
    WHERE l.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Security Gate: Only allow if status is 'verified' (passed documentation)
if (!$student || $student['status'] !== 'verified') {
    die("Access Denied: Student must be verified by a counselor before payment.");
}

$message = "";

// 2. Handle Payment Recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    Security::validateCSRF($_POST['csrf_token']);
    
    $transaction_id = Security::sanitizeString($_POST['transaction_id']);
    
    if (!empty($transaction_id)) {
        try {
            $pdo->beginTransaction();

            // Update lead status to 'paid' and store transaction details
            $update = $pdo->prepare("
                UPDATE leads 
                SET status = 'paid', 
                    fee_transaction_id = ?, 
                    fee_paid_at = NOW() 
                WHERE id = ?
            ");
            $update->execute([$transaction_id, $student_id]);

            // Add an internal note for the audit trail
            $note = "Payment Received. Ref: $transaction_id. Amount: " . $student['base_fee'];
            $log = $pdo->prepare("INSERT INTO lead_notes (lead_id, counselor_id, note_text) VALUES (?, ?, ?)");
            $log->execute([$student_id, $_SESSION['user_id'], $note]);

            $pdo->commit();
            header("Location: index.php?msg=payment_received");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error recording payment: " . $e->getMessage();
        }
    } else {
        $message = "Please enter a valid Transaction ID / Receipt Number.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance Desk | <?= htmlspecialchars($student['full_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen p-12">

    <div class="max-w-2xl mx-auto">
        <a href="index.php" class="text-slate-500 hover:text-slate-900 font-bold text-sm mb-8 inline-block">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>

        <div class="bg-white rounded-[40px] shadow-2xl border border-slate-100 overflow-hidden">
            <div class="p-10 bg-emerald-600 text-white flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black tracking-tight italic">Finance Desk</h2>
                    <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-widest mt-1">Payment Recording</p>
                </div>
                <i class="fas fa-file-invoice-dollar text-3xl opacity-50"></i>
            </div>

            <div class="p-10">
                <?php if ($message): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-2xl text-xs font-bold mb-6 italic"><?= $message ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-6 mb-10">
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Student</p>
                        <p class="font-bold text-slate-800"><?= htmlspecialchars($student['full_name']) ?></p>
                    </div>
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Course Fee</p>
                        <p class="font-black text-emerald-600 text-xl">₹<?= number_format($student['base_fee'], 2) ?></p>
                    </div>
                </div>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF(); ?>">
                    
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Transaction ID / Receipt No.</label>
                        <input type="text" name="transaction_id" required placeholder="Ex: TXN12345678"
                               class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold focus:ring-2 focus:ring-emerald-500">
                        <p class="text-[9px] text-slate-400 mt-2 ml-2 italic">Verify the payment in your bank/accounts portal before submitting.</p>
                    </div>

                    <div class="pt-4">
                        <button type="submit" name="record_payment" class="w-full bg-slate-900 text-white font-black py-5 rounded-[25px] shadow-xl hover:bg-black transition-all uppercase tracking-widest text-xs">
                            Confirm Payment & Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-8 text-center text-[9px] text-slate-300 font-bold uppercase tracking-widest">
            UniCore Finance Module v1.0
        </div>
    </div>

</body>
</html>