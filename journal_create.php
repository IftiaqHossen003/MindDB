<?php
/**
 * Journal Entry Creation API Endpoint
 * 
 * POST endpoint for creating new journal entries.
 * Requires authentication via session.
 * 
 * @package MindDB
 * @version 1.0.0
 */

// Include configuration and dependencies
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/controllers/JournalEntryController.php';

// Initialize controller and handle request
try {
    $controller = new JournalEntryController();
    $controller->createFromRequest();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
