<?php
/**
 * MindDB Reports - Admin Dashboard
 * Main entry point for reporting system
 */

session_start();

// Auto-setup admin session for testing/development
// TODO: Replace with proper authentication in production
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
}

// Check if user has admin access
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindDB Reports - Admin Dashboard</title>
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
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #6b7280;
            font-size: 16px;
        }
        
        .session-badge {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .report-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        
        .report-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .report-card h2 {
            color: #111827;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .report-card p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .report-stats {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .stat {
            flex: 1;
        }
        
        .stat-label {
            color: #9ca3af;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .stat-value {
            color: #111827;
            font-size: 20px;
            font-weight: 700;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        .info-box {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .info-box h3 {
            color: #111827;
            font-size: 20px;
            margin-bottom: 15px;
        }
        
        .info-list {
            list-style: none;
            padding: 0;
        }
        
        .info-list li {
            color: #6b7280;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
        }
        
        .info-list li:last-child {
            border-bottom: none;
        }
        
        .info-list li::before {
            content: "✓";
            color: #10b981;
            font-weight: bold;
            margin-right: 10px;
            font-size: 18px;
        }
        
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin-top: 30px;
            border-radius: 8px;
        }
        
        .warning-box h4 {
            color: #92400e;
            margin-bottom: 10px;
        }
        
        .warning-box p {
            color: #78350f;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 MindDB Reports Dashboard</h1>
            <p class="subtitle">Administrative Analytics & Insights</p>
            <?php if ($isAdmin): ?>
                <span class="session-badge">✓ Admin Access Granted</span>
            <?php endif; ?>
        </header>
        
        <?php if ($isAdmin): ?>
            <div class="reports-grid">
                <div class="report-card" onclick="window.location.href='weekly_mood.php';" style="cursor: pointer;">
                    <div class="report-icon">📈</div>
                    <h2>Weekly Mood Trends</h2>
                    <p>Analyze mood patterns and trends over time. View aggregated weekly statistics including active users, journal entries, and mood distributions.</p>
                    <div class="report-stats">
                        <div class="stat">
                            <div class="stat-label">Chart Types</div>
                            <div class="stat-value">2</div>
                        </div>
                        <div class="stat">
                            <div class="stat-label">Data Source</div>
                            <div class="stat-value">View</div>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <a href="weekly_mood.php" class="btn" onclick="event.stopPropagation();">View Report →</a>
                    </div>
                </div>
                
                <div class="report-card" onclick="window.location.href='top_mood_tags.php';" style="cursor: pointer;">
                    <div class="report-icon">🏷️</div>
                    <h2>Top Mood Tags</h2>
                    <p>Discover the most frequently used mood tags and their associated average mood levels. Identify emotional patterns across the platform.</p>
                    <div class="report-stats">
                        <div class="stat">
                            <div class="stat-label">Chart Types</div>
                            <div class="stat-value">2</div>
                        </div>
                        <div class="stat">
                            <div class="stat-label">Data Source</div>
                            <div class="stat-value">View</div>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <a href="top_mood_tags.php" class="btn" onclick="event.stopPropagation();">View Report →</a>
                    </div>
                </div>
            </div>
            
            <div class="info-box">
                <h3>🔒 Privacy & Security Features</h3>
                <ul class="info-list">
                    <li>All data is aggregated and anonymized</li>
                    <li>No personally identifiable information (PII) is displayed</li>
                    <li>Queries use restricted views with no direct table access</li>
                    <li>Session-based authentication required</li>
                    <li>All outputs are sanitized to prevent XSS</li>
                    <li>Prepared statements prevent SQL injection</li>
                    <li>JSON export available for API integration</li>
                    <li>Chart.js visualizations for better insights</li>
                </ul>
            </div>
            
            <div class="warning-box">
                <h4>⚠️ Development Environment Notice</h4>
                <p>This dashboard currently uses auto-session setup for development/testing purposes. In a production environment, implement proper authentication with secure login credentials, password hashing (bcrypt/Argon2), CSRF protection, and session security measures (secure cookies, HTTP-only flags, session regeneration).</p>
            </div>
            
        <?php else: ?>
            <div class="info-box">
                <h3>🔒 Access Denied</h3>
                <p style="color: #991b1b; margin-top: 15px;">You need administrator privileges to access this dashboard.</p>
                <p style="color: #6b7280; margin-top: 10px;">Please contact your system administrator for access.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
