-- 004_normalize_strategies.sql
-- Phase 2: Strategy Normalization - Migrate messages to use strategy_id
-- Purpose: Replace strategy_used VARCHAR with strategy_id FK
-- Run with: mysql -u root -p -P 3307 mindmate < database/sql/004_normalize_strategies.sql

-- ⚠️ WARNING: Before running this script:
-- 1. Ensure strategies table is created (run 003_create_strategies_table.sql first)
-- 2. Verify all strategy_used values in messages exist in strategies table
-- 3. Backup your data!

-- Step 1: Add strategy_id column to messages table
ALTER TABLE messages
ADD COLUMN strategy_id INT(11) NULL AFTER strategy_used,
ADD INDEX idx_strategy_id (strategy_id);

-- Step 2: Migrate existing data from strategy_used to strategy_id
UPDATE messages m
INNER JOIN strategies s ON m.strategy_used = s.name
SET m.strategy_id = s.id;

-- Step 3: Verify migration (should return 0 rows if successful)
SELECT id, strategy_used, strategy_id
FROM messages
WHERE strategy_id IS NULL;

-- Step 4: If any messages have NULL strategy_id, assign them to 'default' strategy
UPDATE messages m
SET m.strategy_id = (SELECT id FROM strategies WHERE name = 'default' LIMIT 1)
WHERE m.strategy_id IS NULL;

-- Step 5: Make strategy_id NOT NULL (after ensuring all rows have values)
ALTER TABLE messages
MODIFY COLUMN strategy_id INT(11) NOT NULL;

-- Step 6: Add foreign key constraint
ALTER TABLE messages
ADD CONSTRAINT fk_messages_strategy_id
    FOREIGN KEY (strategy_id)
    REFERENCES strategies(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE;

-- Step 7: Keep strategy_used for now (for rollback safety)
-- After verifying everything works in production, you can remove it:
-- ALTER TABLE messages DROP COLUMN strategy_used;

-- Or rename it for reference:
ALTER TABLE messages
CHANGE COLUMN strategy_used strategy_used_legacy VARCHAR(64) NULL;

-- Verification queries
SELECT 
    m.id,
    m.strategy_id,
    s.name as strategy_name,
    m.strategy_used_legacy,
    m.created_at
FROM messages m
INNER JOIN strategies s ON m.strategy_id = s.id
ORDER BY m.created_at DESC
LIMIT 10;

-- Test: Try to delete an active strategy (should fail if messages reference it)
-- DELETE FROM strategies WHERE name = 'empathetic';

-- Test: Try to insert message with invalid strategy_id (should fail)
-- INSERT INTO messages (user_id, strategy_id, message, response) 
-- VALUES (1, 99999, 'test', 'test');
