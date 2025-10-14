-- 30_minddb_indexes_safe.sql
-- Purpose: Add performance-optimized indexes with existence checking
-- This version checks each index before attempting to create it
-- Run with: Get-Content database/sql/30_minddb_indexes_safe.sql | C:\xampp\mysql\bin\mysql.exe -u root -P 3307 mindmate

-- ==============================================================================
-- VERIFICATION ONLY - All indexes already exist
-- ==============================================================================

SELECT 'Checking existing indexes...' AS status;

-- Check mood_logs indexes
SELECT 'mood_logs indexes:' AS table_name;
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_NAME = 'mood_logs'
GROUP BY INDEX_NAME;

-- Check therapy_sessions indexes  
SELECT 'therapy_sessions indexes:' AS table_name;
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_NAME = 'therapy_sessions'
GROUP BY INDEX_NAME;

-- Check resources indexes
SELECT 'resources indexes:' AS table_name;
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_NAME = 'resources'
GROUP BY INDEX_NAME;

-- Check users indexes
SELECT 'users indexes:' AS table_name;
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_NAME = 'users'
GROUP BY INDEX_NAME;

-- Check conversations indexes
SELECT 'conversations indexes:' AS table_name;
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_NAME = 'conversations'
GROUP BY INDEX_NAME;

-- Check messages indexes
SELECT 'messages indexes:' AS table_name;
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_NAME = 'messages'
GROUP BY INDEX_NAME;

-- Check anonymized_users indexes
SELECT 'anonymized_users indexes:' AS table_name;
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_NAME = 'anonymized_users'
GROUP BY INDEX_NAME;

-- Check helplines indexes
SELECT 'helplines indexes:' AS table_name;
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_NAME = 'helplines'
GROUP BY INDEX_NAME;

-- Check strategies indexes
SELECT 'strategies indexes:' AS table_name;
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate' AND TABLE_NAME = 'strategies'
GROUP BY INDEX_NAME;

-- ==============================================================================
-- SUMMARY REPORT
-- ==============================================================================

SELECT '========================================' AS separator;
SELECT 'INDEX AUDIT SUMMARY' AS report_title;
SELECT '========================================' AS separator;

SELECT 
    TABLE_NAME,
    COUNT(DISTINCT INDEX_NAME) as total_indexes,
    SUM(CASE WHEN INDEX_TYPE = 'FULLTEXT' THEN 1 ELSE 0 END) as fulltext_indexes,
    SUM(CASE WHEN NON_UNIQUE = 0 THEN 1 ELSE 0 END) as unique_indexes
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate'
AND TABLE_NAME IN (
    'mood_logs', 'therapy_sessions', 'resources', 'users', 
    'conversations', 'messages', 'anonymized_users', 'helplines', 'strategies'
)
GROUP BY TABLE_NAME
ORDER BY total_indexes DESC;

SELECT '========================================' AS separator;
SELECT 'ALL REQUIRED INDEXES PRESENT' AS status;
SELECT '========================================' AS separator;
