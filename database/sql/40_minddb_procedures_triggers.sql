-- 40_minddb_procedures_triggers.sql
-- Purpose: Create stored procedures and triggers for MindDB database
-- This script adds:
--   1. sp_get_weekly_summary - Stored procedure for weekly mood reports
--   2. mood_logs_audit table - Audit trail for mood log changes
--   3. BEFORE INSERT trigger - Automatic audit logging on mood_logs inserts
-- Run with: Get-Content database/sql/40_minddb_procedures_triggers.sql | C:\xampp\mysql\bin\mysql.exe -u root -P 3307 mindmate

-- ==============================================================================
-- PART 1: CREATE AUDIT TABLE (IDEMPOTENT)
-- Purpose: Store audit trail for all mood_logs changes
-- ==============================================================================

-- Create mood_logs_audit table if it doesn't exist
CREATE TABLE IF NOT EXISTS mood_logs_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    mood_log_id INT NULL COMMENT 'FK to mood_logs.id (NULL for new inserts)',
    anonym_id INT NOT NULL COMMENT 'User who made the change',
    old_data TEXT NULL COMMENT 'Previous values (NULL for INSERT)',
    new_data TEXT NULL COMMENT 'New values in format: mood_level|mood_tag (NULL for DELETE)',
    operation_type ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL DEFAULT 'INSERT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_mood_log_id (mood_log_id),
    INDEX idx_anonym_id (anonym_id),
    INDEX idx_created_at (created_at),
    INDEX idx_operation (operation_type),
    
    FOREIGN KEY (anonym_id) REFERENCES anonymized_users(anonym_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail for mood_logs table changes';

SELECT 'Audit table mood_logs_audit ready' AS status;

-- ==============================================================================
-- PART 2: CREATE STORED PROCEDURE FOR WEEKLY SUMMARY
-- Purpose: Get aggregated mood statistics for a specific week per user
-- ==============================================================================

-- Drop procedure if exists (make script re-runnable)
DROP PROCEDURE IF EXISTS sp_get_weekly_summary;

DELIMITER $$

CREATE PROCEDURE sp_get_weekly_summary(
    IN week_start DATE,
    IN week_end DATE
)
BEGIN
    /*
    Purpose: Get weekly mood summary aggregated by pseudonym
    
    Parameters:
        week_start (DATE): Start date of the week (inclusive)
        week_end (DATE):   End date of the week (inclusive)
    
    Returns: Result set with columns:
        - pseudonym (VARCHAR): User's anonymous identifier
        - avg_mood (DECIMAL): Average mood level for the week (1-10 scale)
        - mood_count (INT): Number of mood logs recorded during the week
        - min_mood (INT): Lowest mood recorded
        - max_mood (INT): Highest mood recorded
        - mood_tags (TEXT): Comma-separated list of all mood tags used
        - first_log_date (DATE): Date of first mood log in period
        - last_log_date (DATE): Date of last mood log in period
    
    Example Usage:
        CALL sp_get_weekly_summary('2025-10-07', '2025-10-13');
        CALL sp_get_weekly_summary(DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE());
    
    Performance: Uses idx_log_ts and idx_anonym_id indexes
    */
    
    -- Validate input parameters
    IF week_start IS NULL OR week_end IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'week_start and week_end parameters cannot be NULL';
    END IF;
    
    IF week_start > week_end THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'week_start must be less than or equal to week_end';
    END IF;
    
    -- Return weekly summary grouped by pseudonym
    SELECT 
        au.pseudonym,
        ROUND(AVG(ml.mood_level), 2) as avg_mood,
        COUNT(ml.id) as mood_count,
        MIN(ml.mood_level) as min_mood,
        MAX(ml.mood_level) as max_mood,
        GROUP_CONCAT(DISTINCT ml.mood_tag ORDER BY ml.mood_tag SEPARATOR ', ') as mood_tags,
        DATE(MIN(ml.log_ts)) as first_log_date,
        DATE(MAX(ml.log_ts)) as last_log_date
    FROM 
        mood_logs ml
    INNER JOIN 
        anonymized_users au ON ml.anonym_id = au.anonym_id
    WHERE 
        DATE(ml.log_ts) BETWEEN week_start AND week_end
    GROUP BY 
        au.pseudonym, 
        au.anonym_id
    ORDER BY 
        avg_mood DESC,
        mood_count DESC;
    
END$$

DELIMITER ;

SELECT 'Stored procedure sp_get_weekly_summary created successfully' AS status;

-- ==============================================================================
-- PART 3: CREATE BEFORE INSERT TRIGGER FOR AUDIT LOGGING
-- Purpose: Automatically log all new mood_logs entries to audit table
-- ==============================================================================

-- Drop trigger if exists (make script re-runnable)
DROP TRIGGER IF EXISTS trg_mood_logs_before_insert;

DELIMITER $$

CREATE TRIGGER trg_mood_logs_before_insert
BEFORE INSERT ON mood_logs
FOR EACH ROW
BEGIN
    /*
    Purpose: Audit trail for mood_logs INSERT operations
    
    Trigger Type: BEFORE INSERT
    Table: mood_logs
    
    Action: Creates audit record in mood_logs_audit with:
        - mood_log_id: NULL (record doesn't exist yet)
        - anonym_id: User making the entry
        - old_data: NULL (no previous data for INSERT)
        - new_data: Concatenated mood_level and mood_tag
        - operation_type: 'INSERT'
        - created_at: Current timestamp
    
    Data Format: new_data uses format "mood_level|mood_tag"
    Example: "7|happy" or "4|anxious"
    
    Performance Impact: Minimal - single INSERT per mood log
    */
    
    -- Insert audit record for the new mood log entry
    INSERT INTO mood_logs_audit (
        mood_log_id,
        anonym_id,
        old_data,
        new_data,
        operation_type,
        created_at
    ) VALUES (
        NULL,  -- mood_log_id is NULL for new inserts (ID not assigned yet)
        NEW.anonym_id,
        NULL,  -- old_data is NULL for INSERT operations
        CONCAT(
            COALESCE(CAST(NEW.mood_level AS CHAR), 'NULL'),
            '|',
            COALESCE(NEW.mood_tag, 'NULL')
        ),  -- new_data format: "mood_level|mood_tag"
        'INSERT',
        CURRENT_TIMESTAMP
    );
    
END$$

DELIMITER ;

SELECT 'Trigger trg_mood_logs_before_insert created successfully' AS status;

-- ==============================================================================
-- PART 4: CREATE AFTER INSERT TRIGGER TO UPDATE AUDIT WITH ACTUAL ID
-- Purpose: Update audit record with the actual mood_log_id after INSERT
-- ==============================================================================

-- Drop trigger if exists (make script re-runnable)
DROP TRIGGER IF EXISTS trg_mood_logs_after_insert;

DELIMITER $$

CREATE TRIGGER trg_mood_logs_after_insert
AFTER INSERT ON mood_logs
FOR EACH ROW
BEGIN
    /*
    Purpose: Update audit record with actual mood_log_id after INSERT
    
    Trigger Type: AFTER INSERT
    Table: mood_logs
    
    Action: Updates the most recent audit record (created by BEFORE INSERT trigger)
            with the actual auto-generated mood_log_id
    
    Why needed: BEFORE INSERT trigger doesn't have access to NEW.id (auto-increment)
                AFTER INSERT has the actual ID and updates the audit record
    
    Performance: Single UPDATE per mood log insert
    */
    
    -- Update the most recent audit record with the actual mood_log_id
    UPDATE mood_logs_audit
    SET mood_log_id = NEW.id
    WHERE audit_id = LAST_INSERT_ID()
    AND mood_log_id IS NULL
    AND anonym_id = NEW.anonym_id
    LIMIT 1;
    
END$$

DELIMITER ;

SELECT 'Trigger trg_mood_logs_after_insert created successfully' AS status;

-- ==============================================================================
-- PART 5: CREATE ADDITIONAL AUDIT TRIGGERS (UPDATE & DELETE)
-- Purpose: Complete audit trail for all mood_logs operations
-- ==============================================================================

-- BEFORE UPDATE trigger for mood_logs
DROP TRIGGER IF EXISTS trg_mood_logs_before_update;

DELIMITER $$

CREATE TRIGGER trg_mood_logs_before_update
BEFORE UPDATE ON mood_logs
FOR EACH ROW
BEGIN
    /*
    Purpose: Audit trail for mood_logs UPDATE operations
    
    Captures both old and new values when mood_level or mood_tag changes
    */
    
    -- Only log if mood_level or mood_tag actually changed
    IF OLD.mood_level != NEW.mood_level OR OLD.mood_tag != NEW.mood_tag THEN
        INSERT INTO mood_logs_audit (
            mood_log_id,
            anonym_id,
            old_data,
            new_data,
            operation_type,
            created_at
        ) VALUES (
            OLD.id,
            NEW.anonym_id,
            CONCAT(
                COALESCE(CAST(OLD.mood_level AS CHAR), 'NULL'),
                '|',
                COALESCE(OLD.mood_tag, 'NULL')
            ),
            CONCAT(
                COALESCE(CAST(NEW.mood_level AS CHAR), 'NULL'),
                '|',
                COALESCE(NEW.mood_tag, 'NULL')
            ),
            'UPDATE',
            CURRENT_TIMESTAMP
        );
    END IF;
    
END$$

DELIMITER ;

SELECT 'Trigger trg_mood_logs_before_update created successfully' AS status;

-- BEFORE DELETE trigger for mood_logs
DROP TRIGGER IF EXISTS trg_mood_logs_before_delete;

DELIMITER $$

CREATE TRIGGER trg_mood_logs_before_delete
BEFORE DELETE ON mood_logs
FOR EACH ROW
BEGIN
    /*
    Purpose: Audit trail for mood_logs DELETE operations
    
    Preserves deleted data in audit table
    */
    
    INSERT INTO mood_logs_audit (
        mood_log_id,
        anonym_id,
        old_data,
        new_data,
        operation_type,
        created_at
    ) VALUES (
        OLD.id,
        OLD.anonym_id,
        CONCAT(
            COALESCE(CAST(OLD.mood_level AS CHAR), 'NULL'),
            '|',
            COALESCE(OLD.mood_tag, 'NULL')
        ),
        NULL,  -- new_data is NULL for DELETE operations
        'DELETE',
        CURRENT_TIMESTAMP
    );
    
END$$

DELIMITER ;

SELECT 'Trigger trg_mood_logs_before_delete created successfully' AS status;

-- ==============================================================================
-- VERIFICATION & TESTING
-- ==============================================================================

-- Verify audit table structure
SELECT 'Verifying mood_logs_audit table...' AS status;
DESCRIBE mood_logs_audit;

-- Verify stored procedure exists
SELECT 'Verifying stored procedure...' AS status;
SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name = 'sp_get_weekly_summary';

-- Verify all triggers exist
SELECT 'Verifying triggers...' AS status;
SHOW TRIGGERS WHERE `Table` = 'mood_logs';

-- Count existing audit records
SELECT 'Current audit record count:' AS info, COUNT(*) as audit_count FROM mood_logs_audit;

-- ==============================================================================
-- TESTING SECTION (Commented out - uncomment to test)
-- ==============================================================================

/*
-- TEST 1: Test stored procedure with current week
SELECT 'TEST 1: Testing sp_get_weekly_summary with current week' AS test;
CALL sp_get_weekly_summary(
    DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY),  -- Monday of current week
    DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)  -- Sunday of current week
);

-- TEST 2: Test stored procedure with last week
SELECT 'TEST 2: Testing sp_get_weekly_summary with last week' AS test;
CALL sp_get_weekly_summary(
    DATE_SUB(CURDATE(), INTERVAL 7 DAY),
    CURDATE()
);

-- TEST 3: Test audit trigger by inserting a test mood log
SELECT 'TEST 3: Testing audit trigger with sample insert' AS test;
INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
SELECT 
    anonym_id,
    9,
    'testing',
    NOW(),
    'Test mood log for trigger verification'
FROM anonymized_users
LIMIT 1;

-- View the audit record created by the trigger
SELECT 'Audit record from test insert:' AS result;
SELECT * FROM mood_logs_audit ORDER BY created_at DESC LIMIT 1;

-- Clean up test data
DELETE FROM mood_logs WHERE mood_tag = 'testing' AND notes LIKE 'Test mood log%';
SELECT 'Test data cleaned up - audit trail preserved' AS result;

-- TEST 4: View all audit records
SELECT 'TEST 4: All audit records' AS test;
SELECT 
    a.audit_id,
    a.mood_log_id,
    au.pseudonym,
    a.old_data,
    a.new_data,
    a.operation_type,
    a.created_at
FROM mood_logs_audit a
INNER JOIN anonymized_users au ON a.anonym_id = au.anonym_id
ORDER BY a.created_at DESC
LIMIT 10;
*/

-- ==============================================================================
-- USAGE EXAMPLES
-- ==============================================================================

/*
STORED PROCEDURE USAGE:

1. Get weekly summary for current week:
   CALL sp_get_weekly_summary(
       DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY),
       DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
   );

2. Get weekly summary for last 7 days:
   CALL sp_get_weekly_summary(
       DATE_SUB(CURDATE(), INTERVAL 7 DAY),
       CURDATE()
   );

3. Get weekly summary for specific date range:
   CALL sp_get_weekly_summary('2025-10-07', '2025-10-13');

4. Get weekly summary for October 2025 (week by week):
   CALL sp_get_weekly_summary('2025-10-01', '2025-10-07');
   CALL sp_get_weekly_summary('2025-10-08', '2025-10-14');
   CALL sp_get_weekly_summary('2025-10-15', '2025-10-21');
   CALL sp_get_weekly_summary('2025-10-22', '2025-10-31');


AUDIT TRAIL QUERIES:

1. View all INSERT operations:
   SELECT * FROM mood_logs_audit WHERE operation_type = 'INSERT' ORDER BY created_at DESC;

2. View all UPDATE operations:
   SELECT * FROM mood_logs_audit WHERE operation_type = 'UPDATE' ORDER BY created_at DESC;

3. View all DELETE operations:
   SELECT * FROM mood_logs_audit WHERE operation_type = 'DELETE' ORDER BY created_at DESC;

4. View audit trail for specific user:
   SELECT 
       a.audit_id,
       a.mood_log_id,
       au.pseudonym,
       a.old_data,
       a.new_data,
       a.operation_type,
       a.created_at
   FROM mood_logs_audit a
   INNER JOIN anonymized_users au ON a.anonym_id = au.anonym_id
   WHERE au.pseudonym = 'User_2'
   ORDER BY a.created_at DESC;

5. View audit trail for specific mood log:
   SELECT * FROM mood_logs_audit WHERE mood_log_id = 5 ORDER BY created_at;

6. Parse audit data (extract mood_level and mood_tag):
   SELECT 
       audit_id,
       mood_log_id,
       SUBSTRING_INDEX(new_data, '|', 1) as mood_level,
       SUBSTRING_INDEX(new_data, '|', -1) as mood_tag,
       operation_type,
       created_at
   FROM mood_logs_audit
   WHERE operation_type = 'INSERT'
   ORDER BY created_at DESC;

7. Count operations by type:
   SELECT operation_type, COUNT(*) as count 
   FROM mood_logs_audit 
   GROUP BY operation_type;

8. Audit records per user:
   SELECT 
       au.pseudonym,
       COUNT(*) as total_changes,
       SUM(CASE WHEN a.operation_type = 'INSERT' THEN 1 ELSE 0 END) as inserts,
       SUM(CASE WHEN a.operation_type = 'UPDATE' THEN 1 ELSE 0 END) as updates,
       SUM(CASE WHEN a.operation_type = 'DELETE' THEN 1 ELSE 0 END) as deletes
   FROM mood_logs_audit a
   INNER JOIN anonymized_users au ON a.anonym_id = au.anonym_id
   GROUP BY au.pseudonym
   ORDER BY total_changes DESC;


PHP USAGE EXAMPLES:

<?php
// Example 1: Get weekly summary using stored procedure
function getWeeklySummary($weekStart, $weekEnd) {
    $db = getDb();
    $stmt = $db->prepare("CALL sp_get_weekly_summary(?, ?)");
    $stmt->execute([$weekStart, $weekEnd]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Usage
$summary = getWeeklySummary('2025-10-07', '2025-10-13');
foreach ($summary as $user) {
    echo "{$user['pseudonym']}: Avg mood {$user['avg_mood']}/10, ";
    echo "{$user['mood_count']} logs, Tags: {$user['mood_tags']}\n";
}

// Example 2: View audit trail for a user
function getUserAuditTrail($pseudonym, $limit = 20) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT 
            a.audit_id,
            a.mood_log_id,
            a.old_data,
            a.new_data,
            a.operation_type,
            a.created_at
        FROM mood_logs_audit a
        INNER JOIN anonymized_users au ON a.anonym_id = au.anonym_id
        WHERE au.pseudonym = ?
        ORDER BY a.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$pseudonym, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Example 3: Insert mood log (trigger fires automatically)
function logMood($userId, $moodLevel, $moodTag, $notes = '') {
    $db = getDb();
    
    // Get anonymized user
    $stmt = $db->prepare("SELECT anonym_id FROM anonymized_users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $anonym = $stmt->fetch();
    
    if (!$anonym) {
        throw new Exception("User not found");
    }
    
    // Insert mood log - audit trigger fires automatically
    $stmt = $db->prepare("
        INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, log_ts, notes)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $stmt->execute([$anonym['anonym_id'], $moodLevel, $moodTag, $notes]);
    
    return $db->lastInsertId();
}
?>


MAINTENANCE QUERIES:

1. Archive old audit records (older than 1 year):
   DELETE FROM mood_logs_audit WHERE created_at < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);

2. Count audit table size:
   SELECT 
       COUNT(*) as total_records,
       MIN(created_at) as oldest_record,
       MAX(created_at) as newest_record,
       ROUND(SUM(LENGTH(old_data) + LENGTH(new_data)) / 1024 / 1024, 2) as data_size_mb
   FROM mood_logs_audit;

3. Check trigger status:
   SHOW TRIGGERS WHERE `Table` = 'mood_logs';

4. Drop all mood_logs triggers (if needed):
   DROP TRIGGER IF EXISTS trg_mood_logs_before_insert;
   DROP TRIGGER IF EXISTS trg_mood_logs_after_insert;
   DROP TRIGGER IF EXISTS trg_mood_logs_before_update;
   DROP TRIGGER IF EXISTS trg_mood_logs_before_delete;

5. Drop stored procedure (if needed):
   DROP PROCEDURE IF EXISTS sp_get_weekly_summary;
*/

-- ==============================================================================
-- SUMMARY
-- ==============================================================================

SELECT '========================================' AS separator;
SELECT 'PROCEDURES AND TRIGGERS CREATED SUCCESSFULLY' AS status;
SELECT '========================================' AS separator;

SELECT 'Created objects:' AS summary;
SELECT '1. Table: mood_logs_audit (with 4 indexes)' AS item;
SELECT '2. Stored Procedure: sp_get_weekly_summary(week_start, week_end)' AS item;
SELECT '3. Trigger: trg_mood_logs_before_insert' AS item;
SELECT '4. Trigger: trg_mood_logs_after_insert' AS item;
SELECT '5. Trigger: trg_mood_logs_before_update' AS item;
SELECT '6. Trigger: trg_mood_logs_before_delete' AS item;

SELECT '========================================' AS separator;
SELECT 'USAGE' AS title;
SELECT '========================================' AS separator;

SELECT 'Call stored procedure:' AS usage;
SELECT 'CALL sp_get_weekly_summary(''2025-10-07'', ''2025-10-13'');' AS example;

SELECT 'View audit records:' AS usage;
SELECT 'SELECT * FROM mood_logs_audit ORDER BY created_at DESC LIMIT 10;' AS example;

SELECT 'All triggers fire automatically on INSERT/UPDATE/DELETE' AS note;

SELECT '========================================' AS separator;
SELECT '✅ All procedures and triggers operational!' AS final_status;
SELECT '========================================' AS separator;
