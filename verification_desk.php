<?php
// verification_desk.php
require_once 'config/db.php';
require_once 'core/Security.php';

session_start();

// 1. Capture and Sanitize Student ID
$student_id = Security::sanitizeInt($_GET['id'] ?? null);
if (!$student_id) die("Error: Student ID required.");

// 2. Fetch Student, College, and Metadata
$stmt = $pdo->prepare("
    SELECT l.*, c.name as college_name 
    FROM leads l 
    JOIN colleges c ON l.college_id = c.id 
    WHERE l.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) die("Error: Student record not found.");

// 3. Mark notification as "Seen" since counselor opened the file
if ($student['doc_seen_by_counselor'] == 0) {
    $pdo->prepare("UPDATE leads SET doc_seen_by_counselor = 1 WHERE id = ?")->execute([$student_id]);
}

// 4. Handle Internal Notes Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $note = Security::sanitizeString($_POST['note_content']);
    if (!empty($note)) {
        $stmt = $pdo->prepare("INSERT INTO lead_notes (lead_id, counselor_id, note_text) VALUES (?, ?, ?)");
        $stmt->execute([$student_id, $_SESSION['user_id'], $note]);
        header("Location: verification_desk.php?id=$student_id&msg=note_added");
        exit;
    }
}

// 5. Handle Final Verification (Lead -> Verified)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_verify'])) {
    Security::validateCSRF($_POST['csrf_token']);
    $update = $pdo->prepare("UPDATE leads SET status = 'verified' WHERE id = ?");
    $update->execute([$student_id]);
    header("Location: index.php?msg=verified&name=" . urlencode($student['full_name']));
    exit;
}

// 6. Fetch all uploaded documents for this student
$docs_stmt = $pdo->prepare("
    SELECT ld.*, dt.doc_name 
    FROM lead_documents ld 
    JOIN document_types dt ON ld.doc_type_id = dt.id 
    WHERE ld.lead_id = ?
");
$docs_stmt->execute([$student_id]);
$uploaded_docs = $docs_stmt->fetchAll();

// 7. Fetch all notes for timeline
$notes_stmt = $pdo->prepare("SELECT * FROM lead_notes WHERE lead_id = ? ORDER BY created_at DESC");
$notes_stmt->execute([$student_id]);
$all_notes = $notes_stmt->fetchAll();

// Generate the Secure Public Link for the student (for manual sharing)
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$upload_url = $protocol . $_SERVER['HTTP_HOST'] . "/unicore_erp/upload.php?token=" . $student['access_token'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify: <?= htmlspecialchars($student['full_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-8">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <a href="index.php" class="text-slate-500 hover:text-slate-900 font-bold text-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back to Pipeline
            </a>
            <div class="text-right">
                <h1 class="text-xl font-black text-slate-800 uppercase tracking-tighter">Verification Desk</h1>
                <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest"><?= $student['college_name'] ?></p>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-8">
            
            <div class="col-span-12 lg:col-span-8 space-y-6">
                <div class="bg-white rounded-[40px] shadow-sm border border-slate-200 overflow-hidden min-h-[600px] flex flex-col">
                    <div class="p-6 border-b bg-slate-50/50 flex justify-between items-center">
                        <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Document Vault</span>
                    </div>

                    <div class="flex-1 p-8">
                        <?php if (empty($uploaded_docs)): ?>
                            <div class="h-full flex flex-col items-center justify-center text-center py-20">
                                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                    <i class="fas fa-folder-open fa-2x"></i>
                                </div>
                                <p class="text-slate-500 font-bold">No documents uploaded yet.</p>
                                <button onclick="navigator.clipboard.writeText('<?= $upload_url ?>'); alert('Link Copied!')" class="mt-4 text-blue-600 font-bold text-[10px] uppercase">Copy Student Upload Link</button>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-2 gap-6">
                                <?php foreach($uploaded_docs as $doc): ?>
                                    <div class="group relative bg-slate-50 border rounded-3xl p-4 hover:border-blue-400 transition-all">
                                        <p class="text-[10px] font-black text-slate-400 uppercase mb-3"><?= htmlspecialchars($doc['doc_name']) ?></p>
                                        <div class="aspect-video bg-slate-200 rounded-xl overflow-hidden mb-4">
                                            <?php 
                                            $ext = pathinfo($doc['file_path'], PATHINFO_EXTENSION);
                                            if ($ext == 'pdf'): ?>
                                                <div class="flex h-full items-center justify-center text-slate-400"><i class="fas fa-file-pdf fa-2x"></i></div>
                                            <?php else: ?>
                                                <img src="uploads/<?= $doc['file_path'] ?>" class="w-full h-full object-cover">
                                            <?php endif; ?>
                                        </div>
                                        <a href="uploads/<?= $doc['file_path'] ?>" target="_blank" class="block text-center bg-white border py-2 rounded-xl text-[10px] font-bold text-slate-600 hover:bg-slate-900 hover:text-white transition-all">View Document</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-200">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Counselor Notes</h3>
                    <form method="POST" class="mb-8">
                        <textarea name="note_content" required placeholder="Add internal comment..." class="w-full bg-slate-50 border-none rounded-2xl p-4 text-xs font-medium focus:ring-2 focus:ring-blue-500"></textarea>
                        <button type="submit" name="add_note" class="mt-3 bg-slate-900 text-white px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest">Post Note</button>
                    </form>
                    <div class="space-y-4">
                        <?php foreach($all_notes as $n): ?>
                            <div class="bg-slate-50 p-4 rounded-2xl border-l-4 border-blue-500">
                                <p class="text-xs text-slate-700 leading-relaxed"><?= htmlspecialchars($n['note_text']) ?></p>
                                <p class="text-[9px] text-slate-400 mt-2 font-bold"><?= date('d M, H:i', strtotime($n['created_at'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4 space-y-6">
                <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-200">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Applicant Meta</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-[9px] text-slate-400 font-black uppercase">Full Name</p>
                            <p class="font-bold text-slate-800"><?= htmlspecialchars($student['full_name']) ?></p>
                        </div>
                        <div>
                            <p class="text-[9px] text-slate-400 font-black uppercase">Contact</p>
                            <p class="font-bold text-slate-800"><?= $student['phone'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-200">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Compliance Check</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF(); ?>">
                        <div class="space-y-4 mb-8">
                            <label class="flex items-center p-4 bg-slate-50 rounded-2xl cursor-pointer">
                                <input type="checkbox" required class="w-5 h-5 rounded border-slate-300 text-blue-600">
                                <span class="ml-3 text-xs font-bold text-slate-600">Documents verified & authentic</span>
                            </label>
                            <label class="flex items-center p-4 bg-slate-50 rounded-2xl cursor-pointer">
                                <input type="checkbox" required class="w-5 h-5 rounded border-slate-300 text-blue-600">
                                <span class="ml-3 text-xs font-bold text-slate-600">Candidate meets eligibility</span>
                            </label>
                        </div>

                        <?php if (count($uploaded_docs) > 0): ?>
                            <button type="submit" name="action_verify" class="w-full bg-blue-600 text-white font-black py-5 rounded-3xl shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all uppercase tracking-widest text-xs">
                                Approve & Move to Finance
                            </button>
                        <?php else: ?>
                            <div class="bg-amber-50 p-4 rounded-2xl text-center border border-amber-100">
                                <p class="text-[10px] font-black text-amber-600 uppercase">Verification Locked</p>
                                <p class="text-[9px] text-amber-500 mt-1">Pending student uploads.</p>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>