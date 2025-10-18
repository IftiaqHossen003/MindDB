<?php
/**
 * Journal Entry Creation Router
 * 
 * Public router specifically for creating new journal entries.
 * Handles user authentication and includes the journal creation view.
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
                You must be logged in to create journal entries.
            </div>
            <a href="/MindDB/setup_session.php" class="btn">🔑 Login</a>
            <a href="/MindDB/" class="btn">🏠 Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Set page title and context
$pageTitle = 'Create New Journal Entry';
$formAction = '/MindDB/journal_create.php'; // POST endpoint for form submission

// Define constant to indicate this is being included via router
define('ROUTER_INCLUDED', true);

// Handle POST request for journal creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include the journal creation handler
    include __DIR__ . '/../journal_create.php';
    exit;
}

// For GET request, show the creation form
include __DIR__ . '/../views/journal/create.php';
?>