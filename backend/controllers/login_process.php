<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get input safely
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check for empty fields
    if (empty($email) || empty($password)) {
        session_unset();
        session_destroy();
        header("Location: ../pages/login.php?error=empty");
        exit();
    }

    // Connect to database (db.php uses $db_password safely now)
    require_once __DIR__ . '/../database/db.php';

    // Find user by email using Prepared Statement
    $stmt = $conn->prepare("SELECT id, email, password_hash FROM login_db WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $storedHash = $row['password_hash'];

        // Verify password against DB hash (or direct string if stored as plain text)
        if (password_verify($password, $storedHash) || $password === $storedHash) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['last_activity'] = time();

            $stmt->close();
            header("Location: ../pages/main.php");
            exit();
        }
    }

    // Failed login: clear any leftover session and redirect with error
    if (isset($stmt)) {
        $stmt->close();
    }
    session_unset();
    session_destroy();

    header("Location: ../pages/login.php?error=invalid");
    exit();
} else {
    header("Location: ../pages/login.php");
    exit();
}
?>