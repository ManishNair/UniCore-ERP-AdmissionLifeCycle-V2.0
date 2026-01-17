<?php
// lead_profile.php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$lead_id = $_GET['id'] ?? null;

if (!$lead_id) {
    die("Error: Critical Lead ID missing.");
}

// 1. FETCH LEAD DETAILS
$stmt = $pdo->prepare("SELECT l.*, c.name as college_name, u.full_name as counselor_name 
                       FROM leads l 
                       LEFT JOIN colleges c ON l.college_id = c.id 
                       LEFT JOIN users u ON l.counselor_id = u.id 
                       WHERE l.id = ?");
$stmt->execute([$lead_id]);
$lead = $stmt->fetch();

if (!$lead) {
    die("Error: Lead not found in UniCore system.");
}

// 2. PERMISSION CHECK (Assigned Colleges vs Global Access)
$user_info = $pdo->prepare("SELECT college_id FROM users WHERE id = ?");
$user_info->execute([$user_id]);
$my_colleges = $user_info->fetchColumn();

$is_chancellor = ($user_role === 'Chancellor');
$is_assigned_to_me = ($lead['counselor_id'] == $user_id);
// Check if lead belongs to a college the user is authorized to see
$has_college_access = ($is_chancellor || in_array($lead['college_id'], explode(',', $my_colleges)));

if (!$has_college_access) {
    die("Security Error: Access Denied to this college's data.");
}

// 3. ACTION: SELF-ASSIGN (CLAIM LEAD)
if (isset($_POST['claim_lead']) && empty($lead['counselor_id'])) {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE leads SET counselor_id = ? WHERE id = ?")->execute([$user_id, $lead_id]);
        
        // Log the event in Activity Log
        $log_sql = "INSERT INTO lead_activity_log (lead_id, user_id, action_type, description) VALUES (?, ?, 'CLAIM', ?)";
        $pdo->prepare($log_sql)->execute([$lead_id, $user_id, "Counselor claimed unassigned lead."]);
        
        $pdo->commit();
        header("Location: lead_profile.php?id=$lead_id&msg=claimed");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Transaction Failed: " . $e->getMessage());
    }
}

// 4. ACTION: ADD ENGAGEMENT NOTE
if (isset($_POST['add_note']) && ($is_assigned_to_me || $is_chancellor)) {
    $note = trim($_POST['note_text']);
    if (!empty($note)) {
        // Record in Engagement Log (for the Timeline)
        $stmt = $pdo->prepare("INSERT INTO lead_engagement_log (lead_id, user_id, note, channel) VALUES (?, ?, ?, 'note')");
        $stmt->execute([$lead_id, $user_id, $note]);
        
        // Record in Global Activity Log (for Chancellor's Audit)
        $pdo->prepare("INSERT INTO lead_activity_log (lead_id, user_id, action_type, description) VALUES (?, ?, 'NOTE_ADDED', 'New session note recorded')")
            ->execute([$lead_id, $user_id]);
            
        header("Location: lead_profile.php?id=$lead_id&msg=note_saved");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($lead['full_name']) ?> | UniCore Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="flex min-h-screen">

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 ml-72 p-12">
        <div class="flex justify-between items-center mb-10">
            <div class="flex items-center gap-6">
                <a href="index.php" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-slate-400 hover:text-blue-600 shadow-sm border border-slate-100 transition-all">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-[900] text-slate-900 tracking-tighter uppercase italic"><?= htmlspecialchars($lead['full_name']) ?></h1>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1 italic">
                        <?= $lead['college_name'] ?> • Intake Stage: <span class="text-blue-600"><?= $lead['current_stage'] ?></span>
                    </p>
                </div>
            </div>

            <div class="flex gap-4">
                <?php if (empty($lead['counselor_id'])): ?>
                    <form method="POST">
                        <button type="submit" name="claim_lead" class="bg-blue-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-500/20 hover:bg-blue-700 transition-all">
                            <i class="fas fa-user-plus mr-2"></i> Claim Lead
                        </button>
                    </form>
                <?php endif; ?>
                <button class="bg-white border border-slate-200 text-slate-900 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest">
                    <i class="fas fa-file-export mr-2"></i> Export Profile
                </button>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-8">
            <div class="col-span-4 space-y-6">
                <div class="bg-white rounded-[35px] p-8 border border-slate-200 shadow-sm">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 border-b pb-4">Lead Intelligence</h3>
                    <div class="space-y-6">
                        <div>
                            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Mobile Contact</p>
                            <p class="font-bold text-slate-900"><?= $lead['phone'] ?></p>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Email Address</p>
                            <p class="font-bold text-slate-900"><?= $lead['email'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 rounded-[35px] p-8 text-white shadow-2xl">
                    <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-6">Assigned Handler</h3>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center font-black">
                            <?= strtoupper(substr($lead['counselor_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div>
                            <p class="font-black italic text-lg"><?= $lead['counselor_name'] ?? 'UNASSIGNED' ?></p>
                            <p class="text-[9px] font-bold text-blue-400 uppercase tracking-widest mt-1">Status: Lead Locked</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-8 space-y-8">
                <?php if ($is_assigned_to_me || $is_chancellor): ?>
                    <div class="bg-white rounded-[40px] p-10 border border-slate-200 shadow-sm">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 italic">Log Counselor Activity</h3>
                        <form method="POST">
                            <textarea name="note_text" rows="4" required placeholder="Type notes from your call or meeting here..." 
                                      class="w-full bg-slate-50 border-2 border-slate-100 rounded-3xl p-6 font-bold text-slate-900 outline-none focus:border-blue-500 transition-all"></textarea>
                            <div class="flex justify-end mt-4">
                                <button type="submit" name="add_note" class="bg-slate-900 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all">
                                    <i class="fas fa-save mr-2"></i> Save Engagement Note
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 p-10 rounded-[40px] flex items-center gap-8">
                        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center text-2xl">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>
                            <h4 class="text-amber-900 font-black uppercase tracking-widest text-xs">Observation Mode Only</h4>
                            <p class="text-amber-700 text-[10px] font-bold mt-1 leading-relaxed italic">This lead is assigned to another staff member. You can monitor the progress, but you cannot log notes or modify student status.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-6">Activity Timeline</h3>
                    <?php
                    $timeline = $pdo->prepare("SELECT e.*, u.full_name FROM lead_engagement_log e 
                                              JOIN users u ON e.user_id = u.id 
                                              WHERE e.lead_id = ? ORDER BY e.created_at DESC");
                    $timeline->execute([$lead_id]);
                    while($row = $timeline->fetch()):
                    ?>
                        <div class="bg-white p-8 rounded-[30px] border border-slate-100 shadow-sm flex gap-8">
                            <div class="text-[9px] font-black text-slate-300 uppercase w-24 pt-1">
                                <?= date('M d, H:i', strtotime($row['created_at'])) ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-[10px] font-black text-blue-600 uppercase italic"><?= $row['full_name'] ?></span>
                                    <span class="w-1 h-1 bg-slate-200 rounded-full"></span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase"><?= $row['channel'] ?></span>
                                </div>
                                <p class="text-sm font-bold text-slate-700 leading-relaxed"><?= htmlspecialchars($row['note']) ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>