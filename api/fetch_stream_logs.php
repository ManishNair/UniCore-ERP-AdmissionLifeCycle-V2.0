<?php
// api/fetch_stream_logs.php
require_once '../config/db.php';

/**
 * 1. SIMULATED INGEST DATA
 * In a live environment, this array would be populated by your 
 * WhatsApp Webhook or Social Media API router.
 */
$incoming_leads = [
    ['name' => 'Alex Smith', 'phone' => '9876543210', 'email' => 'alex@example.com']
];

foreach ($incoming_leads as $lead) {
    $phone = $lead['phone'];
    $email = $lead['email'];

    /**
     * 2. PRE-EMPTIVE INTEGRITY CHECK
     * We search for an existing Master record (is_duplicate = 0)
     * that matches either the phone or the email.
     */
    $check = $pdo->prepare("SELECT id, full_name FROM leads WHERE (phone = ? OR email = ?) AND is_duplicate = 0 LIMIT 1");
    $check->execute([$phone, $email]);
    $master = $check->fetch();

    // Generate a fresh unique token to satisfy the leads.access_token UNIQUE constraint
    $token = bin2hex(random_bytes(16));

    if ($master) {
        /**
         * 3. DUPLICATE DETECTED BRANCH
         * We insert a linked "Child" record. 
         * To satisfy DB UNIQUE constraints, we modify the phone and email 
         * while keeping the original values visible to the counselor.
         */
        $sql = "INSERT INTO leads (full_name, phone, email, is_duplicate, master_lead_id, access_token, current_stage) 
                VALUES (?, ?, ?, 1, ?, ?, 'Compliance Gate')";
        
        $stmt = $pdo->prepare($sql);
        
        $suffix = "-" . rand(100, 999);
        $fake_phone = $phone . $suffix; 
        $fake_email = str_replace('@', $suffix . '@', $email); 

        $stmt->execute([
            $lead['name'], 
            $fake_phone, 
            $fake_email, 
            $master['id'], 
            $token
        ]);

        // HUMAN-CENTRIC UI CARD (Detailed for End-User)
        echo "
        <div class='p-5 bg-white border border-amber-100 rounded-[30px] shadow-sm mb-4 border-l-4 border-l-amber-500 group transition-all hover:border-amber-300'>
            <div class='flex gap-4'>
                <div class='w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0'>
                    <i class='fas fa-fingerprint text-xl'></i>
                </div>
                <div class='flex-1'>
                    <div class='flex justify-between items-start'>
                        <h4 class='text-[10px] font-black text-slate-800 uppercase tracking-tighter italic'>Integrity Guard Active</h4>
                        <span class='text-[8px] font-black bg-amber-500 text-white px-2 py-1 rounded-lg uppercase'>Conflict Caught</span>
                    </div>
                    
                    <p class='text-[11px] text-slate-500 mt-2 leading-relaxed'>
                        The student <strong class='text-slate-800'>" . htmlspecialchars($lead['name']) . "</strong> tried to re-enter using <strong>" . $phone . "</strong>.
                        To keep data clean, this has been <strong>Safely Linked</strong> to the existing profile of <span class='text-blue-600 font-bold'>" . htmlspecialchars($master['full_name']) . "</span>.
                    </p>
                    
                    <div class='mt-4 p-3 bg-slate-50 rounded-2xl flex items-center justify-between border border-slate-100'>
                        <div class='flex items-center gap-2'>
                            <div class='w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse'></div>
                            <p class='text-[9px] font-black text-slate-400 uppercase'>Parent Record: #{$master['id']}</p>
                        </div>
                        <a href='compliance_desk.php?id={$master['id']}' class='text-[9px] font-black text-blue-600 uppercase hover:underline'>
                            Resolve & Merge <i class='fas fa-arrow-right ml-1'></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>";

    } else {
        /**
         * 4. NEW LEAD BRANCH
         * No existing record found; proceed with standard ingestion.
         */
        try {
            $sql = "INSERT INTO leads (full_name, phone, email, access_token, current_stage) 
                    VALUES (?, ?, ?, ?, 'Compliance Gate')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $lead['name'], 
                $phone, 
                $email, 
                $token
            ]);

            // HUMAN-CENTRIC SUCCESS CARD
            echo "
            <div class='p-5 bg-white border border-emerald-100 rounded-[30px] shadow-sm mb-4 border-l-4 border-l-emerald-500'>
                <div class='flex items-center gap-4'>
                    <div class='w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0'>
                        <i class='fas fa-user-plus text-xl'></i>
                    </div>
                    <div>
                        <h4 class='text-[10px] font-black text-slate-800 uppercase italic tracking-tighter'>Intake Success</h4>
                        <p class='text-[11px] text-slate-500 mt-1 leading-tight'>
                            <span class='font-bold text-slate-700'>" . htmlspecialchars($lead['name']) . "</span> has been verified as a unique new contact.
                        </p>
                    </div>
                </div>
            </div>";

        } catch (PDOException $e) {
            // Error handling for unexpected database collisions
            echo "<div class='p-3 bg-rose-50 border border-rose-100 rounded-xl text-rose-600 text-[10px] font-bold'>
                    <i class='fas fa-exclamation-triangle mr-2'></i> System Collision: " . htmlspecialchars($e->getMessage()) . "
                  </div>";
        }
    }
} // End Foreach
?>