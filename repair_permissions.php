<?php
require_once 'config/db.php';

try {
    // 1. Ensure Admin has the "ALL" flag
    $pdo->exec("UPDATE users SET college_id = 'ALL' WHERE role_id = 1");
    
    // 2. Ensure ADD_LEADS permission is registered
    $pdo->exec("INSERT IGNORE INTO permissions (perm_key, perm_desc) VALUES ('ADD_LEADS', 'Allow adding leads')");
    
    // 3. Link ADD_LEADS to Superadmin role
    $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, perm_id) 
                SELECT 1, id FROM permissions WHERE perm_key = 'ADD_LEADS'");

    echo "<div style='font-family:sans-serif; color:green; padding:20px;'>
            <h2>✅ Permissions Repaired!</h2>
            <p>1. Superadmins now have 'ALL' institutional access.</p>
            <p>2. 'ADD_LEADS' permission has been mapped to the Superadmin role.</p>
            <p><strong>IMPORTANT:</strong> You MUST <a href='logout.php'>Logout</a> and Log back in for this to work.</p>
          </div>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}