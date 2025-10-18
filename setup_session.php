<?php
session_start();

// Setup test session for journal testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'TestUser';
$_SESSION['role'] = 'user';

echo "<!DOCTYPE html>";
echo "<html><head><title>Session Setup - MindDB</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }";
echo ".container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; }";
echo ".btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }";
echo ".btn:hover { background: #0056b3; }";
echo "</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔐 Session Setup Complete</h1>";
echo "<p class='success'>✅ Session has been initialized for journal testing!</p>";

echo "<div class='info'>";
echo "<h3>📊 Session Details:</h3>";
echo "<ul>";
echo "<li><strong>User ID:</strong> " . $_SESSION['user_id'] . "</li>";
echo "<li><strong>Username:</strong> " . $_SESSION['username'] . "</li>";
echo "<li><strong>Role:</strong> " . $_SESSION['role'] . "</li>";
echo "<li><strong>Session ID:</strong> " . session_id() . "</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🧪 Test the Journal System:</h3>";
echo "<p>Now you can test all journal functionality with an authenticated session:</p>";

echo "<a href='/MindDB/views/journal/index.php' class='btn'>📖 View Journal List</a>";
echo "<a href='/MindDB/views/journal/create.php' class='btn'>✏️ Create New Entry</a>";
echo "<a href='/MindDB/dashboard.php' class='btn'>🏠 Dashboard</a>";
echo "<a href='/MindDB/reports/' class='btn'>📊 Reports</a>";

echo "<h3>🔗 API Endpoints (for direct testing):</h3>";
echo "<ul>";
echo "<li><a href='/MindDB/journal_list.php' target='_blank'>GET /journal_list.php</a> - List entries</li>";
echo "<li>POST /journal_create.php - Create entry (use form)</li>";
echo "<li>GET /journal_show.php?id=X - Show single entry</li>";
echo "<li>POST /journal_update.php - Update entry (use form)</li>";
echo "<li>POST /journal_delete.php - Delete entry (use form)</li>";
echo "</ul>";

echo "<h3>⚠️ Testing Notes:</h3>";
echo "<ul>";
echo "<li>This session will persist as long as your browser is open</li>";
echo "<li>You can create, edit, view, and delete journal entries</li>";
echo "<li>Try both private and public entries</li>";
echo "<li>Test different mood tags and sentiment scores</li>";
echo "<li>Verify ownership validation works (you can only edit your own entries)</li>";
echo "</ul>";

echo "<div style='margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px;'>";
echo "<strong>🔄 Reset Session:</strong> <a href='/MindDB/logout.php'>Logout</a> | ";
echo "<strong>📝 Setup Again:</strong> <a href='/MindDB/setup_session.php'>Re-setup</a>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>