<?php
// logout.php

// 1. Start the session to gain access to it
session_start();

// 2. Unset all of the session variables
$_SESSION = array();

// 3. If it's desired to kill the session, also delete the session cookie.
// This is a professional security standard that clears the browser's memory.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session on the server side
session_destroy();

// 5. Redirect the user back to the login page (or dashboard to trigger login check)
header("Location: index.php");
exit;
?>