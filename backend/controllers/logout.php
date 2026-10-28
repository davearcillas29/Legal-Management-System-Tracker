<?php
session_start();

// This clears all session cookie if it exists
$_SESSION = array();

// DRemoves the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Ends the current session
session_destroy();

// Redirects the user to the login page
header("Location: ../pages/login.php");
exit();
?>