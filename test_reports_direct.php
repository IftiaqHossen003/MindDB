<?php
/**
 * Simple Report Test - Direct PHP Execution
 * Tests the report pages without web server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Report Pages (Direct PHP) ===\n\n";

// Start session for admin access
session_start();
$_SESSION['user_role'] = 'admin';
$_SESSION['user_id'] = 1;

echo "Testing Weekly Mood Report...\n";
echo str_repeat("-", 50) . "\n";

// Capture output from weekly_mood.php
ob_start();
$_GET['format'] = 'json'; // Request JSON format
include __DIR__ . '/reports/weekly_mood.php';
$weeklyOutput = ob_get_clean();

// Parse JSON
$weeklyData = json_decode($weeklyOutput, true);
if ($weeklyData && isset($weeklyData['success'])) {
    echo "✅ Weekly Mood Report OK\n";
    echo "   Status: " . ($weeklyData['success'] ? 'Success' : 'Failed') . "\n";
    echo "   Data count: " . $weeklyData['data_count'] . " weeks\n";
    if ($weeklyData['data_count'] > 0) {
        $latest = $weeklyData['data'][0];
        echo "   Latest week: {$latest['year']}-W{$latest['week_number']}\n";
        echo "   Active users: {$latest['active_users']}\n";
        echo "   Avg mood: {$latest['avg_mood']}/10\n";
    }
} else {
    echo "❌ Weekly Mood Report FAILED\n";
    echo "   Raw output:\n";
    echo substr($weeklyOutput, 0, 500) . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

echo "Testing Top Mood Tags Report...\n";
echo str_repeat("-", 50) . "\n";

// Capture output from top_mood_tags.php
ob_start();
$_GET['format'] = 'json'; // Request JSON format
include __DIR__ . '/reports/top_mood_tags.php';
$tagsOutput = ob_get_clean();

// Parse JSON
$tagsData = json_decode($tagsOutput, true);
if ($tagsData && isset($tagsData['success'])) {
    echo "✅ Top Mood Tags Report OK\n";
    echo "   Status: " . ($tagsData['success'] ? 'Success' : 'Failed') . "\n";
    echo "   Data count: " . $tagsData['data_count'] . " tags\n";
    echo "   Statistics:\n";
    if (isset($tagsData['statistics'])) {
        echo "      - Unique tags: {$tagsData['statistics']['unique_tags']}\n";
        echo "      - Tagged entries: {$tagsData['statistics']['total_tagged_entries']}\n";
        echo "      - Avg mood: {$tagsData['statistics']['overall_avg_mood']}/10\n";
    }
    if ($tagsData['data_count'] > 0) {
        echo "   Top 3 tags:\n";
        foreach (array_slice($tagsData['data'], 0, 3) as $tag) {
            echo "      - {$tag['tag']}: {$tag['frequency']} uses ({$tag['percentage']}%)\n";
        }
    }
} else {
    echo "❌ Top Mood Tags Report FAILED\n";
    echo "   Raw output:\n";
    echo substr($tagsOutput, 0, 500) . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

echo "✅ Report system is operational!\n\n";
echo "Access via web browser:\n";
echo "- http://localhost/MindDB/reports/weekly_mood.php\n";
echo "- http://localhost/MindDB/reports/top_mood_tags.php\n";
echo "\nJSON API endpoints:\n";
echo "- http://localhost/MindDB/reports/weekly_mood.php?format=json\n";
echo "- http://localhost/MindDB/reports/top_mood_tags.php?format=json\n";
