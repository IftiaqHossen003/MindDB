-- 005_create_conversations_table.sql
-- Phase 3: Optional Enhancements - Add conversation threading support
-- Purpose: Group messages into conversations for better context management
-- Run with: mysql -u root -p -P 3307 mindmate < database/sql/005_create_conversations_table.sql

-- Create conversations table
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

-- Add conversation_id to messages table
ALTER TABLE messages
ADD COLUMN conversation_id INT(11) NULL AFTER user_id,
ADD INDEX idx_conversation_id (conversation_id);

-- Create default conversations for existing messages (one per user)
INSERT INTO conversations (user_id, title, started_at, last_message_at)
SELECT 
    user_id,
    CONCAT('Conversation ', DATE_FORMAT(MIN(created_at), '%Y-%m-%d')),
    MIN(created_at),
    MAX(created_at)
FROM messages
GROUP BY user_id;

-- Link existing messages to their user's conversation
UPDATE messages m
INNER JOIN conversations c ON m.user_id = c.user_id
SET m.conversation_id = c.id;

-- Update message counts
UPDATE conversations c
SET c.message_count = (
    SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id
);

-- Make conversation_id NOT NULL after migration
ALTER TABLE messages
MODIFY COLUMN conversation_id INT(11) NOT NULL;

-- Add foreign key constraint
ALTER TABLE messages
ADD CONSTRAINT fk_messages_conversation_id
    FOREIGN KEY (conversation_id)
    REFERENCES conversations(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

-- Create trigger to update conversation's last_message_at
DELIMITER $$

CREATE TRIGGER trg_messages_after_insert
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    UPDATE conversations 
    SET last_message_at = NEW.created_at,
        message_count = message_count + 1
    WHERE id = NEW.conversation_id;
END$$

CREATE TRIGGER trg_messages_after_delete
AFTER DELETE ON messages
FOR EACH ROW
BEGIN
    UPDATE conversations 
    SET message_count = message_count - 1
    WHERE id = OLD.conversation_id;
END$$

DELIMITER ;

-- Verification queries
SELECT 
    c.id,
    c.user_id,
    u.username,
    c.title,
    c.message_count,
    c.started_at,
    c.last_message_at
FROM conversations c
INNER JOIN users u ON c.user_id = u.id
ORDER BY c.last_message_at DESC;

-- View messages grouped by conversation
SELECT 
    c.title as conversation,
    m.id as message_id,
    m.message,
    m.response,
    m.created_at
FROM messages m
INNER JOIN conversations c ON m.conversation_id = c.id
ORDER BY c.id, m.created_at;
