<?php
// core/Security.php
class Security {
    public static function clean($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function can($perm_key) {
        // If the session hasn't loaded permissions yet, deny access
        if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
            return false;
        }
        return in_array($perm_key, $_SESSION['permissions']);
    }

    public static function guard($perm_key) {
        if (!self::can($perm_key)) {
            // Debugging: uncomment the line below if you keep getting kicked out
            // die("Access Denied: Missing " . $perm_key); 
            header("Location: ../index.php?error=unauthorized_capability");
            exit;
        }
    }
}