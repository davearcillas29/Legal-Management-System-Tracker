<?php
session_start();

// If user is already logged in, route to main dashboard, otherwise to login
if (isset($_SESSION['user_id'])) {
    header("Location: backend/pages/main.php");
}else {
    header("Location: backend/pages/login.php");
}
exit();