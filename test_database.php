<?php
require_once __DIR__ . '/config.php';

echo "=== Database Test ===\n\n";

try {
    $db = getDb();
    echo "✅ Database connection successful\n\n";
    
    // Check users table
    echo "1. Testing users table...\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Users in database: " . $userCount['count'] . "\n";
    
    if ($userCount['count'] > 0) {
        $stmt = $db->prepare("SELECT id, username, role FROM users LIMIT 3");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Sample users:\n";
        foreach ($users as $user) {
            echo "   - ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}\n";
        }
    } else {
        echo "   ⚠️  No users found. Creating a test user...\n";
        
        // Create test user
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $passwordHash = password_hash('testpass123', PASSWORD_DEFAULT);
        $stmt->execute(['testuser', 'test@example.com', $passwordHash, 'user']);
        echo "   ✅ Test user created with ID: " . $db->lastInsertId() . "\n";
    }
    
    // Check journal_entries table
    echo "\n2. Testing journal_entries table...\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM journal_entries");
    $stmt->execute();
    $entryCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Journal entries: " . $entryCount['count'] . "\n";
    
    if ($entryCount['count'] > 0) {
        $stmt = $db->prepare("SELECT id, title, user_id, created_at FROM journal_entries ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Recent entries:\n";
        foreach ($entries as $entry) {
            echo "   - ID: {$entry['id']}, Title: {$entry['title']}, User: {$entry['user_id']}, Created: {$entry['created_at']}\n";
        }
    }
    
    // Test other important tables
    $tables = ['mood_logs', 'therapy_sessions', 'resources', 'helplines'];
    echo "\n3. Checking other tables...\n";
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "   $table: " . $count['count'] . " records\n";
        } catch (Exception $e) {
            echo "   $table: ❌ Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Database test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Database test failed: " . $e->getMessage() . "\n";
}
?>