<?php
// public_upload.php
require_once 'config/db.php';

$token = $_GET['id'] ?? null;
if (!$token) { die("Access Denied."); }

$stmt = $pdo->prepare("SELECT id, full_name FROM leads WHERE upload_token = ?");
$stmt->execute([$token]);
$lead = $stmt->fetch();

if (!$lead) { die("Link Expired."); }

// Fetch already uploaded docs to show status to student
$stmt_docs = $pdo->prepare("SELECT doc_name, status FROM lead_documents WHERE lead_id = ?");
$stmt_docs->execute([$lead['id']]);
$uploaded = $stmt_docs->fetchAll(PDO::FETCH_KEY_PAIR);

// The 4 Required Documents
$required = [
    'High School Marksheet', 
    'National ID / Passport', 
    'Entrance Scorecard', 
    'Migration Certificate'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Portal | UniCore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center justify-center p-6 font-sans">

    <div class="max-w-md w-full bg-white rounded-[40px] p-8 shadow-2xl border border-slate-100">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-600 text-white rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 shadow-lg"><i class="fas fa-cloud-upload-alt"></i></div>
            <h1 class="text-xl font-black text-slate-900 leading-tight">Hello, <?= htmlspecialchars($lead['full_name']) ?></h1>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Secure Applicant Portal</p>
        </div>

        <div class="mb-8 space-y-2">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Your Checklist Status</p>
            <?php foreach($required as $doc): 
                $status = $uploaded[$doc] ?? 'missing';
                $isDone = ($status === 'verified' || $status === 'pending');
            ?>
                <div class="flex items-center justify-between p-3 rounded-xl border <?= $isDone ? 'bg-emerald-50 border-emerald-100' : 'bg-slate-50 border-slate-100' ?>">
                    <span class="text-[10px] font-bold <?= $isDone ? 'text-emerald-700' : 'text-slate-500' ?>"><?= $doc ?></span>
                    <i class="fas <?= $isDone ? 'fa-check-circle text-emerald-500' : 'fa-circle-notch text-slate-300' ?> text-xs"></i>
                </div>
            <?php endforeach; ?>
        </div>

        <form action="api/public_receiver.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 block mb-2">Select Document to Upload</label>
                <select name="doc_name" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl py-4 px-6 text-sm font-bold outline-none focus:border-blue-500 appearance-none" required>
                    <option value="">Choose Document...</option>
                    <?php foreach($required as $doc): ?>
                        <option value="<?= $doc ?>"><?= $doc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 block mb-2">Choose File (PDF/JPG)</label>
                <input type="file" name="file" class="block w-full text-xs text-slate-500 file:mr-4 file:py-3 file:px-6 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-600 file:text-white hover:file:bg-blue-700" required>
            </div>

            <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-[25px] font-black text-xs uppercase tracking-[0.2em] shadow-xl hover:bg-blue-600 transition-all">Upload Now</button>
        </form>
    </div>

    <p class="mt-8 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Powered by UniCore Cloud ERP</p>

</body>
</html>