# Database SQL Scripts

This directory contains SQL scripts for the MindDB database.

## 🆕 Starting Fresh? Use This First!

### Fresh Database Setup (Recommended if starting from scratch)
**000_create_fresh_database.sql** - Complete normalized database with all features
- Creates all tables (users, strategies, conversations, messages, message_audit)
- Inserts default data (admin user, strategies, sample conversation)
- Creates views, triggers, and stored procedures
- Fully normalized to 3NF with all FK constraints
- **Use this if:** Messages table is deleted or starting new project

```powershell
mysql -u root -p -P 3307 mindmate < database/sql/000_create_fresh_database.sql
```

---

## 📚 Migration Scripts (For Existing Databases)

If you have an existing database with the old structure, use these scripts in order:

### Phase 1: Critical Fixes (Required)
1. **001_create_users_table.sql** - Creates the users table with authentication fields
2. **002_add_fk_messages_users.sql** - Adds foreign key constraint to messages.user_id

### Phase 2: Strategy Normalization (Recommended)
3. **003_create_strategies_table.sql** - Creates strategies lookup table
4. **004_normalize_strategies.sql** - Migrates strategy_used to strategy_id FK

### Phase 3: Optional Enhancements
5. **005_create_conversations_table.sql** - Adds conversation threading support
6. **006_add_audit_fields.sql** - Adds audit trail and soft delete functionality

### Additional Features
7. **010_minddb_schema.sql** - Adds privacy and wellness tracking tables:
   - `anonymized_users` - Pseudonymized identities for privacy
   - `mood_logs` - Emotional wellbeing tracking
   - `helplines` - Crisis support resources (7 samples)
   - `therapy_sessions` - Counseling session records
   - `resources` - Educational materials (8 samples)

8. **011_seed_anonymized_users.sql** - Populates test data (idempotent):
   - Creates pseudonyms for all existing users
   - Inserts 10 sample mood logs demonstrating varied patterns
   - Transaction-safe and re-runnable

9. **020_minddb_views.sql** - Creates 8 analytical views for reporting:
   - `weekly_mood_avg_by_pseudonym` - Weekly mood trends per user
   - `mood_tag_counts` - Emotional state frequency analysis
   - `helpline_usage_daily` - Crisis resource directory
   - `user_activity_summary` - Comprehensive engagement metrics
   - `mood_trend_last_30_days` - Recent mood tracking
   - `low_mood_alerts` - Mental health intervention alerts
   - `therapy_session_effectiveness` - Counselor performance metrics
   - `resource_popularity` - Content engagement tracking

10. **030_minddb_indexes.sql** - Adds 9 performance optimization indexes:
   - `resources.idx_active_featured` - Featured resources composite
   - `resources.idx_view_count` - Popularity sorting
   - `anonymized_users.idx_user_id` - Privacy layer lookup
   - `helplines.idx_country` - Geographic filtering
   - `users.idx_email` - Email-based authentication
   - `users.idx_role` - Role-based filtering
   - `conversations.idx_user_last_message` - Recent conversations
   - `messages.idx_conv_created` - Message threading
   - `strategies.idx_is_active` - Active strategy filtering
   - Note: Verifies existing indexes from 10_minddb_schema.sql

11. **040_minddb_procedures_triggers.sql** - Creates stored procedures and audit triggers:
   - `sp_get_weekly_summary(week_start, week_end)` - Weekly mood reports
   - `mood_logs_audit` - Audit trail table with 4 indexes
   - `trg_mood_logs_before_insert` - Auto-audit on INSERT
   - `trg_mood_logs_after_insert` - Update audit with actual ID
   - `trg_mood_logs_before_update` - Auto-audit on UPDATE
   - `trg_mood_logs_before_delete` - Auto-audit on DELETE

---

## How to Execute

### Method 1: Command Line (Recommended)

```powershell
# Navigate to project directory
cd c:\xampp\htdocs\MindDB

# Backup database first!
mysqldump -u root -p -P 3307 mindmate > backup_before_normalization.sql

# Execute scripts in order
mysql -u root -p -P 3307 mindmate < database/sql/001_create_users_table.sql
mysql -u root -p -P 3307 mindmate < database/sql/002_add_fk_messages_users.sql
mysql -u root -p -P 3307 mindmate < database/sql/003_create_strategies_table.sql
mysql -u root -p -P 3307 mindmate < database/sql/004_normalize_strategies.sql

# Optional enhancements
mysql -u root -p -P 3307 mindmate < database/sql/005_create_conversations_table.sql
mysql -u root -p -P 3307 mindmate < database/sql/006_add_audit_fields.sql

# Mental health tracking features
mysql -u root -p -P 3307 mindmate < database/sql/010_minddb_schema.sql
mysql -u root -p -P 3307 mindmate < database/sql/011_seed_anonymized_users.sql

# Analytical views for reporting
mysql -u root -p -P 3307 mindmate < database/sql/020_minddb_views.sql

# Performance optimization indexes
mysql -u root -p -P 3307 mindmate < database/sql/030_minddb_indexes.sql

# Stored procedures and audit triggers
mysql -u root -p -P 3307 mindmate < database/sql/040_minddb_procedures_triggers.sql
```

### Method 2: phpMyAdmin

1. Open phpMyAdmin at http://localhost/phpmyadmin
2. Select the `mindmate` database
3. Click on "SQL" tab
4. Copy and paste the content of each SQL file
5. Click "Go" to execute

### Method 3: MySQL Workbench

1. Open MySQL Workbench
2. Connect to localhost:3307
3. Open each SQL file
4. Execute in order

## Pre-Execution Checklist

Before running these scripts:

- [ ] XAMPP MySQL is running on port 3307
- [ ] Database backup has been created
- [ ] You have reviewed the changes in `docs/erd_current.md`
- [ ] All existing user_id values in messages table are valid
- [ ] No application code is currently writing to the database

## Post-Execution Verification

After each phase, verify the changes:

```sql
-- Check table structure
SHOW TABLES;

-- View all tables and views
SELECT TABLE_NAME, TABLE_TYPE 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'mindmate' 
ORDER BY TABLE_TYPE, TABLE_NAME;

-- View foreign keys
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'mindmate'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Count records in tables
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'strategies', COUNT(*) FROM strategies
UNION ALL
SELECT 'conversations', COUNT(*) FROM conversations
UNION ALL
SELECT 'messages', COUNT(*) FROM messages
UNION ALL
SELECT 'anonymized_users', COUNT(*) FROM anonymized_users
UNION ALL
SELECT 'mood_logs', COUNT(*) FROM mood_logs
UNION ALL
SELECT 'helplines', COUNT(*) FROM helplines
UNION ALL
SELECT 'resources', COUNT(*) FROM resources;

-- Test analytical views
SELECT COUNT(*) as mood_weeks FROM weekly_mood_avg_by_pseudonym;
SELECT COUNT(*) as unique_tags FROM mood_tag_counts;
SELECT COUNT(*) as active_users FROM user_activity_summary WHERE total_mood_logs > 0;
SELECT COUNT(*) as users_needing_support FROM low_mood_alerts;

-- Verify stored procedures and triggers
SHOW PROCEDURE STATUS WHERE Db = 'mindmate';
SHOW TRIGGERS FROM mindmate WHERE `Trigger` LIKE 'trg_mood_logs%';
SELECT COUNT(*) as audit_records FROM mood_logs_audit;

-- Test stored procedure
CALL sp_get_weekly_summary(DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE());

-- Verify performance indexes
SELECT TABLE_NAME, COUNT(DISTINCT INDEX_NAME) as index_count
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mindmate'
AND TABLE_NAME IN ('mood_logs', 'therapy_sessions', 'resources', 'users', 'messages')
GROUP BY TABLE_NAME
ORDER BY index_count DESC;
```

## Rollback Instructions

If you need to rollback:

```powershell
# Restore from backup
mysql -u root -p -P 3307 mindmate < backup_before_normalization.sql
```

## Important Notes

- **002_add_fk_messages_users.sql** requires all user_id values in messages to exist in the users table
- **004_normalize_strategies.sql** keeps the old strategy_used column as strategy_used_legacy for safety
- **005_create_conversations_table.sql** creates one conversation per user by default
- **006_add_audit_fields.sql** creates triggers that automatically track all changes

## Troubleshooting

### Error: Cannot add foreign key constraint

**Cause:** Orphaned records (messages with user_id not in users table)

**Solution:** Run the orphan check queries in the SQL files and clean up data before adding FK

### Error: Duplicate entry

**Cause:** Trying to insert duplicate usernames or emails

**Solution:** Check existing data and ensure uniqueness

### Error: Unknown database

**Cause:** Database name mismatch

**Solution:** Verify database name with `SHOW DATABASES;` and update scripts if needed

## Support

For detailed analysis and recommendations, see:
- `docs/erd_current.md` - Complete ERD analysis and normalization guide
- `ANALYTICS_VIEWS_CREATED.md` - Analytical views documentation with usage examples
- `DATABASE_INDEXES_CREATED.md` - Performance index documentation
- `PROCEDURES_TRIGGERS_CREATED.md` - Stored procedures and triggers documentation
- `DATABASE_SETUP_COMPLETE.md` - PHP usage examples and verification
- `MOOD_TRACKING_SEEDED.md` - Sample data documentation
- `analyze_db.php` - Database structure analysis script
- `db_structure.json` - Raw database structure data

## Default Credentials

The scripts create a default admin user:
- **Username:** admin
- **Email:** admin@minddb.local
- **Password:** admin123
- **⚠️ IMPORTANT:** Change this password immediately in production!

```sql
-- Change admin password
UPDATE users 
SET password_hash = PASSWORD_HASH('your_new_secure_password') 
WHERE username = 'admin';
```
