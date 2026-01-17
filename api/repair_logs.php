<?php
// api/repair_logs.php
$log_path = 'wa_log.txt';
$f = fopen($log_path, "a");
if ($f) {
    fwrite($f, "[" . date('Y-m-d H:i:s') . "] SYSTEM REPAIR: Log stream opened." . PHP_EOL);
    fclose($f);
    chmod($log_path, 0777);
    echo "Log file repaired and writable!";
} else {
    echo "Critical Error: WAMP folder permissions are blocking file creation in /api/";
}