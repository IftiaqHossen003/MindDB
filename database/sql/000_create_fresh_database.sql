-- 000_create_fresh_database.sql
-- FRESH DATABASE SETUP - Run this first if starting from scratch
-- Purpose: Create complete normalized database structure from empty state
-- Run with: mysql -u root -p -P 3307 mindmate < database/sql/000_create_fresh_database.sql

-- ==============================================================================
-- PHASE 1: CORE TABLES
-- ==============================================================================

-- Table: users
-- Purpose: Store user accounts with authentication
CREATE TABLE IF NOT EXISTS users (
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
-- Purpose: Lookup table for AI response strategies
CREATE TABLE IF NOT EXISTS strategies (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: conversations
-- Purpose: Group messages into conversation threads
CREATE TABLE IF NOT EXISTS conversations (
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
-- Purpose: Store user messages and AI responses with full normalization
CREATE TABLE IF NOT EXISTS messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    conversation_id INT(11) NOT NULL,
    strategy_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    message_length INT(11) GENERATED ALWAYS AS (CHAR_LENGTH(message)) STORED,
    response TEXT NOT NULL,
    response_length INT(11) GENERATED ALWAYS AS (CHAR_LENGTH(response)) STORED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    
    -- Foreign key constraints for referential integrity
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (strategy_id) REFERENCES strategies(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_strategy_id (strategy_id),
    INDEX idx_created_at (created_at),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: message_audit
-- Purpose: Track all changes to messages for audit trail
CREATE TABLE IF NOT EXISTS message_audit (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    message_id INT(11) NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_message TEXT NULL,
    new_message TEXT NULL,
    old_response TEXT NULL,
    new_response TEXT NULL,
    changed_by INT(11) NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_message_id (message_id),
    INDEX idx_action (action),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- PHASE 2: INSERT DEFAULT DATA
-- ==============================================================================

-- Insert default admin user
-- Password: 'admin123' (CHANGE THIS IMMEDIATELY!)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@minddb.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Insert demo regular user
INSERT INTO users (username, email, password_hash, role) VALUES
('demo_user', 'demo@minddb.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user')
ON DUPLICATE KEY UPDATE id=id;

-- Insert therapeutic strategies
INSERT INTO strategies (name, description, is_active) VALUES
('cognitive_behavioral', 'Cognitive Behavioral Therapy (CBT) approach focusing on thought patterns and behaviors', TRUE),
('mindfulness', 'Mindfulness-based response promoting present-moment awareness and acceptance', TRUE),
('empathetic', 'Empathetic listening and validation response showing understanding and support', TRUE),
('solution_focused', 'Solution-focused brief therapy approach emphasizing goals and solutions', TRUE),
('psychoeducational', 'Educational response providing information, insights, and knowledge', TRUE),
('motivational', 'Motivational interviewing techniques to encourage positive change', TRUE),
('supportive', 'General supportive counseling approach providing encouragement', TRUE),
('reflective', 'Reflective listening that mirrors and clarifies user emotions', TRUE),
('default', 'Default strategy when no specific therapeutic approach is specified', TRUE)
ON DUPLICATE KEY UPDATE id=id;

-- ==============================================================================
-- PHASE 3: CREATE VIEWS
-- ==============================================================================

-- View: Active messages only (not soft-deleted)
CREATE OR REPLACE VIEW messages_active AS
SELECT * FROM messages WHERE deleted_at IS NULL;

-- View: Conversation summary
CREATE OR REPLACE VIEW conversation_summary AS
SELECT 
    c.id as conversation_id,
    c.user_id,
    u.username,
    c.title,
    c.started_at,
    c.last_message_at,
    c.is_active,
    COUNT(m.id) as actual_message_count,
    MAX(m.created_at) as last_message_timestamp
FROM conversations c
INNER JOIN users u ON c.user_id = u.id
LEFT JOIN messages m ON c.id = m.conversation_id AND m.deleted_at IS NULL
GROUP BY c.id, c.user_id, u.username, c.title, c.started_at, c.last_message_at, c.is_active;

-- ==============================================================================
-- PHASE 4: CREATE TRIGGERS
-- ==============================================================================

DELIMITER $$

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS trg_messages_after_insert$$
DROP TRIGGER IF EXISTS trg_messages_after_delete$$
DROP TRIGGER IF EXISTS trg_messages_audit_insert$$
DROP TRIGGER IF EXISTS trg_messages_audit_update$$
DROP TRIGGER IF EXISTS trg_messages_audit_delete$$

-- Trigger: Update conversation metadata on message insert
CREATE TRIGGER trg_messages_after_insert
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    UPDATE conversations 
    SET last_message_at = NEW.created_at,
        message_count = message_count + 1
    WHERE id = NEW.conversation_id;
END$$

-- Trigger: Update conversation metadata on message delete
CREATE TRIGGER trg_messages_after_delete
AFTER DELETE ON messages
FOR EACH ROW
BEGIN
    UPDATE conversations 
    SET message_count = GREATEST(0, message_count - 1)
    WHERE id = OLD.conversation_id;
END$$

-- Trigger: Audit trail for message insert
CREATE TRIGGER trg_messages_audit_insert
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    INSERT INTO message_audit (message_id, action, new_message, new_response, changed_by)
    VALUES (NEW.id, 'INSERT', NEW.message, NEW.response, NEW.user_id);
END$$

-- Trigger: Audit trail for message update
CREATE TRIGGER trg_messages_audit_update
AFTER UPDATE ON messages
FOR EACH ROW
BEGIN
    IF OLD.message != NEW.message OR OLD.response != NEW.response THEN
        INSERT INTO message_audit (
            message_id, 
            action, 
            old_message, 
            new_message, 
            old_response, 
            new_response,
            changed_by
        )
        VALUES (
            NEW.id, 
            'UPDATE', 
            OLD.message, 
            NEW.message, 
            OLD.response, 
            NEW.response,
            NEW.user_id
        );
    END IF;
END$$

-- Trigger: Audit trail for message delete
CREATE TRIGGER trg_messages_audit_delete
BEFORE DELETE ON messages
FOR EACH ROW
BEGIN
    INSERT INTO message_audit (message_id, action, old_message, old_response, changed_by)
    VALUES (OLD.id, 'DELETE', OLD.message, OLD.response, OLD.user_id);
END$$

DELIMITER ;

-- ==============================================================================
-- PHASE 5: CREATE STORED PROCEDURES
-- ==============================================================================

DELIMITER $$

-- Drop existing procedures if they exist
DROP PROCEDURE IF EXISTS sp_soft_delete_message$$
DROP PROCEDURE IF EXISTS sp_restore_message$$
DROP PROCEDURE IF EXISTS sp_create_conversation_with_message$$

-- Procedure: Soft delete a message
CREATE PROCEDURE sp_soft_delete_message(
    IN p_message_id INT,
    IN p_user_id INT
)
BEGIN
    UPDATE messages 
    SET deleted_at = NOW() 
    WHERE id = p_message_id 
    AND user_id = p_user_id
    AND deleted_at IS NULL;
    
    SELECT ROW_COUNT() as affected_rows;
END$$

-- Procedure: Restore a soft-deleted message
CREATE PROCEDURE sp_restore_message(
    IN p_message_id INT,
    IN p_user_id INT
)
BEGIN
    UPDATE messages 
    SET deleted_at = NULL 
    WHERE id = p_message_id 
    AND user_id = p_user_id
    AND deleted_at IS NOT NULL;
    
    SELECT ROW_COUNT() as affected_rows;
END$$

-- Procedure: Create new conversation with first message
CREATE PROCEDURE sp_create_conversation_with_message(
    IN p_user_id INT,
    IN p_strategy_id INT,
    IN p_message TEXT,
    IN p_response TEXT,
    IN p_conversation_title VARCHAR(255),
    IN p_ip_address VARCHAR(45),
    IN p_user_agent VARCHAR(255)
)
BEGIN
    DECLARE v_conversation_id INT;
    DECLARE v_message_id INT;
    
    -- Create conversation
    INSERT INTO conversations (user_id, title)
    VALUES (p_user_id, p_conversation_title);
    
    SET v_conversation_id = LAST_INSERT_ID();
    
    -- Insert message
    INSERT INTO messages (
        user_id, 
        conversation_id, 
        strategy_id, 
        message, 
        response,
        ip_address,
        user_agent
    )
    VALUES (
        p_user_id,
        v_conversation_id,
        p_strategy_id,
        p_message,
        p_response,
        p_ip_address,
        p_user_agent
    );
    
    SET v_message_id = LAST_INSERT_ID();
    
    -- Return IDs
    SELECT v_conversation_id as conversation_id, v_message_id as message_id;
END$$

DELIMITER ;

-- ==============================================================================
-- PHASE 6: INSERT SAMPLE DATA (OPTIONAL - for testing)
-- ==============================================================================

-- Create sample conversation for demo user
INSERT INTO conversations (user_id, title) VALUES
(2, 'My First Conversation');

SET @conv_id = LAST_INSERT_ID();

-- Insert sample messages
INSERT INTO messages (user_id, conversation_id, strategy_id, message, response) VALUES
(2, @conv_id, 1, 'I feel anxious about my upcoming presentation.', 
 'I understand you''re feeling anxious about your presentation. Let''s explore what specific thoughts are contributing to this anxiety. What worries you most about the presentation?'),

(2, @conv_id, 2, 'I keep thinking everyone will judge me negatively.',
 'Thank you for sharing that. Notice how this thought affects your body and emotions right now. Can you take a moment to observe these feelings without judgment, just acknowledging they''re present?'),

(2, @conv_id, 4, 'I guess I do feel tension in my shoulders. What can I do about this?',
 'That''s great awareness. Let''s focus on a solution: What has helped you feel more confident in similar situations before? Even small things count.');

-- ==============================================================================
-- VERIFICATION QUERIES
-- ==============================================================================

-- Show all tables
SELECT 'Tables created:' as status;
SHOW TABLES;

-- Show foreign keys
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'mindmate'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Show record counts
SELECT 
    'users' as table_name, 
    COUNT(*) as record_count 
FROM users
UNION ALL
SELECT 'strategies', COUNT(*) FROM strategies
UNION ALL
SELECT 'conversations', COUNT(*) FROM conversations
UNION ALL
SELECT 'messages', COUNT(*) FROM messages;

-- Show sample data
SELECT 
    u.username,
    c.title as conversation,
    m.message as user_message,
    s.name as strategy,
    m.created_at
FROM messages m
INNER JOIN users u ON m.user_id = u.id
INNER JOIN conversations c ON m.conversation_id = c.id
INNER JOIN strategies s ON m.strategy_id = s.id
WHERE m.deleted_at IS NULL
ORDER BY m.created_at DESC;

-- ==============================================================================
-- COMPLETION MESSAGE
-- ==============================================================================

SELECT '✅ Database setup complete!' as status,
       'Default admin: admin/admin123 (CHANGE THIS!)' as security_note,
       'Demo user: demo_user/admin123' as demo_account,
       '3 sample messages created for testing' as sample_data;
