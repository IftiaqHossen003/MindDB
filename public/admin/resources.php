<?php
/**
 * Admin Resources Router
 * 
 * Public router for admin resource management.
 * Handles admin authentication and includes the admin resource views.
 * 
 * @package    MindDB
 * @subpackage Public Router
 * @version    1.0.0
 * @author     GitHub Copilot
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Access Required - MindDB</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                max-width: 600px; 
                margin: 100px auto; 
                padding: 20px; 
                text-align: center; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                color: white;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                color: #333;
            }
            .access-denied { 
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
            .btn-warning {
                background: #ffc107;
                color: #212529;
            }
            .btn-warning:hover {
                background: #e0a800;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚫 Admin Access Required</h1>
            <div class="access-denied">
                <strong>Permission Denied:</strong><br>
                You must be an administrator to access the resource management system.
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <p>You are logged in as: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></strong></p>
                <p>Current role: <strong><?= htmlspecialchars($_SESSION['role'] ?? 'None') ?></strong></p>
                <a href="/MindDB/public/journal.php" class="btn">📝 My Journal</a>
            <?php else: ?>
                <a href="/MindDB/setup_session.php" class="btn">🔑 Login</a>
            <?php endif; ?>
            <a href="/MindDB/" class="btn btn-warning">🏠 Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Set page title and include admin resource views
$pageTitle = 'Admin - Resource Management';

// Define constant to indicate this is being included via router
define('ROUTER_INCLUDED', true);

// Get action and ID parameters for routing
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Route to appropriate admin view based on action
switch ($action) {
    case 'create':
        // Show create resource form
        $pageTitle = 'Admin - Create Resource';
        include __DIR__ . '/../../admin/resources/create.php';
        break;
        
    case 'edit':
        // Show edit resource form
        if (!$id) {
            header('Location: /MindDB/public/admin/resources.php');
            exit;
        }
        $pageTitle = 'Admin - Edit Resource';
        include __DIR__ . '/../../admin/resources/edit.php';
        break;
        
    case 'show':
        // Show specific resource details (if you have a show view)
        if (!$id) {
            header('Location: /MindDB/public/admin/resources.php');
            exit;
        }
        $pageTitle = 'Admin - View Resource';
        // Include show view if it exists, otherwise redirect to index
        if (file_exists(__DIR__ . '/../../admin/resources/show.php')) {
            include __DIR__ . '/../../admin/resources/show.php';
        } else {
            header('Location: /MindDB/public/admin/resources.php');
            exit;
        }
        break;
        
    case 'index':
    default:
        // Show admin resources list
        $pageTitle = 'Admin - Resource Management';
        include __DIR__ . '/../../admin/resources/index.php';
        break;
}
?>