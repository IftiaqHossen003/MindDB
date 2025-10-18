<?php
/**
 * Journal Entry Model
 * 
 * Lightweight model class for managing journal_entries table operations.
 * Provides CRUD functionality with prepared statements for security.
 * Supports both PDO and mysqli database connections.
 * 
 * @package MindDB
 * @subpackage Models
 * @version 1.0.0
 * @author MindDB Development Team
 */

class JournalEntry {
    
    /**
     * Database connection instance (PDO or mysqli)
     * @var PDO|mysqli|null
     */
    private $db;
    
    /**
     * Database connection type ('pdo' or 'mysqli')
     * @var string
     */
    private $dbType;
    
    /**
     * Constructor - Initialize database connection
     * 
     * Automatically detects and uses existing DB connection from config.php
     * Priority: getDb() function > global $pdo > global $mysqli
     * 
     * @throws Exception If no database connection is available
     */
    public function __construct() {
        // Ensure config.php is loaded
        if (!defined('DB_NAME')) {
            require_once __DIR__ . '/../../config.php';
        }
        
        // Try getDb() function first (preferred)
        if (function_exists('getDb')) {
            $this->db = getDb();
            $this->dbType = 'pdo';
        }
        // Try global $pdo
        elseif (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->db = $GLOBALS['pdo'];
            $this->dbType = 'pdo';
        }
        // Try global $mysqli
        elseif (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) {
            $this->db = $GLOBALS['mysqli'];
            $this->dbType = 'mysqli';
        }
        // No connection found
        else {
            throw new Exception('No database connection available. Please check config.php');
        }
    }
    
    /**
     * Create a new journal entry
     * 
     * @param array $data Associative array with keys: user_id, title, content, mood_tag, sentiment_score, is_private
     * @return int The ID of the newly created journal entry
     * @throws Exception If insert fails or required fields are missing
     */
    public function create(array $data): int {
        // Validate required fields
        if (empty($data['title']) || empty($data['content'])) {
            throw new Exception('Title and content are required fields');
        }
        
        // Sanitize and prepare data
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $title = trim($data['title']);
        $content = trim($data['content']);
        $moodTag = isset($data['mood_tag']) ? trim($data['mood_tag']) : null;
        $sentimentScore = isset($data['sentiment_score']) ? (float)$data['sentiment_score'] : null;
        $isPrivate = isset($data['is_private']) ? (int)$data['is_private'] : 1;
        
        // Validate sentiment score range
        if ($sentimentScore !== null && ($sentimentScore < -1.00 || $sentimentScore > 1.00)) {
            throw new Exception('Sentiment score must be between -1.00 and 1.00');
        }
        
        $sql = "INSERT INTO journal_entries (user_id, title, content, mood_tag, sentiment_score, is_private) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        if ($this->dbType === 'pdo') {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $title, $content, $moodTag, $sentimentScore, $isPrivate]);
            return (int)$this->db->lastInsertId();
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('isssdi', $userId, $title, $content, $moodTag, $sentimentScore, $isPrivate);
            $stmt->execute();
            $insertId = $this->db->insert_id;
            $stmt->close();
            return $insertId;
        }
    }
    
    /**
     * Find a journal entry by ID
     * 
     * @param int $id The journal entry ID
     * @return array|null Associative array of journal entry data, or null if not found
     */
    public function find(int $id): ?array {
        $sql = "SELECT id, user_id, title, content, mood_tag, sentiment_score, is_private, 
                       created_at, updated_at 
                FROM journal_entries 
                WHERE id = ? 
                LIMIT 1";
        
        if ($this->dbType === 'pdo') {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data ?: null;
        }
    }
    
    /**
     * Get all journal entries for a specific user
     * 
     * Returns entries ordered by creation date (newest first)
     * 
     * @param int $userId The user ID
     * @param int $limit Maximum number of entries to return (default: 100)
     * @return array Array of associative arrays containing journal entry data
     */
    public function allByUser(int $userId, int $limit = 100): array {
        // Sanitize limit
        $limit = max(1, min((int)$limit, 1000)); // Between 1 and 1000
        
        $sql = "SELECT id, user_id, title, content, mood_tag, sentiment_score, is_private, 
                       created_at, updated_at 
                FROM journal_entries 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        if ($this->dbType === 'pdo') {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $entries = [];
            while ($row = $result->fetch_assoc()) {
                $entries[] = $row;
            }
            $stmt->close();
            return $entries;
        }
    }
    
    /**
     * Update an existing journal entry
     * 
     * @param int $id The journal entry ID
     * @param array $data Associative array with fields to update (title, content, mood_tag, sentiment_score, is_private)
     * @return bool True if update successful, false otherwise
     * @throws Exception If sentiment_score is out of range
     */
    public function update(int $id, array $data): bool {
        // Build dynamic update query based on provided fields
        $allowedFields = ['title', 'content', 'mood_tag', 'sentiment_score', 'is_private'];
        $updateFields = [];
        $values = [];
        $types = '';
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateFields[] = "$field = ?";
                
                // Sanitize and validate data
                if ($field === 'title' || $field === 'content' || $field === 'mood_tag') {
                    $values[] = trim($data[$field]);
                    $types .= 's';
                } elseif ($field === 'sentiment_score') {
                    $value = $data[$field] !== null ? (float)$data[$field] : null;
                    if ($value !== null && ($value < -1.00 || $value > 1.00)) {
                        throw new Exception('Sentiment score must be between -1.00 and 1.00');
                    }
                    $values[] = $value;
                    $types .= 'd';
                } elseif ($field === 'is_private') {
                    $values[] = (int)$data[$field];
                    $types .= 'i';
                }
            }
        }
        
        // If no fields to update, return false
        if (empty($updateFields)) {
            return false;
        }
        
        $sql = "UPDATE journal_entries SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $values[] = $id;
        
        if ($this->dbType === 'pdo') {
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($values);
            return $success && $stmt->rowCount() > 0;
        } else {
            $stmt = $this->db->prepare($sql);
            $types .= 'i'; // Add type for id parameter
            
            // Bind parameters dynamically
            $bindParams = [$types];
            foreach ($values as $key => $value) {
                $bindParams[] = &$values[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
            
            $success = $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            return $success && $affectedRows > 0;
        }
    }
    
    /**
     * Delete a journal entry
     * 
     * @param int $id The journal entry ID
     * @return bool True if deletion successful, false otherwise
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM journal_entries WHERE id = ?";
        
        if ($this->dbType === 'pdo') {
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$id]);
            return $success && $stmt->rowCount() > 0;
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $success = $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            return $success && $affectedRows > 0;
        }
    }
    
    /**
     * Get journal entries by mood tag
     * 
     * @param int $userId The user ID
     * @param string $moodTag The mood tag to filter by
     * @param int $limit Maximum number of entries to return (default: 50)
     * @return array Array of associative arrays containing journal entry data
     */
    public function findByMoodTag(int $userId, string $moodTag, int $limit = 50): array {
        $moodTag = trim($moodTag);
        $limit = max(1, min((int)$limit, 1000));
        
        $sql = "SELECT id, user_id, title, content, mood_tag, sentiment_score, is_private, 
                       created_at, updated_at 
                FROM journal_entries 
                WHERE user_id = ? AND mood_tag = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        if ($this->dbType === 'pdo') {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $moodTag, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('isi', $userId, $moodTag, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $entries = [];
            while ($row = $result->fetch_assoc()) {
                $entries[] = $row;
            }
            $stmt->close();
            return $entries;
        }
    }
    
    /**
     * Get journal entry count for a user
     * 
     * @param int $userId The user ID
     * @return int Total number of journal entries for the user
     */
    public function countByUser(int $userId): int {
        $sql = "SELECT COUNT(*) as total FROM journal_entries WHERE user_id = ?";
        
        if ($this->dbType === 'pdo') {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            return (int)$data['total'];
        }
    }
    
    /**
     * Search journal entries by keyword
     * 
     * Searches in both title and content fields
     * 
     * @param int $userId The user ID
     * @param string $keyword The search keyword
     * @param int $limit Maximum number of entries to return (default: 50)
     * @return array Array of associative arrays containing journal entry data
     */
    public function search(int $userId, string $keyword, int $limit = 50): array {
        $keyword = trim($keyword);
        $limit = max(1, min((int)$limit, 1000));
        $searchPattern = '%' . $keyword . '%';
        
        $sql = "SELECT id, user_id, title, content, mood_tag, sentiment_score, is_private, 
                       created_at, updated_at 
                FROM journal_entries 
                WHERE user_id = ? AND (title LIKE ? OR content LIKE ?) 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        if ($this->dbType === 'pdo') {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $searchPattern, $searchPattern, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('issi', $userId, $searchPattern, $searchPattern, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $entries = [];
            while ($row = $result->fetch_assoc()) {
                $entries[] = $row;
            }
            $stmt->close();
            return $entries;
        }
    }
}
