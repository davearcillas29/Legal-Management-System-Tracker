<?php
// Start session
session_start();

// Rederect to Dashboard if logged in
if (isset($_SESSION['user_id'])) {
    header("Location: main.php");
    exit();
}

// Display Error Messages
$errorMsg = "";
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'empty') {
        $errorMsg = "Please fill in all fields.";
    }
    if ($_GET['error'] === 'invalid') {
        $errorMsg = "Invalid email or password.";
    }
    if ($_GET['error'] === 'domain') {
        $errorMsg = "Please use your official @adzu.edu.ph email.";
    }
    if ($_GET['error'] === 'timeout') {
        $errorMsg = "Your session expired due to inactivity. Please log in again.";
    }
}

// Reatained Entered Email
$submittedEmail = isset($_GET['email']) ? $_GET['email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In · ADZU Legal Affairs</title>
    
    <!-- Css and Google Fonts -->
    <link rel="stylesheet" href="../../frontend/src/css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Background Wrapper -->
    <div class="bg-gradient"></div>

    <main class="login-card">
        <header class="brand-header">
            <div class="brand-logo-wrap">
                <img src="../../frontend/src/img/adzu_seal.png" alt="ADZU Logo" class="brand-logo-img">
            </div>
            <h1 class="login-title">LegIT Docs</h1>
            <p class="login-sub">Legal Document Management System</p>
        </header>

        <!-- Error Alert Display -->
        <div class="login-error <?php echo !empty($errorMsg) ? 'show' : ''; ?>" role="alert">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span id="error-text"><?php echo htmlspecialchars($errorMsg); ?></span>
        </div>

        <form id="login-form" action="../controllers/login_process.php" method="POST" novalidate>
            <div class="login-field">
                <label for="login-email">Email Address</label>
                <div class="login-input-wrap">
                    <input 
                        type="email" 
                        id="login-email" 
                        name="email" 
                        placeholder="name@adzu.edu.ph" 
                        value="<?php echo htmlspecialchars($submittedEmail); ?>"
                        required 
                        autocomplete="email"
                    >
                    <svg class="icon icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
            </div>

            <div class="login-field">
                <label for="login-password">Password</label>
                <div class="login-input-wrap">
                    <input 
                        type="password" 
                        id="login-password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                    >
                    <svg class="icon icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <button type="button" class="login-toggle-pw" id="toggle-pw" aria-label="Toggle password visibility">
                        <svg class="icon" id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <span>Log In</span>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>
    </main>

    <script src="../../frontend/src/js/login.js?v=<?php echo time(); ?>"></script>
</body>
</html>