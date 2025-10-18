<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /MindDB/login.php');
    exit();
}

$pageTitle = 'My Journals';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - MindDB</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .nav-bar {
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-links a {
            color: #495057;
            text-decoration: none;
            margin-right: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: #e9ecef;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .journal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .journal-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .journal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .journal-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .journal-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #6c757d;
        }
        
        .mood-tag {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .privacy-indicator {
            font-size: 0.8em;
            padding: 2px 6px;
            border-radius: 10px;
        }
        
        .private {
            background: #fff3cd;
            color: #856404;
        }
        
        .public {
            background: #d4edda;
            color: #155724;
        }
        
        .journal-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .loading {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state h3 {
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        
        .empty-state p {
            margin-bottom: 25px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .nav-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .journal-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📖 My Journals</h1>
            <p>Track your thoughts, feelings, and daily experiences</p>
        </div>
        
        <div class="nav-bar">
            <div class="nav-links">
                <a href="/MindDB/">🏠 Dashboard</a>
                <a href="/MindDB/reports/">📊 Reports</a>
                <a href="/MindDB/public/journal.php" style="font-weight: bold;">📖 Journals</a>
            </div>
            <div>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</span>
                <a href="/MindDB/logout.php" class="btn btn-secondary btn-sm">Logout</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="actions-bar">
                <h2>My Journal Entries</h2>
                <a href="/MindDB/views/journal/create.php" class="btn btn-primary">
                    ✏️ New Journal Entry
                </a>
            </div>
            
            <div id="loading" class="loading" style="display: none;">
                <p>📚 Loading your journals...</p>
            </div>
            
            <div id="error-container"></div>
            
            <div id="journals-container">
                <!-- Journals will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // API base URL
        const API_BASE = '/MindDB';
        
        // Load journals on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadJournals();
        });
        
        async function loadJournals() {
            const loading = document.getElementById('loading');
            const errorContainer = document.getElementById('error-container');
            const journalsContainer = document.getElementById('journals-container');
            
            loading.style.display = 'block';
            errorContainer.innerHTML = '';
            
            try {
                const response = await fetch(`${API_BASE}/journal_list.php?limit=50`, {
                    method: 'GET',
                    credentials: 'include'
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to load journals');
                }
                
                if (data.success) {
                    renderJournals(data.entries, data.count);
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
                
            } catch (error) {
                console.error('Error loading journals:', error);
                errorContainer.innerHTML = `
                    <div class="error">
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
                journalsContainer.innerHTML = '';
            } finally {
                loading.style.display = 'none';
            }
        }
        
        function renderJournals(journals, count) {
            const container = document.getElementById('journals-container');
            
            if (!journals || journals.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <h3>📝 No Journal Entries Yet</h3>
                        <p>Start your journaling journey today! Writing down your thoughts can help improve your mental well-being.</p>
                        <a href="/MindDB/views/journal/create.php" class="btn btn-primary">Create Your First Entry</a>
                    </div>
                `;
                return;
            }
            
            const journalCards = journals.map(journal => `
                <div class="journal-card">
                    <div class="journal-title">${escapeHtml(journal.title)}</div>
                    <div class="journal-meta">
                        <div>
                            ${journal.mood_tag ? `<span class="mood-tag">${escapeHtml(journal.mood_tag)}</span>` : ''}
                            <span class="privacy-indicator ${journal.is_private == 1 ? 'private' : 'public'}">
                                ${journal.is_private == 1 ? '🔒 Private' : '🌍 Public'}
                            </span>
                        </div>
                        <div>${formatDate(journal.created_at)}</div>
                    </div>
                    <div class="journal-actions">
                        <a href="/MindDB/views/journal/show.php?id=${journal.id}" class="btn btn-secondary btn-sm">👁️ View</a>
                        <a href="/MindDB/views/journal/edit.php?id=${journal.id}" class="btn btn-success btn-sm">✏️ Edit</a>
                        <button onclick="confirmDelete(${journal.id}, '${escapeHtml(journal.title)}')" class="btn btn-danger btn-sm">🗑️ Delete</button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = `
                <p style="margin-bottom: 20px; color: #6c757d;">
                    Showing ${journals.length} of ${count} journal entries
                </p>
                <div class="journal-grid">
                    ${journalCards}
                </div>
            `;
        }
        
        async function confirmDelete(journalId, title) {
            if (!confirm(`Are you sure you want to delete "${title}"?\n\nThis action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE}/journal_delete.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'include',
                    body: `id=${journalId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reload the journals list
                    loadJournals();
                    alert('Journal entry deleted successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete journal entry'));
                }
                
            } catch (error) {
                console.error('Delete error:', error);
                alert('Error: Failed to delete journal entry');
            }
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = now - date;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) {
                return `Today ${date.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}`;
            } else if (diffDays === 1) {
                return `Yesterday ${date.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}`;
            } else if (diffDays < 7) {
                return `${diffDays} days ago`;
            } else {
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }
        }
    </script>
</body>
</html>