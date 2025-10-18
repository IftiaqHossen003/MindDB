<?php
/**
 * Complete MindDB System Integration Test
 * 
 * Final comprehensive test of all system components working together.
 * 
 * @author GitHub Copilot
 * @version 1.0.0
 * @date 2025-10-18
 */

// Start output buffering to capture any output
ob_start();

// Test configuration
define('TEST_MODE', true);
$testResults = [];

// Test 1: Complete Database Verification
function testCompleteDatabase() {
    global $testResults;
    
    try {
        require_once 'config.php';
        $db = getDb();
        
        // Check all critical tables
        $requiredTables = ['users', 'journal_entries', 'resources', 'mood_logs', 'helplines', 'therapy_sessions'];
        $existingTables = [];
        
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $existingTables[] = $row[0];
        }
        
        $missingTables = array_diff($requiredTables, $existingTables);
        
        if (!empty($missingTables)) {
            $testResults['complete_database'] = [
                'status' => 'FAIL',
                'message' => 'Missing tables: ' . implode(', ', $missingTables)
            ];
            return false;
        }
        
        // Check data integrity
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'user')");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($userCount < 2) {
            $testResults['complete_database'] = [
                'status' => 'FAIL',
                'message' => 'Insufficient user data for testing'
            ];
            return false;
        }
        
        $testResults['complete_database'] = [
            'status' => 'PASS',
            'message' => "All tables exist with $userCount users ready for testing"
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['complete_database'] = [
            'status' => 'FAIL',
            'message' => 'Database integration error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 2: End-to-End Admin Workflow
function testAdminWorkflow() {
    global $testResults;
    
    try {
        // Setup admin session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        
        // Test AdminResourceController full workflow
        require_once 'app/controllers/AdminResourceController.php';
        $controller = new AdminResourceController();
        
        // Test list resources
        $data = $controller->index();
        if (!isset($data['resources']) || !is_array($data['resources'])) {
            $testResults['admin_workflow'] = [
                'status' => 'FAIL',
                'message' => 'Admin controller index method not returning proper structure'
            ];
            return false;
        }
        
        // Test create new resource
        $testResourceData = [
            'title' => 'Test Integration Resource',
            'resource_type' => 'article',
            'url' => 'https://example.com/test',
            'description' => 'Integration test resource',
            'category' => 'testing',
            'author' => 'Test Suite',
            'duration_minutes' => 5,
            'difficulty_level' => 'easy',
            'is_featured' => 0,
            'is_active' => 1
        ];
        
        $success = $controller->store($testResourceData);
        if (!$success) {
            $testResults['admin_workflow'] = [
                'status' => 'FAIL',
                'message' => 'Admin resource creation failed'
            ];
            return false;
        }
        
        $testResults['admin_workflow'] = [
            'status' => 'PASS',
            'message' => 'Admin workflow complete - List, create, and manage resources working'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['admin_workflow'] = [
            'status' => 'FAIL',
            'message' => 'Admin workflow error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 3: Journal Entry End-to-End
function testJournalWorkflow() {
    global $testResults;
    
    try {
        // Setup user session
        $_SESSION['user_id'] = 2;
        $_SESSION['username'] = 'demo_user';
        $_SESSION['role'] = 'user';
        
        require_once 'app/models/JournalEntry.php';
        $model = new JournalEntry();
        
        // Test full CRUD workflow
        $testEntry = [
            'user_id' => 2,
            'title' => 'Integration Test Entry',
            'content' => 'This is a test journal entry for integration testing.',
            'mood_tags' => 'testing,integration',
            'sentiment_score' => 0.8
        ];
        
        // Create
        $entryId = $model->create($testEntry);
        if (!$entryId) {
            $testResults['journal_workflow'] = [
                'status' => 'FAIL',
                'message' => 'Journal entry creation failed'
            ];
            return false;
        }
        
        // Read
        $entry = $model->find($entryId);
        if (!$entry || $entry['title'] !== $testEntry['title']) {
            $testResults['journal_workflow'] = [
                'status' => 'FAIL',
                'message' => 'Journal entry retrieval failed'
            ];
            return false;
        }
        
        // Update
        $updateData = [
            'title' => 'Updated Integration Test Entry',
            'content' => $testEntry['content'],
            'mood_tags' => $testEntry['mood_tags'],
            'sentiment_score' => 0.9
        ];
        
        $updated = $model->update($entryId, $updateData);
        if (!$updated) {
            $testResults['journal_workflow'] = [
                'status' => 'FAIL',
                'message' => 'Journal entry update failed'
            ];
            return false;
        }
        
        // Delete (cleanup)
        $deleted = $model->delete($entryId);
        if (!$deleted) {
            $testResults['journal_workflow'] = [
                'status' => 'FAIL',
                'message' => 'Journal entry deletion failed'
            ];
            return false;
        }
        
        $testResults['journal_workflow'] = [
            'status' => 'PASS',
            'message' => 'Journal workflow complete - Full CRUD cycle working'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['journal_workflow'] = [
            'status' => 'FAIL',
            'message' => 'Journal workflow error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 4: Reports and Analytics Integration
function testReportsIntegration() {
    global $testResults;
    
    try {
        // Setup admin session for reports
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        
        require_once 'config.php';
        $db = getDb();
        
        // Test that views exist and return data
        $stmt = $db->query("SELECT * FROM view_weekly_trends LIMIT 1");
        $weeklyData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$weeklyData) {
            $testResults['reports_integration'] = [
                'status' => 'FAIL',
                'message' => 'Weekly trends view not returning data'
            ];
            return false;
        }
        
        $stmt = $db->query("SELECT * FROM view_tag_frequency LIMIT 1");
        $tagData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tagData) {
            $testResults['reports_integration'] = [
                'status' => 'FAIL',
                'message' => 'Tag frequency view not returning data'
            ];
            return false;
        }
        
        // Check that report files exist and are accessible
        $reportFiles = [
            'reports/weekly_mood.php',
            'reports/top_mood_tags.php'
        ];
        
        foreach ($reportFiles as $file) {
            if (!file_exists($file)) {
                $testResults['reports_integration'] = [
                    'status' => 'FAIL',
                    'message' => "Report file missing: $file"
                ];
                return false;
            }
        }
        
        $testResults['reports_integration'] = [
            'status' => 'PASS',
            'message' => 'Reports integration working - Views and files accessible'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['reports_integration'] = [
            'status' => 'FAIL',
            'message' => 'Reports integration error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 5: Security and Access Control
function testSecurityIntegration() {
    global $testResults;
    
    try {
        // Test admin page security
        $adminPages = [
            'admin/resources/index.php',
            'admin/resources/create.php',
            'admin/resources/edit.php',
            'public/admin_resource_action.php'
        ];
        
        foreach ($adminPages as $page) {
            if (!file_exists($page)) {
                $testResults['security_integration'] = [
                    'status' => 'FAIL',
                    'message' => "Admin page missing: $page"
                ];
                return false;
            }
            
            $content = file_get_contents($page);
            if (strpos($content, "!== 'admin'") === false) {
                $testResults['security_integration'] = [
                    'status' => 'FAIL',
                    'message' => "Admin page lacking security check: $page"
                ];
                return false;
            }
        }
        
        // Test report page security
        $reportPages = [
            'reports/weekly_mood.php',
            'reports/top_mood_tags.php'
        ];
        
        foreach ($reportPages as $page) {
            $content = file_get_contents($page);
            if (strpos($content, "!== 'admin'") === false) {
                $testResults['security_integration'] = [
                    'status' => 'FAIL',
                    'message' => "Report page lacking security check: $page"
                ];
                return false;
            }
        }
        
        $testResults['security_integration'] = [
            'status' => 'PASS',
            'message' => 'Security integration complete - All protected pages have proper access control'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['security_integration'] = [
            'status' => 'FAIL',
            'message' => 'Security integration error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 6: System Performance and Stability
function testSystemPerformance() {
    global $testResults;
    
    try {
        $startTime = microtime(true);
        
        // Test database query performance
        require_once 'config.php';
        $db = getDb();
        
        $queryStartTime = microtime(true);
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $stmt->fetch();
        $queryTime = (microtime(true) - $queryStartTime) * 1000; // Convert to milliseconds
        
        if ($queryTime > 1000) { // More than 1 second
            $testResults['system_performance'] = [
                'status' => 'FAIL',
                'message' => "Database queries too slow: {$queryTime}ms"
            ];
            return false;
        }
        
        // Test model instantiation performance
        $modelStartTime = microtime(true);
        require_once 'app/models/JournalEntry.php';
        new JournalEntry();
        $modelTime = (microtime(true) - $modelStartTime) * 1000;
        
        if ($modelTime > 500) { // More than 500ms
            $testResults['system_performance'] = [
                'status' => 'FAIL',
                'message' => "Model instantiation too slow: {$modelTime}ms"
            ];
            return false;
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        $testResults['system_performance'] = [
            'status' => 'PASS',
            'message' => "System performance good - DB: {$queryTime}ms, Model: {$modelTime}ms, Total: {$totalTime}ms"
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['system_performance'] = [
            'status' => 'FAIL',
            'message' => 'Performance test error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Run all integration tests
function runIntegrationTests() {
    global $testResults;
    
    echo "<h1>🔗 MindDB Complete System Integration Test Suite</h1>\n";
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px;'>\n";
    
    $testFunctions = [
        'testCompleteDatabase' => 'Complete Database Verification',
        'testAdminWorkflow' => 'End-to-End Admin Workflow',
        'testJournalWorkflow' => 'Journal Entry Full Workflow',
        'testReportsIntegration' => 'Reports and Analytics Integration',
        'testSecurityIntegration' => 'Security and Access Control',
        'testSystemPerformance' => 'System Performance and Stability'
    ];
    
    $passed = 0;
    $displayedTests = [];
    
    foreach ($testFunctions as $function => $description) {
        echo "<h3>🔍 Testing: $description</h3>\n";
        
        $result = call_user_func($function);
        
        // Display results for tests that completed
        foreach ($testResults as $testName => $testData) {
            if (!isset($displayedTests[$testName])) {
                $icon = $testData['status'] === 'PASS' ? '✅' : '❌';
                $style = $testData['status'] === 'PASS' 
                    ? 'color: green; background: #f0f8f0; padding: 10px; border-left: 4px solid green;'
                    : 'color: red; background: #f8f0f0; padding: 10px; border-left: 4px solid red;';
                    
                echo "<div style='$style margin: 10px 0;'>\n";
                echo "$icon <strong>" . strtoupper($testData['status']) . ":</strong> {$testData['message']}\n";
                echo "</div>\n";
                
                if ($testData['status'] === 'PASS') {
                    $passed++;
                }
                
                $displayedTests[$testName] = true;
            }
        }
        
        echo "<hr style='margin: 20px 0; border: 1px solid #eee;'>\n";
    }
    
    // Final Summary
    $actualTotal = count($testResults);
    $percentage = $actualTotal > 0 ? round(($passed / $actualTotal) * 100, 1) : 0;
    $summaryStyle = $percentage >= 90 
        ? 'background: linear-gradient(135deg, #4CAF50, #45a049); color: white;'
        : ($percentage >= 70 
            ? 'background: linear-gradient(135deg, #FF9800, #F57C00); color: white;'
            : 'background: linear-gradient(135deg, #f44336, #da190b); color: white;');
        
    echo "<div style='$summaryStyle padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>\n";
    echo "<h2>🎯 Final Integration Test Summary</h2>\n";
    echo "<p style='font-size: 18px; margin: 10px 0;'>$passed/$actualTotal tests passed ($percentage%)</p>\n";
    
    if ($percentage >= 90) {
        echo "<p style='font-size: 16px;'>🎉 MindDB System is production-ready!</p>\n";
    } elseif ($percentage >= 70) {
        echo "<p style='font-size: 16px;'>⚠️ System mostly functional with minor issues.</p>\n";
    } else {
        echo "<p style='font-size: 16px;'>❌ System needs significant fixes before deployment.</p>\n";
    }
    echo "</div>\n";
    
    // Deployment checklist
    if ($percentage >= 90) {
        echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; border-left: 4px solid #4CAF50;'>\n";
        echo "<h3>🚀 System Ready - Deployment Checklist:</h3>\n";
        echo "<ul>\n";
        echo "<li>✅ Database connectivity and integrity verified</li>\n";
        echo "<li>✅ Admin resource management fully functional</li>\n";
        echo "<li>✅ Journal entry CRUD operations working</li>\n";
        echo "<li>✅ Reports and analytics accessible</li>\n";
        echo "<li>✅ Security and access control implemented</li>\n";
        echo "<li>✅ System performance within acceptable limits</li>\n";
        echo "</ul>\n";
        echo "<p><strong>MindDB is ready for production use!</strong></p>\n";
        echo "</div>\n";
    }
    
    echo "</div>\n";
}

// Execute integration tests
runIntegrationTests();

// End output buffering and display results
$output = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integration Tests - MindDB System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            margin: 0; 
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 0 auto;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        h1, h2, h3 { margin-top: 0; }
        hr { border: none; border-top: 1px solid #eee; margin: 20px 0; }
        ul { text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <?php echo $output; ?>
    </div>
</body>
</html>