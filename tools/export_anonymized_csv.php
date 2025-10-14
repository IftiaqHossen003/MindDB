<?php
/**
 * Anonymized Data Export Tool
 * 
 * This script exports anonymized user activity data to CSV format without any
 * personally identifiable information (PII). Only pseudonyms are used.
 * 
 * @package    MindDB
 * @subpackage Tools
 * @version    1.0.0
 * @author     MindDB Team
 * @license    MIT
 * 
 * FEATURES:
 * - Auto-detects PDO or mysqli database connection from config.php
 * - Memory-safe streaming (unbuffered queries for large datasets)
 * - No PII exposure (only pseudonyms, no emails, usernames, real names)
 * - Uses read-only views and tables (no production table alterations)
 * - Timestamped filenames for audit trail
 * - Prepared statements for security
 * - Comprehensive error handling
 * 
 * DATA SOURCES:
 * - anonymized_users table: Pseudonym mapping
 * - user_activity_summary view: Aggregated mood and therapy metrics
 * 
 * OUTPUT COLUMNS:
 * - pseudonym: Anonymous identifier (e.g., "User_1")
 * - avg_mood_last_30_days: Average mood level (0-10 scale) for last 30 days
 * - total_journals: Total number of mood log entries
 * - last_journal_date: Most recent mood entry date (YYYY-MM-DD)
 * 
 * SECURITY:
 * - NO user_id, email, username, or real name in output
 * - Uses prepared statements to prevent SQL injection
 * - Read-only operations (SELECT only)
 * - No modifications to production database
 * 
 * USAGE:
 * 
 *   Command Line (Recommended):
 *   php tools/export_anonymized_csv.php
 * 
 *   PHP Script Include:
 *   require_once 'tools/export_anonymized_csv.php';
 *   $exporter = new AnonymizedDataExporter();
 *   $result = $exporter->export();
 *   echo "Exported to: {$result['file']}\n";
 * 
 * OUTPUT:
 *   File: exports/anonymized_report_YYYY-MM-DD_HHMMSS.csv
 *   Format: UTF-8 CSV with headers
 * 
 * REQUIREMENTS:
 * - PHP 7.4+
 * - MySQL/MariaDB database
 * - config.php file with database connection
 * - user_activity_summary view created (from 020_minddb_views.sql)
 * - anonymized_users table (from 010_minddb_schema.sql)
 * 
 * ERROR HANDLING:
 * - Validates config.php exists
 * - Checks database connection
 * - Verifies required tables/views exist
 * - Validates exports directory writable
 * - Comprehensive error messages
 * 
 * @example
 * ```bash
 * # Export all anonymized data
 * php tools/export_anonymized_csv.php
 * 
 * # Output example:
 * Starting anonymized data export...
 * Database connection: PDO (MySQL)
 * Query rows: 5 users
 * CSV file created: exports/anonymized_report_2025-10-14_143052.csv
 * Export completed successfully!
 * ```
 */

// Prevent direct browser access (optional, comment out if needed)
if (php_sapi_name() !== 'cli') {
    // Allow both CLI and web access, but warn for web
    if (!defined('EXPORT_WEB_ACCESS_ALLOWED')) {
        echo "⚠️ WARNING: This script is designed for CLI usage. ";
        echo "For security, web access is restricted by default.\n";
        echo "To enable web access, define EXPORT_WEB_ACCESS_ALLOWED in your config.\n";
        exit(1);
    }
}

/**
 * AnonymizedDataExporter Class
 * 
 * Handles secure export of anonymized user activity data to CSV format.
 * Uses memory-safe streaming for large datasets and auto-detects database
 * connection type (PDO or mysqli).
 */
class AnonymizedDataExporter
{
    /**
     * Database connection object (PDO or mysqli)
     * @var PDO|mysqli|null
     */
    private $db = null;

    /**
     * Database connection type: 'pdo' or 'mysqli'
     * @var string
     */
    private $dbType = '';

    /**
     * Export directory path
     * @var string
     */
    private $exportDir = '';

    /**
     * Export file path
     * @var string
     */
    private $exportFile = '';

    /**
     * Error messages array
     * @var array
     */
    private $errors = [];

    /**
     * Constructor - Initialize exporter
     * 
     * @param string|null $configPath Path to config.php (optional)
     */
    public function __construct($configPath = null)
    {
        // Determine config path
        if ($configPath === null) {
            $configPath = __DIR__ . '/../config.php';
        }

        // Validate config file exists
        if (!file_exists($configPath)) {
            $this->addError("Config file not found: {$configPath}");
            $this->addError("Please ensure config.php exists with database connection.");
            return;
        }

        // Load config
        require_once $configPath;

        // Initialize database connection
        $this->initDatabase();

        // Set export directory
        $this->exportDir = __DIR__ . '/../exports';
        
        // Ensure exports directory exists
        if (!is_dir($this->exportDir)) {
            if (!mkdir($this->exportDir, 0755, true)) {
                $this->addError("Failed to create exports directory: {$this->exportDir}");
            }
        }

        // Validate exports directory is writable
        if (!is_writable($this->exportDir)) {
            $this->addError("Exports directory is not writable: {$this->exportDir}");
        }
    }

    /**
     * Initialize database connection by auto-detecting connection type
     * 
     * Checks for existing database connection functions/objects:
     * - getDb() function returning PDO
     * - $db global variable (PDO or mysqli)
     * - Direct database connection attempt
     * 
     * @return void
     */
    private function initDatabase()
    {
        // Method 1: Check for getDb() function (preferred pattern)
        if (function_exists('getDb')) {
            $this->db = getDb();
            
            if ($this->db instanceof PDO) {
                $this->dbType = 'pdo';
                // Enable unbuffered queries for memory efficiency
                $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                return;
            }
            
            if ($this->db instanceof mysqli) {
                $this->dbType = 'mysqli';
                return;
            }
        }

        // Method 2: Check for global $db variable
        if (isset($GLOBALS['db'])) {
            $this->db = $GLOBALS['db'];
            
            if ($this->db instanceof PDO) {
                $this->dbType = 'pdo';
                $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                return;
            }
            
            if ($this->db instanceof mysqli) {
                $this->dbType = 'mysqli';
                return;
            }
        }

        // Method 3: Check for database configuration constants/variables
        $dbConfig = $this->detectDatabaseConfig();
        
        if ($dbConfig) {
            // Try PDO connection first
            try {
                $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
                $this->db = new PDO(
                    $dsn,
                    $dbConfig['username'],
                    $dbConfig['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // Memory-safe streaming
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                $this->dbType = 'pdo';
                return;
            } catch (PDOException $e) {
                // PDO failed, try mysqli
                try {
                    $this->db = new mysqli(
                        $dbConfig['host'],
                        $dbConfig['username'],
                        $dbConfig['password'],
                        $dbConfig['database'],
                        $dbConfig['port']
                    );
                    
                    if ($this->db->connect_error) {
                        throw new Exception($this->db->connect_error);
                    }
                    
                    $this->db->set_charset('utf8mb4');
                    $this->dbType = 'mysqli';
                    return;
                } catch (Exception $e) {
                    $this->addError("Database connection failed: " . $e->getMessage());
                }
            }
        }

        // If we get here, all connection methods failed
        if (!$this->db) {
            $this->addError("Could not establish database connection.");
            $this->addError("Please ensure config.php defines database connection.");
        }
    }

    /**
     * Detect database configuration from various sources
     * 
     * Checks for common configuration patterns:
     * - Defined constants (DB_HOST, DB_NAME, etc.)
     * - Global variables ($db_host, $db_name, etc.)
     * - Config arrays ($config['db'], etc.)
     * 
     * @return array|null Database configuration or null if not found
     */
    private function detectDatabaseConfig()
    {
        $config = [
            'host' => 'localhost',
            'port' => 3307, // XAMPP default for MindDB
            'database' => 'mindmate',
            'username' => 'root',
            'password' => ''
        ];

        // Check for defined constants
        if (defined('DB_HOST')) $config['host'] = DB_HOST;
        if (defined('DB_PORT')) $config['port'] = DB_PORT;
        if (defined('DB_NAME')) $config['database'] = DB_NAME;
        if (defined('DB_USER')) $config['username'] = DB_USER;
        if (defined('DB_PASSWORD')) $config['password'] = DB_PASSWORD;

        // Check for global variables
        if (isset($GLOBALS['db_host'])) $config['host'] = $GLOBALS['db_host'];
        if (isset($GLOBALS['db_port'])) $config['port'] = $GLOBALS['db_port'];
        if (isset($GLOBALS['db_name'])) $config['database'] = $GLOBALS['db_name'];
        if (isset($GLOBALS['db_user'])) $config['username'] = $GLOBALS['db_user'];
        if (isset($GLOBALS['db_password'])) $config['password'] = $GLOBALS['db_password'];

        // Check for config array
        if (isset($GLOBALS['config']['database'])) {
            $dbConfig = $GLOBALS['config']['database'];
            if (isset($dbConfig['host'])) $config['host'] = $dbConfig['host'];
            if (isset($dbConfig['port'])) $config['port'] = $dbConfig['port'];
            if (isset($dbConfig['name'])) $config['database'] = $dbConfig['name'];
            if (isset($dbConfig['username'])) $config['username'] = $dbConfig['username'];
            if (isset($dbConfig['password'])) $config['password'] = $dbConfig['password'];
        }

        return $config;
    }

    /**
     * Add error message to errors array
     * 
     * @param string $message Error message
     * @return void
     */
    private function addError($message)
    {
        $this->errors[] = $message;
    }

    /**
     * Get all error messages
     * 
     * @return array Array of error messages
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check if exporter has errors
     * 
     * @return bool True if errors exist
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Validate required database tables and views exist
     * 
     * Checks for:
     * - anonymized_users table
     * - user_activity_summary view
     * 
     * @return bool True if all required objects exist
     */
    private function validateDatabaseStructure()
    {
        $requiredObjects = [
            'anonymized_users' => 'table',
            'user_activity_summary' => 'view'
        ];

        foreach ($requiredObjects as $object => $type) {
            if (!$this->objectExists($object, $type)) {
                $this->addError("Required {$type} not found: {$object}");
                $this->addError("Please run database migrations (010_minddb_schema.sql and 020_minddb_views.sql)");
                return false;
            }
        }

        return true;
    }

    /**
     * Check if database table or view exists
     * 
     * @param string $objectName Table or view name
     * @param string $type 'table' or 'view'
     * @return bool True if object exists
     */
    private function objectExists($objectName, $type = 'table')
    {
        $tableType = ($type === 'view') ? 'VIEW' : 'BASE TABLE';
        
        $sql = "
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
            AND TABLE_TYPE = ?
        ";

        if ($this->dbType === 'pdo') {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$objectName, $tableType]);
            $result = $stmt->fetch();
            return ($result['count'] > 0);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ss', $objectName, $tableType);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return ($row['count'] > 0);
        }
    }

    /**
     * Execute export process
     * 
     * Main export workflow:
     * 1. Validate database structure
     * 2. Query anonymized data (memory-safe streaming)
     * 3. Write CSV file with headers
     * 4. Return export results
     * 
     * @return array Export results with status, file path, row count, errors
     */
    public function export()
    {
        $startTime = microtime(true);

        // Check for initialization errors
        if ($this->hasErrors()) {
            return [
                'success' => false,
                'errors' => $this->getErrors(),
                'file' => null,
                'rows' => 0,
                'time' => 0
            ];
        }

        echo "Starting anonymized data export...\n";
        echo "Database connection: " . strtoupper($this->dbType) . "\n";

        // Validate database structure
        if (!$this->validateDatabaseStructure()) {
            return [
                'success' => false,
                'errors' => $this->getErrors(),
                'file' => null,
                'rows' => 0,
                'time' => 0
            ];
        }

        // Generate timestamped filename
        $timestamp = date('Y-m-d_His');
        $this->exportFile = $this->exportDir . "/anonymized_report_{$timestamp}.csv";

        // Open CSV file for writing
        $fp = fopen($this->exportFile, 'w');
        if (!$fp) {
            $this->addError("Failed to create CSV file: {$this->exportFile}");
            return [
                'success' => false,
                'errors' => $this->getErrors(),
                'file' => null,
                'rows' => 0,
                'time' => 0
            ];
        }

        // Write UTF-8 BOM for Excel compatibility
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write CSV headers
        $headers = [
            'pseudonym',
            'avg_mood_last_30_days',
            'total_journals',
            'last_journal_date'
        ];
        fputcsv($fp, $headers);

        // Query anonymized data with 30-day mood average
        // NO PII: Only pseudonym, aggregated metrics, dates
        $sql = "
            SELECT 
                au.pseudonym,
                COALESCE(
                    ROUND(
                        (SELECT AVG(ml.mood_level) 
                         FROM mood_logs ml 
                         WHERE ml.anonym_id = au.anonym_id 
                         AND ml.log_ts >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
                        2
                    ),
                    0
                ) as avg_mood_last_30_days,
                uas.total_mood_logs as total_journals,
                uas.last_mood_log_date as last_journal_date
            FROM anonymized_users au
            LEFT JOIN user_activity_summary uas ON au.anonym_id = uas.anonym_id
            ORDER BY au.pseudonym ASC
        ";

        $rowCount = 0;

        try {
            if ($this->dbType === 'pdo') {
                // PDO: Unbuffered query for memory efficiency
                $stmt = $this->db->query($sql);
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Prepare row data
                    $csvRow = [
                        $row['pseudonym'],
                        $row['avg_mood_last_30_days'] ?? '0.00',
                        $row['total_journals'] ?? '0',
                        $row['last_journal_date'] ?? 'N/A'
                    ];
                    
                    fputcsv($fp, $csvRow);
                    $rowCount++;
                }
                
                $stmt->closeCursor(); // Free result set
                
            } else {
                // mysqli: Unbuffered query for memory efficiency
                $result = $this->db->query($sql, MYSQLI_USE_RESULT);
                
                if (!$result) {
                    throw new Exception("Query failed: " . $this->db->error);
                }
                
                while ($row = $result->fetch_assoc()) {
                    // Prepare row data
                    $csvRow = [
                        $row['pseudonym'],
                        $row['avg_mood_last_30_days'] ?? '0.00',
                        $row['total_journals'] ?? '0',
                        $row['last_journal_date'] ?? 'N/A'
                    ];
                    
                    fputcsv($fp, $csvRow);
                    $rowCount++;
                }
                
                $result->free(); // Free result set
            }

            fclose($fp);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 3);

            echo "Query rows: {$rowCount} users\n";
            echo "CSV file created: {$this->exportFile}\n";
            echo "Export completed successfully!\n";
            echo "Execution time: {$executionTime} seconds\n";

            return [
                'success' => true,
                'file' => $this->exportFile,
                'rows' => $rowCount,
                'time' => $executionTime,
                'errors' => []
            ];

        } catch (Exception $e) {
            fclose($fp);
            @unlink($this->exportFile); // Clean up partial file
            
            $this->addError("Export failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'errors' => $this->getErrors(),
                'file' => null,
                'rows' => 0,
                'time' => 0
            ];
        }
    }

    /**
     * Destructor - Clean up database connection
     */
    public function __destruct()
    {
        // Close database connection
        if ($this->db) {
            if ($this->dbType === 'mysqli') {
                $this->db->close();
            }
            $this->db = null;
        }
    }
}

// ==============================================================================
// CLI EXECUTION
// ==============================================================================

// Only execute if run directly from command line
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "\n";
    echo "========================================\n";
    echo "  Anonymized Data Export Tool\n";
    echo "  MindDB - Privacy-First Analytics\n";
    echo "========================================\n";
    echo "\n";

    // Create exporter instance
    $exporter = new AnonymizedDataExporter();

    // Check for initialization errors
    if ($exporter->hasErrors()) {
        echo "❌ Initialization failed:\n";
        foreach ($exporter->getErrors() as $error) {
            echo "  - {$error}\n";
        }
        echo "\n";
        exit(1);
    }

    // Execute export
    $result = $exporter->export();

    echo "\n";

    if ($result['success']) {
        echo "✅ Export successful!\n";
        echo "   File: {$result['file']}\n";
        echo "   Rows: {$result['rows']} users\n";
        echo "   Time: {$result['time']}s\n";
        echo "\n";
        echo "⚠️  PRIVACY NOTE:\n";
        echo "   This file contains NO personally identifiable information (PII).\n";
        echo "   Only pseudonyms and aggregated metrics are included.\n";
        echo "   Safe for analysis, reporting, and sharing.\n";
        echo "\n";
        exit(0);
    } else {
        echo "❌ Export failed:\n";
        foreach ($result['errors'] as $error) {
            echo "  - {$error}\n";
        }
        echo "\n";
        exit(1);
    }
}
