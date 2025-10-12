-- 002_add_fk_messages_users.sql
-- Phase 1: Critical Fixes - Add Foreign Key Constraint
-- Purpose: Enforce referential integrity between messages and users
-- Run with: mysql -u root -p -P 3307 mindmate < database/sql/002_add_fk_messages_users.sql

-- ⚠️ WARNING: Before running this script:
-- 1. Ensure users table is created (run 001_create_users_table.sql first)
-- 2. Ensure all user_id values in messages table exist in users table
-- 3. If orphaned user_id values exist, either:
--    a) Create corresponding users records, OR
--    b) Delete/update the orphaned messages

-- Check for orphaned messages (messages with user_id not in users table)
SELECT m.id, m.user_id, m.message, m.created_at
FROM messages m
LEFT JOIN users u ON m.user_id = u.id
WHERE u.id IS NULL;

-- If orphaned messages exist, uncomment ONE of the following options:

-- OPTION A: Delete orphaned messages
-- DELETE FROM messages WHERE user_id NOT IN (SELECT id FROM users);

-- OPTION B: Set orphaned messages to a default user (create default user first)
-- UPDATE messages SET user_id = 1 WHERE user_id NOT IN (SELECT id FROM users);

-- Modify user_id to be NOT NULL (if it should always have a user)
ALTER TABLE messages
MODIFY COLUMN user_id INT(11) NOT NULL;

-- Add foreign key constraint
ALTER TABLE messages
ADD CONSTRAINT fk_messages_user_id 
    FOREIGN KEY (user_id) 
    REFERENCES users(id) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

-- Verification: Check foreign key was created
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'mindmate'
AND TABLE_NAME = 'messages'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Test: Try to insert a message with invalid user_id (should fail)
-- INSERT INTO messages (user_id, message, response, strategy_used) 
-- VALUES (99999, 'test', 'test', 'test');
