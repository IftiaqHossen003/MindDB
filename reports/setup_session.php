<?php
/**
 * Session Setup for Admin Access
 * Run this page first to set up admin session, then access reports
 */

session_start();

// Set admin session
$_SESSION['user_role'] = 'admin';
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Session Setup - MindDB</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .success-message {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        
        .success-message h2 {
            color: #065f46;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .success-message p {
            color: #047857;
            line-height: 1.6;
        }
        
        .session-info {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .session-info h3 {
            color: #374151;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .session-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .session-info td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .session-info td:first-child {
            font-weight: 600;
            color: #6b7280;
            width: 40%;
        }
        
        .session-info td:last-child {
            color: #111827;
        }
        
        .links {
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px 10px 10px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 4px 12px rgba(240, 147, 251, 0.4);
        }
        
        .note {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            font-size: 14px;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Admin Session Setup Complete</h1>
        
        <div class="success-message">
            <h2>🎉 Success!</h2>
            <p>Your admin session has been created. You now have access to all reporting pages.</p>
        </div>
        
        <div class="session-info">
            <h3>📋 Session Information</h3>
            <table>
                <tr>
                    <td>Session ID:</td>
                    <td><code><?php echo session_id(); ?></code></td>
                </tr>
                <tr>
                    <td>User Role:</td>
                    <td><strong><?php echo $_SESSION['user_role']; ?></strong></td>
                </tr>
                <tr>
                    <td>User ID:</td>
                    <td><?php echo $_SESSION['user_id']; ?></td>
                </tr>
                <tr>
                    <td>Username:</td>
                    <td><?php echo $_SESSION['username']; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="links">
            <h3 style="color: #374151; margin-bottom: 15px;">📊 Access Reports:</h3>
            <a href="reports/weekly_mood.php" class="btn">📈 Weekly Mood Report</a>
            <a href="reports/top_mood_tags.php" class="btn btn-secondary">🏷️ Top Mood Tags</a>
        </div>
        
        <div class="note">
            <strong>⚠️ Development Note:</strong> This is a simplified session setup for testing. 
            In production, implement proper authentication with login credentials, password hashing, 
            and session security measures.
        </div>
    </div>
</body>
</html>
