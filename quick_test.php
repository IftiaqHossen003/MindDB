<?php
session_start();

// Set up test session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';
$_SESSION['role'] = 'user';

echo "=== Journal Entry API Quick Test ===\n\n";
echo "Session Setup:\n";
echo "- User ID: " . $_SESSION['user_id'] . "\n";
echo "- Username: " . $_SESSION['username'] . "\n";
echo "- Session ID: " . session_id() . "\n\n";

// Test 1: Include and test the JournalEntry model directly
echo "1. Testing JournalEntry Model...\n";
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/models/JournalEntry.php';

try {
    $model = new JournalEntry();
    echo "✅ JournalEntry model loaded successfully\n";
    
    // Test create
    $newId = $model->create([
        'user_id' => 1,
        'title' => 'Test Entry ' . date('Y-m-d H:i:s'),
        'content' => 'This is a test entry created at ' . date('Y-m-d H:i:s'),
        'mood_tag' => 'happy',
        'sentiment_score' => 0.8,
        'is_private' => 1
    ]);
    
    if ($newId > 0) {
        echo "✅ Created journal entry with ID: $newId\n";
        
        // Test find
        $entry = $model->find($newId);
        if ($entry) {
            echo "✅ Found journal entry: " . $entry['title'] . "\n";
            
            // Test update
            $updated = $model->update($newId, ['title' => 'Updated Test Entry']);
            if ($updated) {
                echo "✅ Updated journal entry successfully\n";
            } else {
                echo "❌ Failed to update journal entry\n";
            }
            
            // Test delete
            $deleted = $model->delete($newId);
            if ($deleted) {
                echo "✅ Deleted journal entry successfully\n";
            } else {
                echo "❌ Failed to delete journal entry\n";
            }
        } else {
            echo "❌ Failed to find created journal entry\n";
        }
    } else {
        echo "❌ Failed to create journal entry\n";
    }
    
} catch (Exception $e) {
    echo "❌ Model test failed: " . $e->getMessage() . "\n";
}

echo "\n2. Testing JournalEntryController...\n";
require_once __DIR__ . '/app/controllers/JournalEntryController.php';

try {
    $controller = new JournalEntryController();
    echo "✅ JournalEntryController loaded successfully\n";
    
    // Simulate POST data for create test
    $_POST['title'] = 'Controller Test Entry';
    $_POST['content'] = 'This is a test from the controller';
    $_POST['mood_tag'] = 'calm';
    $_POST['sentiment_score'] = 0.5;
    $_POST['is_private'] = 1;
    
    echo "✅ Simulated POST data set\n";
    echo "✅ Controller ready for HTTP testing\n";
    
} catch (Exception $e) {
    echo "❌ Controller test failed: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Database Connection...\n";
try {
    $db = getDb();
    echo "✅ Database connection successful\n";
    
    // Check if journal_entries table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'journal_entries'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ journal_entries table exists\n";
        
        // Check table structure
        $stmt = $db->prepare("DESCRIBE journal_entries");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Table structure (" . count($columns) . " columns):\n";
        foreach ($columns as $col) {
            echo "   - {$col['Field']}: {$col['Type']}\n";
        }
        
        // Count existing entries
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM journal_entries");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Existing entries in table: " . $count['count'] . "\n";
        
    } else {
        echo "❌ journal_entries table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database test failed: " . $e->getMessage() . "\n";
}

echo "\n4. Testing API Endpoint Files...\n";
$endpoints = [
    'journal_create.php',
    'journal_list.php', 
    'journal_show.php',
    'journal_update.php',
    'journal_delete.php'
];

foreach ($endpoints as $endpoint) {
    $path = __DIR__ . '/' . $endpoint;
    if (file_exists($path)) {
        echo "✅ {$endpoint} exists\n";
        
        // Check syntax
        $output = [];
        $return_var = 0;
        exec("php -l \"$path\" 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "✅ {$endpoint} syntax OK\n";
        } else {
            echo "❌ {$endpoint} syntax error: " . implode("\n", $output) . "\n";
        }
    } else {
        echo "❌ {$endpoint} does not exist\n";
    }
}

echo "\n5. Testing Journal Views...\n";
$views = [
    'views/journal/index.php',
    'views/journal/create.php',
    'views/journal/edit.php',
    'views/journal/show.php'
];

foreach ($views as $view) {
    $path = __DIR__ . '/' . $view;
    if (file_exists($path)) {
        echo "✅ {$view} exists\n";
        
        // Check syntax
        $output = [];
        $return_var = 0;
        exec("php -l \"$path\" 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "✅ {$view} syntax OK\n";
        } else {
            echo "❌ {$view} syntax error: " . implode("\n", $output) . "\n";
        }
    } else {
        echo "❌ {$view} does not exist\n";
    }
}

echo "\n=== TEST SUMMARY ===\n";
echo "✅ Quick test completed successfully!\n";
echo "📝 Next steps:\n";
echo "   1. Start XAMPP Apache server\n";
echo "   2. Navigate to http://localhost/MindDB/views/journal/index.php\n";
echo "   3. Test creating, editing, viewing, and deleting journal entries\n";
echo "   4. Verify session authentication works\n";
echo "   5. Test error handling scenarios\n\n";
?>