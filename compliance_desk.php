<?php
// compliance_desk.php - V2.0 Dynamic RBAC & Multi-Group Terminal
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/db.php';

/**
 * 1. PRE-HEADER SECURITY GATE
 * Checks session directly to avoid "Headers already sent" and "Undefined function" errors.
 */
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

// Check for compliance access permission key
$user_perms = $_SESSION['permissions'] ?? [];
if (!in_array('view_compliance', $user_perms)) {
    header("Location: index.php?error=unauthorized_capability");
    exit;
}

$lead_id = $_GET['id'] ?? null;
if (!$lead_id || !is_numeric($lead_id)) {
    header("Location: compliance_queue.php");
    exit;
}

/**
 * 2. DATA PERSISTENCE & TOKENS
 */
$pdo->prepare("UPDATE leads SET visit_count = COALESCE(visit_count, 0) + 1 WHERE id = ?")->execute([$lead_id]);

$checkToken = $pdo->prepare("SELECT upload_token FROM leads WHERE id = ?");
$checkToken->execute([$lead_id]);
$tRow = $checkToken->fetch();
if (empty($tRow['upload_token'])) {
    $newToken = bin2hex(random_bytes(16));
    $pdo->prepare("UPDATE leads SET upload_token = ? WHERE id = ?")->execute([$newToken, $lead_id]);
}

/**
 * 3. FETCH COMPREHENSIVE LEAD DATA
 * Includes Group Tracking logic to identify if this phone number has other leads.
 */
$stmt = $pdo->prepare("SELECT l.*, c.name as college_name, 
                      (SELECT COUNT(*) FROM leads WHERE phone = l.phone AND id != l.id) as group_count 
                      FROM leads l LEFT JOIN colleges c ON l.college_id = c.id WHERE l.id = ?");
$stmt->execute([$lead_id]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) { die("Lead Record Not Found."); }

// Construct Public Link
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$public_upload_url = $protocol . $_SERVER['HTTP_HOST'] . "/unicore_erp/public_upload.php?id=" . $lead['upload_token'];

/**
 * 4. FETCH GROUPED INTERESTS
 * V2.0 Group Tracking: List other colleges the student applied for.
 */
$group_stmt = $pdo->prepare("SELECT l.id, c.name as college_name, l.current_stage 
                             FROM leads l 
                             JOIN colleges c ON l.college_id = c.id
                             WHERE l.phone = ? AND l.id != ?");
$group_stmt->execute([$lead['phone'], $lead_id]);
$other_apps = $group_stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * 5. ENGAGEMENT & DOCUMENT LOGIC
 */
$stmt_ref = $pdo->prepare("SELECT note FROM lead_engagement_log WHERE lead_id = ? AND channel = 'note' ORDER BY created_at DESC LIMIT 1");
$stmt_ref->execute([$lead_id]);
$ref_note = $stmt_ref->fetch(PDO::FETCH_ASSOC);

$required_docs = ['High School Marksheet', 'National ID / Passport', 'Entrance Scorecard', 'Migration Certificate'];
$stmt_docs = $pdo->prepare("SELECT doc_name, status, file_path FROM lead_documents WHERE lead_id = ?");
$stmt_docs->execute([$lead_id]);
$uploaded_raw = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

$uploaded_map = [];
foreach($uploaded_raw as $row) {
    $name = trim($row['doc_name']);
    $uploaded_map[$name] = ['status' => strtolower(trim($row['status'])), 'file' => $row['file_path']];
}

$checklist = [];
$verified_count = 0;
foreach ($required_docs as $doc_name) {
    $data = $uploaded_map[$doc_name] ?? null;
    $status = $data ? $data['status'] : 'missing';
    if ($status === 'verified') $verified_count++;
    $checklist[] = ['name' => $doc_name, 'status' => $status, 'file' => ($data ? $data['file'] : null)];
}
$can_proceed = ($verified_count >= 4);

$stmt_history = $pdo->prepare("SELECT el.*, u.full_name as counselor_name FROM lead_engagement_log el JOIN users u ON el.user_id = u.id WHERE el.lead_id = ? ORDER BY el.created_at DESC");
$stmt_history->execute([$lead_id]);
$history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

include_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Compliance Terminal | <?= htmlspecialchars($lead['full_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { overflow: hidden; height: 100vh; background-color: #f8fafc; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .custom-scrollbar::-webkit-scrollbar { width: 12px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .work-area-viewport { height: calc(100vh - 88px); overflow-y: auto; }
        .noisy-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    </style>
</head>
<body class="flex min-h-screen text-slate-900 overflow-x-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen bg-[#f8fafc]">
        <header class="h-[88px] p-6 bg-white border-b flex justify-between items-center z-50 shrink-0 shadow-sm">
            <div class="flex items-center gap-4">
                <a href="compliance_queue.php" class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i class="fas fa-arrow-left"></i></a>
                <div>
                    <h1 class="text-sm font-black uppercase italic tracking-tighter">Verification Terminal <span class="text-blue-600">/ #<?= $lead['id'] ?></span></h1>
                    <?php if($lead['group_count'] > 0): ?>
                        <span class="text-[8px] bg-indigo-600 text-white px-2 py-0.5 rounded-md uppercase font-black noisy-pulse">
                            <i class="fas fa-layer-group mr-1"></i> Multi-Lead Profile: <?= $lead['group_count'] + 1 ?> Applications
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase">App Opens</p>
                    <p class="text-lg font-black text-slate-900 leading-none"><?= $lead['visit_count'] ?></p>
                </div>
                <span class="bg-blue-600 text-white text-[9px] font-black px-4 py-2 rounded-md uppercase italic shadow-lg">Stage: Compliance</span>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden">
            <main class="flex-[7] bg-white work-area-viewport custom-scrollbar p-10">
                <div class="max-w-4xl mx-auto pb-24">
                    
                    <div class="bg-slate-900 rounded-[45px] p-10 mb-8 shadow-2xl relative overflow-hidden">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-8">
                                <div class="w-16 h-16 bg-blue-600 text-white rounded-[22px] flex items-center justify-center text-2xl shadow-xl"><i class="fas fa-user-graduate"></i></div>
                                <div>
                                    <h2 class="text-xl font-black text-white italic tracking-tight mb-1 uppercase"><?= htmlspecialchars($lead['full_name']) ?></h2>
                                    <div class="flex items-center gap-3">
                                        <button onclick="initiateCallLog(<?= $lead['id'] ?>, '<?= $lead['phone'] ?>')" class="flex items-center gap-2 group">
                                            <div class="w-6 h-6 bg-emerald-500/10 text-emerald-400 rounded-lg flex items-center justify-center text-[10px] group-hover:bg-rose-500 group-hover:text-white transition-all">
                                                <i class="fas fa-phone-alt"></i>
                                            </div>
                                            <span class="text-[11px] font-black text-slate-400 group-hover:text-white transition-colors tracking-widest"><?= htmlspecialchars($lead['phone']) ?></span>
                                        </button>
                                        <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
                                        <button onclick="copyLink()" class="text-[9px] font-black text-blue-400 uppercase bg-blue-500/10 px-3 py-1.5 rounded-lg border border-blue-500/20 hover:bg-blue-600 hover:text-white transition-all">Copy Upload Link</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if(!empty($other_apps)): ?>
                        <div class="mb-6 flex gap-2 overflow-x-auto pb-2">
                            <?php foreach($other_apps as $app): ?>
                                <a href="compliance_desk.php?id=<?= $app['id'] ?>" class="shrink-0 bg-white/5 border border-white/10 px-4 py-2 rounded-xl hover:bg-white/10 transition-all">
                                    <p class="text-[7px] font-black text-indigo-400 uppercase tracking-widest">Also Applied:</p>
                                    <p class="text-[9px] font-bold text-slate-200"><?= htmlspecialchars($app['college_name']) ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="bg-white/5 rounded-[35px] p-8 border border-white/10">
                            <div id="strategy-display" class="mb-6 p-5 bg-blue-600/10 border-l-4 border-blue-500 rounded-r-2xl">
                                <p class="text-[8px] font-black text-blue-400 uppercase tracking-widest mb-1">Internal Strategy</p>
                                <p id="strategy-text" class="text-[11px] text-slate-200 italic">"<?= $ref_note ? htmlspecialchars($ref_note['note']) : 'No strategy recorded.' ?>"</p>
                            </div>
                            <div class="flex justify-between items-center mb-6">
                                <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest"><i class="fab fa-whatsapp mr-2 text-lg"></i> WhatsApp Outreach</p>
                                <div class="flex gap-2">
                                    <button onclick="setTemplate('docs')" class="text-[8px] font-black uppercase text-slate-300 hover:text-white px-3 py-1.5 bg-slate-800 rounded-lg">Doc Nudge</button>
                                    <button onclick="setTemplate('fee')" class="text-[8px] font-black uppercase text-slate-300 hover:text-white px-3 py-1.5 bg-slate-800 rounded-lg">Fee Alert</button>
                                </div>
                            </div>
                            <textarea id="wa_custom_msg" placeholder="Draft message..." class="w-full bg-slate-800 border-none rounded-[25px] p-6 text-sm text-white h-24 outline-none focus:ring-2 focus:ring-emerald-500 transition-all"></textarea>
                            <button onclick="executeWhatsApp()" class="w-full mt-4 py-4 bg-emerald-600 text-white rounded-[20px] font-black text-xs uppercase tracking-[0.2em] shadow-xl hover:bg-emerald-500 transition-all">Dispatch Message</button>
                        </div>
                    </div>

                    <section class="bg-slate-50 rounded-[45px] p-10 border border-slate-200 mb-10">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-slate-900 font-black uppercase text-[10px] tracking-widest">Likelihood Assessment</h3>
                            <span id="intent-badge" class="text-[10px] font-black text-blue-600 bg-blue-50 px-4 py-2 rounded-full border border-blue-100 italic">Intent: <?= $lead['conversion_probability'] ?>%</span>
                        </div>
                        <div class="grid grid-cols-4 gap-4 mb-8">
                            <?php foreach([25 => 'rose', 50 => 'orange', 75 => 'yellow', 100 => 'emerald'] as $val => $color): ?>
                                <button onclick="setProbability(<?= $val ?>)" class="prob-btn py-5 rounded-[25px] transition-all border-4 bg-white border-transparent shadow-sm hover:border-slate-300 <?= ($lead['conversion_probability'] == $val) ? 'border-slate-900' : '' ?>">
                                    <span class="block text-xl font-black"><?= $val ?>%</span>
                                </button>
                            <?php endforeach; ?>
                            <input type="hidden" id="selected_prob" value="<?= $lead['conversion_probability'] ?>">
                        </div>
                        <textarea id="log_note" placeholder="Record internal strategy note..." class="w-full bg-white border-2 border-slate-200 rounded-[30px] p-6 text-sm font-bold text-slate-800 h-28 outline-none focus:ring-4 focus:ring-blue-500/10"></textarea>
                        <button onclick="saveStrategy()" class="w-full py-4 bg-[#0f172a] text-white rounded-[20px] font-black text-xs uppercase tracking-widest mt-4">Record Strategy</button>
                    </section>

                    <div class="grid grid-cols-2 gap-6">
                        <?php foreach ($checklist as $item): ?>
                            <div class="p-8 border-2 rounded-[40px] transition-all <?= ($item['status'] === 'verified') ? 'border-emerald-100 bg-emerald-50/20' : ($item['status'] == 'missing' ? 'border-rose-100 bg-rose-50/20' : 'border-blue-50 bg-blue-50/30') ?>">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl <?= ($item['status'] === 'verified') ? 'bg-emerald-500 text-white' : ($item['status'] == 'missing' ? 'bg-rose-100 text-rose-500' : 'bg-blue-600 text-white') ?>"><i class="fas fa-file-invoice"></i></div>
                                    <div class="flex-1">
                                        <p class="text-[10px] font-black uppercase mb-1"><?= $item['name'] ?></p>
                                        <span class="status-badge text-[8px] font-black px-2 py-1 rounded uppercase <?= $item['status'] == 'verified' ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500' ?>"><?= $item['status'] ?></span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <?php if ($item['status'] == 'missing'): ?>
                                        <button onclick="document.getElementById('file_<?= str_replace(' ', '_', $item['name']) ?>').click()" class="attach-btn w-full py-3 bg-rose-500 text-white rounded-xl text-[10px] font-black uppercase shadow-lg">Attach</button>
                                        <form action="api/manual_upload.php" method="POST" enctype="multipart/form-data" class="hidden" target="upload_target">
                                            <input type="hidden" name="lead_id" value="<?= $lead_id ?>"><input type="hidden" name="doc_name" value="<?= $item['name'] ?>">
                                            <input type="file" name="file" id="file_<?= str_replace(' ', '_', $item['name']) ?>" onchange="handleManualUpload(this)">
                                        </form>
                                    <?php else: ?>
                                        <button onclick="previewFile('<?= $item['file'] ?>')" class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase shadow-sm">View</button>
                                        <?php if ($item['status'] !== 'verified'): ?>
                                            <button onclick="updateDocStatus('<?= $item['name'] ?>', 'verified')" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase shadow-lg">Verify</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-12 flex flex-col items-center">
                        <button id="promote-btn" onclick="promoteToFinance(<?= $lead_id ?>)" <?= !$can_proceed ? 'disabled' : '' ?> class="w-full max-w-xl py-6 rounded-[35px] font-black uppercase tracking-widest text-xs flex items-center justify-center gap-4 transition-all <?= $can_proceed ? 'bg-slate-900 text-white hover:bg-blue-600 shadow-xl' : 'bg-slate-100 text-slate-300 border-2 border-dashed' ?>">
                            Finalize Enrollment Gate <i class="fas fa-chevron-right text-blue-400"></i>
                        </button>
                    </div>
                </div>
            </main>

            <aside class="flex-[3] bg-slate-900 flex flex-col shadow-2xl border-l border-slate-800">
                <div id="viewer-container" class="h-[35%] bg-slate-800 m-4 rounded-[30px] overflow-hidden border border-slate-700/50">
                    <div id="file-display-area" class="h-full flex flex-col items-center justify-center text-slate-600 text-center opacity-40">
                        <i class="fas fa-file-pdf text-4xl mb-4"></i><p class="text-[9px] font-black uppercase tracking-widest">File Preview</p>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6" id="history-container">
                    <?php foreach($history as $h): 
                        $theme = ($h['channel'] == 'whatsapp') ? 'emerald' : (($h['channel'] == 'system') ? 'amber' : (($h['channel'] == 'call') ? 'rose' : 'blue'));
                    ?>
                        <div class="relative pl-6 border-l-2 border-slate-800 pb-4">
                            <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-slate-900 border-2 border-<?= $theme ?>-500"></div>
                            <div class="bg-slate-800/40 p-4 rounded-xl">
                                <span class="text-[8px] font-black uppercase text-<?= $theme ?>-400 block mb-1"><?= $h['channel'] ?> | <?= date('H:i', strtotime($h['created_at'])) ?></span>
                                <p class="text-[10px] text-slate-300 italic">"<?= htmlspecialchars($h['note']) ?>"</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
    </div>
    
    <div id="callModal" class="hidden fixed inset-0 bg-slate-900/90 backdrop-blur-sm z-[100] flex items-center justify-center">
        </div>
    <iframe name="upload_target" id="upload_target" style="display:none;"></iframe>

    <script>
        // ... all existing JavaScript logic from original file preserved ...
        let startTime, timerInterval, activeLeadId;
        function initiateCallLog(id, phone) {
            activeLeadId = id;
            startTime = Date.now();
            document.getElementById('dialingNumber').innerText = phone;
            document.getElementById('callModal').classList.remove('hidden');
            timerInterval = setInterval(() => {
                let elapsed = Math.floor((Date.now() - startTime) / 1000);
                document.getElementById('liveTimer').innerText = elapsed + "s";
            }, 1000);
            window.location.href = "tel:" + phone;
        }
        async function saveCallLog() {
            const duration = Math.floor((Date.now() - startTime) / 1000);
            const outcome = document.getElementById('callOutcome').value;
            const note = document.getElementById('callNote').value;
            const res = await fetch('api/log_call.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `lead_id=${activeLeadId}&outcome=${outcome}&note=${note}&duration=${duration}`
            });
            location.reload();
        }
        function logVisit(id) {
            fetch('api/log_visit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `lead_id=${id}`
            }).then(() => location.reload());
        }
        const magicLink = "<?= $public_upload_url ?>";
        function copyLink() { navigator.clipboard.writeText(magicLink).then(() => { alert("Upload Link Copied!"); }); }
        function setTemplate(t) {
            const area = document.getElementById('wa_custom_msg');
            area.value = (t === 'docs') ? `Hi <?= $lead['full_name'] ?>, please upload your documents: ${magicLink}` : `Hi <?= $lead['full_name'] ?>, docs verified. Next step is finance: ${magicLink}`;
        }
        function executeWhatsApp() {
            const msg = document.getElementById('wa_custom_msg').value;
            fetch('api/log_whatsapp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lead_id: <?= $lead_id ?>, message: "WhatsApp: " + msg })
            }).then(() => {
                const phone = "<?= str_replace(['+', ' ', '-'], '', $lead['phone']) ?>";
                window.open(`https://wa.me/${phone}?text=${encodeURIComponent(msg)}`, '_blank');
                location.reload();
            });
        }
        function setProbability(val) {
            document.getElementById('selected_prob').value = val;
            document.querySelectorAll('.prob-btn').forEach(b => b.classList.remove('border-slate-900'));
            event.currentTarget.classList.add('border-slate-900');
            document.getElementById('intent-badge').innerText = `Intent: ${val}%`;
        }
        function saveStrategy() {
            const note = document.getElementById('log_note').value;
            fetch('api/save_engagement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lead_id: <?= $lead_id ?>, probability: document.getElementById('selected_prob').value, note: note })
            }).then(() => { location.reload(); });
        }
        function updateDocStatus(docName, status) {
            fetch('api/verify_document.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lead_id: <?= $lead_id ?>, doc_name: docName, status: status })
            }).then(() => { location.reload(); });
        }
        function promoteToFinance(id) {
            fetch('api/promote_to_finance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lead_id: id })
            }).then(res => res.json()).then(data => {
                if(data.success) {
                    window.open(data.whatsapp_url, '_blank');
                    window.location.href = 'compliance_queue.php';
                }
            });
        }
        function handleManualUpload(input) {
            input.form.submit();
            setTimeout(() => { location.reload(); }, 1500); 
        }
        function previewFile(path) {
            document.getElementById('file-display-area').innerHTML = `<iframe src="uploads/docs/${path}" class="w-full h-full border-0 bg-white rounded-2xl"></iframe>`;
        }
    </script>
</body>
</html>