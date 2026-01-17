<?php
// upload.php
require_once 'config/db.php';

// 1. Capture the Secure Token from the URL
$token = $_GET['token'] ?? null;
$status = $_GET['status'] ?? null;

if (!$token) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'><h2>Access Denied</h2><p>A valid security token is required to access this portal.</p></div>");
}

// 2. Validate Token and Fetch Student Information
$stmt = $pdo->prepare("SELECT id, full_name FROM leads WHERE access_token = ?");
$stmt->execute([$token]);
$student = $stmt->fetch();

if (!$student) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'><h2>Link Expired</h2><p>This secure link is no longer valid or has been revoked.</p></div>");
}

// 3. Fetch Dynamic Document Requirements from Settings
$doc_types = $pdo->query("SELECT * FROM document_types ORDER BY id ASC")->fetchAll();

// 4. Fetch already uploaded documents to show current progress
$stmt = $pdo->prepare("SELECT doc_type_id FROM lead_documents WHERE lead_id = ?");
$stmt->execute([$student['id']]);
$uploaded_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 5. Handle File Upload Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_dir = "uploads/";
    
    // Ensure the physical directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $files_processed = 0;

    foreach ($doc_types as $type) {
        $field_name = "doc_" . $type['id'];
        
        // Check if a file was actually submitted for this specific requirement
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] == 0) {
            $file_ext = strtolower(pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

            if (in_array($file_ext, $allowed)) {
                // Generate Unique Filename: {studentID}_{docTypeID}_{timestamp}.{ext}
                $new_filename = $student['id'] . "_" . $type['id'] . "_" . time() . "." . $file_ext;
                
                if (move_uploaded_file($_FILES[$field_name]['tmp_name'], $target_dir . $new_filename)) {
                    // Save to lead_documents table using UPSERT logic
                    $stmt = $pdo->prepare("
                        INSERT INTO lead_documents (lead_id, doc_type_id, file_path) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE file_path = ?, uploaded_at = NOW()
                    ");
                    $stmt->execute([$student['id'], $type['id'], $new_filename, $new_filename]);
                    
                    // Mark the main lead record as "Unseen" for the counselor's notification
                    $pdo->prepare("UPDATE leads SET doc_uploaded_at = NOW(), doc_seen_by_counselor = 0 WHERE id = ?")
                        ->execute([$student['id']]);
                        
                    $files_processed++;
                }
            }
        }
    }

    if ($files_processed > 0) {
        header("Location: upload.php?token=" . $token . "&status=success");
        exit;
    } else {
        $error = "Please select at least one valid file (JPG, PNG, or PDF) to upload.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Document Portal | UniCore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-xl w-full bg-white rounded-[40px] shadow-2xl p-10 border border-slate-100">
        
        <?php if ($status === 'success'): ?>
            <div class="text-center py-10">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check fa-2x"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-800 mb-2">Uploads Received!</h2>
                <p class="text-slate-500 text-sm leading-relaxed mb-8">
                    Thank you, <span class="font-bold"><?= htmlspecialchars($student['full_name']) ?></span>. 
                    Your documents have been securely transmitted to the Admissions Desk.
                </p>
                <a href="upload.php?token=<?= $token ?>" class="bg-slate-100 text-slate-600 px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all">
                    Upload More Files
                </a>
            </div>

        <?php else: ?>
            <div class="mb-10 text-center">
                <div class="inline-block p-4 bg-blue-50 text-blue-600 rounded-3xl mb-4">
                    <i class="fas fa-shield-alt fa-2x"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-800">Secure Upload Portal</h2>
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-2">Applicant ID: <?= $student['id'] ?> | <?= htmlspecialchars($student['full_name']) ?></p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-2xl text-[10px] font-black uppercase mb-6 border border-red-100 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php foreach ($doc_types as $type): 
                    $is_done = in_array($type['id'], $uploaded_ids);
                ?>
                    <div class="p-6 rounded-[30px] border-2 transition-all <?= $is_done ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50/50 border-dashed border-slate-200 hover:border-blue-300' ?>">
                        <div class="flex justify-between items-center mb-4">
                            <label class="text-[11px] font-black uppercase tracking-tight <?= $is_done ? 'text-emerald-700' : 'text-slate-500' ?>">
                                <?= htmlspecialchars($type['doc_name']) ?> <?= $type['is_required'] ? '*' : '(Optional)' ?>
                            </label>
                            <?php if ($is_done): ?>
                                <span class="bg-emerald-500 text-white text-[9px] px-2 py-1 rounded-lg font-black uppercase"><i class="fas fa-check mr-1"></i> Received</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$is_done): ?>
                            <input type="file" name="doc_<?= $type['id'] ?>" class="block w-full text-[11px] text-slate-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-[10px] file:font-black
                                file:bg-blue-600 file:text-white
                                hover:file:bg-blue-700 file:cursor-pointer">
                        <?php else: ?>
                            <div class="flex items-center text-[10px] text-emerald-600 font-bold italic">
                                <i class="fas fa-lock mr-2"></i> File is locked and encrypted.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="w-full bg-slate-900 text-white font-black py-5 rounded-[25px] shadow-xl hover:bg-black hover:-translate-y-1 transition-all uppercase tracking-widest text-xs mt-6">
                    Finalize & Submit Documents
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-12 text-center">
            <p class="text-[9px] text-slate-300 font-bold uppercase tracking-[0.3em]">End-to-End Encrypted Admission Portal</p>
        </div>
    </div>

</body>
</html>