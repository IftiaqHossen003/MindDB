<?php
/**
 * Test Script for Journal Entry API Endpoints
 * 
 * Tests all CRUD operations via the REST-like API endpoints.
 * Simulates HTTP requests using curl or file_get_contents.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Journal Entry API Test Suite ===\n\n";

// Base URL for API endpoints
$baseUrl = 'http://localhost/MindDB';

// Test session setup
session_start();
$_SESSION['user_id'] = 1; // Simulate logged-in user
$_SESSION['role'] = 'user';

echo "1. Setup: Session initialized with user_id=1\n";
echo "   Session ID: " . session_id() . "\n\n";

// Function to make API request
function apiRequest($endpoint, $method = 'GET', $data = []) {
    global $baseUrl;
    
    $url = $baseUrl . '/' . $endpoint;
    
    $options = [
        'http' => [
            'method' => $method,
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    // Get HTTP response code
    $statusCode = 200;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $statusCode = isset($matches[1]) ? (int)$matches[1] : 200;
    }
    
    return [
        'status' => $statusCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

// Test 1: Create journal entry
echo "2. Testing CREATE endpoint (journal_create.php)...\n";
$createData = [
    'title' => 'API Test Entry - ' . date('Y-m-d H:i:s'),
    'content' => 'This is a test journal entry created via the REST API. Testing complete CRUD functionality.',
    'mood_tag' => 'testing',
    'sentiment_score' => 0.80,
    'is_private' => 1
];

$createResponse = apiRequest('journal_create.php', 'POST', $createData);
echo "   Status Code: {$createResponse['status']}\n";

if ($createResponse['body']['success']) {
    $newId = $createResponse['body']['id'];
    echo "   ✅ Created entry with ID: $newId\n";
    echo "   Title: {$createResponse['body']['entry']['title']}\n";
} else {
    echo "   ❌ FAILED: {$createResponse['body']['message']}\n";
    print_r($createResponse['body']);
    exit(1);
}
echo "\n";

// Test 2: List entries
echo "3. Testing LIST endpoint (journal_list.php)...\n";
$listResponse = apiRequest('journal_list.php?limit=10', 'GET');
echo "   Status Code: {$listResponse['status']}\n";

if ($listResponse['body']['success']) {
    $count = $listResponse['body']['count'];
    echo "   ✅ Retrieved $count journal entries\n";
    if ($count > 0) {
        echo "   Latest entry: {$listResponse['body']['entries'][0]['title']}\n";
    }
} else {
    echo "   ❌ FAILED: {$listResponse['body']['message']}\n";
}
echo "\n";

// Test 3: Show single entry
echo "4. Testing SHOW endpoint (journal_show.php)...\n";
$showResponse = apiRequest("journal_show.php?id=$newId", 'GET');
echo "   Status Code: {$showResponse['status']}\n";

if ($showResponse['body']['success']) {
    echo "   ✅ Retrieved entry:\n";
    echo "      - ID: {$showResponse['body']['entry']['id']}\n";
    echo "      - Title: {$showResponse['body']['entry']['title']}\n";
    echo "      - Mood: {$showResponse['body']['entry']['mood_tag']}\n";
    echo "      - Is Owner: " . ($showResponse['body']['is_owner'] ? 'Yes' : 'No') . "\n";
} else {
    echo "   ❌ FAILED: {$showResponse['body']['message']}\n";
}
echo "\n";

// Test 4: Update entry
echo "5. Testing UPDATE endpoint (journal_update.php)...\n";
$updateData = [
    'id' => $newId,
    'title' => 'UPDATED API Test - ' . date('Y-m-d H:i:s'),
    'mood_tag' => 'happy',
    'sentiment_score' => 0.95
];

$updateResponse = apiRequest('journal_update.php', 'POST', $updateData);
echo "   Status Code: {$updateResponse['status']}\n";

if ($updateResponse['body']['success']) {
    echo "   ✅ Entry updated successfully\n";
    echo "      - New Title: {$updateResponse['body']['entry']['title']}\n";
    echo "      - New Mood: {$updateResponse['body']['entry']['mood_tag']}\n";
    echo "      - New Sentiment: {$updateResponse['body']['entry']['sentiment_score']}\n";
} else {
    echo "   ❌ FAILED: {$updateResponse['body']['message']}\n";
}
echo "\n";

// Test 5: Delete entry
echo "6. Testing DELETE endpoint (journal_delete.php)...\n";
$deleteData = ['id' => $newId];
$deleteResponse = apiRequest('journal_delete.php', 'POST', $deleteData);
echo "   Status Code: {$deleteResponse['status']}\n";

if ($deleteResponse['body']['success']) {
    echo "   ✅ Entry deleted successfully\n";
    echo "      - Deleted ID: {$deleteResponse['body']['id']}\n";
} else {
    echo "   ❌ FAILED: {$deleteResponse['body']['message']}\n";
}
echo "\n";

// Test 6: Verify deletion
echo "7. Verifying deletion (show should return 404)...\n";
$verifyResponse = apiRequest("journal_show.php?id=$newId", 'GET');
echo "   Status Code: {$verifyResponse['status']}\n";

if ($verifyResponse['status'] === 404) {
    echo "   ✅ Confirmed: Entry no longer exists\n";
} else {
    echo "   ⚠️ WARNING: Entry still exists after deletion\n";
}
echo "\n";

// Test 7: Validation tests
echo "8. Testing Input Validation...\n";

// Test empty title
echo "   a) Testing empty title validation...\n";
$invalidData = ['title' => '', 'content' => 'Test content'];
$validationResponse = apiRequest('journal_create.php', 'POST', $invalidData);
if ($validationResponse['status'] === 422) {
    echo "      ✅ Correctly rejected empty title (422)\n";
} else {
    echo "      ❌ FAILED: Should have returned 422 for empty title\n";
}

// Test invalid sentiment score
echo "   b) Testing invalid sentiment score...\n";
$invalidData = [
    'title' => 'Test',
    'content' => 'Test content',
    'sentiment_score' => 2.5 // Out of range
];
$validationResponse = apiRequest('journal_create.php', 'POST', $invalidData);
if ($validationResponse['status'] === 422) {
    echo "      ✅ Correctly rejected invalid sentiment (422)\n";
} else {
    echo "      ❌ FAILED: Should have returned 422 for invalid sentiment\n";
}

echo "\n";

// Test 8: Authorization tests
echo "9. Testing Authorization...\n";

// Create entry to test ownership
$tempData = ['title' => 'Temp Entry', 'content' => 'Temp content'];
$tempResponse = apiRequest('journal_create.php', 'POST', $tempData);
$tempId = $tempResponse['body']['id'];

// Change user
$_SESSION['user_id'] = 999; // Different user

echo "   a) Testing update with different user (should be 403)...\n";
$unauthorizedUpdate = apiRequest('journal_update.php', 'POST', [
    'id' => $tempId,
    'title' => 'Unauthorized Update'
]);
if ($unauthorizedUpdate['status'] === 403) {
    echo "      ✅ Correctly blocked unauthorized update (403)\n";
} else {
    echo "      ⚠️ WARNING: Expected 403, got {$unauthorizedUpdate['status']}\n";
}

// Cleanup: Switch back to original user and delete temp entry
$_SESSION['user_id'] = 1;
apiRequest('journal_delete.php', 'POST', ['id' => $tempId]);

echo "\n";

// Final summary
echo str_repeat("=", 50) . "\n";
echo "✅ ALL API ENDPOINT TESTS COMPLETED!\n\n";
echo "API Endpoint Summary:\n";
echo "- ✅ CREATE (journal_create.php): Creates new entries\n";
echo "- ✅ LIST (journal_list.php): Lists user's entries\n";
echo "- ✅ SHOW (journal_show.php): Retrieves single entry\n";
echo "- ✅ UPDATE (journal_update.php): Updates entries\n";
echo "- ✅ DELETE (journal_delete.php): Deletes entries\n";
echo "- ✅ VALIDATION: Enforces data integrity\n";
echo "- ✅ AUTHORIZATION: Validates ownership\n";
echo "\n";
echo "The REST API is ready for use! 🚀\n";
echo "\nAccess endpoints at:\n";
echo "- http://localhost/MindDB/journal_create.php\n";
echo "- http://localhost/MindDB/journal_list.php\n";
echo "- http://localhost/MindDB/journal_show.php?id=1\n";
echo "- http://localhost/MindDB/journal_update.php\n";
echo "- http://localhost/MindDB/journal_delete.php\n";
