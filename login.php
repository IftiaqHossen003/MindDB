<?php
/**
 * MindDB Login Page
 * 
 * Provides user authentication with proper user selection.
 * 
 * @package    MindDB
 * @version    1.0.0
 * @author     GitHub Copilot
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user_type = $_POST['user_type'] ?? '';
    
    switch ($user_type) {
        case 'admin':
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin_test';
            $_SESSION['role'] = 'admin';
            break;
        case 'testuser':
            $_SESSION['user_id'] = 2;
            $_SESSION['username'] = 'TestUser';
            $_SESSION['role'] = 'user';
            break;
        case 'demo':
            $_SESSION['user_id'] = 3;
            $_SESSION['username'] = 'DemoUser';
            $_SESSION['role'] = 'user';
            break;
        default:
            $error = "Please select a valid user type.";
    }
    
    if (!isset($error)) {
        // Redirect to main page after successful login
        header('Location: /MindDB/');
        exit;
    }
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    $current_user = $_SESSION['username'];
    $current_role = $_SESSION['role'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MindDB</title>
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
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .current-session {
            background: #e8f5e8;
            border: 1px solid #4CAF50;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .current-session h3 {
            color: #4CAF50;
            margin-bottom: 10px;
        }
        
        .session-info {
            color: #333;
            margin-bottom: 15px;
        }
        
        .user-selection {
            margin-bottom: 30px;
        }
        
        .user-option {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .user-option:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .user-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .user-option input[type="radio"]:checked + .option-content {
            color: #667eea;
        }
        
        .user-option input[type="radio"]:checked ~ .checkmark {
            display: block;
        }
        
        .option-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .user-details {
            text-align: left;
        }
        
        .user-name {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .user-role {
            color: #666;
            font-size: 0.9rem;
        }
        
        .user-icon {
            font-size: 2rem;
        }
        
        .checkmark {
            display: none;
            color: #4CAF50;
            font-size: 1.5rem;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 20px;
            margin: 5px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .footer {
            margin-top: 30px;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🧠</div>
        <h1>MindDB</h1>
        <p class="subtitle">Mental Health Journal & Resource Management</p>
        
        <?php if (isset($current_user)): ?>
            <div class="current-session">
                <h3>✅ Currently Logged In</h3>
                <div class="session-info">
                    <strong>User:</strong> <?= htmlspecialchars($current_user) ?><br>
                    <strong>Role:</strong> <?= htmlspecialchars($current_role) ?><br>
                    <strong>User ID:</strong> <?= $_SESSION['user_id'] ?>
                </div>
                <a href="/MindDB/" class="btn-secondary">🏠 Go to Dashboard</a>
                <a href="/MindDB/logout.php" class="btn-secondary">🚪 Logout</a>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="user-selection">
                <h3 style="color: #333; margin-bottom: 20px;">Select User Account</h3>
                
                <label class="user-option">
                    <input type="radio" name="user_type" value="admin" required>
                    <div class="option-content">
                        <div class="user-details">
                            <div class="user-name">Admin Test</div>
                            <div class="user-role">Administrator - Full Access</div>
                        </div>
                        <div class="user-icon">⚙️</div>
                    </div>
                    <div class="checkmark">✓</div>
                </label>
                
                <label class="user-option">
                    <input type="radio" name="user_type" value="testuser" required>
                    <div class="option-content">
                        <div class="user-details">
                            <div class="user-name">Test User</div>
                            <div class="user-role">Regular User - Journal Access</div>
                        </div>
                        <div class="user-icon">👤</div>
                    </div>
                    <div class="checkmark">✓</div>
                </label>
                
                <label class="user-option">
                    <input type="radio" name="user_type" value="demo" required>
                    <div class="option-content">
                        <div class="user-details">
                            <div class="user-name">Demo User</div>
                            <div class="user-role">Demo Account - Limited Access</div>
                        </div>
                        <div class="user-icon">🎭</div>
                    </div>
                    <div class="checkmark">✓</div>
                </label>
            </div>
            
            <button type="submit" name="login" class="btn">
                🔑 Login to MindDB
            </button>
        </form>
        
        <div class="footer">
            <p><strong>Test Environment</strong></p>
            <p>Choose your user type to access different features and permissions</p>
        </div>
    </div>
    
    <script>
        // Enhanced radio button interaction
        document.querySelectorAll('.user-option').forEach(option => {
            option.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Remove previous selections
                document.querySelectorAll('.user-option').forEach(opt => {
                    opt.style.borderColor = '#e9ecef';
                    opt.style.background = '#f8f9fa';
                });
                
                // Highlight selected option
                this.style.borderColor = '#667eea';
                this.style.background = '#f0f4ff';
            });
        });
    </script>
</body>
</html>