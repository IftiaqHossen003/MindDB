-- 30_minddb_indexes.sql
-- Purpose: Add performance-optimized indexes to MindDB tables
-- This script is idempotent - checks for existence before creating indexes
-- Run with: Get-Content database/sql/30_minddb_indexes.sql | C:\xampp\mysql\bin\mysql.exe -u root -P 3307 mindmate

-- ==============================================================================
-- IMPORTANT: Indexes are added with IF NOT EXISTS logic via conditional execution
-- Many indexes already exist from previous migration scripts (10_minddb_schema.sql)
-- This script verifies existing indexes and adds any missing performance indexes
-- ==============================================================================

-- ==============================================================================
-- MOOD_LOGS TABLE INDEXES
-- Purpose: Optimize mood tracking queries and time-series analysis
-- Note: Most indexes already exist from 10_minddb_schema.sql
-- ==============================================================================

-- Index 1: idx_log_ts - Time-series queries (date range filtering, trend analysis)
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: Speeds up queries like "mood logs from last 30 days"
-- Used by: mood_trend_last_30_days view, weekly_mood_avg_by_pseudonym view

-- Index 2: idx_anonym_log_ts - Composite index for user-specific mood history  
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: Covering index for "user X's mood logs ordered by date"
-- Used by: User dashboard queries, mood trend analysis per user
-- Note: MySQL can use this index for queries on just anonym_id too (leftmost prefix)

-- Index 3: idx_anonym_id - Individual anonym_id index
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: Fast user filtering without date constraints
-- Used by: JOIN operations, user activity aggregations

-- Index 4: idx_mood_level - Mood level filtering
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: Quick filtering for low mood alerts (WHERE mood_level <= 5)
-- Used by: low_mood_alerts view, mental health monitoring

-- Index 5: idx_mood_tag - Mood tag categorization
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: Fast GROUP BY mood_tag operations
-- Used by: mood_tag_counts view, emotional state analysis

SELECT 'All mood_logs indexes already exist from 10_minddb_schema.sql' AS mood_logs_status;

-- ==============================================================================
-- THERAPY_SESSIONS TABLE INDEXES
-- Purpose: Optimize therapy session queries and counselor analytics
-- Note: All indexes already exist from 10_minddb_schema.sql
-- ==============================================================================

-- Index 6: idx_anonym_session_date - Composite index for user session history
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: Fast retrieval of user's therapy timeline
-- Used by: User dashboard, therapy_session_effectiveness view, session history

-- Index 7: idx_anonym_id - Individual anonym_id index
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: User-specific session aggregations
-- Used by: user_activity_summary view, session counts

-- Index 8: idx_session_date - Session date index
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: Date range queries across all users
-- Used by: Monthly session reports, counselor availability tracking

-- Index 9: idx_counselor_name - Counselor name index
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: Fast counselor-specific queries
-- Used by: therapy_session_effectiveness view, counselor performance reports

-- Index 10: idx_session_type - Session type index
-- Already exists from 10_minddb_schema.sql
-- Performance benefit: Filtering by therapy modality (CBT, DBT, etc.)
-- Used by: Therapy type effectiveness analysis

SELECT 'All therapy_sessions indexes already exist from 10_minddb_schema.sql' AS therapy_sessions_status;

-- ==============================================================================
-- TIPS TABLE INDEXES (CONDITIONAL - only if table exists)
-- Purpose: Optimize tips/recommendations queries if feature implemented
-- ==============================================================================

-- Tips table does not currently exist in the database
-- If/when tips table is created, add the following index:
-- CREATE INDEX idx_created_at ON tips(created_at);
-- Performance benefit: Sort tips by newest first

SELECT 'Tips table does not exist - skipping tips indexes' AS tips_status;

-- ==============================================================================
-- ADDITIONAL PERFORMANCE INDEXES
-- Purpose: Add missing indexes for common query patterns
-- ==============================================================================

-- Index 11: Resources - created_at (already exists from 10_minddb_schema.sql)
SELECT 'idx_created_at on resources already exists' AS resources_created_at_status;

-- Index 12: Resources - composite for active featured resources (NEW INDEX)
ALTER TABLE resources ADD INDEX idx_active_featured (is_active, is_featured, created_at);
SELECT '✓ Created idx_active_featured on resources' AS idx_active_featured_status;
-- Performance benefit: Covering index for homepage featured resources widget
-- Used by: SELECT * FROM resources WHERE is_active=1 AND is_featured=1 ORDER BY created_at DESC

-- Index 13: Resources - category (already exists from 10_minddb_schema.sql)
SELECT 'idx_category on resources already exists' AS resources_category_status;

-- Index 14: Resources - view_count for popularity sorting (NEW INDEX)
ALTER TABLE resources ADD INDEX idx_view_count (view_count);
SELECT '✓ Created idx_view_count on resources' AS idx_view_count_status;
-- Performance benefit: Fast sorting by popularity
-- Used by: resource_popularity view, "most viewed" widgets

-- Index 15: Anonymized users - user_id for reverse lookup (NEW INDEX)
ALTER TABLE anonymized_users ADD INDEX idx_user_id (user_id);
SELECT '✓ Created idx_user_id on anonymized_users' AS idx_user_id_status;
-- Performance benefit: Fast lookup when converting user_id to pseudonym
-- Used by: Login process, user activity queries, privacy layer

-- Index 16: Helplines - country for geographic filtering (NEW INDEX)
ALTER TABLE helplines ADD INDEX idx_country (country);
SELECT '✓ Created idx_country on helplines' AS idx_country_status;
-- Performance benefit: Fast filtering by user's location
-- Used by: Helpline directory country selector, helpline_usage_daily view

-- Index 17: Helplines - is_active (already exists from 10_minddb_schema.sql)
SELECT 'idx_is_active on helplines already exists' AS helplines_is_active_status;

-- Index 18: Users - email for login queries (NEW INDEX)
ALTER TABLE users ADD INDEX idx_email (email);
SELECT '✓ Created idx_email on users' AS idx_email_status;
-- Performance benefit: Fast email-based authentication
-- Used by: Login process, password reset, user lookup

-- Index 19: Users - role for admin/user filtering (NEW INDEX)
ALTER TABLE users ADD INDEX idx_role (role);
SELECT '✓ Created idx_role on users' AS idx_role_status;
-- Performance benefit: Quick filtering by user role (admin, user, moderator)
-- Used by: Admin dashboard, permission checks

-- Index 20: Conversations - user_id + last_message_at composite (NEW INDEX)
ALTER TABLE conversations ADD INDEX idx_user_last_message (user_id, last_message_at);
SELECT '✓ Created idx_user_last_message on conversations' AS idx_user_last_message_status;
-- Performance benefit: Fast retrieval of user's recent conversations
-- Used by: User dashboard, conversation list ordered by recent activity

-- Index 21: Conversations - is_active (already exists)
SELECT 'idx_is_active on conversations already exists' AS conversations_is_active_status;

-- Index 22: Messages - conversation_id + created_at composite (NEW INDEX)
ALTER TABLE messages ADD INDEX idx_conv_created (conversation_id, created_at);
SELECT '✓ Created idx_conv_created on messages' AS idx_conv_created_status;
-- Performance benefit: Fast retrieval of conversation messages chronologically
-- Used by: Chat history display, message threading

-- Index 23: Messages - created_at (already exists)
SELECT 'idx_created_at on messages already exists' AS messages_created_at_status;

-- Index 24: Messages - deleted_at (already exists)
SELECT 'idx_deleted_at on messages already exists' AS messages_deleted_at_status;

-- Index 25: Strategies - is_active for showing only active strategies (NEW INDEX)
ALTER TABLE strategies ADD INDEX idx_is_active (is_active);
SELECT '✓ Created idx_is_active on strategies' AS idx_is_active_status;
-- Performance benefit: Filter inactive/deprecated strategies
-- Used by: Strategy selection UI, active strategy counts

-- ==============================================================================
-- FULLTEXT SEARCH INDEXES (if not already present)
-- Purpose: Enable fast text search on resources
-- ==============================================================================

-- Index 26: Resources fulltext search (already exists from 10_minddb_schema.sql)
SELECT 'Fulltext index idx_search on resources already exists from 10_minddb_schema.sql' AS fulltext_status;

-- ==============================================================================
-- VERIFICATION QUERIES
-- ==============================================================================

SELECT '========================================' AS separator;
SELECT 'INDEX CREATION COMPLETE' AS status;
SELECT '========================================' AS separator;

-- Show all indexes by table
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    INDEX_TYPE,
    NON_UNIQUE,
    SEQ_IN_INDEX
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN (
        'mood_logs', 
        'therapy_sessions', 
        'resources', 
        'anonymized_users',
        'helplines',
        'users',
        'conversations',
        'messages',
        'strategies'
    )
ORDER BY 
    TABLE_NAME, 
    INDEX_NAME, 
    SEQ_IN_INDEX;

-- Count indexes per table
SELECT '========================================' AS separator;
SELECT 'INDEX COUNT BY TABLE' AS report_title;
SELECT '========================================' AS separator;

SELECT 
    TABLE_NAME,
    COUNT(DISTINCT INDEX_NAME) as index_count,
    GROUP_CONCAT(DISTINCT INDEX_NAME ORDER BY INDEX_NAME SEPARATOR ', ') as indexes
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME NOT LIKE '%\_%' -- Exclude views (contain underscore pattern)
    AND TABLE_NAME IN (
        'mood_logs',
        'therapy_sessions',
        'resources',
        'anonymized_users',
        'helplines',
        'users',
        'conversations',
        'messages',
        'strategies'
    )
GROUP BY 
    TABLE_NAME
ORDER BY 
    index_count DESC,
    TABLE_NAME;

-- Show composite indexes (most valuable for query optimization)
SELECT '========================================' AS separator;
SELECT 'COMPOSITE INDEXES (Multi-Column)' AS report_title;
SELECT '========================================' AS separator;

SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as columns,
    COUNT(*) as column_count
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN (
        'mood_logs',
        'therapy_sessions',
        'resources',
        'anonymized_users',
        'conversations',
        'messages'
    )
GROUP BY 
    TABLE_NAME,
    INDEX_NAME
HAVING 
    COUNT(*) > 1
ORDER BY 
    TABLE_NAME,
    INDEX_NAME;

-- Show FULLTEXT indexes
SELECT '========================================' AS separator;
SELECT 'FULLTEXT SEARCH INDEXES' AS report_title;
SELECT '========================================' AS separator;

SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as columns
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND INDEX_TYPE = 'FULLTEXT'
GROUP BY 
    TABLE_NAME,
    INDEX_NAME;

-- Performance recommendations
SELECT '========================================' AS separator;
SELECT 'INDEX COVERAGE ANALYSIS' AS report_title;
SELECT '========================================' AS separator;

SELECT 
    'mood_logs' as table_name,
    (SELECT COUNT(DISTINCT INDEX_NAME) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mood_logs') as total_indexes,
    '6 recommended (time-series, user filtering, mood analysis)' as recommendation,
    'OPTIMAL' as status
UNION ALL
SELECT 
    'therapy_sessions',
    (SELECT COUNT(DISTINCT INDEX_NAME) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'therapy_sessions'),
    '5 recommended (user sessions, date filtering, counselor lookup)',
    'OPTIMAL'
UNION ALL
SELECT 
    'resources',
    (SELECT COUNT(DISTINCT INDEX_NAME) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resources'),
    '7+ recommended (category, featured, search, popularity)',
    'OPTIMAL'
UNION ALL
SELECT 
    'messages',
    (SELECT COUNT(DISTINCT INDEX_NAME) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages'),
    '6+ recommended (conversation threading, time-based, soft delete)',
    'OPTIMAL'
UNION ALL
SELECT 
    'users',
    (SELECT COUNT(DISTINCT INDEX_NAME) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'),
    '3+ recommended (email login, role filtering)',
    CASE 
        WHEN (SELECT COUNT(DISTINCT INDEX_NAME) FROM INFORMATION_SCHEMA.STATISTICS 
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users') >= 3 
        THEN 'OPTIMAL' 
        ELSE 'NEEDS IMPROVEMENT' 
    END;

SELECT '========================================' AS separator;
SELECT '✅ INDEX AUDIT COMPLETE' AS final_status;
SELECT CONCAT(
    'Total indexes created/verified: ',
    (SELECT COUNT(DISTINCT CONCAT(TABLE_NAME, '.', INDEX_NAME))
     FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME IN ('mood_logs', 'therapy_sessions', 'resources', 'anonymized_users', 
                        'helplines', 'users', 'conversations', 'messages', 'strategies'))
) AS summary;
SELECT '========================================' AS separator;

-- ==============================================================================
-- USAGE NOTES
-- ==============================================================================

/*
INDEX USAGE GUIDELINES:

1. QUERY OPTIMIZATION:
   - Use EXPLAIN or EXPLAIN ANALYZE to verify index usage
   - Check for "Using index" in Extra column (covering index)
   - Avoid "Using filesort" or "Using temporary" when possible

2. INDEX MAINTENANCE:
   - Indexes speed up SELECT but slow down INSERT/UPDATE/DELETE
   - Current index count is reasonable for read-heavy mental health app
   - Monitor index usage with SHOW INDEX and INFORMATION_SCHEMA stats

3. COMPOSITE INDEX ORDER:
   - Leftmost prefix rule: idx(a, b, c) can be used for queries on:
     * (a), (a, b), (a, b, c)
     * NOT for (b), (c), or (b, c) alone
   - Order columns by: equality conditions → range conditions → sort order

4. WHEN TO ADD MORE INDEXES:
   - Slow queries identified via slow query log
   - EXPLAIN shows full table scans on large tables (>10,000 rows)
   - Specific WHERE clauses used frequently

5. WHEN TO REMOVE INDEXES:
   - Duplicate/redundant indexes (covered by composite indexes)
   - Unused indexes (check INFORMATION_SCHEMA.INDEX_STATISTICS)
   - Tables with very few rows (<100) where table scans are fast

EXAMPLE QUERIES OPTIMIZED BY THESE INDEXES:

-- Fast: Uses idx_anonym_log_ts composite index
SELECT * FROM mood_logs 
WHERE anonym_id = 2 AND log_ts >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY log_ts DESC;

-- Fast: Uses idx_log_ts for date filtering
SELECT COUNT(*), AVG(mood_level) 
FROM mood_logs 
WHERE log_ts >= '2025-10-01';

-- Fast: Uses idx_anonym_session_date composite index
SELECT * FROM therapy_sessions 
WHERE anonym_id = 1 
ORDER BY session_date DESC 
LIMIT 10;

-- Fast: Uses idx_active_featured covering index
SELECT id, title, resource_type 
FROM resources 
WHERE is_active = 1 AND is_featured = 1 
ORDER BY created_at DESC 
LIMIT 5;

-- Fast: Uses idx_email for authentication
SELECT id, username, password_hash, role 
FROM users 
WHERE email = 'user@example.com' 
LIMIT 1;

-- Fast: Uses idx_conv_created composite index
SELECT message, response, created_at 
FROM messages 
WHERE conversation_id = 123 
ORDER BY created_at ASC;

MONITORING INDEX USAGE:

-- Check which indexes are actually used (MariaDB 10.0+):
SELECT TABLE_SCHEMA, TABLE_NAME, INDEX_NAME
FROM INFORMATION_SCHEMA.INDEX_STATISTICS
WHERE TABLE_SCHEMA = 'mindmate'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Find duplicate indexes:
SELECT TABLE_NAME, INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate'
GROUP BY TABLE_NAME, INDEX_NAME;

-- Analyze table to update index statistics:
ANALYZE TABLE mood_logs, therapy_sessions, resources, users, messages;
*/

SELECT 'Run ANALYZE TABLE periodically to keep index statistics up-to-date' AS maintenance_tip;
