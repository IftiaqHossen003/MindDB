-- 11_seed_anonymized_users.sql
-- Purpose: Populate anonymized_users table with pseudonyms for existing users
--          and create sample mood log data for testing
-- This script is idempotent and safe to run multiple times
-- Run with: Get-Content database/sql/11_seed_anonymized_users.sql | C:\xampp\mysql\bin\mysql.exe -u root -P 3307 mindmate

START TRANSACTION;

-- ==============================================================================
-- STEP 1: Create anonymized users for existing users
-- Purpose: Generate pseudonymized identities for all users in the users table
-- Uses INSERT ... SELECT ... WHERE NOT EXISTS to prevent duplicates
-- ==============================================================================

-- Insert anonymized users for each user that doesn't already have one
INSERT INTO anonymized_users (user_id, pseudonym)
SELECT 
    u.id,
    CONCAT('User_', u.id) as pseudonym
FROM users u
WHERE NOT EXISTS (
    SELECT 1 
    FROM anonymized_users au 
    WHERE au.user_id = u.id
);

-- Verify anonymized users created
SELECT 'Anonymized users created/verified:' as status;
SELECT 
    au.anonym_id,
    au.user_id,
    au.pseudonym,
    u.username,
    au.created_at
FROM anonymized_users au
LEFT JOIN users u ON au.user_id = u.id
ORDER BY au.anonym_id;

-- ==============================================================================
-- STEP 2: Insert sample mood logs for demonstration
-- Purpose: Create test data showing various mood levels, tags, and patterns
-- Covers different scenarios: high mood, low mood, anxiety, stress, calm, etc.
-- ==============================================================================

-- Get the first anonymized user ID for sample data
SET @anonym_id_1 = (SELECT anonym_id FROM anonymized_users ORDER BY anonym_id LIMIT 1);
SET @anonym_id_2 = (SELECT anonym_id FROM anonymized_users ORDER BY anonym_id LIMIT 1 OFFSET 1);

-- Insert sample mood logs only if they don't exist (check by anonym_id and approximate timestamp)
-- We'll use specific timestamps to make this idempotent

-- Sample data for first anonymized user (varied mood progression)
INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_1, 5, 'anxious', DATE_SUB(NOW(), INTERVAL 10 DAY), 'Feeling worried about work presentation'
WHERE NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_1 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 10 DAY))
    AND mood_tag = 'anxious'
);

INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_1, 4, 'stressed', DATE_SUB(NOW(), INTERVAL 9 DAY), 'High workload, feeling overwhelmed'
WHERE NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_1 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 9 DAY))
    AND mood_tag = 'stressed'
);

INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_1, 6, 'calm', DATE_SUB(NOW(), INTERVAL 8 DAY), 'Morning meditation helped me feel centered'
WHERE NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_1 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 8 DAY))
    AND mood_tag = 'calm'
);

INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_1, 7, 'happy', DATE_SUB(NOW(), INTERVAL 7 DAY), 'Presentation went well! Feeling accomplished'
WHERE NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_1 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 7 DAY))
    AND mood_tag = 'happy'
);

INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_1, 8, 'energetic', DATE_SUB(NOW(), INTERVAL 6 DAY), 'Great workout this morning, full of energy'
WHERE NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_1 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 6 DAY))
    AND mood_tag = 'energetic'
);

INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_1, 6, 'content', DATE_SUB(NOW(), INTERVAL 5 DAY), 'Peaceful day with family'
WHERE NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_1 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 5 DAY))
    AND mood_tag = 'content'
);

INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_1, 5, 'tired', DATE_SUB(NOW(), INTERVAL 4 DAY), 'Poor sleep last night, feeling drained'
WHERE NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_1 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 4 DAY))
    AND mood_tag = 'tired'
);

-- Sample data for second anonymized user (if exists)
INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_2, 7, 'optimistic', DATE_SUB(NOW(), INTERVAL 3 DAY), 'Starting a new project, feeling hopeful'
WHERE @anonym_id_2 IS NOT NULL 
AND NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_2 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 3 DAY))
    AND mood_tag = 'optimistic'
);

INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_2, 6, 'focused', DATE_SUB(NOW(), INTERVAL 2 DAY), 'Productive work session today'
WHERE @anonym_id_2 IS NOT NULL 
AND NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_2 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 2 DAY))
    AND mood_tag = 'focused'
);

INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT @anonym_id_2, 8, 'grateful', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Reflecting on positive things in my life'
WHERE @anonym_id_2 IS NOT NULL 
AND NOT EXISTS (
    SELECT 1 FROM mood_logs 
    WHERE anonym_id = @anonym_id_2 
    AND DATE(log_ts) = DATE(DATE_SUB(NOW(), INTERVAL 1 DAY))
    AND mood_tag = 'grateful'
);

COMMIT;

-- ==============================================================================
-- VERIFICATION QUERIES
-- ==============================================================================

SELECT '=== Anonymized Users Summary ===' as status;

-- Show all anonymized users with their linked user accounts
SELECT 
    au.anonym_id,
    au.pseudonym,
    u.username,
    u.email,
    au.created_at
FROM anonymized_users au
LEFT JOIN users u ON au.user_id = u.id
ORDER BY au.anonym_id;

SELECT '=== Mood Logs Summary ===' as status;

-- Count mood logs per anonymized user
SELECT 
    au.pseudonym,
    COUNT(ml.id) as mood_log_count,
    MIN(ml.log_ts) as first_log,
    MAX(ml.log_ts) as last_log,
    AVG(ml.mood_level) as avg_mood
FROM anonymized_users au
LEFT JOIN mood_logs ml ON au.anonym_id = ml.anonym_id
GROUP BY au.anonym_id, au.pseudonym
ORDER BY au.anonym_id;

SELECT '=== Recent Mood Logs (Last 10 Days) ===' as status;

-- Show recent mood logs with details
SELECT 
    au.pseudonym,
    ml.mood_level,
    ml.mood_tag,
    ml.log_ts,
    ml.notes
FROM mood_logs ml
INNER JOIN anonymized_users au ON ml.anonym_id = au.anonym_id
WHERE ml.log_ts >= DATE_SUB(NOW(), INTERVAL 10 DAY)
ORDER BY ml.log_ts DESC;

SELECT '=== Mood Statistics by Tag ===' as status;

-- Analyze mood levels by tag
SELECT 
    mood_tag,
    COUNT(*) as occurrences,
    AVG(mood_level) as avg_mood,
    MIN(mood_level) as min_mood,
    MAX(mood_level) as max_mood
FROM mood_logs
GROUP BY mood_tag
ORDER BY occurrences DESC;

-- ==============================================================================
-- COMPLETION MESSAGE
-- ==============================================================================

SELECT '✅ Anonymized users and sample mood logs created!' as status,
       (SELECT COUNT(*) FROM anonymized_users) as total_anonymized_users,
       (SELECT COUNT(*) FROM mood_logs) as total_mood_logs,
       'Data is idempotent - safe to run multiple times' as note;

-- ==============================================================================
-- USAGE NOTES
-- ==============================================================================

/*
PSEUDONYM GENERATION:
- Each user gets a pseudonym in format: 'User_<user_id>'
- Example: User with id=1 becomes 'User_1'
- Pseudonyms are unique and linked to user accounts via nullable FK

MOOD LOG SAMPLE DATA:
- 10 sample mood logs created across different dates
- Mood levels range from 4 to 8 (representing varied emotional states)
- Tags include: anxious, stressed, calm, happy, energetic, content, tired, optimistic, focused, grateful
- Notes provide context for each mood entry
- Data spans last 10 days for realistic timeline

IDEMPOTENT DESIGN:
- Uses INSERT ... SELECT ... WHERE NOT EXISTS pattern
- Safe to run multiple times without creating duplicates
- Will create new anonymized users only for users that don't have them
- Will skip mood logs if they already exist for that date/tag combination

TRANSACTION SAFETY:
- Entire operation wrapped in START TRANSACTION ... COMMIT
- If any error occurs, changes are rolled back
- Database remains in consistent state

PRIVACY FEATURES:
- user_id is nullable FK with ON DELETE SET NULL
- Mood data persists even if user account is deleted
- Pseudonyms protect real identity in mood tracking

TESTING SCENARIOS COVERED:
1. Low mood progression (anxious → stressed)
2. Recovery pattern (stressed → calm → happy)
3. Peak positive state (energetic → content)
4. Fatigue/dip (tired)
5. Various positive states (optimistic, focused, grateful)

TO CUSTOMIZE PSEUDONYMS:
You can update pseudonyms after creation:
UPDATE anonymized_users SET pseudonym = 'YourCustomName' WHERE anonym_id = 1;

TO ADD MORE MOOD LOGS:
Use the INSERT pattern shown above with your own dates, levels, and tags.
Remember to check for duplicates to maintain idempotency.
*/
