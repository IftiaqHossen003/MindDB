<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /MindDB/login.php');
    exit();
}

// Get journal ID from URL
$journalId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$journalId) {
    header('Location: /MindDB/views/journal/index.php');
    exit();
}

$pageTitle = 'View Journal Entry';
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
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #6f42c1, #e83e8c);
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
        
        .journal-header {
            margin-bottom: 30px;
        }
        
        .journal-title {
            font-size: 2.5em;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        
        .journal-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95em;
            color: #6c757d;
        }
        
        .mood-tag {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .privacy-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .private {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .public {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .sentiment-display {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sentiment-bar {
            width: 100px;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(to right, #dc3545 0%, #ffc107 50%, #28a745 100%);
            position: relative;
        }
        
        .sentiment-indicator {
            position: absolute;
            top: -2px;
            width: 4px;
            height: 12px;
            background: #333;
            border-radius: 2px;
        }
        
        .sentiment-value {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.85em;
        }
        
        .sentiment-negative {
            background: #f8d7da;
            color: #721c24;
        }
        
        .sentiment-neutral {
            background: #fff3cd;
            color: #856404;
        }
        
        .sentiment-positive {
            background: #d4edda;
            color: #155724;
        }
        
        .journal-content {
            background: #fefefe;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            line-height: 1.7;
            font-size: 1.1em;
            color: #333;
        }
        
        .journal-content p {
            margin-bottom: 15px;
        }
        
        .actions-bar {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
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
        
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .journal-entry {
            display: none;
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
            
            .journal-title {
                font-size: 1.8em;
            }
            
            .journal-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .actions-bar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👁️ Journal Entry</h1>
            <p>Reading your thoughts and reflections</p>
        </div>
        
        <div class="nav-bar">
            <div class="nav-links">
                <a href="/MindDB/dashboard.php">🏠 Dashboard</a>
                <a href="/MindDB/reports/">📊 Reports</a>
                <a href="/MindDB/views/journal/index.php">📖 My Journals</a>
            </div>
            <div>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</span>
            </div>
        </div>
        
        <div class="main-content">
            <div id="loading" class="loading">
                <p>📖 Loading journal entry...</p>
            </div>
            
            <div id="error-container"></div>
            
            <div id="journal-entry" class="journal-entry">
                <div class="journal-header">
                    <h1 id="journal-title" class="journal-title"></h1>
                    <div id="journal-meta" class="journal-meta">
                        <!-- Meta information will be populated here -->
                    </div>
                </div>
                
                <div id="journal-content" class="journal-content">
                    <!-- Content will be populated here -->
                </div>
                
                <div id="actions-bar" class="actions-bar">
                    <!-- Action buttons will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // API base URL and journal ID
        const API_BASE = '/MindDB';
        const JOURNAL_ID = <?= $journalId ?>;
        
        // Elements
        const loading = document.getElementById('loading');
        const errorContainer = document.getElementById('error-container');
        const journalEntry = document.getElementById('journal-entry');
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadJournalEntry();
        });
        
        async function loadJournalEntry() {
            loading.style.display = 'block';
            errorContainer.innerHTML = '';
            journalEntry.style.display = 'none';
            
            try {
                const response = await fetch(`${API_BASE}/journal_show.php?id=${JOURNAL_ID}`, {
                    method: 'GET',
                    credentials: 'include'
                });
                
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to load journal entry');
                }
                
                renderJournalEntry(data.entry, data.is_owner);
                
            } catch (error) {
                console.error('Error loading journal:', error);
                errorContainer.innerHTML = `
                    <div class="alert alert-error">
                        <strong>Error:</strong> ${error.message}
                        <br><br>
                        <a href="/MindDB/views/journal/index.php" class="btn btn-secondary">← Back to Journals</a>
                    </div>
                `;
            } finally {
                loading.style.display = 'none';
            }
        }
        
        function renderJournalEntry(entry, isOwner) {
            // Set title
            document.getElementById('journal-title').textContent = entry.title;
            
            // Set meta information
            const createdAt = new Date(entry.created_at);
            const updatedAt = new Date(entry.updated_at);
            const isUpdated = createdAt.getTime() !== updatedAt.getTime();
            
            const metaHtml = `
                <div class="meta-item">
                    <span>📅</span>
                    <span>Created: ${formatDate(createdAt)}</span>
                </div>
                ${isUpdated ? `
                <div class="meta-item">
                    <span>📝</span>
                    <span>Updated: ${formatDate(updatedAt)}</span>
                </div>
                ` : ''}
                ${entry.mood_tag ? `
                <div class="mood-tag">
                    ${getMoodEmoji(entry.mood_tag)} ${capitalizeFirst(entry.mood_tag)}
                </div>
                ` : ''}
                <div class="privacy-badge ${entry.is_private == 1 ? 'private' : 'public'}">
                    ${entry.is_private == 1 ? '🔒 Private' : '🌍 Public'}
                </div>
                ${entry.sentiment_score !== null ? `
                <div class="sentiment-display">
                    <span>Feeling:</span>
                    <div class="sentiment-bar">
                        <div class="sentiment-indicator" style="left: ${((parseFloat(entry.sentiment_score) + 1) * 50)}%;"></div>
                    </div>
                    <div class="sentiment-value ${getSentimentClass(entry.sentiment_score)}">
                        ${getSentimentLabel(entry.sentiment_score)}
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('journal-meta').innerHTML = metaHtml;
            
            // Set content (preserve line breaks)
            const contentElement = document.getElementById('journal-content');
            const formattedContent = entry.content.replace(/\n/g, '<br>');
            contentElement.innerHTML = `<div>${formattedContent}</div>`;
            
            // Set action buttons
            const actionsHtml = `
                <a href="/MindDB/views/journal/index.php" class="btn btn-secondary">
                    ← Back to Journals
                </a>
                ${isOwner ? `
                    <a href="/MindDB/views/journal/edit.php?id=${entry.id}" class="btn btn-success">
                        ✏️ Edit Entry
                    </a>
                    <button onclick="confirmDelete(${entry.id}, '${escapeHtml(entry.title)}')" class="btn btn-danger">
                        🗑️ Delete Entry
                    </button>
                ` : ''}
                <button onclick="shareEntry()" class="btn btn-primary">
                    📤 Share Link
                </button>
            `;
            
            document.getElementById('actions-bar').innerHTML = actionsHtml;
            
            // Show the entry
            journalEntry.style.display = 'block';
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
                    alert('Journal entry deleted successfully!');
                    window.location.href = '/MindDB/views/journal/index.php';
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete journal entry'));
                }
                
            } catch (error) {
                console.error('Delete error:', error);
                alert('Error: Failed to delete journal entry');
            }
        }
        
        function shareEntry() {
            const currentUrl = window.location.href;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Journal Entry - MindDB',
                    url: currentUrl
                });
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(currentUrl).then(() => {
                    alert('📋 Link copied to clipboard!');
                });
            } else {
                // Fallback
                prompt('Copy this link:', currentUrl);
            }
        }
        
        function formatDate(date) {
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function getMoodEmoji(mood) {
            const moodEmojis = {
                happy: '😊',
                excited: '🎉',
                content: '😌',
                calm: '😇',
                relaxed: '😌',
                grateful: '🙏',
                neutral: '😐',
                tired: '😴',
                stressed: '😰',
                anxious: '😟',
                worried: '😕',
                sad: '😢',
                angry: '😠',
                frustrated: '😤',
                overwhelmed: '😵',
                lonely: '😔',
                confused: '😕',
                hopeful: '🌟',
                motivated: '💪',
                reflective: '🤔'
            };
            return moodEmojis[mood] || '😐';
        }
        
        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        function getSentimentClass(score) {
            const value = parseFloat(score);
            if (value < -0.3) return 'sentiment-negative';
            if (value > 0.3) return 'sentiment-positive';
            return 'sentiment-neutral';
        }
        
        function getSentimentLabel(score) {
            const value = parseFloat(score);
            if (value < -0.7) return 'Very Negative';
            if (value < -0.3) return 'Negative';
            if (value > 0.7) return 'Very Positive';
            if (value > 0.3) return 'Positive';
            return 'Neutral';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/'/g, "\\'");
        }
    </script>
</body>
</html>