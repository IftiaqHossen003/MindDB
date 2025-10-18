-- File: database/sql/60_create_journal_entries_table.sql
-- ==============================================================================
-- MindDB - Journal Entries Table Creation
-- ==============================================================================
-- Purpose: Create journal_entries table for user journaling functionality
-- Version: 1.0.0
-- Date: 2025-10-15
-- Idempotent: Yes (uses IF NOT EXISTS)
-- ==============================================================================

USE mindmate;

-- ==============================================================================
-- TABLE: journal_entries
-- Purpose: Store user journal entries with mood tracking and sentiment analysis
-- ==============================================================================

CREATE TABLE IF NOT EXISTS `journal_entries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique identifier for journal entry',
    `user_id` INT NULL COMMENT 'Reference to user (nullable for anonymized entries)',
    `title` VARCHAR(255) NOT NULL COMMENT 'Journal entry title',
    `content` TEXT NOT NULL COMMENT 'Main journal entry content',
    `mood_tag` VARCHAR(100) NULL COMMENT 'Optional mood tag: happy, sad, anxious, calm, etc.',
    `sentiment_score` DECIMAL(3,2) NULL COMMENT 'Sentiment analysis score (-1.00 to 1.00, NULL if not analyzed)',
    `is_private` TINYINT(1) DEFAULT 1 COMMENT 'Privacy flag: 1 = private (default), 0 = public/shared',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Entry creation timestamp',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    -- Indexes for performance optimization
    INDEX `idx_user_id` (`user_id`) COMMENT 'Fast lookup by user',
    INDEX `idx_created_at` (`created_at`) COMMENT 'Fast sorting by creation date',
    INDEX `idx_user_created` (`user_id`, `created_at`) COMMENT 'Composite index for user timeline queries',
    INDEX `idx_mood_tag` (`mood_tag`) COMMENT 'Fast filtering by mood',
    INDEX `idx_is_private` (`is_private`) COMMENT 'Fast filtering by privacy status',
    
    -- Constraints
    CHECK (`sentiment_score` IS NULL OR (`sentiment_score` >= -1.00 AND `sentiment_score` <= 1.00))
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User journal entries with mood tracking and sentiment analysis for mental health monitoring';

-- ==============================================================================
-- VERIFICATION
-- ==============================================================================

-- Verify table creation
SELECT 
    TABLE_NAME,
    TABLE_COMMENT,
    ENGINE,
    TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'mindmate' 
AND TABLE_NAME = 'journal_entries';

-- Verify columns
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'mindmate' 
AND TABLE_NAME = 'journal_entries'
ORDER BY ORDINAL_POSITION;

-- Verify indexes
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    INDEX_TYPE,
    INDEX_COMMENT
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' 
AND TABLE_NAME = 'journal_entries'
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- ==============================================================================
-- USAGE EXAMPLES
-- ==============================================================================

-- Example 1: Insert a private journal entry
-- INSERT INTO journal_entries (user_id, title, content, mood_tag, is_private) 
-- VALUES (1, 'Today was challenging', 'I felt anxious about work deadlines...', 'anxious', 1);

-- Example 2: Insert a journal entry with sentiment score
-- INSERT INTO journal_entries (user_id, title, content, mood_tag, sentiment_score, is_private) 
-- VALUES (1, 'Great therapy session', 'Made progress on anxiety coping strategies', 'hopeful', 0.85, 1);

-- Example 3: Retrieve user's recent journal entries (last 7 days)
-- SELECT id, title, mood_tag, sentiment_score, created_at 
-- FROM journal_entries 
-- WHERE user_id = 1 
-- AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
-- ORDER BY created_at DESC;

-- Example 4: Update journal entry
-- UPDATE journal_entries 
-- SET content = 'Updated content...', mood_tag = 'calm' 
-- WHERE id = 1 AND user_id = 1;

-- Example 5: Get mood distribution from journal entries
-- SELECT mood_tag, COUNT(*) as entry_count 
-- FROM journal_entries 
-- WHERE user_id = 1 AND mood_tag IS NOT NULL
-- GROUP BY mood_tag 
-- ORDER BY entry_count DESC;

-- Example 6: Get average sentiment score over time
-- SELECT 
--     DATE(created_at) as entry_date,
--     AVG(sentiment_score) as avg_sentiment,
--     COUNT(*) as entries
-- FROM journal_entries 
-- WHERE user_id = 1 AND sentiment_score IS NOT NULL
-- GROUP BY DATE(created_at)
-- ORDER BY entry_date DESC
-- LIMIT 30;

SELECT '✅ journal_entries table created successfully!' as status,
       'Table supports CRUD operations, mood tracking, and sentiment analysis' as features,
       'Use provided usage examples to interact with the table' as next_steps;
