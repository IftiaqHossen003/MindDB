-- 000_create_fresh_database_simple.sql
-- SIMPLIFIED FRESH DATABASE SETUP
-- Purpose: Create complete normalized database structure without triggers/procedures
-- Run with: Get-Content database/sql/000_create_fresh_database_simple.sql | C:\xampp\mysql\bin\mysql.exe -u root -P 3307 mindmate

-- Drop existing tables if they exist (in reverse FK dependency order)
DROP TABLE IF EXISTS message_audit;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS strategies;
DROP TABLE IF EXISTS users;

-- ==============================================================================
-- CORE TABLES
-- ==============================================================================

-- Table: users
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: strategies
CREATE TABLE strategies (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: conversations
CREATE TABLE conversations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NULL,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_message_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    message_count INT(11) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_message (last_message_at),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: messages (RECREATED WITH PROPER STRUCTURE)
CREATE TABLE messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    conversation_id INT(11) NOT NULL,
    strategy_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    response TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (strategy_id) REFERENCES strategies(id) ON DELETE RESTRICT,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_strategy_id (strategy_id),
    INDEX idx_created_at (created_at),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- INSERT DEFAULT DATA
-- ==============================================================================

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@minddb.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert demo user (password: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES
('demo_user', 'demo@minddb.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Insert therapeutic strategies
INSERT INTO strategies (name, description, is_active) VALUES
('cognitive_behavioral', 'Cognitive Behavioral Therapy (CBT) approach', TRUE),
('mindfulness', 'Mindfulness-based response', TRUE),
('empathetic', 'Empathetic listening and validation', TRUE),
('solution_focused', 'Solution-focused brief therapy', TRUE),
('psychoeducational', 'Educational response with insights', TRUE),
('motivational', 'Motivational interviewing techniques', TRUE),
('supportive', 'General supportive counseling', TRUE),
('reflective', 'Reflective listening approach', TRUE),
('default', 'Default strategy', TRUE);

-- Create sample conversation for demo user
INSERT INTO conversations (user_id, title) VALUES (2, 'My First Conversation');

-- Insert sample messages
INSERT INTO messages (user_id, conversation_id, strategy_id, message, response) VALUES
(2, 1, 1, 'I feel anxious about my upcoming presentation.', 
 'I understand you are feeling anxious about your presentation. Let us explore what specific thoughts are contributing to this anxiety.'),
(2, 1, 2, 'I keep thinking everyone will judge me negatively.',
 'Thank you for sharing that. Notice how this thought affects your body and emotions right now.'),
(2, 1, 4, 'I guess I do feel tension in my shoulders. What can I do?',
 'That is great awareness. Let us focus on a solution: What has helped you feel more confident before?');

-- Update conversation message count
UPDATE conversations SET message_count = 3 WHERE id = 1;

-- ==============================================================================
-- VERIFICATION
-- ==============================================================================

SELECT '=== Database Setup Complete ===' as Status;
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_TYPE = 'BASE TABLE';
SELECT 'users' as tbl, COUNT(*) as cnt FROM users UNION ALL SELECT 'strategies', COUNT(*) FROM strategies UNION ALL SELECT 'conversations', COUNT(*) FROM conversations UNION ALL SELECT 'messages', COUNT(*) FROM messages;
