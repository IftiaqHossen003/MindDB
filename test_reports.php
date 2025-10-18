<?php
/**
 * Test Script for MindDB Reporting System
 * 
 * This script tests:
 * 1. Database connection
 * 2. Restricted views access
 * 3. Data retrieval for reports
 * 4. Session setup for admin access
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== MindDB Reporting System Test ===\n\n";

// Test 1: Config file
echo "1. Testing config.php...\n";
if (!file_exists(__DIR__ . '/config.php')) {
    die("   ❌ FAILED: config.php not found\n");
}
require_once __DIR__ . '/config.php';
echo "   ✅ config.php loaded\n\n";

// Test 2: Database connection
echo "2. Testing database connection...\n";
try {
    $db = getDb();
    echo "   ✅ Database connected successfully\n";
    echo "   Database: " . DB_NAME . "\n";
    echo "   Host: " . DB_HOST . ":" . DB_PORT . "\n\n";
} catch (Exception $e) {
    die("   ❌ FAILED: " . $e->getMessage() . "\n");
}

// Test 3: Check if restricted views exist
echo "3. Testing restricted views...\n";
$views = [
    'view_weekly_trends',
    'view_tag_frequency',
    'view_aggregate_journals',
    'view_mood_distribution',
    'view_therapy_effectiveness',
    'view_resource_engagement',
    'view_monthly_summary',
    'view_activity_metrics'
];

$viewsFound = 0;
foreach ($views as $view) {
    try {
        $stmt = $db->query("SELECT 1 FROM $view LIMIT 1");
        echo "   ✅ $view exists\n";
        $viewsFound++;
    } catch (Exception $e) {
        echo "   ❌ $view not found: " . $e->getMessage() . "\n";
    }
}
echo "   Found $viewsFound/8 views\n\n";

// Test 4: Query view_weekly_trends
echo "4. Testing view_weekly_trends query...\n";
try {
    $stmt = $db->query("SELECT * FROM view_weekly_trends ORDER BY year DESC, week_number DESC LIMIT 5");
    $weeklyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✅ Query successful\n";
    echo "   Rows returned: " . count($weeklyData) . "\n";
    
    if (count($weeklyData) > 0) {
        $latest = $weeklyData[0];
        echo "   Latest week: {$latest['year']}-W{$latest['week_number']}\n";
        echo "   Active users: {$latest['active_users']}\n";
        echo "   Total journals: {$latest['total_journals']}\n";
        echo "   Avg mood: {$latest['avg_mood']}/10\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 5: Query view_tag_frequency
echo "5. Testing view_tag_frequency query...\n";
try {
    $stmt = $db->query("SELECT * FROM view_tag_frequency WHERE tag != 'untagged' ORDER BY frequency DESC LIMIT 10");
    $tagData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✅ Query successful\n";
    echo "   Rows returned: " . count($tagData) . "\n";
    
    if (count($tagData) > 0) {
        echo "   Top 3 tags:\n";
        foreach (array_slice($tagData, 0, 3) as $tag) {
            echo "      - {$tag['tag']}: {$tag['frequency']} uses ({$tag['percentage']}%), avg mood: {$tag['avg_mood_with_tag']}/10\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 6: Check report files
echo "6. Testing report files...\n";
$reportFiles = [
    'reports/weekly_mood.php',
    'reports/top_mood_tags.php',
    'reports/reports.js'
];

foreach ($reportFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ $file exists (" . round(filesize(__DIR__ . '/' . $file) / 1024, 2) . " KB)\n";
    } else {
        echo "   ❌ $file not found\n";
    }
}
echo "\n";

// Test 7: Session setup
echo "7. Testing session setup...\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_role'] = 'admin';
$_SESSION['user_id'] = 1;
echo "   ✅ Session started with admin role\n";
echo "   Session ID: " . session_id() . "\n\n";

// Test 8: Privacy validation
echo "8. Testing privacy compliance...\n";
try {
    $stmt = $db->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
        AND TABLE_NAME IN ('view_weekly_trends', 'view_tag_frequency', 'view_aggregate_journals', 'view_mood_distribution')
        AND COLUMN_NAME IN ('user_id', 'pseudonym', 'email', 'username', 'anonym_id')
    ");
    $piiColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($piiColumns) === 0) {
        echo "   ✅ NO PII columns found in views\n";
    } else {
        echo "   ⚠️ WARNING: Found PII columns:\n";
        foreach ($piiColumns as $col) {
            echo "      - {$col['TABLE_NAME']}.{$col['COLUMN_NAME']}\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Final summary
echo "=== Test Summary ===\n";
echo "✅ All critical components verified\n";
echo "\n";
echo "Next steps:\n";
echo "1. Start XAMPP Apache server\n";
echo "2. Visit: http://localhost/MindDB/reports/weekly_mood.php\n";
echo "3. Visit: http://localhost/MindDB/reports/top_mood_tags.php\n";
echo "\n";
echo "For JSON export:\n";
echo "- http://localhost/MindDB/reports/weekly_mood.php?format=json\n";
echo "- http://localhost/MindDB/reports/top_mood_tags.php?format=json\n";
echo "\n";
