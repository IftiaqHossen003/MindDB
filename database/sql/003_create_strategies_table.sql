-- 003_create_strategies_table.sql
-- Phase 2: Strategy Normalization - Create Strategies Table
-- Purpose: Normalize strategy data and eliminate varchar repetition
-- Run with: mysql -u root -p -P 3307 mindmate < database/sql/003_create_strategies_table.sql

CREATE TABLE IF NOT EXISTS strategies (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert common therapeutic strategies
-- Customize based on your actual strategy_used values
INSERT INTO strategies (name, description, is_active) VALUES
('cognitive_behavioral', 'Cognitive Behavioral Therapy (CBT) approach focusing on thought patterns', TRUE),
('mindfulness', 'Mindfulness-based response promoting present-moment awareness', TRUE),
('empathetic', 'Empathetic listening and validation response', TRUE),
('solution_focused', 'Solution-focused brief therapy approach', TRUE),
('psychoeducational', 'Educational response providing information and insights', TRUE),
('motivational', 'Motivational interviewing techniques', TRUE),
('supportive', 'General supportive counseling approach', TRUE),
('default', 'Default strategy when no specific approach is specified', TRUE)
ON DUPLICATE KEY UPDATE id=id;

-- Find all unique strategy values currently in messages table
SELECT DISTINCT strategy_used, COUNT(*) as usage_count
FROM messages
GROUP BY strategy_used
ORDER BY usage_count DESC;

-- Insert any missing strategies found in the messages table
-- Run this query, review the results, and add INSERT statements as needed
INSERT IGNORE INTO strategies (name, description)
SELECT DISTINCT strategy_used, CONCAT('Imported from existing data: ', strategy_used)
FROM messages
WHERE strategy_used NOT IN (SELECT name FROM strategies);

-- Verification
SELECT * FROM strategies ORDER BY name;
