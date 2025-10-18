<?php
/**
 * Journal Entry Router
 * 
 * Public router for journal entry management.
 * Handles user authentication and includes the journal views.
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

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Authentication Required - MindDB</title>
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
            .auth-required { 
                background: #fff3cd; 
                color: #856404; 
                padding: 20px; 
                border-radius: 5px; 
                margin: 20px 0;
                border: 1px solid #ffeaa7; 
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
            <h1>🔐 Authentication Required</h1>
            <div class="auth-required">
                <strong>Access Denied:</strong><br>
                You must be logged in to access your journal entries.
            </div>
            <a href="/MindDB/setup_session.php" class="btn">🔑 Login</a>
            <a href="/MindDB/" class="btn">🏠 Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Set page title and include journal views
$pageTitle = 'My Journal Entries';

// Define constant to indicate this is being included via router
define('ROUTER_INCLUDED', true);

// Get action parameter for routing
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Route to appropriate view based on action
switch ($action) {
    case 'create':
        // Show create form
        $pageTitle = 'Create Journal Entry';
        include __DIR__ . '/../views/journal/create.php';
        break;
        
    case 'show':
        // Show specific entry
        if (!$id) {
            header('Location: /MindDB/public/journal.php');
            exit;
        }
        $pageTitle = 'View Journal Entry';
        include __DIR__ . '/../views/journal/show.php';
        break;
        
    case 'edit':
        // Show edit form
        if (!$id) {
            header('Location: /MindDB/public/journal.php');
            exit;
        }
        $pageTitle = 'Edit Journal Entry';
        include __DIR__ . '/../views/journal/edit.php';
        break;
        
    case 'index':
    default:
        // Show journal list
        $pageTitle = 'My Journal Entries';
        include __DIR__ . '/../views/journal/index.php';
        break;
}
?>