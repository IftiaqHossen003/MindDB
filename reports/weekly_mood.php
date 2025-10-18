<?php
/**
 * Weekly Mood Report
 * 
 * Displays weekly mood trends using Chart.js line chart.
 * Uses view_weekly_trends for aggregated data (no PII).
 * 
 * @package    MindDB
 * @subpackage Reports
 * @version    1.0.0
 * @access     Admin only (session-based)
 */

// Start session for admin authentication
session_start();

// Check if user is admin (session-based authentication)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Access Denied: Admin privileges required');
}

// Load database configuration
require_once __DIR__ . '/../config.php';

/**
 * Get database connection
 * @return PDO Database connection
 */
function getDatabaseConnection() {
    try {
        return getDb();
    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection error");
    }
}

/**
 * Fetch weekly mood trends from view
 * @return array Weekly mood data
 */
function getWeeklyMoodTrends() {
    $db = getDatabaseConnection();
    
    // Query the restricted view (no PII)
    $sql = "
        SELECT 
            year,
            week_number,
            week_start_date,
            active_users,
            total_journals,
            avg_mood,
            min_mood,
            max_mood,
            pct_low_mood,
            pct_medium_mood,
            pct_high_mood
        FROM view_weekly_trends
        ORDER BY year DESC, week_number DESC
        LIMIT 12
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Check if request wants JSON response
 * @return bool
 */
function wantsJson() {
    return isset($_GET['format']) && $_GET['format'] === 'json';
}

// Fetch data
$weeklyData = getWeeklyMoodTrends();

// Return JSON if requested
if (wantsJson()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $weeklyData,
        'count' => count($weeklyData)
    ]);
    exit;
}

// HTML Page below
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Mood Report - MindDB</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px;
        }
        
        .header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        
        h1 {
            color: #333;
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 0.95em;
        }
        
        .privacy-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin: 30px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            font-size: 0.9em;
        }
        
        .data-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table tr:hover {
            background: #f9fafb;
        }
        
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95em;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        .info-message {
            background: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.5em;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Weekly Mood Trends Report</h1>
            <p class="subtitle">Aggregated mood data across all users (Last 12 weeks)</p>
            <span class="privacy-badge">🔒 Privacy-Safe: No PII</span>
        </div>

        <?php if (empty($weeklyData)): ?>
            <div class="info-message">
                ℹ️ No data available yet. Start logging mood entries to see trends.
            </div>
        <?php else: ?>
            <!-- Summary Statistics -->
            <?php
                $totalUsers = array_sum(array_column($weeklyData, 'active_users'));
                $totalJournals = array_sum(array_column($weeklyData, 'total_journals'));
                $avgMoodOverall = round(array_sum(array_column($weeklyData, 'avg_mood')) / count($weeklyData), 2);
                $latestWeek = $weeklyData[count($weeklyData) - 1];
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Active Users</div>
                    <div class="stat-value"><?php echo htmlspecialchars($totalUsers, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label">(Last 12 weeks)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Journal Entries</div>
                    <div class="stat-value"><?php echo htmlspecialchars($totalJournals, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label">(Last 12 weeks)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Average Mood</div>
                    <div class="stat-value"><?php echo htmlspecialchars($avgMoodOverall, ENT_QUOTES, 'UTF-8'); ?>/10</div>
                    <div class="stat-label">(12-week average)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Latest Week Mood</div>
                    <div class="stat-value"><?php echo htmlspecialchars($latestWeek['avg_mood'], ENT_QUOTES, 'UTF-8'); ?>/10</div>
                    <div class="stat-label">(Week <?php echo htmlspecialchars($latestWeek['week_number'], ENT_QUOTES, 'UTF-8'); ?>)</div>
                </div>
            </div>

            <!-- Mood Trend Chart -->
            <h2 style="margin-top: 40px; color: #333;">Mood Trend Over Time</h2>
            <div class="chart-container">
                <canvas id="moodTrendChart"></canvas>
            </div>

            <!-- Mood Distribution Chart -->
            <h2 style="margin-top: 40px; color: #333;">Mood Distribution (Latest Week)</h2>
            <div class="chart-container" style="height: 300px;">
                <canvas id="moodDistributionChart"></canvas>
            </div>

            <!-- Data Table -->
            <h2 style="margin-top: 40px; color: #333;">Detailed Weekly Data</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Week Start</th>
                        <th>Active Users</th>
                        <th>Journals</th>
                        <th>Avg Mood</th>
                        <th>Min/Max</th>
                        <th>Low Mood %</th>
                        <th>Medium Mood %</th>
                        <th>High Mood %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weeklyData as $week): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($week['week_start_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($week['active_users'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($week['total_journals'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo htmlspecialchars($week['avg_mood'], ENT_QUOTES, 'UTF-8'); ?>/10</strong></td>
                            <td><?php echo htmlspecialchars($week['min_mood'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($week['max_mood'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($week['pct_low_mood'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                            <td><?php echo htmlspecialchars($week['pct_medium_mood'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                            <td><?php echo htmlspecialchars($week['pct_high_mood'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Actions -->
        <div class="actions">
            <a href="top_mood_tags.php" class="btn btn-primary">View Top Mood Tags →</a>
            <a href="?format=json" class="btn btn-secondary" target="_blank">Export JSON</a>
            <a href="../" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Chart Data -->
    <script>
        const weeklyData = <?php echo json_encode($weeklyData); ?>;
    </script>
    
    <!-- Custom Charts Script -->
    <script src="reports.js"></script>
</body>
</html>
