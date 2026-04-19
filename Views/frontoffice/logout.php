<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_unset();
session_destroy();

// Use an absolute path if possible, but relative should work in this context
header("Location: nutrismart-website.html");
exit;
?>
