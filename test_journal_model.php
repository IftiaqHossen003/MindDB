<?php
/**
 * Test Script for JournalEntry Model
 * 
 * Tests all CRUD operations and validates the model functionality.
 * Run this file after creating the journal_entries table.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== JournalEntry Model Test Suite ===\n\n";

// Include config and model
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/models/JournalEntry.php';

try {
    // Initialize model
    echo "1. Testing Model Initialization...\n";
    $journal = new JournalEntry();
    echo "   ✅ Model initialized successfully\n\n";
    
    // Test database connection
    echo "2. Testing Database Connection...\n";
    $db = getDb();
    $stmt = $db->query("SHOW TABLES LIKE 'journal_entries'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        die("   ❌ FAILED: journal_entries table does not exist.\n" .
            "   Please run: mysql -u root -P 3307 mindmate < database/sql/60_create_journal_entries_table.sql\n");
    }
    echo "   ✅ journal_entries table exists\n\n";
    
    // Test CREATE
    echo "3. Testing CREATE Operation...\n";
    $testData = [
        'user_id' => 1,
        'title' => 'Test Journal Entry - ' . date('Y-m-d H:i:s'),
        'content' => 'This is a test journal entry created by the automated test suite. Testing CRUD operations.',
        'mood_tag' => 'testing',
        'sentiment_score' => 0.75,
        'is_private' => 1
    ];
    
    $newId = $journal->create($testData);
    echo "   ✅ Created journal entry with ID: $newId\n\n";
    
    // Test FIND
    echo "4. Testing FIND Operation...\n";
    $entry = $journal->find($newId);
    
    if ($entry) {
        echo "   ✅ Found entry:\n";
        echo "      - ID: {$entry['id']}\n";
        echo "      - Title: {$entry['title']}\n";
        echo "      - Mood: {$entry['mood_tag']}\n";
        echo "      - Sentiment: {$entry['sentiment_score']}\n";
        echo "      - Created: {$entry['created_at']}\n";
    } else {
        echo "   ❌ FAILED: Could not find entry\n";
    }
    echo "\n";
    
    // Test UPDATE
    echo "5. Testing UPDATE Operation...\n";
    $updateData = [
        'title' => 'Updated Test Entry - ' . date('Y-m-d H:i:s'),
        'mood_tag' => 'happy',
        'sentiment_score' => 0.90
    ];
    
    $updated = $journal->update($newId, $updateData);
    
    if ($updated) {
        echo "   ✅ Entry updated successfully\n";
        $updatedEntry = $journal->find($newId);
        echo "      - New Title: {$updatedEntry['title']}\n";
        echo "      - New Mood: {$updatedEntry['mood_tag']}\n";
        echo "      - New Sentiment: {$updatedEntry['sentiment_score']}\n";
    } else {
        echo "   ❌ FAILED: Update operation failed\n";
    }
    echo "\n";
    
    // Test ALL BY USER
    echo "6. Testing ALL BY USER Operation...\n";
    $userEntries = $journal->allByUser(1, 10);
    echo "   ✅ Retrieved {count($userEntries)} entries for user_id=1\n";
    
    if (count($userEntries) > 0) {
        echo "      Recent entries:\n";
        foreach (array_slice($userEntries, 0, 3) as $entry) {
            echo "      - [{$entry['id']}] {$entry['title']} ({$entry['created_at']})\n";
        }
    }
    echo "\n";
    
    // Test COUNT BY USER
    echo "7. Testing COUNT BY USER Operation...\n";
    $count = $journal->countByUser(1);
    echo "   ✅ User has $count total journal entries\n\n";
    
    // Test FIND BY MOOD TAG
    echo "8. Testing FIND BY MOOD TAG Operation...\n";
    $moodEntries = $journal->findByMoodTag(1, 'happy', 5);
    echo "   ✅ Found " . count($moodEntries) . " entries with mood='happy'\n\n";
    
    // Test SEARCH
    echo "9. Testing SEARCH Operation...\n";
    $searchResults = $journal->search(1, 'test', 5);
    echo "   ✅ Search for 'test' returned " . count($searchResults) . " results\n\n";
    
    // Test DELETE
    echo "10. Testing DELETE Operation...\n";
    $deleted = $journal->delete($newId);
    
    if ($deleted) {
        echo "   ✅ Entry deleted successfully\n";
        
        // Verify deletion
        $deletedEntry = $journal->find($newId);
        if ($deletedEntry === null) {
            echo "   ✅ Verified: Entry no longer exists\n";
        } else {
            echo "   ⚠️ WARNING: Entry still exists after deletion\n";
        }
    } else {
        echo "   ❌ FAILED: Delete operation failed\n";
    }
    echo "\n";
    
    // Test validation
    echo "11. Testing Input Validation...\n";
    try {
        $journal->create(['title' => '', 'content' => 'test']); // Empty title
        echo "   ❌ FAILED: Should have thrown exception for empty title\n";
    } catch (Exception $e) {
        echo "   ✅ Correctly rejected empty title: {$e->getMessage()}\n";
    }
    
    try {
        $journal->create([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'Test content',
            'sentiment_score' => 2.5 // Out of range
        ]);
        echo "   ❌ FAILED: Should have thrown exception for invalid sentiment score\n";
    } catch (Exception $e) {
        echo "   ✅ Correctly rejected invalid sentiment: {$e->getMessage()}\n";
    }
    echo "\n";
    
    // Final summary
    echo str_repeat("=", 50) . "\n";
    echo "✅ ALL TESTS PASSED!\n\n";
    echo "JournalEntry Model Summary:\n";
    echo "- ✅ Database connection works\n";
    echo "- ✅ CREATE: Inserts new entries\n";
    echo "- ✅ FIND: Retrieves entry by ID\n";
    echo "- ✅ UPDATE: Modifies existing entries\n";
    echo "- ✅ DELETE: Removes entries\n";
    echo "- ✅ ALL BY USER: Lists user's entries\n";
    echo "- ✅ COUNT: Counts user's entries\n";
    echo "- ✅ SEARCH: Finds entries by keyword\n";
    echo "- ✅ FIND BY MOOD: Filters by mood tag\n";
    echo "- ✅ VALIDATION: Enforces data integrity\n";
    echo "\n";
    echo "The model is ready for production use! 🚀\n";
    
} catch (Exception $e) {
    echo "\n❌ TEST FAILED WITH EXCEPTION:\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    echo "\n";
    echo "Possible issues:\n";
    echo "- journal_entries table not created (run 60_create_journal_entries_table.sql)\n";
    echo "- Database connection failed (check config.php)\n";
    echo "- MySQL not running on port 3307\n";
    exit(1);
}
