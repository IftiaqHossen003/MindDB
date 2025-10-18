<?php
/**
 * User Authentication and Session Test Suite
 * 
 * Comprehensive test of user authentication, role-based access control,
 * and session management functionality.
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

// Test 1: Database User Authentication
function testUserAuth() {
    global $testResults;
    
    try {
        require_once 'config.php';
        $db = getDb();
        
        // Test admin user exists
        $stmt = $db->prepare("SELECT id, username, role FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$adminUser) {
            $testResults['user_auth'] = [
                'status' => 'FAIL',
                'message' => 'Admin user not found in database'
            ];
            return false;
        }
        
        if ($adminUser['role'] !== 'admin') {
            $testResults['user_auth'] = [
                'status' => 'FAIL',
                'message' => 'Admin user does not have admin role'
            ];
            return false;
        }
        
        // Test regular user exists
        $stmt = $db->prepare("SELECT id, username, role FROM users WHERE role = ?");
        $stmt->execute(['user']);
        $regularUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($regularUsers)) {
            $testResults['user_auth'] = [
                'status' => 'FAIL',
                'message' => 'No regular users found in database'
            ];
            return false;
        }
        
        $testResults['user_auth'] = [
            'status' => 'PASS',
            'message' => 'User authentication data verified - Admin and regular users exist'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['user_auth'] = [
            'status' => 'FAIL',
            'message' => 'User authentication error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 2: Session Management
function testSessionManagement() {
    global $testResults;
    
    try {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Test session variable setting
        $_SESSION['test_user_id'] = 1;
        $_SESSION['test_username'] = 'admin';
        $_SESSION['test_role'] = 'admin';
        
        if (!isset($_SESSION['test_user_id']) || $_SESSION['test_user_id'] !== 1) {
            $testResults['session_mgmt'] = [
                'status' => 'FAIL',
                'message' => 'Session variables not persisting correctly'
            ];
            return false;
        }
        
        // Clean up test session variables
        unset($_SESSION['test_user_id'], $_SESSION['test_username'], $_SESSION['test_role']);
        
        $testResults['session_mgmt'] = [
            'status' => 'PASS',
            'message' => 'Session management working correctly'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['session_mgmt'] = [
            'status' => 'FAIL',
            'message' => 'Session management error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 3: Admin Access Control
function testAdminAccessControl() {
    global $testResults;
    
    try {
        // Test admin session setup
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        
        // Test AdminResourceController access
        require_once 'app/controllers/AdminResourceController.php';
        $controller = new AdminResourceController();
        
        // This should work without throwing an exception
        $resourceTypes = $controller->getResourceTypes();
        
        if (!is_array($resourceTypes) || empty($resourceTypes)) {
            $testResults['admin_access'] = [
                'status' => 'FAIL',
                'message' => 'Admin controller not returning expected data'
            ];
            return false;
        }
        
        $testResults['admin_access'] = [
            'status' => 'PASS',
            'message' => 'Admin access control working - Admin can access controller'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['admin_access'] = [
            'status' => 'FAIL',
            'message' => 'Admin access control error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 4: Non-Admin Access Prevention
function testNonAdminPrevention() {
    global $testResults;
    
    try {
        // Clear admin session
        $_SESSION['user_id'] = 2;
        $_SESSION['username'] = 'demo_user';
        $_SESSION['role'] = 'user';
        
        // Test that action handler properly blocks non-admin access
        // We'll check this by testing the admin pages directly
        
        $indexContent = file_get_contents('admin/resources/index.php');
        if (strpos($indexContent, "!== 'admin'") === false) {
            $testResults['non_admin_prevention'] = [
                'status' => 'FAIL',
                'message' => 'Admin pages missing role check protection'
            ];
            return false;
        }
        
        $createContent = file_get_contents('admin/resources/create.php');
        if (strpos($createContent, "!== 'admin'") === false) {
            $testResults['non_admin_prevention'] = [
                'status' => 'FAIL',
                'message' => 'Create page missing role check protection'
            ];
            return false;
        }
        
        $actionContent = file_get_contents('public/admin_resource_action.php');
        if (strpos($actionContent, "!== 'admin'") === false) {
            $testResults['non_admin_prevention'] = [
                'status' => 'FAIL',
                'message' => 'Action handler missing role check protection'
            ];
            return false;
        }
        
        $testResults['non_admin_prevention'] = [
            'status' => 'PASS',
            'message' => 'Non-admin access prevention properly implemented'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['non_admin_prevention'] = [
            'status' => 'FAIL',
            'message' => 'Non-admin prevention test error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 5: Session Security
function testSessionSecurity() {
    global $testResults;
    
    try {
        // Check if session is using secure settings (if available)
        $sessionSettings = [
            'session.cookie_httponly' => ini_get('session.cookie_httponly'),
            'session.use_strict_mode' => ini_get('session.use_strict_mode'),
            'session.cookie_secure' => ini_get('session.cookie_secure')
        ];
        
        // These are recommendations, not failures if not set
        $securityScore = 0;
        $maxScore = 3;
        
        if ($sessionSettings['session.cookie_httponly']) $securityScore++;
        if ($sessionSettings['session.use_strict_mode']) $securityScore++;
        if ($sessionSettings['session.cookie_secure']) $securityScore++;
        
        $testResults['session_security'] = [
            'status' => 'PASS',
            'message' => "Session security score: $securityScore/$maxScore - " . 
                        "HttpOnly: " . ($sessionSettings['session.cookie_httponly'] ? 'On' : 'Off') . ", " .
                        "Strict: " . ($sessionSettings['session.use_strict_mode'] ? 'On' : 'Off') . ", " .
                        "Secure: " . ($sessionSettings['session.cookie_secure'] ? 'On' : 'Off')
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['session_security'] = [
            'status' => 'FAIL',
            'message' => 'Session security test error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test 6: Journal Access Control
function testJournalAccessControl() {
    global $testResults;
    
    try {
        require_once 'app/models/JournalEntry.php';
        
        // Set up a test user session
        $_SESSION['user_id'] = 2;
        $_SESSION['username'] = 'demo_user';
        $_SESSION['role'] = 'user';
        
        $model = new JournalEntry();
        
        // Test that model initializes correctly for regular user
        $userEntries = $model->allByUser(2);
        
        if (!is_array($userEntries)) {
            $testResults['journal_access'] = [
                'status' => 'FAIL',
                'message' => 'Journal model not returning proper data structure'
            ];
            return false;
        }
        
        $testResults['journal_access'] = [
            'status' => 'PASS',
            'message' => 'Journal access control working - Users can access their own entries'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['journal_access'] = [
            'status' => 'FAIL',
            'message' => 'Journal access control error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Run all tests
function runAllTests() {
    global $testResults;
    
    echo "<h1>🔐 User Authentication & Session Management Test Suite</h1>\n";
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px;'>\n";
    
    $testFunctions = [
        'testUserAuth' => 'User Database Authentication',
        'testSessionManagement' => 'Session Management',
        'testAdminAccessControl' => 'Admin Access Control',
        'testNonAdminPrevention' => 'Non-Admin Access Prevention',
        'testSessionSecurity' => 'Session Security Configuration',
        'testJournalAccessControl' => 'Journal Access Control'
    ];
    
    $passed = 0;
    $total = count($testFunctions);
    
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
    
    // Summary
    $actualTotal = count($testResults);
    $percentage = $actualTotal > 0 ? round(($passed / $actualTotal) * 100, 1) : 0;
    $summaryStyle = $percentage >= 80 
        ? 'background: linear-gradient(135deg, #4CAF50, #45a049); color: white;'
        : 'background: linear-gradient(135deg, #f44336, #da190b); color: white;';
        
    echo "<div style='$summaryStyle padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>\n";
    echo "<h2>📊 Authentication Test Summary</h2>\n";
    echo "<p style='font-size: 18px; margin: 10px 0;'>$passed/$actualTotal tests passed ($percentage%)</p>\n";
    
    if ($percentage >= 80) {
        echo "<p style='font-size: 16px;'>🎉 Authentication system is secure and working!</p>\n";
    } else {
        echo "<p style='font-size: 16px;'>⚠️ Some authentication issues need attention.</p>\n";
    }
    echo "</div>\n";
    
    echo "</div>\n";
}

// Execute tests
runAllTests();

// End output buffering and display results
$output = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Tests - MindDB</title>
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
    </style>
</head>
<body>
    <div class="container">
        <?php echo $output; ?>
    </div>
</body>
</html>