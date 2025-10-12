-- 006_add_audit_fields.sql
-- Phase 3: Optional Enhancements - Add audit trail and soft delete
-- Purpose: Track changes and enable soft deletion for data recovery
-- Run with: mysql -u root -p -P 3307 mindmate < database/sql/006_add_audit_fields.sql

-- Add audit and soft delete fields to messages table
ALTER TABLE messages
ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
ADD COLUMN deleted_at DATETIME NULL AFTER updated_at,
ADD COLUMN ip_address VARCHAR(45) NULL AFTER deleted_at,
ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip_address,
ADD INDEX idx_deleted_at (deleted_at),
ADD INDEX idx_updated_at (updated_at);

-- Add computed column for message length (for analytics)
ALTER TABLE messages
ADD COLUMN message_length INT(11) GENERATED ALWAYS AS (CHAR_LENGTH(message)) STORED AFTER message,
ADD COLUMN response_length INT(11) GENERATED ALWAYS AS (CHAR_LENGTH(response)) STORED AFTER response;

-- Create message_audit table for tracking changes
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

-- Create triggers for audit trail
DELIMITER $$

CREATE TRIGGER trg_messages_audit_insert
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    INSERT INTO message_audit (message_id, action, new_message, new_response, changed_by)
    VALUES (NEW.id, 'INSERT', NEW.message, NEW.response, NEW.user_id);
END$$

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

CREATE TRIGGER trg_messages_audit_delete
BEFORE DELETE ON messages
FOR EACH ROW
BEGIN
    INSERT INTO message_audit (message_id, action, old_message, old_response, changed_by)
    VALUES (OLD.id, 'DELETE', OLD.message, OLD.response, OLD.user_id);
END$$

DELIMITER ;

-- Create view for active (non-deleted) messages
CREATE OR REPLACE VIEW messages_active AS
SELECT * FROM messages WHERE deleted_at IS NULL;

-- Create stored procedure for soft delete
DELIMITER $$

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

-- Create stored procedure for restore
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

DELIMITER ;

-- Verification queries

-- View audit trail
SELECT 
    ma.id,
    ma.message_id,
    ma.action,
    u.username as changed_by,
    ma.changed_at
FROM message_audit ma
LEFT JOIN users u ON ma.changed_by = u.id
ORDER BY ma.changed_at DESC
LIMIT 20;

-- View messages with length statistics
SELECT 
    id,
    user_id,
    message_length,
    response_length,
    created_at,
    updated_at,
    deleted_at
FROM messages
ORDER BY created_at DESC
LIMIT 10;

-- Test soft delete (replace 1 with actual message_id and user_id)
-- CALL sp_soft_delete_message(1, 1);

-- View active messages only
-- SELECT * FROM messages_active;

-- Restore a soft-deleted message
-- CALL sp_restore_message(1, 1);
