<?php
// api/test_wa.php

// 1. DYNAMIC URL DETECTION
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
// Get the directory where THIS file is (e.g., /unicore_erp_v1.0/api)
$currentDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$rootPath = ($currentDir == '/') ? '' : $currentDir;

// Target the webhook in the SAME 'api' folder
$webhook_url = $protocol . $host . $rootPath . '/whatsapp_webhook.php';

// 2. TEST DATA
$test_data = [
    'phone'   => '9988776655',
    'name'    => 'Aditya Professional',
    'message' => 'Testing folder structure paths.'
];

// 3. EXECUTE CURL
$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Professional Path Debugger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 p-12 font-sans">

    <div class="max-w-2xl mx-auto bg-white rounded-[40px] shadow-2xl overflow-hidden border border-slate-100">
        <div class="bg-slate-900 p-10 text-white">
            <h2 class="text-2xl font-black italic">Webhook Debugger</h2>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">Status: Folder-Based Architecture</p>
        </div>

        <div class="p-10 space-y-6">
            <div>
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Targeted Webhook URL</h3>
                <code class="block bg-slate-50 p-4 rounded-xl text-[11px] text-blue-600 break-all font-mono border border-slate-100">
                    <?= $webhook_url ?>
                </code>
            </div>

            <div>
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Response</h3>
                <div class="bg-slate-900 rounded-2xl p-6">
                    <pre class="text-emerald-400 font-mono text-xs whitespace-pre-wrap lowercase"><?php 
                        if ($curl_error) echo "cURL Error: " . $curl_error;
                        elseif (empty($response)) echo "--- BLANK RESPONSE ---\nCheck paths in api/whatsapp_webhook.php";
                        else echo htmlspecialchars($response);
                    ?></pre>
                </div>
            </div>

            <div class="flex gap-4">
                <a href="test_wa.php" class="flex-1 bg-blue-600 text-white text-center font-black py-4 rounded-2xl text-[10px] uppercase tracking-widest hover:bg-blue-700 transition-all">Retry</a>
                <a href="../index.php" class="flex-1 bg-slate-100 text-slate-600 text-center font-black py-4 rounded-2xl text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all">Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>