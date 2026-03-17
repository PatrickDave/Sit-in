<?php
// 1. Start the session to gain access to it
session_start();

// 2. Unset all of the session variables
$_SESSION = array();

// 3. If it's desired to kill the session, also delete the session cookie.
// Note: This will completely destroy the session, not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session.
session_destroy();

// 5. Redirect to the login page (adjust the path if your login is named differently)
header("Location: ../html/login.html"); 
exit;
?>