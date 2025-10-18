<?php
/**
 * Admin Resource Action Handler
 * 
 * This handler processes admin resource management actions and routes them
 * to the appropriate AdminResourceController methods.
 * 
 * @author GitHub Copilot
 * @version 1.0.0
 * @date 2025-10-18
 */

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - MindDB Admin</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                max-width: 600px; 
                margin: 100px auto; 
                padding: 20px; 
                text-align: center; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            .error { 
                background: #f8d7da; 
                color: #721c24; 
                padding: 20px; 
                border-radius: 5px; 
                margin: 20px 0;
                border: 1px solid #f5c6cb; 
            }
            .btn { 
                display: inline-block; 
                padding: 12px 24px; 
                background: #007bff; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px; 
                margin: 10px;
                transition: all 0.3s;
            }
            .btn:hover {
                background: #0056b3;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚫 Access Denied</h1>
            <div class="error">
                <strong>Permission Required:</strong><br>
                You must be an administrator to access this resource.
            </div>
            <a href="/MindDB/dashboard.php" class="btn">🏠 Back to Dashboard</a>
            <a href="/MindDB/login.php" class="btn">🔐 Login as Admin</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $_SESSION['flash_message'] = 'Method not allowed. Use POST requests only.';
    $_SESSION['flash_type'] = 'error';
    header('Location: /MindDB/admin/resources/');
    exit;
}

// Get action parameter
$action = $_POST['action'] ?? '';

// Validate action
$allowedActions = ['store', 'update', 'destroy'];
if (!in_array($action, $allowedActions)) {
    $_SESSION['flash_message'] = 'Invalid action specified.';
    $_SESSION['flash_type'] = 'error';
    header('Location: /MindDB/admin/resources/');
    exit;
}

// Initialize controller
require_once __DIR__ . '/../app/controllers/AdminResourceController.php';

try {
    $controller = new AdminResourceController();
    
    switch ($action) {
        case 'store':
            // Create new resource
            $success = $controller->store($_POST);
            
            if (!$success) {
                // Controller handles error redirect
                exit;
            }
            break;
            
        case 'update':
            // Update existing resource
            $resourceId = (int)($_POST['id'] ?? 0);
            
            if (!$resourceId) {
                $_SESSION['flash_message'] = 'Resource ID is required for update.';
                $_SESSION['flash_type'] = 'error';
                header('Location: /MindDB/admin/resources/');
                exit;
            }
            
            $success = $controller->update($resourceId, $_POST);
            
            if (!$success) {
                // Controller handles error redirect
                exit;
            }
            break;
            
        case 'destroy':
            // Delete resource
            $resourceId = (int)($_POST['id'] ?? 0);
            
            if (!$resourceId) {
                $_SESSION['flash_message'] = 'Resource ID is required for deletion.';
                $_SESSION['flash_type'] = 'error';
                header('Location: /MindDB/admin/resources/');
                exit;
            }
            
            $success = $controller->destroy($resourceId);
            
            if (!$success) {
                // Controller handles error redirect
                exit;
            }
            break;
            
        default:
            $_SESSION['flash_message'] = 'Unknown action requested.';
            $_SESSION['flash_type'] = 'error';
            header('Location: /MindDB/admin/resources/');
            exit;
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("AdminResourceAction Error: " . $e->getMessage());
    
    // Set user-friendly error message
    $_SESSION['flash_message'] = 'An unexpected error occurred. Please try again.';
    $_SESSION['flash_type'] = 'error';
    
    // Redirect based on action
    $redirectUrl = '/MindDB/admin/resources/';
    
    if ($action === 'store') {
        $redirectUrl = '/MindDB/admin/resources/create.php';
    } elseif ($action === 'update' && !empty($_POST['id'])) {
        $redirectUrl = '/MindDB/admin/resources/edit.php?id=' . (int)$_POST['id'];
    }
    
    header("Location: $redirectUrl");
    exit;
}

// If we reach here, the action was successful and the controller has already redirected
// This is a fallback just in case
header('Location: /MindDB/admin/resources/');
exit;
?>