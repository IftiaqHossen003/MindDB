<?php
/**
 * MindDB Main Application Entry Point
 * 
 * Provides navigation to all main application features.
 * 
 * @package    MindDB
 * @version    1.0.0
 * @author     GitHub Copilot
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindDB - Mental Health Journal & Resource Management</title>
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
            padding: 20px;
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s, background 0.3s;
            cursor: pointer;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .feature-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: #4CAF50;
            border-color: #4CAF50;
        }
        
        .btn-primary:hover {
            background: #45a049;
        }
        
        .btn-admin {
            background: #FF9800;
            border-color: #FF9800;
        }
        
        .btn-admin:hover {
            background: #F57C00;
        }
        
        .login-section {
            text-align: center;
            margin-top: 30px;
        }
        
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .status.online {
            background: #4CAF50;
        }
        
        .status.offline {
            background: #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧠 MindDB</h1>
            <p>Your Personal Mental Health Journal & Resource Management System</p>
        </div>
        
        <?php if ($isLoggedIn): ?>
            <div class="user-info">
                <h2>Welcome back, <?= htmlspecialchars($username) ?>! 👋</h2>
                <p>User ID: <?= $_SESSION['user_id'] ?> | Role: <span class="status <?= $isAdmin ? 'online' : 'offline' ?>"><?= htmlspecialchars($_SESSION['role'] ?? 'user') ?></span></p>
            </div>
            
            <div class="features-grid">
                <!-- Journal Management -->
                <div class="feature-card" onclick="location.href='/MindDB/public/journal.php'">
                    <span class="icon">📝</span>
                    <h3>My Journal</h3>
                    <p>View, create, and manage your personal journal entries with mood tracking and sentiment analysis.</p>
                    <a href="/MindDB/public/journal.php" class="btn btn-primary">Open Journal</a>
                </div>
                
                <!-- Quick Create -->
                <div class="feature-card" onclick="location.href='/MindDB/public/journal_create.php'">
                    <span class="icon">✍️</span>
                    <h3>Quick Create</h3>
                    <p>Quickly create a new journal entry to capture your thoughts and feelings right now.</p>
                    <a href="/MindDB/public/journal_create.php" class="btn btn-primary">Create Entry</a>
                </div>
                
                <!-- Reports & Analytics -->
                <div class="feature-card" onclick="location.href='/MindDB/reports/weekly_mood.php'">
                    <span class="icon">📊</span>
                    <h3>Mood Reports</h3>
                    <p>View your mood trends, analytics, and insights to track your mental health journey.</p>
                    <a href="/MindDB/reports/weekly_mood.php" class="btn">View Reports</a>
                </div>
                
                <!-- Mood Analytics Dashboard -->
                <div class="feature-card" onclick="location.href='/MindDB/reports/'">
                    <span class="icon">📈</span>
                    <h3>Analytics Dashboard</h3>
                    <p>Comprehensive mood analytics, sentiment trends, and wellness insights.</p>
                    <a href="/MindDB/reports/" class="btn">View Dashboard</a>
                </div>
                
                <?php if ($isAdmin): ?>
                    <!-- Admin Panel -->
                    <div class="feature-card" onclick="location.href='/MindDB/public/admin/resources.php'">
                        <span class="icon">⚙️</span>
                        <h3>Admin Panel</h3>
                        <p>Manage mental health resources, user accounts, and system configuration.</p>
                        <a href="/MindDB/public/admin/resources.php" class="btn btn-admin">Admin Panel</a>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <div class="login-section">
                <h2>Please log in to access your journal</h2>
                <p>You need to be authenticated to use the MindDB system.</p>
                <br>
                <a href="/MindDB/login.php" class="btn btn-primary">🔑 Login to MindDB</a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; text-align: center; opacity: 0.7;">
            <p><strong>Available Features:</strong></p>
            <p>Journal Entry Management • Mood Tracking • Analytics & Reports • Resource Management</p>
            <p><small>MindDB v1.0 | Built with PHP, MySQL, and modern web technologies</small></p>
        </div>
    </div>
</body>
</html>