<?php
// Check if this file is being accessed directly or through a router
$isDirectAccess = !defined('ROUTER_INCLUDED') && !isset($pageTitle);

// If direct access, redirect to proper router
if ($isDirectAccess) {
    header('Location: /MindDB/public/journal_create.php');
    exit;
}

// Start session if not already started (for router compatibility)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /MindDB/login.php');
    exit();
}

$pageTitle = 'New Journal Entry';
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #28a745, #20c997);
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
        
        .form-container {
            max-width: 100%;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 1.1em;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .form-textarea {
            min-height: 150px;
            resize: vertical;
            font-family: inherit;
        }
        
        .form-help {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-input {
            width: auto;
            margin: 0;
        }
        
        .checkbox-label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }
        
        .sentiment-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .sentiment-slider {
            flex: 1;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(to right, #dc3545 0%, #ffc107 50%, #28a745 100%);
        }
        
        .sentiment-value {
            font-weight: 600;
            font-size: 1.1em;
            min-width: 80px;
            text-align: center;
            padding: 5px 10px;
            border-radius: 15px;
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
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
            margin-right: 15px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
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
            
            .sentiment-container {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ New Journal Entry</h1>
            <p>Express your thoughts and track your mental well-being</p>
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
            <div id="alert-container"></div>
            
            <form id="journal-form" class="form-container">
                <div class="form-group">
                    <label for="title" class="form-label">
                        Title <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-input" 
                        required 
                        maxlength="255"
                        placeholder="What's on your mind today?"
                    >
                    <div class="form-help">Give your journal entry a meaningful title</div>
                </div>
                
                <div class="form-group">
                    <label for="content" class="form-label">
                        Content <span class="required">*</span>
                    </label>
                    <textarea 
                        id="content" 
                        name="content" 
                        class="form-textarea" 
                        required
                        placeholder="Write about your day, feelings, thoughts, or experiences..."
                    ></textarea>
                    <div class="form-help">Express yourself freely - this is your safe space</div>
                </div>
                
                <div class="form-group">
                    <label for="mood_tag" class="form-label">Mood Tag</label>
                    <select id="mood_tag" name="mood_tag" class="form-select">
                        <option value="">Select your current mood (optional)</option>
                        <option value="happy">😊 Happy</option>
                        <option value="excited">🎉 Excited</option>
                        <option value="content">😌 Content</option>
                        <option value="calm">😇 Calm</option>
                        <option value="relaxed">😌 Relaxed</option>
                        <option value="grateful">🙏 Grateful</option>
                        <option value="neutral">😐 Neutral</option>
                        <option value="tired">😴 Tired</option>
                        <option value="stressed">😰 Stressed</option>
                        <option value="anxious">😟 Anxious</option>
                        <option value="worried">😕 Worried</option>
                        <option value="sad">😢 Sad</option>
                        <option value="angry">😠 Angry</option>
                        <option value="frustrated">😤 Frustrated</option>
                        <option value="overwhelmed">😵 Overwhelmed</option>
                        <option value="lonely">😔 Lonely</option>
                        <option value="confused">😕 Confused</option>
                        <option value="hopeful">🌟 Hopeful</option>
                        <option value="motivated">💪 Motivated</option>
                        <option value="reflective">🤔 Reflective</option>
                    </select>
                    <div class="form-help">Tag your entry with your current emotional state</div>
                </div>
                
                <div class="form-group">
                    <label for="sentiment_score" class="form-label">Overall Feeling</label>
                    <div class="sentiment-container">
                        <span>Very Negative</span>
                        <input 
                            type="range" 
                            id="sentiment_score" 
                            name="sentiment_score" 
                            class="sentiment-slider"
                            min="-1" 
                            max="1" 
                            step="0.1" 
                            value="0"
                        >
                        <span>Very Positive</span>
                        <div id="sentiment-value" class="sentiment-value sentiment-neutral">0.0</div>
                    </div>
                    <div class="form-help">Rate how you're feeling overall (-1.0 = very negative, 0 = neutral, +1.0 = very positive)</div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-container">
                        <input 
                            type="checkbox" 
                            id="is_private" 
                            name="is_private" 
                            class="checkbox-input"
                            checked
                        >
                        <label for="is_private" class="checkbox-label">
                            🔒 Keep this entry private (only you can see it)
                        </label>
                    </div>
                    <div class="form-help">Private entries are only visible to you. Public entries may be used for research (anonymously)</div>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" id="submit-btn" class="btn btn-primary">
                        💾 Save Journal Entry
                    </button>
                    <a href="/MindDB/views/journal/index.php" class="btn btn-secondary">
                        ❌ Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <p>💾 Saving your journal entry...</p>
        </div>
    </div>

    <script>
        // API base URL
        const API_BASE = '/MindDB';
        
        // Form elements
        const form = document.getElementById('journal-form');
        const submitBtn = document.getElementById('submit-btn');
        const alertContainer = document.getElementById('alert-container');
        const loadingOverlay = document.getElementById('loading-overlay');
        const sentimentSlider = document.getElementById('sentiment_score');
        const sentimentValue = document.getElementById('sentiment-value');
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Update sentiment display
            updateSentimentDisplay();
            
            // Add event listeners
            sentimentSlider.addEventListener('input', updateSentimentDisplay);
            form.addEventListener('submit', handleSubmit);
        });
        
        function updateSentimentDisplay() {
            const value = parseFloat(sentimentSlider.value);
            const display = document.getElementById('sentiment-value');
            
            display.textContent = value.toFixed(1);
            
            // Update styling based on value
            display.className = 'sentiment-value';
            if (value < -0.3) {
                display.classList.add('sentiment-negative');
            } else if (value > 0.3) {
                display.classList.add('sentiment-positive');
            } else {
                display.classList.add('sentiment-neutral');
            }
        }
        
        async function handleSubmit(event) {
            event.preventDefault();
            
            // Clear previous alerts
            alertContainer.innerHTML = '';
            
            // Get form data
            const formData = new FormData(form);
            const data = {
                title: formData.get('title').trim(),
                content: formData.get('content').trim(),
                mood_tag: formData.get('mood_tag'),
                sentiment_score: formData.get('sentiment_score'),
                is_private: formData.get('is_private') ? 1 : 0
            };
            
            // Basic validation
            if (!data.title) {
                showAlert('Please enter a title for your journal entry.', 'error');
                return;
            }
            
            if (!data.content) {
                showAlert('Please write some content for your journal entry.', 'error');
                return;
            }
            
            // Show loading
            submitBtn.disabled = true;
            loadingOverlay.style.display = 'flex';
            
            try {
                // Prepare form data for submission
                const submitData = new URLSearchParams();
                for (const [key, value] of Object.entries(data)) {
                    if (value !== '' && value !== null) {
                        submitData.append(key, value);
                    }
                }
                
                const response = await fetch(`${API_BASE}/journal_create.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'include',
                    body: submitData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Success - redirect to journal list
                    showAlert('🎉 Journal entry created successfully!', 'success');
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = '/MindDB/views/journal/index.php';
                    }, 1500);
                    
                } else {
                    // API returned error
                    throw new Error(result.message || 'Failed to create journal entry');
                }
                
            } catch (error) {
                console.error('Submit error:', error);
                showAlert(`Error: ${error.message}`, 'error');
                submitBtn.disabled = false;
            } finally {
                loadingOverlay.style.display = 'none';
            }
        }
        
        function showAlert(message, type) {
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;
            
            // Scroll to top to show alert
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Character counter for title
        document.getElementById('title').addEventListener('input', function() {
            const maxLength = 255;
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;
            
            // Find or create character counter
            let counter = this.nextElementSibling.nextElementSibling;
            if (!counter || !counter.classList.contains('char-counter')) {
                counter = document.createElement('div');
                counter.className = 'form-help char-counter';
                this.parentNode.appendChild(counter);
            }
            
            if (remaining < 20) {
                counter.style.color = remaining < 0 ? '#dc3545' : '#ffc107';
                counter.textContent = `${remaining} characters remaining`;
            } else {
                counter.textContent = 'Give your journal entry a meaningful title';
                counter.style.color = '#6c757d';
            }
        });
        
        // Auto-resize textarea
        document.getElementById('content').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(150, this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>