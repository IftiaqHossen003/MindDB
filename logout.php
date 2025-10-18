<?php
/**
 * MindDB Logout Page
 * 
 * Handles user logout and session cleanup.
 * 
 * @package    MindDB
 * @version    1.0.0
 * @author     GitHub Copilot
 */

// Start session to access session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store current user info before destroying session
$was_logged_in = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Unknown';

// Destroy session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - MindDB</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .logout-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        
        .message {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e9ecef;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .footer {
            margin-top: 30px;
            color: #999;
            font-size: 0.9rem;
        }
    </style>
    <script>
        // Auto-redirect after 10 seconds
        setTimeout(function() {
            window.location.href = '/MindDB/login.php';
        }, 10000);
        
        // Countdown timer
        let countdown = 10;
        setInterval(function() {
            countdown--;
            const element = document.getElementById('countdown');
            if (element && countdown > 0) {
                element.textContent = countdown;
            }
        }, 1000);
    </script>
</head>
<body>
    <div class="logout-container">
        <div class="icon">👋</div>
        
        <?php if ($was_logged_in): ?>
            <h1>Successfully Logged Out</h1>
            <div class="message">
                Goodbye, <strong><?= htmlspecialchars($username) ?></strong>!<br>
                Your session has been securely ended.<br><br>
                Thank you for using MindDB.
            </div>
        <?php else: ?>
            <h1>Already Logged Out</h1>
            <div class="message">
                You were not logged in or your session has already ended.
            </div>
        <?php endif; ?>
        
        <div>
            <a href="/MindDB/login.php" class="btn btn-primary">🔑 Login Again</a>
            <a href="/MindDB/" class="btn btn-secondary">🏠 Home</a>
        </div>
        
        <div class="footer">
            <p>Automatically redirecting to login in <span id="countdown">10</span> seconds...</p>
        </div>
    </div>
</body>
</html>