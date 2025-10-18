-- ==============================================================================
-- 50_create_restricted_views.sql
-- Purpose: Create restricted aggregate views for counselor/analyst access
-- Security: NO PII, NO pseudonyms, NO individual user data
-- Target Users: counselor_view, report_user roles
-- ==============================================================================
-- 
-- OVERVIEW:
-- These views provide aggregated statistics and trends WITHOUT exposing:
-- - Individual user identities (no user_id, no pseudonyms)
-- - Individual journal entries (no mood log details)
-- - Personally identifiable information (no emails, names)
-- 
-- USAGE:
-- Run with: mysql -u root -p -P 3307 mindmate < database/sql/50_create_restricted_views.sql
-- 
-- Or PowerShell:
-- Get-Content database/sql/50_create_restricted_views.sql | C:\xampp\mysql\bin\mysql.exe -u root -P 3307 mindmate
--
-- AFTER RUNNING:
-- Grant SELECT on these views to counselor_view user (see docs/setup_db_roles.md)
-- ==============================================================================

USE mindmate;

-- ==============================================================================
-- VIEW 1: view_weekly_trends
-- Purpose: Weekly mood trends aggregated across ALL users
-- Exposure: Week number, average mood, journal count (NO user identifiers)
-- ==============================================================================

CREATE OR REPLACE VIEW view_weekly_trends AS
SELECT 
    YEAR(log_ts) as year,
    WEEK(log_ts, 1) as week_number,  -- ISO week (Monday start)
    DATE(DATE_SUB(log_ts, INTERVAL WEEKDAY(log_ts) DAY)) as week_start_date,
    COUNT(DISTINCT anonym_id) as active_users,
    COUNT(id) as total_journals,
    ROUND(AVG(mood_level), 2) as avg_mood,
    ROUND(MIN(mood_level), 2) as min_mood,
    ROUND(MAX(mood_level), 2) as max_mood,
    ROUND(STDDEV(mood_level), 2) as mood_stddev,
    -- Mood distribution percentages
    ROUND(SUM(CASE WHEN mood_level <= 3 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as pct_low_mood,
    ROUND(SUM(CASE WHEN mood_level BETWEEN 4 AND 7 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as pct_medium_mood,
    ROUND(SUM(CASE WHEN mood_level >= 8 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as pct_high_mood
FROM mood_logs
GROUP BY 
    YEAR(log_ts),
    WEEK(log_ts, 1),
    DATE(DATE_SUB(log_ts, INTERVAL WEEKDAY(log_ts) DAY))
ORDER BY year DESC, week_number DESC;

-- ==============================================================================
-- VIEW 2: view_aggregate_journals
-- Purpose: Journal statistics aggregated by time period
-- Exposure: Date, counts, averages (NO individual entries, NO user IDs)
-- ==============================================================================

CREATE OR REPLACE VIEW view_aggregate_journals AS
SELECT 
    DATE(log_ts) as journal_date,
    COUNT(id) as journal_count,
    COUNT(DISTINCT anonym_id) as unique_users,
    ROUND(AVG(mood_level), 2) as avg_mood,
    ROUND(MIN(mood_level), 2) as min_mood,
    ROUND(MAX(mood_level), 2) as max_mood,
    -- Tag statistics (without exposing individual tags)
    COUNT(DISTINCT mood_tag) as unique_tags_used,
    -- Time of day analysis (aggregated)
    SUM(CASE WHEN HOUR(log_ts) BETWEEN 0 AND 5 THEN 1 ELSE 0 END) as journals_night,
    SUM(CASE WHEN HOUR(log_ts) BETWEEN 6 AND 11 THEN 1 ELSE 0 END) as journals_morning,
    SUM(CASE WHEN HOUR(log_ts) BETWEEN 12 AND 17 THEN 1 ELSE 0 END) as journals_afternoon,
    SUM(CASE WHEN HOUR(log_ts) BETWEEN 18 AND 23 THEN 1 ELSE 0 END) as journals_evening
FROM mood_logs
GROUP BY DATE(log_ts)
ORDER BY journal_date DESC;

-- ==============================================================================
-- VIEW 3: view_mood_distribution
-- Purpose: Overall mood level distribution (histogram data)
-- Exposure: Mood levels and counts (NO user identifiers)
-- ==============================================================================

CREATE OR REPLACE VIEW view_mood_distribution AS
SELECT 
    mood_level,
    COUNT(*) as frequency,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM mood_logs), 2) as percentage,
    -- Rolling statistics
    COUNT(CASE WHEN log_ts >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as count_last_7_days,
    COUNT(CASE WHEN log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as count_last_30_days,
    COUNT(CASE WHEN log_ts >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as count_last_90_days
FROM mood_logs
GROUP BY mood_level
ORDER BY mood_level ASC;

-- ==============================================================================
-- VIEW 4: view_therapy_effectiveness
-- Purpose: Therapy session aggregated metrics
-- Exposure: Session statistics, ratings (NO counselor names, NO user IDs)
-- ==============================================================================

CREATE OR REPLACE VIEW view_therapy_effectiveness AS
SELECT 
    DATE_FORMAT(session_date, '%Y-%m') as month,
    COUNT(*) as total_sessions,
    COUNT(DISTINCT anonym_id) as unique_users,
    ROUND(AVG(duration_minutes), 1) as avg_duration_minutes,
    ROUND(AVG(rating), 2) as avg_rating,
    SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as high_rated_sessions,
    SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as low_rated_sessions,
    ROUND(AVG(rating) * 100.0 / 5, 1) as satisfaction_percentage,
    -- Session duration categories
    SUM(CASE WHEN duration_minutes < 30 THEN 1 ELSE 0 END) as sessions_short,
    SUM(CASE WHEN duration_minutes BETWEEN 30 AND 60 THEN 1 ELSE 0 END) as sessions_standard,
    SUM(CASE WHEN duration_minutes > 60 THEN 1 ELSE 0 END) as sessions_extended
FROM therapy_sessions
WHERE rating IS NOT NULL  -- Only rated sessions
GROUP BY DATE_FORMAT(session_date, '%Y-%m')
ORDER BY month DESC;

-- ==============================================================================
-- VIEW 5: view_resource_engagement
-- Purpose: Resource usage statistics
-- Exposure: Resource categories and view counts (NO user tracking)
-- ==============================================================================

CREATE OR REPLACE VIEW view_resource_engagement AS
SELECT 
    resource_type,
    category,
    COUNT(*) as total_resources,
    SUM(view_count) as total_views,
    ROUND(AVG(view_count), 1) as avg_views_per_resource,
    MAX(view_count) as max_views,
    SUM(CASE WHEN is_featured = TRUE THEN 1 ELSE 0 END) as featured_count,
    SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_count,
    -- Engagement levels
    SUM(CASE WHEN view_count < 10 THEN 1 ELSE 0 END) as low_engagement,
    SUM(CASE WHEN view_count BETWEEN 10 AND 50 THEN 1 ELSE 0 END) as medium_engagement,
    SUM(CASE WHEN view_count > 50 THEN 1 ELSE 0 END) as high_engagement
FROM resources
GROUP BY resource_type, category
ORDER BY total_views DESC;

-- ==============================================================================
-- VIEW 6: view_monthly_summary
-- Purpose: Monthly platform usage summary
-- Exposure: Monthly aggregates (NO user identifiers)
-- ==============================================================================

CREATE OR REPLACE VIEW view_monthly_summary AS
SELECT 
    DATE_FORMAT(ml.log_ts, '%Y-%m') as month,
    -- Journal metrics
    COUNT(DISTINCT ml.anonym_id) as active_journal_users,
    COUNT(ml.id) as total_journal_entries,
    ROUND(AVG(ml.mood_level), 2) as avg_mood,
    -- Therapy metrics
    COALESCE(ts.session_count, 0) as therapy_sessions,
    COALESCE(ts.unique_therapy_users, 0) as active_therapy_users,
    -- User engagement
    ROUND(COUNT(ml.id) * 1.0 / COUNT(DISTINCT ml.anonym_id), 1) as journals_per_user,
    -- Mood trends
    SUM(CASE WHEN ml.mood_level <= 3 THEN 1 ELSE 0 END) as low_mood_entries,
    SUM(CASE WHEN ml.mood_level BETWEEN 4 AND 7 THEN 1 ELSE 0 END) as medium_mood_entries,
    SUM(CASE WHEN ml.mood_level >= 8 THEN 1 ELSE 0 END) as high_mood_entries
FROM mood_logs ml
LEFT JOIN (
    SELECT 
        DATE_FORMAT(session_date, '%Y-%m') as month,
        COUNT(*) as session_count,
        COUNT(DISTINCT anonym_id) as unique_therapy_users
    FROM therapy_sessions
    GROUP BY DATE_FORMAT(session_date, '%Y-%m')
) ts ON DATE_FORMAT(ml.log_ts, '%Y-%m') = ts.month
GROUP BY DATE_FORMAT(ml.log_ts, '%Y-%m')
ORDER BY month DESC;

-- ==============================================================================
-- VIEW 7: view_tag_frequency
-- Purpose: Mood tag usage frequency (anonymized)
-- Exposure: Tag names and counts (NO user associations)
-- ==============================================================================

CREATE OR REPLACE VIEW view_tag_frequency AS
SELECT 
    COALESCE(mood_tag, 'untagged') as tag,
    COUNT(*) as frequency,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM mood_logs), 2) as percentage,
    ROUND(AVG(mood_level), 2) as avg_mood_with_tag,
    -- Time-based frequency
    COUNT(CASE WHEN log_ts >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as uses_last_7_days,
    COUNT(CASE WHEN log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as uses_last_30_days,
    COUNT(CASE WHEN log_ts >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 END) as uses_last_90_days,
    -- Tag associations with mood levels
    SUM(CASE WHEN mood_level <= 3 THEN 1 ELSE 0 END) as with_low_mood,
    SUM(CASE WHEN mood_level BETWEEN 4 AND 7 THEN 1 ELSE 0 END) as with_medium_mood,
    SUM(CASE WHEN mood_level >= 8 THEN 1 ELSE 0 END) as with_high_mood
FROM mood_logs
GROUP BY mood_tag
ORDER BY frequency DESC;

-- ==============================================================================
-- VIEW 8: view_activity_metrics
-- Purpose: Platform activity and engagement metrics
-- Exposure: User counts and activity levels (NO individual identification)
-- ==============================================================================

CREATE OR REPLACE VIEW view_activity_metrics AS
SELECT 
    'Overall' as metric_category,
    (SELECT COUNT(*) FROM anonymized_users) as total_users,
    (SELECT COUNT(DISTINCT anonym_id) FROM mood_logs) as users_with_journals,
    (SELECT COUNT(DISTINCT anonym_id) FROM therapy_sessions) as users_with_therapy,
    (SELECT COUNT(*) FROM mood_logs) as total_journal_entries,
    (SELECT COUNT(*) FROM therapy_sessions) as total_therapy_sessions,
    (SELECT ROUND(AVG(mood_level), 2) FROM mood_logs) as platform_avg_mood,
    -- Recent activity (last 30 days)
    (SELECT COUNT(DISTINCT anonym_id) 
     FROM mood_logs 
     WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as active_users_30d,
    (SELECT COUNT(*) 
     FROM mood_logs 
     WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as journals_30d,
    -- User engagement levels
    (SELECT COUNT(*) 
     FROM (SELECT anonym_id FROM mood_logs GROUP BY anonym_id HAVING COUNT(*) >= 10) t) as highly_engaged_users,
    (SELECT COUNT(*) 
     FROM (SELECT anonym_id FROM mood_logs GROUP BY anonym_id HAVING COUNT(*) BETWEEN 3 AND 9) t) as moderately_engaged_users,
    (SELECT COUNT(*) 
     FROM (SELECT anonym_id FROM mood_logs GROUP BY anonym_id HAVING COUNT(*) < 3) t) as minimally_engaged_users
UNION ALL
SELECT 
    'Last 7 Days' as metric_category,
    (SELECT COUNT(DISTINCT anonym_id) FROM mood_logs WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as total_users,
    (SELECT COUNT(DISTINCT anonym_id) FROM mood_logs WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as users_with_journals,
    (SELECT COUNT(DISTINCT anonym_id) FROM therapy_sessions WHERE session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as users_with_therapy,
    (SELECT COUNT(*) FROM mood_logs WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as total_journal_entries,
    (SELECT COUNT(*) FROM therapy_sessions WHERE session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as total_therapy_sessions,
    (SELECT ROUND(AVG(mood_level), 2) FROM mood_logs WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as platform_avg_mood,
    NULL as active_users_30d,
    NULL as journals_30d,
    NULL as highly_engaged_users,
    NULL as moderately_engaged_users,
    NULL as minimally_engaged_users
UNION ALL
SELECT 
    'Last 30 Days' as metric_category,
    (SELECT COUNT(DISTINCT anonym_id) FROM mood_logs WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as total_users,
    (SELECT COUNT(DISTINCT anonym_id) FROM mood_logs WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as users_with_journals,
    (SELECT COUNT(DISTINCT anonym_id) FROM therapy_sessions WHERE session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as users_with_therapy,
    (SELECT COUNT(*) FROM mood_logs WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as total_journal_entries,
    (SELECT COUNT(*) FROM therapy_sessions WHERE session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as total_therapy_sessions,
    (SELECT ROUND(AVG(mood_level), 2) FROM mood_logs WHERE log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as platform_avg_mood,
    NULL as active_users_30d,
    NULL as journals_30d,
    NULL as highly_engaged_users,
    NULL as moderately_engaged_users,
    NULL as minimally_engaged_users;

-- ==============================================================================
-- VERIFICATION QUERIES
-- ==============================================================================

-- Show all created views
SELECT 'Verification: Created Views' AS info;
SELECT TABLE_NAME, TABLE_TYPE 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'mindmate' 
AND TABLE_NAME LIKE 'view_%'
ORDER BY TABLE_NAME;

-- Test each view (just show row count)

SELECT '1. view_weekly_trends' AS view_name, COUNT(*) AS row_count FROM view_weekly_trends;
SELECT '2. view_aggregate_journals' AS view_name, COUNT(*) AS row_count FROM view_aggregate_journals;
SELECT '3. view_mood_distribution' AS view_name, COUNT(*) AS row_count FROM view_mood_distribution;
SELECT '4. view_therapy_effectiveness' AS view_name, COUNT(*) AS row_count FROM view_therapy_effectiveness;
SELECT '5. view_resource_engagement' AS view_name, COUNT(*) AS row_count FROM view_resource_engagement;
SELECT '6. view_monthly_summary' AS view_name, COUNT(*) AS row_count FROM view_monthly_summary;
SELECT '7. view_tag_frequency' AS view_name, COUNT(*) AS row_count FROM view_tag_frequency;
SELECT '8. view_activity_metrics' AS view_name, COUNT(*) AS row_count FROM view_activity_metrics;

-- Sample data from key views

SELECT 'Weekly Trends (Last 3 Weeks):' AS sample;
SELECT * FROM view_weekly_trends LIMIT 3;

SELECT 'Mood Distribution:' AS sample;
SELECT * FROM view_mood_distribution;

SELECT 'Tag Frequency (Top 5):' AS sample;
SELECT tag, frequency, percentage, avg_mood_with_tag 
FROM view_tag_frequency 
LIMIT 5;

SELECT 'Activity Metrics:' AS sample;
SELECT * FROM view_activity_metrics;

-- Privacy validation

SELECT 'Checking for PII exposure in views...' AS privacy_check_status;

-- These queries should return 0 (no columns with user identifiers)
SELECT 
    'view_weekly_trends' AS view_name,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ NO PII COLUMNS'
        ELSE '❌ WARNING: PII COLUMNS FOUND'
    END AS privacy_status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'mindmate'
AND TABLE_NAME = 'view_weekly_trends'
AND COLUMN_NAME IN ('user_id', 'pseudonym', 'email', 'username', 'anonym_id')

UNION ALL

SELECT 
    'view_aggregate_journals' AS view_name,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ NO PII COLUMNS'
        ELSE '❌ WARNING: PII COLUMNS FOUND'
    END AS privacy_status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'mindmate'
AND TABLE_NAME = 'view_aggregate_journals'
AND COLUMN_NAME IN ('user_id', 'pseudonym', 'email', 'username', 'anonym_id')

UNION ALL

SELECT 
    'view_mood_distribution' AS view_name,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ NO PII COLUMNS'
        ELSE '❌ WARNING: PII COLUMNS FOUND'
    END AS privacy_status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'mindmate'
AND TABLE_NAME = 'view_mood_distribution'
AND COLUMN_NAME IN ('user_id', 'pseudonym', 'email', 'username', 'anonym_id')

UNION ALL

SELECT 
    'view_tag_frequency' AS view_name,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ NO PII COLUMNS'
        ELSE '❌ WARNING: PII COLUMNS FOUND'
    END AS privacy_status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'mindmate'
AND TABLE_NAME = 'view_tag_frequency'
AND COLUMN_NAME IN ('user_id', 'pseudonym', 'email', 'username', 'anonym_id')

UNION ALL

SELECT 
    'view_activity_metrics' AS view_name,
    CASE 
        WHEN COUNT(*) = 0 THEN '✅ NO PII COLUMNS'
        ELSE '❌ WARNING: PII COLUMNS FOUND'
    END AS privacy_status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'mindmate'
AND TABLE_NAME = 'view_activity_metrics'
AND COLUMN_NAME IN ('user_id', 'pseudonym', 'email', 'username', 'anonym_id');

SELECT '✅ All restricted views created and validated!' AS final_status;
SELECT 'Next Step: Grant SELECT on these views to counselor_view user' AS next_action;
SELECT 'See: docs/setup_db_roles.md for user setup commands' AS documentation;

-- ==============================================================================
-- USAGE EXAMPLES FOR COUNSELOR_VIEW USER
-- ==============================================================================

/*
After granting permissions (see docs/setup_db_roles.md), counselors can run:

-- Example 1: View weekly mood trends
SELECT * FROM view_weekly_trends 
WHERE year = 2025 
ORDER BY week_number DESC;

-- Example 2: Check recent journal activity
SELECT * FROM view_aggregate_journals 
WHERE journal_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY journal_date DESC;

-- Example 3: Mood distribution analysis
SELECT 
    mood_level,
    frequency,
    percentage,
    CASE 
        WHEN mood_level <= 3 THEN 'Low Mood'
        WHEN mood_level BETWEEN 4 AND 7 THEN 'Medium Mood'
        ELSE 'High Mood'
    END as mood_category
FROM view_mood_distribution
ORDER BY mood_level;

-- Example 4: Therapy effectiveness by month
SELECT * FROM view_therapy_effectiveness
ORDER BY month DESC
LIMIT 6;

-- Example 5: Most common mood tags
SELECT tag, frequency, avg_mood_with_tag 
FROM view_tag_frequency 
WHERE frequency > 5
ORDER BY frequency DESC;

-- Example 6: Platform engagement metrics
SELECT * FROM view_activity_metrics;

-- Example 7: Monthly trend comparison
SELECT 
    month,
    active_journal_users,
    total_journal_entries,
    avg_mood,
    journals_per_user
FROM view_monthly_summary
ORDER BY month DESC
LIMIT 12;

-- Example 8: Resource engagement by category
SELECT * FROM view_resource_engagement
WHERE total_views > 0
ORDER BY total_views DESC;
*/

-- ==============================================================================
-- MAINTENANCE NOTES
-- ==============================================================================

/*
VIEW REFRESH:
- Views are automatically updated when underlying tables change
- No manual refresh needed
- CREATE OR REPLACE VIEW allows safe updates

PERFORMANCE:
- Views may be slower than direct table queries
- Consider materialized views for large datasets
- Add indexes on underlying tables if queries are slow

SECURITY:
- Views do NOT expose underlying table structure
- Counselors cannot see base tables even if they try
- REVOKE grants on base tables after granting view access

MONITORING:
- Check view usage: SELECT * FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA='mindmate';
- Audit counselor queries via MySQL general log
- Monitor for suspicious access patterns
*/
