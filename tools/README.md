# MindDB Tools

This directory contains utility scripts for database management and data export.

## 📁 Available Tools

### export_anonymized_csv.php

**Purpose:** Export anonymized user activity data to CSV format without PII

**Usage:**
```bash
php tools/export_anonymized_csv.php
```

**Output:** `exports/anonymized_report_YYYY-MM-DD_HHMMSS.csv`

**Features:**
- ✅ Privacy-first (no PII)
- ✅ Auto-detects PDO/mysqli
- ✅ Memory-safe streaming
- ✅ Prepared statements

**Documentation:** See [EXPORT_TOOL_DOCUMENTATION.md](../EXPORT_TOOL_DOCUMENTATION.md)

---

## 🔐 Security

All tools follow these security principles:

1. **Privacy First** - No PII in exports
2. **Read-Only** - No production table modifications
3. **Prepared Statements** - SQL injection prevention
4. **Memory-Safe** - Unbuffered queries for large datasets

---

## 📚 Documentation

- [EXPORT_TOOL_DOCUMENTATION.md](../EXPORT_TOOL_DOCUMENTATION.md) - Complete export tool guide
- [EXPORT_TOOL_CREATED.md](../EXPORT_TOOL_CREATED.md) - Task completion summary

---

## 🚀 Quick Start

```bash
# Export anonymized data
php tools/export_anonymized_csv.php

# View exports
dir exports\

# Read CSV
type exports\anonymized_report_*.csv
```
