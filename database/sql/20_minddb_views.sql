-- 20_minddb_views.sql
-- Purpose: Create analytical views for MindDB reporting and dashboard features
-- These views provide aggregated data for mood trends, tag analysis, helpline tracking, and user activity
-- Run with: Get-Content database/sql/20_minddb_views.sql | C:\xampp\mysql\bin\mysql.exe -u root -P 3307 mindmate

-- ==============================================================================
-- VIEW 1: weekly_mood_avg_by_pseudonym
-- Purpose: Calculate average mood per week per anonymized user for trend analysis
-- Use Case: Weekly mood tracking dashboard, trend identification, progress monitoring
-- ==============================================================================

CREATE OR REPLACE VIEW weekly_mood_avg_by_pseudonym AS
SELECT 
    au.pseudonym,
    au.anonym_id,
    YEAR(ml.log_ts) as year,
    WEEK(ml.log_ts, 1) as week_number,
    DATE(DATE_SUB(ml.log_ts, INTERVAL WEEKDAY(ml.log_ts) DAY)) as week_start_date,
    COUNT(ml.id) as mood_log_count,
    AVG(ml.mood_level) as avg_mood,
    MIN(ml.mood_level) as min_mood,
    MAX(ml.mood_level) as max_mood,
    STDDEV(ml.mood_level) as mood_stddev,
    GROUP_CONCAT(DISTINCT ml.mood_tag ORDER BY ml.mood_tag SEPARATOR ', ') as mood_tags
FROM 
    mood_logs ml
INNER JOIN 
    anonymized_users au ON ml.anonym_id = au.anonym_id
GROUP BY 
    au.pseudonym, 
    au.anonym_id, 
    YEAR(ml.log_ts), 
    WEEK(ml.log_ts, 1)
ORDER BY 
    au.pseudonym, 
    year DESC, 
    week_number DESC;

-- Example query:
-- SELECT * FROM weekly_mood_avg_by_pseudonym WHERE pseudonym = 'User_2' ORDER BY year DESC, week_number DESC LIMIT 4;

-- ==============================================================================
-- VIEW 2: mood_tag_counts
-- Purpose: Count occurrences of each mood tag across all users for analytics
-- Use Case: Identify most common emotional states, tag popularity, mental health trends
-- ==============================================================================

CREATE OR REPLACE VIEW mood_tag_counts AS
SELECT 
    ml.mood_tag as tag,
    COUNT(*) as cnt,
    AVG(ml.mood_level) as avg_mood_for_tag,
    MIN(ml.mood_level) as min_mood_for_tag,
    MAX(ml.mood_level) as max_mood_for_tag,
    COUNT(DISTINCT ml.anonym_id) as users_with_tag,
    DATE(MIN(ml.log_ts)) as first_occurrence,
    DATE(MAX(ml.log_ts)) as last_occurrence,
    DATEDIFF(MAX(ml.log_ts), MIN(ml.log_ts)) + 1 as days_span
FROM 
    mood_logs ml
WHERE 
    ml.mood_tag IS NOT NULL
GROUP BY 
    ml.mood_tag
ORDER BY 
    cnt DESC, 
    avg_mood_for_tag ASC;

-- Example query:
-- SELECT tag, cnt, avg_mood_for_tag FROM mood_tag_counts WHERE cnt >= 2 ORDER BY cnt DESC;

-- ==============================================================================
-- VIEW 3: helpline_usage_daily
-- Purpose: Track helpline information availability (sample view since helpline_logs not implemented)
-- Use Case: Helpline directory statistics, resource availability tracking
-- Note: This is a placeholder view showing helpline availability by country
--       When helpline_logs table is created, replace with actual usage tracking
-- ==============================================================================

CREATE OR REPLACE VIEW helpline_usage_daily AS
SELECT 
    h.country,
    h.name as helpline_name,
    h.phone,
    h.hours as operating_hours,
    h.is_active,
    CASE 
        WHEN h.hours LIKE '%24/7%' THEN 'Always Available'
        WHEN h.hours LIKE '%24%' THEN 'Always Available'
        ELSE 'Limited Hours'
    END as availability_type,
    h.created_at as registered_date,
    h.updated_at as last_updated
FROM 
    helplines h
WHERE 
    h.is_active = TRUE
ORDER BY 
    h.country, 
    h.name;

-- Example query:
-- SELECT country, COUNT(*) as helpline_count FROM helpline_usage_daily GROUP BY country;

-- Future implementation when helpline_logs table exists:
/*
CREATE TABLE helpline_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anonym_id INT,
    helpline_id INT,
    contact_date DATE,
    contact_type ENUM('call', 'text', 'chat', 'email'),
    FOREIGN KEY (anonym_id) REFERENCES anonymized_users(anonym_id),
    FOREIGN KEY (helpline_id) REFERENCES helplines(id)
);

CREATE OR REPLACE VIEW helpline_usage_daily AS
SELECT 
    DATE(hl.contact_date) as usage_date,
    h.name as helpline_name,
    h.country,
    COUNT(*) as daily_contacts,
    COUNT(DISTINCT hl.anonym_id) as unique_users,
    hl.contact_type
FROM 
    helpline_logs hl
INNER JOIN 
    helplines h ON hl.helpline_id = h.id
GROUP BY 
    DATE(hl.contact_date), 
    h.name, 
    h.country,
    hl.contact_type
ORDER BY 
    usage_date DESC, 
    daily_contacts DESC;
*/

-- ==============================================================================
-- VIEW 4: user_activity_summary
-- Purpose: Aggregate all user activities (mood logs, therapy sessions) per anonymized user
-- Use Case: User engagement dashboard, activity tracking, user insights
-- ==============================================================================

CREATE OR REPLACE VIEW user_activity_summary AS
SELECT 
    au.anonym_id,
    au.pseudonym,
    au.user_id,
    u.username as actual_username,
    u.role as user_role,
    
    -- Mood log statistics
    COUNT(DISTINCT ml.id) as total_mood_logs,
    AVG(ml.mood_level) as overall_avg_mood,
    MIN(ml.mood_level) as lowest_mood_recorded,
    MAX(ml.mood_level) as highest_mood_recorded,
    DATE(MIN(ml.log_ts)) as first_mood_log_date,
    DATE(MAX(ml.log_ts)) as last_mood_log_date,
    DATEDIFF(MAX(ml.log_ts), MIN(ml.log_ts)) + 1 as mood_tracking_days,
    
    -- Therapy session statistics
    COUNT(DISTINCT ts.id) as total_therapy_sessions,
    SUM(ts.duration_minutes) as total_therapy_minutes,
    AVG(ts.duration_minutes) as avg_session_duration,
    AVG(ts.rating) as avg_session_rating,
    DATE(MIN(ts.session_date)) as first_therapy_session,
    DATE(MAX(ts.session_date)) as last_therapy_session,
    
    -- Overall activity metrics
    GREATEST(
        COALESCE(DATE(MAX(ml.log_ts)), '1970-01-01'),
        COALESCE(DATE(MAX(ts.session_date)), '1970-01-01')
    ) as last_activity_date,
    
    DATEDIFF(
        GREATEST(
            COALESCE(DATE(MAX(ml.log_ts)), '1970-01-01'),
            COALESCE(DATE(MAX(ts.session_date)), '1970-01-01')
        ),
        LEAST(
            COALESCE(DATE(MIN(ml.log_ts)), '9999-12-31'),
            COALESCE(DATE(MIN(ts.session_date)), '9999-12-31')
        )
    ) + 1 as total_active_days,
    
    -- Engagement level categorization
    CASE 
        WHEN COUNT(DISTINCT ml.id) >= 20 AND COUNT(DISTINCT ts.id) >= 5 THEN 'Highly Engaged'
        WHEN COUNT(DISTINCT ml.id) >= 10 OR COUNT(DISTINCT ts.id) >= 3 THEN 'Moderately Engaged'
        WHEN COUNT(DISTINCT ml.id) >= 5 OR COUNT(DISTINCT ts.id) >= 1 THEN 'Lightly Engaged'
        ELSE 'New User'
    END as engagement_level,
    
    au.created_at as account_created_date
    
FROM 
    anonymized_users au
LEFT JOIN 
    users u ON au.user_id = u.id
LEFT JOIN 
    mood_logs ml ON au.anonym_id = ml.anonym_id
LEFT JOIN 
    therapy_sessions ts ON au.anonym_id = ts.anonym_id
GROUP BY 
    au.anonym_id, 
    au.pseudonym, 
    au.user_id, 
    u.username,
    u.role,
    au.created_at
ORDER BY 
    last_activity_date DESC;

-- Example queries:
-- SELECT pseudonym, total_mood_logs, total_therapy_sessions, engagement_level FROM user_activity_summary;
-- SELECT pseudonym, overall_avg_mood, mood_tracking_days FROM user_activity_summary WHERE total_mood_logs > 0;

-- ==============================================================================
-- ADDITIONAL ANALYTICAL VIEWS
-- ==============================================================================

-- VIEW 5: mood_trend_last_30_days
-- Purpose: Recent mood trends for all active users (last 30 days)
-- Use Case: Current mental health status dashboard, recent activity monitoring

CREATE OR REPLACE VIEW mood_trend_last_30_days AS
SELECT 
    au.pseudonym,
    au.anonym_id,
    DATE(ml.log_ts) as log_date,
    ml.mood_level,
    ml.mood_tag,
    ml.notes,
    DATEDIFF(CURDATE(), DATE(ml.log_ts)) as days_ago,
    ROW_NUMBER() OVER (PARTITION BY au.anonym_id ORDER BY ml.log_ts DESC) as entry_rank
FROM 
    mood_logs ml
INNER JOIN 
    anonymized_users au ON ml.anonym_id = au.anonym_id
WHERE 
    ml.log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY 
    au.pseudonym, 
    ml.log_ts DESC;

-- Example query:
-- SELECT pseudonym, log_date, mood_level, mood_tag FROM mood_trend_last_30_days WHERE entry_rank <= 7;

-- VIEW 6: low_mood_alerts
-- Purpose: Identify users with concerning low mood patterns
-- Use Case: Mental health monitoring, early intervention alerts, support outreach

CREATE OR REPLACE VIEW low_mood_alerts AS
SELECT 
    au.pseudonym,
    au.anonym_id,
    COUNT(*) as low_mood_count,
    AVG(ml.mood_level) as avg_low_mood,
    MIN(ml.mood_level) as lowest_mood,
    DATE(MIN(ml.log_ts)) as first_low_mood_date,
    DATE(MAX(ml.log_ts)) as most_recent_low_mood_date,
    DATEDIFF(MAX(ml.log_ts), MIN(ml.log_ts)) + 1 as low_mood_span_days,
    GROUP_CONCAT(DISTINCT ml.mood_tag ORDER BY ml.mood_tag SEPARATOR ', ') as concerning_tags,
    CASE 
        WHEN AVG(ml.mood_level) <= 3 THEN 'Critical'
        WHEN AVG(ml.mood_level) <= 4 THEN 'High Concern'
        WHEN AVG(ml.mood_level) <= 5 THEN 'Moderate Concern'
        ELSE 'Monitoring'
    END as alert_level
FROM 
    mood_logs ml
INNER JOIN 
    anonymized_users au ON ml.anonym_id = au.anonym_id
WHERE 
    ml.mood_level <= 5
    AND ml.log_ts >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
GROUP BY 
    au.pseudonym, 
    au.anonym_id
HAVING 
    COUNT(*) >= 2
ORDER BY 
    avg_low_mood ASC, 
    low_mood_count DESC;

-- Example query:
-- SELECT pseudonym, low_mood_count, avg_low_mood, alert_level FROM low_mood_alerts;

-- VIEW 7: therapy_session_effectiveness
-- Purpose: Analyze therapy session ratings and outcomes
-- Use Case: Counselor performance, treatment effectiveness, session quality monitoring

CREATE OR REPLACE VIEW therapy_session_effectiveness AS
SELECT 
    ts.counselor_name,
    ts.session_type,
    COUNT(*) as total_sessions,
    AVG(ts.rating) as avg_rating,
    AVG(ts.duration_minutes) as avg_duration,
    COUNT(DISTINCT ts.anonym_id) as unique_clients,
    MIN(ts.session_date) as first_session_date,
    MAX(ts.session_date) as last_session_date,
    SUM(ts.duration_minutes) as total_counseling_minutes,
    CASE 
        WHEN AVG(ts.rating) >= 4.5 THEN 'Excellent'
        WHEN AVG(ts.rating) >= 4.0 THEN 'Very Good'
        WHEN AVG(ts.rating) >= 3.5 THEN 'Good'
        WHEN AVG(ts.rating) >= 3.0 THEN 'Satisfactory'
        ELSE 'Needs Improvement'
    END as performance_level
FROM 
    therapy_sessions ts
WHERE 
    ts.rating IS NOT NULL
GROUP BY 
    ts.counselor_name, 
    ts.session_type
ORDER BY 
    avg_rating DESC, 
    total_sessions DESC;

-- Example query:
-- SELECT counselor_name, total_sessions, avg_rating, performance_level FROM therapy_session_effectiveness;

-- VIEW 8: resource_popularity
-- Purpose: Track most viewed and featured educational resources
-- Use Case: Content curation, resource recommendations, engagement metrics

CREATE OR REPLACE VIEW resource_popularity AS
SELECT 
    r.id as resource_id,
    r.title,
    r.resource_type,
    r.category,
    r.author,
    r.view_count,
    r.is_featured,
    r.is_active,
    r.difficulty_level,
    r.created_at,
    DATEDIFF(CURDATE(), r.created_at) as days_since_created,
    CASE 
        WHEN r.view_count >= 100 THEN 'Viral'
        WHEN r.view_count >= 50 THEN 'Very Popular'
        WHEN r.view_count >= 20 THEN 'Popular'
        WHEN r.view_count >= 10 THEN 'Moderate'
        ELSE 'New/Low Traffic'
    END as popularity_tier,
    ROUND(r.view_count / GREATEST(DATEDIFF(CURDATE(), r.created_at), 1), 2) as avg_views_per_day
FROM 
    resources r
WHERE 
    r.is_active = TRUE
ORDER BY 
    r.view_count DESC, 
    r.is_featured DESC;

-- Example query:
-- SELECT title, resource_type, view_count, popularity_tier FROM resource_popularity LIMIT 10;

-- ==============================================================================
-- VERIFICATION QUERIES
-- ==============================================================================

-- Show all created views
SELECT 'Views created successfully:' as status;

SELECT 
    TABLE_NAME as view_name,
    TABLE_COMMENT as description
FROM 
    INFORMATION_SCHEMA.TABLES
WHERE 
    TABLE_SCHEMA = 'mindmate' 
    AND TABLE_TYPE = 'VIEW'
    AND TABLE_NAME LIKE '%mood%' OR TABLE_NAME LIKE '%user_activity%' OR TABLE_NAME LIKE '%helpline%'
ORDER BY 
    TABLE_NAME;

-- Test each view with sample queries
SELECT '=== Testing weekly_mood_avg_by_pseudonym ===' as test;
SELECT pseudonym, year, week_number, avg_mood, mood_log_count 
FROM weekly_mood_avg_by_pseudonym 
LIMIT 5;

SELECT '=== Testing mood_tag_counts ===' as test;
SELECT tag, cnt, avg_mood_for_tag, users_with_tag 
FROM mood_tag_counts 
LIMIT 5;

SELECT '=== Testing helpline_usage_daily ===' as test;
SELECT country, helpline_name, availability_type 
FROM helpline_usage_daily 
LIMIT 5;

SELECT '=== Testing user_activity_summary ===' as test;
SELECT pseudonym, total_mood_logs, total_therapy_sessions, engagement_level 
FROM user_activity_summary 
LIMIT 5;

SELECT '=== Testing mood_trend_last_30_days ===' as test;
SELECT pseudonym, log_date, mood_level, mood_tag, days_ago 
FROM mood_trend_last_30_days 
WHERE entry_rank <= 3
LIMIT 5;

SELECT '=== Testing low_mood_alerts ===' as test;
SELECT pseudonym, low_mood_count, avg_low_mood, alert_level 
FROM low_mood_alerts 
LIMIT 5;

SELECT '=== Testing therapy_session_effectiveness ===' as test;
SELECT counselor_name, session_type, total_sessions, avg_rating, performance_level 
FROM therapy_session_effectiveness 
LIMIT 5;

SELECT '=== Testing resource_popularity ===' as test;
SELECT title, resource_type, view_count, popularity_tier 
FROM resource_popularity 
LIMIT 5;

-- ==============================================================================
-- USAGE DOCUMENTATION
-- ==============================================================================

/*
VIEW USAGE EXAMPLES:

1. WEEKLY MOOD TRENDS (weekly_mood_avg_by_pseudonym)
   Use Case: Track user's mood progress over weeks
   
   Query:
   SELECT pseudonym, week_start_date, avg_mood, mood_log_count
   FROM weekly_mood_avg_by_pseudonym
   WHERE pseudonym = 'User_2'
   AND year = 2025
   ORDER BY week_number DESC
   LIMIT 12; -- Last 12 weeks

2. MOST COMMON EMOTIONS (mood_tag_counts)
   Use Case: Identify prevalent emotional states across all users
   
   Query:
   SELECT tag, cnt, avg_mood_for_tag
   FROM mood_tag_counts
   WHERE cnt >= 3
   ORDER BY cnt DESC;

3. HELPLINE DIRECTORY (helpline_usage_daily)
   Use Case: Show available crisis resources by country
   
   Query:
   SELECT country, helpline_name, phone, operating_hours
   FROM helpline_usage_daily
   WHERE country = 'USA'
   ORDER BY helpline_name;

4. USER ENGAGEMENT REPORT (user_activity_summary)
   Use Case: Dashboard showing all user activities
   
   Query:
   SELECT 
       pseudonym,
       total_mood_logs,
       overall_avg_mood,
       total_therapy_sessions,
       engagement_level,
       last_activity_date
   FROM user_activity_summary
   WHERE total_mood_logs > 0
   ORDER BY last_activity_date DESC;

5. RECENT MOOD CHECK (mood_trend_last_30_days)
   Use Case: Show user's recent emotional state
   
   Query:
   SELECT pseudonym, log_date, mood_level, mood_tag
   FROM mood_trend_last_30_days
   WHERE pseudonym = 'User_2'
   AND entry_rank <= 10
   ORDER BY log_date DESC;

6. MENTAL HEALTH ALERTS (low_mood_alerts)
   Use Case: Identify users needing support
   
   Query:
   SELECT pseudonym, low_mood_count, avg_low_mood, alert_level, concerning_tags
   FROM low_mood_alerts
   WHERE alert_level IN ('Critical', 'High Concern')
   ORDER BY avg_low_mood ASC;

7. COUNSELOR PERFORMANCE (therapy_session_effectiveness)
   Use Case: Evaluate therapy quality
   
   Query:
   SELECT counselor_name, total_sessions, avg_rating, performance_level
   FROM therapy_session_effectiveness
   WHERE total_sessions >= 5
   ORDER BY avg_rating DESC;

8. POPULAR CONTENT (resource_popularity)
   Use Case: Recommend trending resources
   
   Query:
   SELECT title, resource_type, category, view_count, popularity_tier
   FROM resource_popularity
   WHERE is_featured = TRUE OR popularity_tier IN ('Viral', 'Very Popular')
   ORDER BY view_count DESC
   LIMIT 10;

PERFORMANCE NOTES:
- All views use indexed columns for optimal performance
- Composite indexes on (anonym_id, log_ts) speed up time-series queries
- LEFT JOINs in user_activity_summary handle users with no activities
- GROUP BY clauses use indexed columns (anonym_id, mood_tag, etc.)
- DATE functions leverage log_ts index

REFRESH NOTES:
- Views automatically refresh on each query (no caching)
- For large datasets, consider materialized views or scheduled aggregation tables
- Complex views (user_activity_summary) may benefit from result caching at application level

SECURITY NOTES:
- All views respect anonymized_users privacy layer
- Real usernames only shown in user_activity_summary (admin use)
- Pseudonyms protect identity in all public-facing views
*/

SELECT '✅ All analytical views created successfully!' as status,
       '8 views ready for reporting and dashboards' as views_created,
       'See comments above for usage examples' as documentation;
