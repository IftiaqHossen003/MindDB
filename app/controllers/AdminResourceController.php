<?php
/**
 * AdminResourceController - Manages mental health resources for administrators
 * 
 * This controller provides CRUD operations for mental health resources
 * with proper admin authentication and authorization checks.
 * 
 * @author GitHub Copilot
 * @version 1.0.0
 * @date 2025-10-18
 */

class AdminResourceController
{
    private $db;
    
    /**
     * Constructor - Initialize database connection and verify admin access
     */
    public function __construct()
    {
        // Check admin authentication
        if (!$this->isAdmin()) {
            http_response_code(403);
            $this->showError('Access Denied', 'You must be an administrator to access this resource.');
            exit;
        }
        
        // Initialize database connection
        require_once __DIR__ . '/../../config.php';
        $this->db = getDb();
    }
    
    /**
     * Check if current user is an admin
     * 
     * @return bool True if user is admin, false otherwise
     */
    private function isAdmin(): bool
    {
        // Start session only if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['role']) && 
               $_SESSION['role'] === 'admin';
    }
    
    /**
     * Display error page
     * 
     * @param string $title Error title
     * @param string $message Error message
     */
    private function showError(string $title, string $message): void
    {
        echo "<!DOCTYPE html>";
        echo "<html><head><title>{$title} - MindDB Admin</title>";
        echo "<style>body{font-family:Arial,sans-serif;max-width:600px;margin:100px auto;padding:20px;text-align:center;}";
        echo ".error{background:#f8d7da;color:#721c24;padding:20px;border-radius:5px;margin:20px 0;}";
        echo ".btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:10px;}";
        echo "</style></head><body>";
        echo "<h1>{$title}</h1>";
        echo "<div class='error'>{$message}</div>";
        echo "<a href='/MindDB/dashboard.php' class='btn'>← Back to Dashboard</a>";
        echo "</body></html>";
    }
    
    /**
     * Redirect with message
     * 
     * @param string $url Redirect URL
     * @param string $message Flash message
     * @param string $type Message type (success, error, info)
     */
    private function redirect(string $url, string $message = '', string $type = 'success'): void
    {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        header("Location: $url");
        exit;
    }
    
    /**
     * Sanitize input string
     * 
     * @param mixed $input Input to sanitize
     * @return string Sanitized string
     */
    private function sanitize($input): string
    {
        return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * List all resources with pagination
     * 
     * @param int $page Current page number
     * @param int $limit Records per page
     * @return array Resources data
     */
    public function index(int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM resources");
            $stmt->execute();
            $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get resources with pagination
            $stmt = $this->db->prepare("
                SELECT 
                    id, title, resource_type, url, description, category, author,
                    duration_minutes, difficulty_level, is_featured, is_active,
                    view_count, created_at, updated_at
                FROM resources 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'resources' => $resources,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalCount / $limit),
                    'total_count' => $totalCount,
                    'per_page' => $limit,
                    'has_prev' => $page > 1,
                    'has_next' => $page < ceil($totalCount / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("AdminResourceController::index() - " . $e->getMessage());
            return ['resources' => [], 'pagination' => []];
        }
    }
    
    /**
     * Show create form
     */
    public function create(): void
    {
        // This method is called by create.php to get form data if needed
        // For now, it just ensures admin access (constructor handles this)
    }
    
    /**
     * Store a new resource
     * 
     * @param array $data Resource data from form
     * @return bool Success status
     */
    public function store(array $data): bool
    {
        try {
            // Validate required fields
            $required = ['title', 'resource_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->redirect('/MindDB/admin/resources/create.php', 
                        "Error: {$field} is required", 'error');
                    return false;
                }
            }
            
            // Prepare data
            $insertData = [
                'title' => $this->sanitize($data['title']),
                'resource_type' => $this->sanitize($data['resource_type']),
                'url' => !empty($data['url']) ? $this->sanitize($data['url']) : null,
                'description' => !empty($data['description']) ? $this->sanitize($data['description']) : null,
                'category' => !empty($data['category']) ? $this->sanitize($data['category']) : null,
                'author' => !empty($data['author']) ? $this->sanitize($data['author']) : null,
                'duration_minutes' => !empty($data['duration_minutes']) ? (int)$data['duration_minutes'] : null,
                'difficulty_level' => !empty($data['difficulty_level']) ? $this->sanitize($data['difficulty_level']) : null,
                'is_featured' => isset($data['is_featured']) ? 1 : 0,
                'is_active' => isset($data['is_active']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert resource
            $stmt = $this->db->prepare("
                INSERT INTO resources (
                    title, resource_type, url, description, category, author,
                    duration_minutes, difficulty_level, is_featured, is_active,
                    created_at, updated_at
                ) VALUES (
                    :title, :resource_type, :url, :description, :category, :author,
                    :duration_minutes, :difficulty_level, :is_featured, :is_active,
                    :created_at, :updated_at
                )
            ");
            
            $success = $stmt->execute($insertData);
            
            if ($success) {
                $this->redirect('/MindDB/admin/resources/', 
                    'Resource created successfully!', 'success');
                return true;
            } else {
                $this->redirect('/MindDB/admin/resources/create.php', 
                    'Error creating resource. Please try again.', 'error');
                return false;
            }
            
        } catch (Exception $e) {
            error_log("AdminResourceController::store() - " . $e->getMessage());
            $this->redirect('/MindDB/admin/resources/create.php', 
                'Database error occurred. Please try again.', 'error');
            return false;
        }
    }
    
    /**
     * Show edit form for specific resource
     * 
     * @param int $id Resource ID
     * @return array|null Resource data or null if not found
     */
    public function edit(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id, title, resource_type, url, description, category, author,
                    duration_minutes, difficulty_level, is_featured, is_active,
                    view_count, created_at, updated_at
                FROM resources 
                WHERE id = :id
            ");
            
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resource) {
                $this->redirect('/MindDB/admin/resources/', 
                    'Resource not found.', 'error');
                return null;
            }
            
            return $resource;
            
        } catch (Exception $e) {
            error_log("AdminResourceController::edit() - " . $e->getMessage());
            $this->redirect('/MindDB/admin/resources/', 
                'Error loading resource.', 'error');
            return null;
        }
    }
    
    /**
     * Update an existing resource
     * 
     * @param int $id Resource ID
     * @param array $data Updated resource data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool
    {
        try {
            // Validate required fields
            $required = ['title', 'resource_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->redirect("/MindDB/admin/resources/edit.php?id={$id}", 
                        "Error: {$field} is required", 'error');
                    return false;
                }
            }
            
            // Check if resource exists
            $stmt = $this->db->prepare("SELECT id FROM resources WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                $this->redirect('/MindDB/admin/resources/', 
                    'Resource not found.', 'error');
                return false;
            }
            
            // Prepare update data
            $updateData = [
                'id' => $id,
                'title' => $this->sanitize($data['title']),
                'resource_type' => $this->sanitize($data['resource_type']),
                'url' => !empty($data['url']) ? $this->sanitize($data['url']) : null,
                'description' => !empty($data['description']) ? $this->sanitize($data['description']) : null,
                'category' => !empty($data['category']) ? $this->sanitize($data['category']) : null,
                'author' => !empty($data['author']) ? $this->sanitize($data['author']) : null,
                'duration_minutes' => !empty($data['duration_minutes']) ? (int)$data['duration_minutes'] : null,
                'difficulty_level' => !empty($data['difficulty_level']) ? $this->sanitize($data['difficulty_level']) : null,
                'is_featured' => isset($data['is_featured']) ? 1 : 0,
                'is_active' => isset($data['is_active']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update resource
            $stmt = $this->db->prepare("
                UPDATE resources SET 
                    title = :title,
                    resource_type = :resource_type,
                    url = :url,
                    description = :description,
                    category = :category,
                    author = :author,
                    duration_minutes = :duration_minutes,
                    difficulty_level = :difficulty_level,
                    is_featured = :is_featured,
                    is_active = :is_active,
                    updated_at = :updated_at
                WHERE id = :id
            ");
            
            $success = $stmt->execute($updateData);
            
            if ($success) {
                $this->redirect('/MindDB/admin/resources/', 
                    'Resource updated successfully!', 'success');
                return true;
            } else {
                $this->redirect("/MindDB/admin/resources/edit.php?id={$id}", 
                    'Error updating resource. Please try again.', 'error');
                return false;
            }
            
        } catch (Exception $e) {
            error_log("AdminResourceController::update() - " . $e->getMessage());
            $this->redirect("/MindDB/admin/resources/edit.php?id={$id}", 
                'Database error occurred. Please try again.', 'error');
            return false;
        }
    }
    
    /**
     * Delete a resource
     * 
     * @param int $id Resource ID
     * @return bool Success status
     */
    public function destroy(int $id): bool
    {
        try {
            // Check if resource exists
            $stmt = $this->db->prepare("SELECT title FROM resources WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$resource) {
                $this->redirect('/MindDB/admin/resources/', 
                    'Resource not found.', 'error');
                return false;
            }
            
            // Delete resource
            $stmt = $this->db->prepare("DELETE FROM resources WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $success = $stmt->execute();
            
            if ($success) {
                $this->redirect('/MindDB/admin/resources/', 
                    "Resource '{$resource['title']}' deleted successfully!", 'success');
                return true;
            } else {
                $this->redirect('/MindDB/admin/resources/', 
                    'Error deleting resource. Please try again.', 'error');
                return false;
            }
            
        } catch (Exception $e) {
            error_log("AdminResourceController::destroy() - " . $e->getMessage());
            $this->redirect('/MindDB/admin/resources/', 
                'Database error occurred. Please try again.', 'error');
            return false;
        }
    }
    
    /**
     * Get resource types for form dropdowns
     * 
     * @return array Available resource types
     */
    public function getResourceTypes(): array
    {
        return [
            'article' => 'Article',
            'video' => 'Video',
            'audio' => 'Audio/Podcast',
            'tool' => 'Interactive Tool',
            'exercise' => 'Exercise/Worksheet',
            'book' => 'Book/E-book',
            'app' => 'Mobile App',
            'other' => 'Other'
        ];
    }
    
    /**
     * Get difficulty levels for form dropdowns
     * 
     * @return array Available difficulty levels
     */
    public function getDifficultyLevels(): array
    {
        return [
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate', 
            'advanced' => 'Advanced'
        ];
    }
    
    /**
     * Get common categories for form suggestions
     * 
     * @return array Common resource categories
     */
    public function getCategories(): array
    {
        return [
            'anxiety',
            'depression', 
            'mindfulness',
            'cbt',
            'sleep',
            'stress',
            'relationships',
            'self-care',
            'trauma',
            'grief',
            'addiction',
            'general'
        ];
    }
}
?>