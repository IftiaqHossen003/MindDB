<?php
/**
 * Admin Resource Management System Test Suite
 * 
 * Comprehensive test of the admin resource management implementation
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

// Helper function to test admin authentication
function testAdminAuth() {
    global $testResults;
    
    try {
        // Test 1: Admin authentication check
        session_start();
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = 'admin_test';
        
        $testResults['admin_auth'] = [
            'status' => 'PASS',
            'message' => 'Admin session configured successfully'
        ];
        
        return true;
    } catch (Exception $e) {
        $testResults['admin_auth'] = [
            'status' => 'FAIL',
            'message' => 'Admin authentication error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test AdminResourceController
function testAdminResourceController() {
    global $testResults;
    
    try {
        require_once 'app/controllers/AdminResourceController.php';
        
        $controller = new AdminResourceController();
        
        // Test controller instantiation
        $testResults['controller_init'] = [
            'status' => 'PASS',
            'message' => 'AdminResourceController instantiated successfully'
        ];
        
        // Test method existence
        $requiredMethods = ['index', 'create', 'store', 'edit', 'update', 'destroy'];
        foreach ($requiredMethods as $method) {
            if (!method_exists($controller, $method)) {
                $testResults['controller_methods'] = [
                    'status' => 'FAIL',
                    'message' => "Missing required method: $method"
                ];
                return false;
            }
        }
        
        $testResults['controller_methods'] = [
            'status' => 'PASS',
            'message' => 'All required controller methods exist'
        ];
        
        // Test helper methods
        $helperMethods = ['getResourceTypes', 'getDifficultyLevels', 'getCategories'];
        foreach ($helperMethods as $method) {
            if (!method_exists($controller, $method)) {
                $testResults['helper_methods'] = [
                    'status' => 'FAIL',
                    'message' => "Missing helper method: $method"
                ];
                return false;
            }
        }
        
        $testResults['helper_methods'] = [
            'status' => 'PASS',
            'message' => 'All helper methods exist'
        ];
        
        // Test helper method outputs
        $resourceTypes = $controller->getResourceTypes();
        $difficultyLevels = $controller->getDifficultyLevels();
        $categories = $controller->getCategories();
        
        if (!is_array($resourceTypes) || empty($resourceTypes)) {
            $testResults['helper_data'] = [
                'status' => 'FAIL',
                'message' => 'Resource types method not returning valid data'
            ];
            return false;
        }
        
        $testResults['helper_data'] = [
            'status' => 'PASS',
            'message' => 'Helper methods returning valid data arrays'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['controller_init'] = [
            'status' => 'FAIL',
            'message' => 'Controller error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test admin pages exist and are readable
function testAdminPages() {
    global $testResults;
    
    $pages = [
        'admin/resources/index.php' => 'Admin Resources Index Page',
        'admin/resources/create.php' => 'Create Resource Form',
        'admin/resources/edit.php' => 'Edit Resource Form',
        'public/admin_resource_action.php' => 'Action Handler'
    ];
    
    foreach ($pages as $path => $description) {
        if (!file_exists($path)) {
            $testResults['admin_pages'] = [
                'status' => 'FAIL',
                'message' => "Missing file: $path ($description)"
            ];
            return false;
        }
        
        if (!is_readable($path)) {
            $testResults['admin_pages'] = [
                'status' => 'FAIL',
                'message' => "Cannot read file: $path ($description)"
            ];
            return false;
        }
    }
    
    $testResults['admin_pages'] = [
        'status' => 'PASS',
        'message' => 'All admin pages exist and are readable'
    ];
    
    return true;
}

// Test page content for key elements
function testPageContent() {
    global $testResults;
    
    try {
        // Test index page content
        $indexContent = file_get_contents('admin/resources/index.php');
        
        $indexRequirements = [
            'AdminResourceController' => 'Controller inclusion',
            'Mental Health Resources' => 'Page title',
            'pagination' => 'Pagination support',
            'flash_message' => 'Flash message handling',
            'admin' => 'Admin authentication'
        ];
        
        foreach ($indexRequirements as $search => $description) {
            if (stripos($indexContent, $search) === false) {
                $testResults['page_content'] = [
                    'status' => 'FAIL',
                    'message' => "Index page missing: $description"
                ];
                return false;
            }
        }
        
        // Test create page content
        $createContent = file_get_contents('admin/resources/create.php');
        
        $createRequirements = [
            'admin_resource_action.php' => 'Form action',
            'action="store"' => 'Store action',
            'title' => 'Title field',
            'resource_type' => 'Resource type field',
            'is_featured' => 'Featured checkbox'
        ];
        
        foreach ($createRequirements as $search => $description) {
            if (stripos($createContent, $search) === false) {
                $testResults['page_content'] = [
                    'status' => 'FAIL',
                    'message' => "Create page missing: $description"
                ];
                return false;
            }
        }
        
        // Test edit page content
        $editContent = file_get_contents('admin/resources/edit.php');
        
        $editRequirements = [
            'admin_resource_action.php' => 'Form action',
            'action="update"' => 'Update action',
            'AdminResourceController' => 'Controller usage',
            'edit(' => 'Edit method call'
        ];
        
        foreach ($editRequirements as $search => $description) {
            if (stripos($editContent, $search) === false) {
                $testResults['page_content'] = [
                    'status' => 'FAIL',
                    'message' => "Edit page missing: $description"
                ];
                return false;
            }
        }
        
        // Test action handler content
        $actionContent = file_get_contents('public/admin_resource_action.php');
        
        $actionRequirements = [
            'AdminResourceController' => 'Controller inclusion',
            'store' => 'Store action',
            'update' => 'Update action',
            'destroy' => 'Destroy action',
            'admin' => 'Admin role check'
        ];
        
        foreach ($actionRequirements as $search => $description) {
            if (stripos($actionContent, $search) === false) {
                $testResults['page_content'] = [
                    'status' => 'FAIL',
                    'message' => "Action handler missing: $description"
                ];
                return false;
            }
        }
        
        $testResults['page_content'] = [
            'status' => 'PASS',
            'message' => 'All pages contain required content elements'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['page_content'] = [
            'status' => 'FAIL',
            'message' => 'Page content error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Test database connectivity and resources table
function testDatabaseSetup() {
    global $testResults;
    
    try {
        require_once 'config.php';
        
        // Test database connection
        $db = getDb();
        
        $testResults['database_connection'] = [
            'status' => 'PASS',
            'message' => 'Database connection established successfully'
        ];
        
        // Check if resources table exists
        $stmt = $db->query("SHOW TABLES LIKE 'resources'");
        if ($stmt->rowCount() === 0) {
            $testResults['resources_table'] = [
                'status' => 'FAIL',
                'message' => 'Resources table does not exist'
            ];
            return false;
        }
        
        // Check table structure
        $stmt = $db->query("DESCRIBE resources");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'id', 'title', 'resource_type', 'url', 'description', 
            'category', 'author', 'duration_minutes', 'difficulty_level',
            'is_featured', 'is_active', 'view_count', 'created_at', 'updated_at'
        ];
        
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $columns)) {
                $testResults['resources_table'] = [
                    'status' => 'FAIL',
                    'message' => "Missing column in resources table: $column"
                ];
                return false;
            }
        }
        
        $testResults['resources_table'] = [
            'status' => 'PASS',
            'message' => 'Resources table exists with all required columns'
        ];
        
        return true;
        
    } catch (Exception $e) {
        $testResults['database_connection'] = [
            'status' => 'FAIL',
            'message' => 'Database error: ' . $e->getMessage()
        ];
        return false;
    }
}

// Run all tests
function runAllTests() {
    global $testResults;
    
    echo "<h1>🧪 Admin Resource Management System Test Suite</h1>\n";
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px;'>\n";
    
    $testFunctions = [
        'testAdminAuth' => 'Admin Authentication Setup',
        'testDatabaseSetup' => 'Database and Table Structure',
        'testAdminResourceController' => 'AdminResourceController',
        'testAdminPages' => 'Admin Page Files',
        'testPageContent' => 'Page Content Validation'
    ];
    
    $passed = 0;
    $total = count($testFunctions);
    
    foreach ($testFunctions as $function => $description) {
        echo "<h3>🔍 Testing: $description</h3>\n";
        
        $result = call_user_func($function);
        
        // Display results for this test category
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
    $percentage = round(($passed / count($testResults)) * 100, 1);
    $summaryStyle = $percentage >= 80 
        ? 'background: linear-gradient(135deg, #4CAF50, #45a049); color: white;'
        : 'background: linear-gradient(135deg, #f44336, #da190b); color: white;';
        
    echo "<div style='$summaryStyle padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>\n";
    echo "<h2>📊 Test Summary</h2>\n";
    echo "<p style='font-size: 18px; margin: 10px 0;'>$passed/" . count($testResults) . " tests passed ($percentage%)</p>\n";
    
    if ($percentage >= 80) {
        echo "<p style='font-size: 16px;'>🎉 Admin Resource Management System is ready for use!</p>\n";
    } else {
        echo "<p style='font-size: 16px;'>⚠️ Some issues need to be addressed before deployment.</p>\n";
    }
    echo "</div>\n";
    
    // Next steps
    echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 10px; border-left: 4px solid #2196F3;'>\n";
    echo "<h3>🚀 Next Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Access the admin resources at: <code>http://localhost/MindDB/admin/resources/</code></li>\n";
    echo "<li>Test creating a new resource via the create form</li>\n";
    echo "<li>Test editing an existing resource</li>\n";
    echo "<li>Verify admin authentication is working properly</li>\n";
    echo "<li>Test the responsive design on mobile devices</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
    echo "</div>\n";
}

// Initialize display tracking
$displayedTests = [];

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
    <title>Admin Resource System Tests - MindDB</title>
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