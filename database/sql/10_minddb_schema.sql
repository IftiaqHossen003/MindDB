-- 10_minddb_schema.sql
-- Purpose: Add new tables for anonymization, mood tracking, helplines, therapy sessions, and resources
-- This script is idempotent and does NOT alter existing tables
-- Run with: Get-Content database/sql/10_minddb_schema.sql | C:\xampp\mysql\bin\mysql.exe -u root -P 3307 mindmate

-- ==============================================================================
-- TABLE: anonymized_users
-- Purpose: Store pseudonymized user identities for privacy-conscious features
-- Allows users to log mood/therapy data without direct user_id linkage
-- ==============================================================================
CREATE TABLE IF NOT EXISTS anonymized_users (
    anonym_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NULL,
    pseudonym VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key to users table (nullable for privacy)
    -- SET NULL on delete allows anonymized data to persist even if user account deleted
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_pseudonym (pseudonym),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Anonymized user identities for privacy-focused mood and therapy tracking';

-- ==============================================================================
-- TABLE: mood_logs
-- Purpose: Track user mood levels and emotional states over time
-- Linked to anonymized_users for privacy protection
-- ==============================================================================
CREATE TABLE IF NOT EXISTS mood_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    anonym_id INT(11) NOT NULL,
    mood_level TINYINT NOT NULL COMMENT 'Mood scale 1-10, where 1=very low, 10=excellent',
    mood_tag VARCHAR(50) NULL COMMENT 'Optional tag: anxious, happy, sad, stressed, calm, etc.',
    log_ts DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when mood was logged',
    notes TEXT NULL COMMENT 'Optional user notes about their mood',
    
    -- Foreign key to anonymized_users
    FOREIGN KEY (anonym_id) REFERENCES anonymized_users(anonym_id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes for performance and analytics
    INDEX idx_anonym_id (anonym_id),
    INDEX idx_log_ts (log_ts),
    INDEX idx_mood_level (mood_level),
    INDEX idx_mood_tag (mood_tag),
    
    -- Composite index for time-series queries
    INDEX idx_anonym_log_ts (anonym_id, log_ts),
    
    -- Constraint: mood_level must be between 1 and 10
    CHECK (mood_level >= 1 AND mood_level <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mood tracking logs with timestamps for emotional wellbeing analytics';

-- ==============================================================================
-- TABLE: helplines
-- Purpose: Store mental health helpline contacts and crisis resources
-- Provides users with emergency support information
-- ==============================================================================
CREATE TABLE IF NOT EXISTS helplines (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Name of helpline organization',
    phone VARCHAR(50) NOT NULL COMMENT 'Phone number with country code',
    hours VARCHAR(100) NULL COMMENT 'Operating hours, e.g., "24/7" or "Mon-Fri 9am-5pm"',
    country VARCHAR(100) NULL COMMENT 'Country or region served',
    description TEXT NULL COMMENT 'Brief description of services offered',
    website VARCHAR(255) NULL COMMENT 'Official website URL',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this helpline is currently active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_country (country),
    INDEX idx_is_active (is_active),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mental health crisis helplines and support resources';

-- ==============================================================================
-- TABLE: therapy_sessions
-- Purpose: Track therapy/counseling sessions for anonymized users
-- Maintains privacy while allowing session history
-- ==============================================================================
CREATE TABLE IF NOT EXISTS therapy_sessions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    anonym_id INT(11) NOT NULL,
    counselor_name VARCHAR(150) NULL COMMENT 'Name or ID of counselor/therapist',
    session_date DATETIME NOT NULL COMMENT 'Date and time of therapy session',
    duration_minutes INT(11) NULL COMMENT 'Session duration in minutes',
    session_type VARCHAR(50) NULL COMMENT 'Type: individual, group, family, online, etc.',
    notes TEXT NULL COMMENT 'Private notes about the session',
    rating TINYINT NULL COMMENT 'Session rating 1-5 for user feedback',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key to anonymized_users
    FOREIGN KEY (anonym_id) REFERENCES anonymized_users(anonym_id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes for queries and analytics
    INDEX idx_anonym_id (anonym_id),
    INDEX idx_session_date (session_date),
    INDEX idx_counselor_name (counselor_name),
    INDEX idx_session_type (session_type),
    
    -- Composite index for user session history
    INDEX idx_anonym_session_date (anonym_id, session_date),
    
    -- Constraint: rating must be between 1 and 5 if provided
    CHECK (rating IS NULL OR (rating >= 1 AND rating <= 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Therapy session records linked to anonymized users';

-- ==============================================================================
-- TABLE: resources
-- Purpose: Mental health educational resources, articles, videos, and tools
-- Provides curated content for users seeking self-help materials
-- ==============================================================================
CREATE TABLE IF NOT EXISTS resources (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT 'Resource title',
    resource_type ENUM('article', 'video', 'audio', 'tool', 'exercise', 'book', 'app', 'other') NOT NULL COMMENT 'Type of resource',
    url VARCHAR(500) NULL COMMENT 'External URL or file path',
    description TEXT NULL COMMENT 'Brief description of the resource',
    category VARCHAR(100) NULL COMMENT 'Category: anxiety, depression, stress, mindfulness, etc.',
    author VARCHAR(150) NULL COMMENT 'Author or creator name',
    duration_minutes INT(11) NULL COMMENT 'Duration for videos/audio in minutes',
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') NULL COMMENT 'Difficulty level for exercises/tools',
    is_featured BOOLEAN DEFAULT FALSE COMMENT 'Featured resource for homepage',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether resource is currently available',
    view_count INT(11) DEFAULT 0 COMMENT 'Number of times viewed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for search and filtering
    INDEX idx_resource_type (resource_type),
    INDEX idx_category (category),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at),
    
    -- Full-text search index for title and description
    FULLTEXT INDEX idx_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Educational resources and self-help materials for mental health';

-- ==============================================================================
-- INSERT SAMPLE DATA
-- ==============================================================================

-- Sample helplines (international)
INSERT IGNORE INTO helplines (name, phone, hours, country, description) VALUES
('National Suicide Prevention Lifeline', '988', '24/7', 'USA', 'Free and confidential support for people in distress'),
('Crisis Text Line', 'Text HOME to 741741', '24/7', 'USA', 'Free crisis counseling via text message'),
('Samaritans', '116 123', '24/7', 'UK', 'Confidential support for anyone in emotional distress'),
('Lifeline', '13 11 14', '24/7', 'Australia', 'Crisis support and suicide prevention services'),
('Kids Help Phone', '1-800-668-6868', '24/7', 'Canada', 'Support for young people'),
('SAMHSA National Helpline', '1-800-662-4357', '24/7', 'USA', 'Treatment referral and information service'),
('Befrienders Worldwide', 'Visit befrienders.org', 'Varies', 'International', 'Network of emotional support centers worldwide');

-- Sample resources
INSERT IGNORE INTO resources (title, resource_type, category, description, is_featured, is_active) VALUES
('Understanding Anxiety: A Beginner''s Guide', 'article', 'anxiety', 'Comprehensive guide to understanding and managing anxiety', TRUE, TRUE),
('10-Minute Mindfulness Meditation', 'video', 'mindfulness', 'Quick guided meditation for stress relief', TRUE, TRUE),
('Cognitive Behavioral Therapy Workbook', 'book', 'CBT', 'Self-help workbook for cognitive restructuring', FALSE, TRUE),
('Breathing Exercises for Panic Attacks', 'exercise', 'anxiety', 'Step-by-step breathing techniques for panic management', TRUE, TRUE),
('Depression Symptoms Checklist', 'tool', 'depression', 'Interactive self-assessment tool for depression symptoms', FALSE, TRUE),
('Sleep Hygiene Best Practices', 'article', 'sleep', 'Evidence-based tips for better sleep quality', FALSE, TRUE),
('Headspace: Meditation App', 'app', 'mindfulness', 'Popular meditation and mindfulness mobile application', TRUE, TRUE),
('The Body Scan Meditation', 'audio', 'mindfulness', '20-minute guided body scan for relaxation', FALSE, TRUE);

-- ==============================================================================
-- VERIFICATION QUERIES
-- ==============================================================================

-- Show all new tables created
SELECT 'New tables created:' as status;
SELECT TABLE_NAME, TABLE_COMMENT 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'mindmate' 
AND TABLE_NAME IN ('anonymized_users', 'mood_logs', 'helplines', 'therapy_sessions', 'resources');

-- Show foreign key relationships for new tables
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'mindmate'
AND TABLE_NAME IN ('anonymized_users', 'mood_logs', 'therapy_sessions')
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Show record counts
SELECT 'helplines' as table_name, COUNT(*) as record_count FROM helplines
UNION ALL
SELECT 'resources', COUNT(*) FROM resources;

-- ==============================================================================
-- USAGE EXAMPLES
-- ==============================================================================

-- Example 1: Create anonymized user
-- INSERT INTO anonymized_users (user_id, pseudonym) VALUES (1, 'BlueButterfly42');

-- Example 2: Log mood
-- INSERT INTO mood_logs (anonym_id, mood_level, mood_tag, notes) 
-- VALUES (1, 7, 'calm', 'Feeling better after meditation session');

-- Example 3: Record therapy session
-- INSERT INTO therapy_sessions (anonym_id, counselor_name, session_date, duration_minutes, notes)
-- VALUES (1, 'Dr. Smith', NOW(), 50, 'Discussed coping strategies for anxiety');

-- Example 4: Get mood trends for last 30 days
-- SELECT DATE(log_ts) as log_date, AVG(mood_level) as avg_mood, COUNT(*) as entries
-- FROM mood_logs
-- WHERE anonym_id = 1 AND log_ts >= DATE_SUB(NOW(), INTERVAL 30 DAY)
-- GROUP BY DATE(log_ts)
-- ORDER BY log_date;

-- Example 5: Search resources by category
-- SELECT title, resource_type, description 
-- FROM resources 
-- WHERE category = 'anxiety' AND is_active = TRUE
-- ORDER BY is_featured DESC, created_at DESC;

SELECT '✅ Schema additions complete!' as status,
       '5 new tables created' as tables_added,
       '7 sample helplines inserted' as sample_data_1,
       '8 sample resources inserted' as sample_data_2;
