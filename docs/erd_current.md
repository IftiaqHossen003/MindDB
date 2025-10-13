# MindDB Entity-Relationship Diagram (ERD) - Current State

**Database Name:** mindmate  
**XAMPP Port:** 3307  
**Last Updated:** October 13, 2025  
**Normalization Level:** 3NF (Third Normal Form)

---

## Executive Summary

The MindDB database consists of **9 base tables** and **2 views**, fully normalized to 3NF with comprehensive foreign key constraints enforcing referential integrity. The database supports user authentication, AI conversation management, mood tracking, therapy session recording, crisis resources, and educational materials.

---

## Entity-Relationship Diagram (ASCII)

```
┌─────────────────────┐
│       USERS         │
│  (Authentication)   │
├─────────────────────┤
│ PK  id              │
│     username        │
│     email           │
│     password_hash   │
│     role            │
│     created_at      │
└──────┬──────────────┘
       │ 1
       │
       ├──────────────────────────────┐
       │                              │
       │ N                            │ N
┌──────┴──────────────┐      ┌───────┴─────────────┐
│   CONVERSATIONS     │      │  ANONYMIZED_USERS   │
│   (Threading)       │      │  (Privacy Layer)    │
├─────────────────────┤      ├─────────────────────┤
│ PK  id              │      │ PK  anonym_id       │
│ FK  user_id         │      │ FK  user_id (NULL)  │
│     title           │      │     pseudonym       │
│     started_at      │      │     created_at      │
│     message_count   │      └──────┬──────────────┘
└──────┬──────────────┘             │ 1
       │ 1                           │
       │                             ├────────────────────┐
       │ N                           │ N                  │ N
┌──────┴──────────────┐      ┌──────┴──────┐    ┌────────┴──────────┐
│     MESSAGES        │◄─┐   │  MOOD_LOGS  │    │ THERAPY_SESSIONS  │
│   (Conversations)   │  │   │  (Tracking) │    │  (Counseling)     │
├─────────────────────┤  │   ├─────────────┤    ├───────────────────┤
│ PK  id              │  │   │ PK  id      │    │ PK  id            │
│ FK  user_id         │  │   │ FK  anonym  │    │ FK  anonym_id     │
│ FK  conversation_id │  │   │     mood    │    │     counselor     │
│ FK  strategy_id     │──┘   │     tag     │    │     session_date  │
│     message         │      │     log_ts  │    │     notes         │
│     response        │      │     notes   │    │     rating        │
│     created_at      │      └─────────────┘    └───────────────────┘
└─────────────────────┘

┌──────────────────┐      ┌────────────────────┐      ┌──────────────────┐
│   STRATEGIES     │      │    HELPLINES       │      │    RESOURCES     │
│   (AI Methods)   │      │  (Crisis Support)  │      │  (Education)     │
├──────────────────┤      ├────────────────────┤      ├──────────────────┤
│ PK  id           │      │ PK  id             │      │ PK  id           │
│     name         │      │     name           │      │     title        │
│     description  │      │     phone          │      │     type         │
│     is_active    │      │     hours          │      │     url          │
└──────────────────┘      │     country        │      │     category     │
         ▲                │     description    │      │     is_featured  │
         │                └────────────────────┘      └──────────────────┘
         │ N
         │
    (referenced by messages)

VIEWS:
- messages_active: Non-deleted messages (WHERE deleted_at IS NULL)
- conversation_summary: Conversation stats with message counts
```

---

## Complete Table Structure

### 1. users (Authentication & Authorization)
**Purpose:** Store user accounts with authentication credentials

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT(11) | NO | PRI | Primary key, auto-increment |
| username | VARCHAR(100) | NO | UNI | Unique username |
| email | VARCHAR(255) | NO | UNI | Unique email address |
| password_hash | VARCHAR(255) | NO | | Hashed password |
| role | ENUM('user','admin') | NO | | User role |
| created_at | DATETIME | YES | | Account creation timestamp |
| updated_at | DATETIME | YES | | Last update timestamp |
| last_login | DATETIME | YES | | Last login timestamp |
| is_active | BOOLEAN | YES | | Account active status |

**Indexes:** PRIMARY(id), UNIQUE(username), UNIQUE(email), idx_role(role)

---

### 2. strategies (AI Response Strategies)
**Purpose:** Lookup table for therapeutic AI response methods

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT(11) | NO | PRI | Primary key |
| name | VARCHAR(64) | NO | UNI | Strategy name (cognitive_behavioral, mindfulness, etc.) |
| description | TEXT | YES | | Strategy description |
| is_active | BOOLEAN | YES | | Whether strategy is active |
| created_at | DATETIME | YES | | Creation timestamp |

**Indexes:** PRIMARY(id), UNIQUE(name), idx_is_active(is_active)

**Sample Data:** 9 strategies (cognitive_behavioral, mindfulness, empathetic, etc.)

---

### 3. conversations (Message Threading)
**Purpose:** Group messages into conversation threads

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT(11) | NO | PRI | Primary key |
| user_id | INT(11) | NO | FK | Foreign key to users |
| title | VARCHAR(255) | YES | | Conversation title |
| started_at | DATETIME | YES | | Conversation start time |
| last_message_at | DATETIME | YES | | Last message timestamp |
| is_active | BOOLEAN | YES | | Active status |
| message_count | INT(11) | YES | | Number of messages |

**Foreign Keys:** user_id → users(id) ON DELETE CASCADE

**Indexes:** PRIMARY(id), idx_user_id(user_id), idx_last_message(last_message_at)

---

### 4. messages (User Messages & AI Responses)
**Purpose:** Store user messages with AI responses and strategy metadata

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT(11) | NO | PRI | Primary key |
| user_id | INT(11) | NO | FK | Foreign key to users |
| conversation_id | INT(11) | NO | FK | Foreign key to conversations |
| strategy_id | INT(11) | NO | FK | Foreign key to strategies |
| message | TEXT | NO | | User's message |
| response | TEXT | NO | | AI's response |
| created_at | DATETIME | YES | | Message timestamp |
| updated_at | DATETIME | YES | | Last update timestamp |
| deleted_at | DATETIME | YES | | Soft delete timestamp |

**Foreign Keys:**
- user_id → users(id) ON DELETE CASCADE
- conversation_id → conversations(id) ON DELETE CASCADE
- strategy_id → strategies(id) ON DELETE RESTRICT

**Indexes:** PRIMARY(id), idx_user_id, idx_conversation_id, idx_strategy_id, idx_created_at, idx_deleted_at

---

### 5. anonymized_users (Privacy Layer)
**Purpose:** Pseudonymized identities for privacy-conscious features

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| anonym_id | INT(11) | NO | PRI | Primary key |
| user_id | INT(11) | YES | FK | Nullable foreign key to users |
| pseudonym | VARCHAR(50) | NO | | Pseudonym (User_1, User_2, etc.) |
| created_at | DATETIME | YES | | Creation timestamp |

**Foreign Keys:** user_id → users(id) ON DELETE SET NULL (preserves data if user deleted)

**Indexes:** PRIMARY(anonym_id), idx_user_id(user_id), idx_pseudonym(pseudonym)

**Privacy Feature:** Nullable user_id allows complete anonymization while preserving mood/therapy data

---

### 6. mood_logs (Emotional Wellbeing Tracking) ⭐
**Purpose:** Track user mood levels and emotional states over time

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT(11) | NO | PRI | Primary key |
| anonym_id | INT(11) | NO | FK | Foreign key to anonymized_users |
| mood_level | TINYINT | NO | | Mood scale 1-10 (1=very low, 10=excellent) |
| mood_tag | VARCHAR(50) | YES | | Tag: anxious, happy, stressed, calm, etc. |
| log_ts | DATETIME | YES | | Timestamp when mood was logged |
| notes | TEXT | YES | | Optional user notes about mood |

**Foreign Keys:** anonym_id → anonymized_users(anonym_id) ON DELETE CASCADE

**Indexes:** 
- PRIMARY(id)
- idx_anonym_id(anonym_id)
- idx_log_ts(log_ts) ⭐ For time-series queries
- idx_mood_level(mood_level)
- idx_mood_tag(mood_tag)
- idx_anonym_log_ts(anonym_id, log_ts) ⭐ Composite for user timelines

**Constraints:** CHECK (mood_level >= 1 AND mood_level <= 10)

---

### 7. therapy_sessions (Counseling Records)
**Purpose:** Track therapy/counseling sessions with privacy protection

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT(11) | NO | PRI | Primary key |
| anonym_id | INT(11) | NO | FK | Foreign key to anonymized_users |
| counselor_name | VARCHAR(150) | YES | | Counselor name or ID |
| session_date | DATETIME | NO | | Session date and time |
| duration_minutes | INT(11) | YES | | Session duration |
| session_type | VARCHAR(50) | YES | | Type: individual, group, family, online |
| notes | TEXT | YES | | Private session notes |
| rating | TINYINT | YES | | Session rating 1-5 |
| created_at | DATETIME | YES | | Record creation timestamp |
| updated_at | DATETIME | YES | | Last update timestamp |

**Foreign Keys:** anonym_id → anonymized_users(anonym_id) ON DELETE CASCADE

**Indexes:** idx_anonym_id, idx_session_date, idx_counselor_name, idx_session_type

**Constraints:** CHECK (rating IS NULL OR (rating >= 1 AND rating <= 5))

---

### 8. helplines (Crisis Support Resources)
**Purpose:** Mental health helpline contacts and emergency resources

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT(11) | NO | PRI | Primary key |
| name | VARCHAR(255) | NO | | Helpline organization name |
| phone | VARCHAR(50) | NO | | Phone number with country code |
| hours | VARCHAR(100) | YES | | Operating hours (e.g., "24/7") |
| country | VARCHAR(100) | YES | | Country or region served |
| description | TEXT | YES | | Service description |
| website | VARCHAR(255) | YES | | Official website URL |
| is_active | BOOLEAN | YES | | Active status |
| created_at | DATETIME | YES | | Creation timestamp |
| updated_at | DATETIME | YES | | Last update timestamp |

**Indexes:** PRIMARY(id), idx_country(country), idx_is_active(is_active), idx_name(name)

**Sample Data:** 7 helplines (USA, UK, Australia, Canada, International)

---

### 9. resources (Educational Materials)
**Purpose:** Mental health educational resources and self-help tools

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT(11) | NO | PRI | Primary key |
| title | VARCHAR(255) | NO | | Resource title |
| resource_type | ENUM | NO | | article, video, audio, tool, exercise, book, app, other |
| url | VARCHAR(500) | YES | | External URL or file path |
| description | TEXT | YES | | Resource description |
| category | VARCHAR(100) | YES | | anxiety, depression, stress, mindfulness, etc. |
| author | VARCHAR(150) | YES | | Author or creator name |
| duration_minutes | INT(11) | YES | | Duration for videos/audio |
| difficulty_level | ENUM | YES | | beginner, intermediate, advanced |
| is_featured | BOOLEAN | YES | | Featured on homepage |
| is_active | BOOLEAN | YES | | Active status |
| view_count | INT(11) | YES | | Number of views |
| created_at | DATETIME | YES | | Creation timestamp |
| updated_at | DATETIME | YES | | Last update timestamp |

**Indexes:** 
- PRIMARY(id)
- idx_resource_type, idx_category, idx_is_featured, idx_is_active
- FULLTEXT idx_search(title, description) ⭐ For search functionality

**Sample Data:** 8 resources (articles, videos, tools, apps)

---

## Normalization Analysis

### Normal Form Status: 3NF ✅

#### 1NF (First Normal Form) ✅ ACHIEVED
- ✅ All columns contain atomic values
- ✅ No repeating groups
- ✅ Primary keys exist on all tables
- ✅ Each row is unique

#### 2NF (Second Normal Form) ✅ ACHIEVED
- ✅ In 1NF
- ✅ No partial dependencies (all PKs are single-column)
- ✅ All non-key attributes depend on entire primary key

#### 3NF (Third Normal Form) ✅ ACHIEVED
- ✅ In 2NF
- ✅ No transitive dependencies
- ✅ Strategies normalized into lookup table
- ✅ Users separated from conversations and mood logs
- ✅ Anonymization layer eliminates direct user references in sensitive data

---

## Foreign Key Relationships

### Complete FK Network (7 constraints)

```
users (1)
  ├── conversations (N) - CASCADE DELETE
  ├── messages (N) - CASCADE DELETE
  └── anonymized_users (N) - SET NULL DELETE (privacy preservation)

anonymized_users (1)
  ├── mood_logs (N) - CASCADE DELETE
  └── therapy_sessions (N) - CASCADE DELETE

conversations (1)
  └── messages (N) - CASCADE DELETE

strategies (1)
  └── messages (N) - RESTRICT DELETE (prevent accidental deletion)
```

### FK Constraints Summary

| Child Table | Column | References | On Delete | On Update |
|-------------|--------|------------|-----------|-----------|
| conversations | user_id | users(id) | CASCADE | CASCADE |
| messages | user_id | users(id) | CASCADE | CASCADE |
| messages | conversation_id | conversations(id) | CASCADE | CASCADE |
| messages | strategy_id | strategies(id) | RESTRICT | CASCADE |
| anonymized_users | user_id | users(id) | SET NULL | CASCADE |
| mood_logs | anonym_id | anonymized_users(anonym_id) | CASCADE | CASCADE |
| therapy_sessions | anonym_id | anonymized_users(anonym_id) | CASCADE | CASCADE |

---

## Views

### 1. messages_active
**Purpose:** Filter out soft-deleted messages
```sql
CREATE VIEW messages_active AS
SELECT * FROM messages WHERE deleted_at IS NULL;
```

### 2. conversation_summary
**Purpose:** Aggregate conversation statistics
```sql
CREATE VIEW conversation_summary AS
SELECT 
    c.id, c.user_id, u.username, c.title,
    c.started_at, c.last_message_at, c.is_active,
    COUNT(m.id) as actual_message_count,
    MAX(m.created_at) as last_message_timestamp
FROM conversations c
JOIN users u ON c.user_id = u.id
LEFT JOIN messages m ON c.id = m.conversation_id AND m.deleted_at IS NULL
GROUP BY c.id, ...;
```

---

## Index Strategy

### Performance Optimizations

1. **Primary Keys:** All tables have auto-increment integer PKs
2. **Foreign Keys:** Indexed automatically for JOIN performance
3. **Time-Series Queries:** Indexes on created_at, log_ts, session_date
4. **Composite Indexes:** (anonym_id, log_ts) for user mood timelines
5. **Fulltext Search:** On resources(title, description) for content discovery
6. **Filtered Queries:** Indexes on is_active, is_featured, mood_tag

---

## Database Statistics

| Table | Rows | Purpose |
|-------|------|---------|
| users | 2 | admin + demo_user |
| strategies | 9 | AI response methods |
| conversations | 1 | Sample conversation |
| messages | 3 | Sample messages |
| anonymized_users | 2 | User_1, User_2 |
| mood_logs | 10 | 10 days of mood data |
| therapy_sessions | 0 | Ready for data |
| helplines | 7 | International crisis resources |
| resources | 8 | Educational materials |

**Total Tables:** 9 base tables + 2 views = 11  
**Total Foreign Keys:** 7 constraints  
**Total Sample Records:** 41

---

## Privacy & Security Features

### 1. Anonymization Layer
- `anonymized_users` provides pseudonymization
- Mood and therapy data linked to pseudonyms, not real users
- `user_id` is nullable with `ON DELETE SET NULL`
- Data persists even if user account deleted

### 2. Soft Deletes
- `messages.deleted_at` allows recovery
- `messages_active` view filters deleted records
- Audit trail preserved

### 3. Data Integrity
- Foreign key constraints enforce referential integrity
- CHECK constraints validate mood_level (1-10) and rating (1-5)
- ENUM types limit values to predefined options
- NOT NULL constraints on critical fields

### 4. Access Control
- `users.role` supports RBAC (role-based access control)
- Admin vs. user distinction
- Session-based auth via `$_SESSION['user_id']`

---

## Use Cases Supported

### 1. AI Conversations
- User authentication
- Multi-turn conversation threading
- Strategy-based response generation
- Message history with timestamps

### 2. Mood Tracking
- Daily mood logging (1-10 scale)
- Mood tag categorization
- Time-series trend analysis
- Privacy-protected data

### 3. Therapy Management
- Session scheduling and records
- Counselor tracking
- Session notes and ratings
- Privacy-protected records

### 4. Crisis Support
- Helpline directory
- International coverage
- Operating hours information
- Active/inactive status

### 5. Educational Resources
- Categorized materials
- Resource type filtering
- Featured content
- Full-text search capability
- View tracking

---

## Verification Commands

### Check All Tables
```sql
SHOW TABLES;
```

### Describe mood_logs
```sql
DESCRIBE mood_logs;
SHOW INDEX FROM mood_logs;
```

### Check Foreign Keys
```sql
SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'mindmate' AND REFERENCED_TABLE_NAME IS NOT NULL;
```

### Check Data
```sql
SELECT 'users' as tbl, COUNT(*) as cnt FROM users
UNION ALL SELECT 'anonymized_users', COUNT(*) FROM anonymized_users
UNION ALL SELECT 'mood_logs', COUNT(*) FROM mood_logs
UNION ALL SELECT 'conversations', COUNT(*) FROM conversations
UNION ALL SELECT 'messages', COUNT(*) FROM messages;
```

---

## Conclusion

The MindDB database is fully normalized to 3NF with:
- ✅ Comprehensive foreign key constraints
- ✅ Privacy-focused anonymization layer
- ✅ Optimized indexes for performance
- ✅ Data integrity through CHECK constraints
- ✅ Soft delete capability
- ✅ Audit trail support
- ✅ Fulltext search capability
- ✅ International resource support

**Database Status:** Production-ready with sample data for testing

---

*Documentation Generated: October 13, 2025*  
*Database: mindmate on XAMPP MySQL Port 3307*  
*Schema Version: 1.0*  
*Normalization Level: 3NF*
