<?php
/**
 * Top Mood Tags Report
 * 
 * Displays most common mood tags using Chart.js bar chart.
 * Uses view_tag_frequency for aggregated data (no user associations).
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
 * Fetch top mood tags from view
 * @param int $limit Number of tags to return
 * @return array Tag frequency data
 */
function getTopMoodTags($limit = 15) {
    $db = getDatabaseConnection();
    
    // Query the restricted view (no user associations)
    $sql = "
        SELECT 
            tag,
            frequency,
            percentage,
            avg_mood_with_tag,
            uses_last_7_days,
            uses_last_30_days,
            with_low_mood,
            with_medium_mood,
            with_high_mood
        FROM view_tag_frequency
        WHERE tag != 'untagged'
        ORDER BY frequency DESC
        LIMIT :limit
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get mood tag statistics summary
 * @return array Summary statistics
 */
function getTagStatistics() {
    $db = getDatabaseConnection();
    
    $sql = "
        SELECT 
            COUNT(DISTINCT tag) as unique_tags,
            SUM(frequency) as total_tagged_entries,
            ROUND(AVG(avg_mood_with_tag), 2) as overall_avg_mood
        FROM view_tag_frequency
        WHERE tag != 'untagged'
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Check if request wants JSON response
 * @return bool
 */
function wantsJson() {
    return isset($_GET['format']) && $_GET['format'] === 'json';
}

// Fetch data
$topTags = getTopMoodTags(15);
$statistics = getTagStatistics();

// Return JSON if requested
if (wantsJson()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $topTags,
        'statistics' => $statistics,
        'count' => count($topTags)
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
    <title>Top Mood Tags Report - MindDB</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            border-bottom: 3px solid #f5576c;
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
            height: 500px;
            margin: 30px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        
        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        
        .tag-card {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }
        
        .tag-card:hover {
            border-color: #f5576c;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(245, 87, 108, 0.2);
        }
        
        .tag-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .tag-emoji {
            font-size: 2em;
            margin-right: 10px;
        }
        
        .tag-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.85em;
            color: #666;
        }
        
        .tag-mood-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .tag-mood-fill {
            height: 100%;
            background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
            transition: width 0.3s;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            font-size: 0.9em;
        }
        
        .data-table th {
            background: #f5576c;
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
        
        .mood-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .mood-low {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .mood-medium {
            background: #fef3c7;
            color: #92400e;
        }
        
        .mood-high {
            background: #d1fae5;
            color: #065f46;
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
            background: #f5576c;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e0475a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
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
                height: 400px;
            }
            
            .tags-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏷️ Top Mood Tags Report</h1>
            <p class="subtitle">Most frequently used mood tags across all users</p>
            <span class="privacy-badge">🔒 Privacy-Safe: No User Associations</span>
        </div>

        <?php if (empty($topTags)): ?>
            <div class="info-message">
                ℹ️ No mood tag data available yet. Start tagging mood entries to see patterns.
            </div>
        <?php else: ?>
            <!-- Summary Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Unique Tags</div>
                    <div class="stat-value"><?php echo htmlspecialchars($statistics['unique_tags'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label">(All time)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Tagged Entries</div>
                    <div class="stat-value"><?php echo htmlspecialchars($statistics['total_tagged_entries'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label">(Total journals)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Average Mood</div>
                    <div class="stat-value"><?php echo htmlspecialchars($statistics['overall_avg_mood'], ENT_QUOTES, 'UTF-8'); ?>/10</div>
                    <div class="stat-label">(With tags)</div>
                </div>
            </div>

            <!-- Tag Frequency Chart -->
            <h2 style="margin-top: 40px; color: #333;">Top 15 Mood Tags by Frequency</h2>
            <div class="chart-container">
                <canvas id="tagFrequencyChart"></canvas>
            </div>

            <!-- Average Mood by Tag Chart -->
            <h2 style="margin-top: 40px; color: #333;">Average Mood Level by Tag</h2>
            <div class="chart-container" style="height: 400px;">
                <canvas id="tagMoodChart"></canvas>
            </div>

            <!-- Tag Cards Grid -->
            <h2 style="margin-top: 40px; color: #333;">Tag Details</h2>
            <div class="tags-grid">
                <?php 
                $emojiMap = [
                    'happy' => '😊', 'sad' => '😢', 'anxious' => '😰', 'calm' => '😌',
                    'stressed' => '😫', 'excited' => '🤩', 'tired' => '😴', 'angry' => '😠',
                    'grateful' => '🙏', 'hopeful' => '🌟', 'lonely' => '😔', 'energetic' => '⚡',
                    'content' => '😌', 'worried' => '😟', 'joyful' => '😄', 'frustrated' => '😤',
                    'peaceful' => '☮️', 'overwhelmed' => '😵', 'motivated' => '💪', 'optimistic' => '🌈'
                ];
                
                foreach ($topTags as $tag): 
                    $emoji = $emojiMap[strtolower($tag['tag'])] ?? '🎭';
                    $moodPercent = ($tag['avg_mood_with_tag'] / 10) * 100;
                ?>
                    <div class="tag-card">
                        <div class="tag-name">
                            <span class="tag-emoji"><?php echo $emoji; ?></span>
                            <?php echo htmlspecialchars($tag['tag'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="tag-stats">
                            <span><strong><?php echo htmlspecialchars($tag['frequency'], ENT_QUOTES, 'UTF-8'); ?></strong> uses</span>
                            <span><?php echo htmlspecialchars($tag['percentage'], ENT_QUOTES, 'UTF-8'); ?>%</span>
                        </div>
                        <div class="tag-stats">
                            <span>Avg Mood: <strong><?php echo htmlspecialchars($tag['avg_mood_with_tag'], ENT_QUOTES, 'UTF-8'); ?>/10</strong></span>
                        </div>
                        <div class="tag-mood-bar">
                            <div class="tag-mood-fill" style="width: <?php echo htmlspecialchars($moodPercent, ENT_QUOTES, 'UTF-8'); ?>%"></div>
                        </div>
                        <div class="tag-stats" style="margin-top: 10px; font-size: 0.75em;">
                            <span>Last 7d: <?php echo htmlspecialchars($tag['uses_last_7_days'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>Last 30d: <?php echo htmlspecialchars($tag['uses_last_30_days'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Detailed Data Table -->
            <h2 style="margin-top: 40px; color: #333;">Detailed Tag Statistics</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Frequency</th>
                        <th>Percentage</th>
                        <th>Avg Mood</th>
                        <th>With Low Mood</th>
                        <th>With Medium Mood</th>
                        <th>With High Mood</th>
                        <th>Last 30 Days</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topTags as $tag): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($tag['tag'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars($tag['frequency'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tag['percentage'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                            <td>
                                <?php 
                                $mood = $tag['avg_mood_with_tag'];
                                $badgeClass = $mood <= 3 ? 'mood-low' : ($mood >= 8 ? 'mood-high' : 'mood-medium');
                                ?>
                                <span class="mood-badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($mood, ENT_QUOTES, 'UTF-8'); ?>/10
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($tag['with_low_mood'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tag['with_medium_mood'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tag['with_high_mood'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tag['uses_last_30_days'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Actions -->
        <div class="actions">
            <a href="weekly_mood.php" class="btn btn-primary">← View Weekly Trends</a>
            <a href="?format=json" class="btn btn-secondary" target="_blank">Export JSON</a>
            <a href="../" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Chart Data -->
    <script>
        const tagData = <?php echo json_encode($topTags); ?>;
    </script>
    
    <!-- Custom Charts Script -->
    <script src="reports.js"></script>
</body>
</html>
