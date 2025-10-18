<?php
/**
 * Journal Entry Controller
 * 
 * REST-like controller for journal entry CRUD operations.
 * Handles HTTP requests, authentication, authorization, and JSON responses.
 * 
 * @package MindDB
 * @subpackage Controllers
 * @version 1.0.0
 * @author MindDB Development Team
 */

require_once __DIR__ . '/../models/JournalEntry.php';

class JournalEntryController {
    
    /**
     * JournalEntry model instance
     * @var JournalEntry
     */
    private $model;
    
    /**
     * Current authenticated user ID
     * @var int|null
     */
    private $currentUserId;
    
    /**
     * Constructor - Initialize model and authentication
     */
    public function __construct() {
        $this->model = new JournalEntry();
        $this->currentUserId = $this->getAuthenticatedUserId();
    }
    
    /**
     * Get authenticated user ID from session
     * 
     * @return int|null User ID if authenticated, null otherwise
     */
    private function getAuthenticatedUserId(): ?int {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Use session-based authentication
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }
        
        return null;
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if authenticated, false otherwise
     */
    private function isAuthenticated(): bool {
        return $this->currentUserId !== null;
    }
    
    /**
     * Send JSON response with HTTP status code
     * 
     * @param int $statusCode HTTP status code
     * @param array $data Response data
     * @return void
     */
    private function sendJsonResponse(int $statusCode, array $data): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Create journal entry from POST request
     * 
     * Reads POST data, validates required fields, creates entry.
     * 
     * @return void Sends JSON response
     */
    public function createFromRequest(): void {
        // Check authentication
        if (!$this->isAuthenticated()) {
            $this->sendJsonResponse(401, [
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'You must be logged in to create journal entries'
            ]);
        }
        
        // Validate HTTP method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(405, [
                'success' => false,
                'error' => 'Method Not Allowed',
                'message' => 'Only POST requests are allowed'
            ]);
        }
        
        // Get POST data
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $moodTag = isset($_POST['mood_tag']) ? trim($_POST['mood_tag']) : null;
        $sentimentScore = isset($_POST['sentiment_score']) ? (float)$_POST['sentiment_score'] : null;
        $isPrivate = isset($_POST['is_private']) ? (int)$_POST['is_private'] : 1;
        
        // Validate required fields
        if (empty($title)) {
            $this->sendJsonResponse(422, [
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Title is required',
                'field' => 'title'
            ]);
        }
        
        if (empty($content)) {
            $this->sendJsonResponse(422, [
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Content is required',
                'field' => 'content'
            ]);
        }
        
        // Validate sentiment score range if provided
        if ($sentimentScore !== null && ($sentimentScore < -1.00 || $sentimentScore > 1.00)) {
            $this->sendJsonResponse(422, [
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Sentiment score must be between -1.00 and 1.00',
                'field' => 'sentiment_score'
            ]);
        }
        
        // Prepare data
        $data = [
            'user_id' => $this->currentUserId,
            'title' => $title,
            'content' => $content,
            'mood_tag' => $moodTag,
            'sentiment_score' => $sentimentScore,
            'is_private' => $isPrivate
        ];
        
        try {
            // Create entry
            $newId = $this->model->create($data);
            
            // Fetch created entry
            $entry = $this->model->find($newId);
            
            $this->sendJsonResponse(201, [
                'success' => true,
                'message' => 'Journal entry created successfully',
                'id' => $newId,
                'entry' => $entry
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(500, [
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => 'Failed to create journal entry: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update journal entry from POST/PUT request
     * 
     * Validates ownership before updating.
     * 
     * @return void Sends JSON response
     */
    public function updateFromRequest(): void {
        // Check authentication
        if (!$this->isAuthenticated()) {
            $this->sendJsonResponse(401, [
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'You must be logged in to update journal entries'
            ]);
        }
        
        // Get entry ID
        $id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
        
        if ($id <= 0) {
            $this->sendJsonResponse(422, [
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Valid journal entry ID is required',
                'field' => 'id'
            ]);
        }
        
        // Fetch existing entry
        $entry = $this->model->find($id);
        
        if (!$entry) {
            $this->sendJsonResponse(404, [
                'success' => false,
                'error' => 'Not Found',
                'message' => 'Journal entry not found'
            ]);
        }
        
        // Validate ownership
        if ((int)$entry['user_id'] !== $this->currentUserId) {
            $this->sendJsonResponse(403, [
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You do not have permission to update this journal entry'
            ]);
        }
        
        // Collect update data (only fields that are provided)
        $updateData = [];
        
        if (isset($_POST['title'])) {
            $updateData['title'] = trim($_POST['title']);
            if (empty($updateData['title'])) {
                $this->sendJsonResponse(422, [
                    'success' => false,
                    'error' => 'Validation Error',
                    'message' => 'Title cannot be empty',
                    'field' => 'title'
                ]);
            }
        }
        
        if (isset($_POST['content'])) {
            $updateData['content'] = trim($_POST['content']);
            if (empty($updateData['content'])) {
                $this->sendJsonResponse(422, [
                    'success' => false,
                    'error' => 'Validation Error',
                    'message' => 'Content cannot be empty',
                    'field' => 'content'
                ]);
            }
        }
        
        if (isset($_POST['mood_tag'])) {
            $updateData['mood_tag'] = trim($_POST['mood_tag']);
        }
        
        if (isset($_POST['sentiment_score'])) {
            $sentimentScore = (float)$_POST['sentiment_score'];
            if ($sentimentScore < -1.00 || $sentimentScore > 1.00) {
                $this->sendJsonResponse(422, [
                    'success' => false,
                    'error' => 'Validation Error',
                    'message' => 'Sentiment score must be between -1.00 and 1.00',
                    'field' => 'sentiment_score'
                ]);
            }
            $updateData['sentiment_score'] = $sentimentScore;
        }
        
        if (isset($_POST['is_private'])) {
            $updateData['is_private'] = (int)$_POST['is_private'];
        }
        
        // Check if any fields to update
        if (empty($updateData)) {
            $this->sendJsonResponse(422, [
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'No fields provided for update'
            ]);
        }
        
        try {
            // Update entry
            $updated = $this->model->update($id, $updateData);
            
            if (!$updated) {
                $this->sendJsonResponse(500, [
                    'success' => false,
                    'error' => 'Internal Server Error',
                    'message' => 'Failed to update journal entry'
                ]);
            }
            
            // Fetch updated entry
            $updatedEntry = $this->model->find($id);
            
            $this->sendJsonResponse(200, [
                'success' => true,
                'message' => 'Journal entry updated successfully',
                'id' => $id,
                'entry' => $updatedEntry
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(500, [
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => 'Failed to update journal entry: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete journal entry from POST request
     * 
     * Validates ownership before deleting.
     * 
     * @return void Sends JSON response
     */
    public function deleteFromRequest(): void {
        // Check authentication
        if (!$this->isAuthenticated()) {
            $this->sendJsonResponse(401, [
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'You must be logged in to delete journal entries'
            ]);
        }
        
        // Get entry ID
        $id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
        
        if ($id <= 0) {
            $this->sendJsonResponse(422, [
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Valid journal entry ID is required',
                'field' => 'id'
            ]);
        }
        
        // Fetch existing entry
        $entry = $this->model->find($id);
        
        if (!$entry) {
            $this->sendJsonResponse(404, [
                'success' => false,
                'error' => 'Not Found',
                'message' => 'Journal entry not found'
            ]);
        }
        
        // Validate ownership
        if ((int)$entry['user_id'] !== $this->currentUserId) {
            $this->sendJsonResponse(403, [
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You do not have permission to delete this journal entry'
            ]);
        }
        
        try {
            // Delete entry
            $deleted = $this->model->delete($id);
            
            if (!$deleted) {
                $this->sendJsonResponse(500, [
                    'success' => false,
                    'error' => 'Internal Server Error',
                    'message' => 'Failed to delete journal entry'
                ]);
            }
            
            $this->sendJsonResponse(200, [
                'success' => true,
                'message' => 'Journal entry deleted successfully',
                'id' => $id
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(500, [
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => 'Failed to delete journal entry: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * List all journal entries for current user
     * 
     * Returns simplified list with id, title, created_at, mood_tag.
     * 
     * @return void Sends JSON response
     */
    public function listByUser(): void {
        // Check authentication
        if (!$this->isAuthenticated()) {
            $this->sendJsonResponse(401, [
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'You must be logged in to view journal entries'
            ]);
        }
        
        // Get optional limit parameter
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $limit = max(1, min($limit, 1000)); // Between 1 and 1000
        
        try {
            // Fetch user's entries
            $entries = $this->model->allByUser($this->currentUserId, $limit);
            
            // Simplify response (only include necessary fields)
            $simplifiedEntries = array_map(function($entry) {
                return [
                    'id' => (int)$entry['id'],
                    'title' => $entry['title'],
                    'mood_tag' => $entry['mood_tag'],
                    'created_at' => $entry['created_at'],
                    'is_private' => (int)$entry['is_private']
                ];
            }, $entries);
            
            $this->sendJsonResponse(200, [
                'success' => true,
                'count' => count($simplifiedEntries),
                'entries' => $simplifiedEntries
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(500, [
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => 'Failed to fetch journal entries: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show single journal entry
     * 
     * Returns full entry if user is owner OR entry is public (is_private=0).
     * 
     * @return void Sends JSON response
     */
    public function show(): void {
        // Get entry ID
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            $this->sendJsonResponse(422, [
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Valid journal entry ID is required',
                'field' => 'id'
            ]);
        }
        
        try {
            // Fetch entry
            $entry = $this->model->find($id);
            
            if (!$entry) {
                $this->sendJsonResponse(404, [
                    'success' => false,
                    'error' => 'Not Found',
                    'message' => 'Journal entry not found'
                ]);
            }
            
            // Check access permissions
            $isOwner = $this->isAuthenticated() && (int)$entry['user_id'] === $this->currentUserId;
            $isPublic = (int)$entry['is_private'] === 0;
            
            if (!$isOwner && !$isPublic) {
                $this->sendJsonResponse(403, [
                    'success' => false,
                    'error' => 'Forbidden',
                    'message' => 'You do not have permission to view this private journal entry'
                ]);
            }
            
            // Return entry
            $this->sendJsonResponse(200, [
                'success' => true,
                'entry' => $entry,
                'is_owner' => $isOwner
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(500, [
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => 'Failed to fetch journal entry: ' . $e->getMessage()
            ]);
        }
    }
}
