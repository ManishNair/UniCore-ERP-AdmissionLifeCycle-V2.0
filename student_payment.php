<?php
// student_payment.php
require_once 'config/db.php';
$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM leads WHERE upload_token = ?");
$stmt->execute([$token]);
$student = $stmt->fetch();

if (!$student) die("Invalid Access Token.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref = $_POST['payment_ref'];
    $stmt = $pdo->prepare("UPDATE leads SET payment_ref = ?, updated_at = NOW() WHERE upload_token = ?");
    if($stmt->execute([$ref, $token])) {
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Enrollment | Payment Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-6 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white rounded-[40px] shadow-2xl p-10 border border-slate-100">
        <?php if(isset($success)): ?>
            <div class="text-center">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="text-2xl font-black italic mb-2">Payment Recorded!</h2>
                <p class="text-slate-500 text-sm">Our finance team is verifying your transaction. You will be notified shortly.</p>
            </div>
        <?php else: ?>
            <h1 class="text-xl font-black uppercase italic mb-6">Enrollment <span class="text-blue-600">Payment</span></h1>
            <div class="bg-blue-50 p-6 rounded-3xl mb-8">
                <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Total Fee Amount</p>
                <p class="text-3xl font-black text-blue-900">$2,500.00</p>
            </div>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Transaction ID / Payment Ref</label>
                    <input type="text" name="payment_ref" required placeholder="Ex: TXN12345678" 
                           class="w-full mt-2 bg-slate-100 border-none rounded-2xl p-4 font-bold text-slate-800 outline-none focus:ring-4 focus:ring-blue-500/10">
                </div>
                <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-[25px] font-black uppercase tracking-widest shadow-xl">
                    Submit Payment Detail
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>