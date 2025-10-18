<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - MindDB Admin</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; text-align: center; }
            .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px; }
        </style>
    </head>
    <body>
        <h1>Access Denied</h1>
        <div class="error">You must be an administrator to access this resource.</div>
        <a href="/MindDB/dashboard.php" class="btn">← Back to Dashboard</a>
    </body>
    </html>
    <?php
    exit;
}

// Initialize controller for helper methods
require_once __DIR__ . '/../../app/controllers/AdminResourceController.php';
$controller = new AdminResourceController();

$pageTitle = 'Create New Resource';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - MindDB Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            flex-wrap: wrap;
            gap: 15px;
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
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
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40,167,69,0.1);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }
        
        .form-help {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        
        .checkbox-container:hover {
            background-color: #f8f9fa;
        }
        
        .checkbox-input {
            width: auto;
            margin: 0;
        }
        
        .checkbox-label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
            flex: 1;
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
            background: linear-gradient(45th, #28a745, #20c997);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
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
        
        .url-preview {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9em;
            display: none;
        }
        
        .category-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .category-tag {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .category-tag:hover {
            background: #28a745;
            color: white;
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
                text-align: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .checkbox-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>➕ Create New Resource</h1>
            <p>Add a new mental health resource to help users</p>
        </div>
        
        <div class="nav-bar">
            <div class="nav-links">
                <a href="/MindDB/dashboard.php">🏠 Dashboard</a>
                <a href="/MindDB/admin/resources/">🎯 Resources</a>
                <a href="/MindDB/views/journal/index.php">📖 Journals</a>
                <a href="/MindDB/reports/">📊 Reports</a>
            </div>
            <div>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>!</span>
            </div>
        </div>
        
        <div class="main-content">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>
            
            <form id="resource-form" method="POST" action="/MindDB/public/admin_resource_action.php" class="form-container">
                <input type="hidden" name="action" value="store">
                
                <div class="form-grid">
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
                            placeholder="e.g., Mindfulness Meditation Guide"
                        >
                        <div class="form-help">A clear, descriptive title for the resource</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="resource_type" class="form-label">
                            Type <span class="required">*</span>
                        </label>
                        <select id="resource_type" name="resource_type" class="form-select" required>
                            <option value="">Select resource type...</option>
                            <?php foreach ($controller->getResourceTypes() as $key => $label): ?>
                                <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-help">What kind of resource is this?</div>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="url" class="form-label">URL</label>
                    <input 
                        type="url" 
                        id="url" 
                        name="url" 
                        class="form-input" 
                        placeholder="https://example.com/resource"
                    >
                    <div class="form-help">Link to the external resource (optional)</div>
                    <div id="url-preview" class="url-preview"></div>
                </div>
                
                <div class="form-group full-width">
                    <label for="description" class="form-label">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea" 
                        placeholder="Provide a detailed description of this resource, what it covers, and how it can help users..."
                    ></textarea>
                    <div class="form-help">Detailed information about the resource and its benefits</div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="category" class="form-label">Category</label>
                        <input 
                            type="text" 
                            id="category" 
                            name="category" 
                            class="form-input" 
                            placeholder="e.g., anxiety, mindfulness"
                        >
                        <div class="form-help">Primary category or topic</div>
                        <div class="category-suggestions">
                            <?php foreach ($controller->getCategories() as $category): ?>
                                <span class="category-tag" onclick="selectCategory('<?= $category ?>')"><?= ucfirst($category) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="author" class="form-label">Author</label>
                        <input 
                            type="text" 
                            id="author" 
                            name="author" 
                            class="form-input" 
                            placeholder="Author or organization name"
                        >
                        <div class="form-help">Creator or source of the resource</div>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                        <input 
                            type="number" 
                            id="duration_minutes" 
                            name="duration_minutes" 
                            class="form-input" 
                            min="1" 
                            max="1440"
                            placeholder="e.g., 10"
                        >
                        <div class="form-help">How long does it take to complete? (optional)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="difficulty_level" class="form-label">Difficulty Level</label>
                        <select id="difficulty_level" name="difficulty_level" class="form-select">
                            <option value="">Select difficulty...</option>
                            <?php foreach ($controller->getDifficultyLevels() as $key => $label): ?>
                                <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-help">Complexity level for users</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Resource Options</label>
                    <div class="checkbox-grid">
                        <div class="checkbox-container">
                            <input 
                                type="checkbox" 
                                id="is_featured" 
                                name="is_featured" 
                                class="checkbox-input"
                            >
                            <label for="is_featured" class="checkbox-label">
                                ⭐ Featured Resource
                                <div class="form-help">Highlight this resource prominently</div>
                            </label>
                        </div>
                        
                        <div class="checkbox-container">
                            <input 
                                type="checkbox" 
                                id="is_active" 
                                name="is_active" 
                                class="checkbox-input"
                                checked
                            >
                            <label for="is_active" class="checkbox-label">
                                ✅ Active Status
                                <div class="form-help">Make this resource available to users</div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                    <button type="submit" id="submit-btn" class="btn btn-primary">
                        💾 Create Resource
                    </button>
                    <a href="/MindDB/admin/resources/" class="btn btn-secondary">
                        ❌ Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation and enhancements
        const form = document.getElementById('resource-form');
        const submitBtn = document.getElementById('submit-btn');
        const urlInput = document.getElementById('url');
        const urlPreview = document.getElementById('url-preview');
        
        // URL preview functionality
        urlInput.addEventListener('input', function() {
            const url = this.value.trim();
            if (url && isValidUrl(url)) {
                urlPreview.innerHTML = `<strong>Preview:</strong> <a href="${url}" target="_blank" style="color: #007bff;">${url}</a>`;
                urlPreview.style.display = 'block';
            } else {
                urlPreview.style.display = 'none';
            }
        });
        
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }
        
        // Category selection
        function selectCategory(category) {
            document.getElementById('category').value = category;
        }
        
        // Form submission handling
        form.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const resourceType = document.getElementById('resource_type').value;
            
            if (!title) {
                alert('Please enter a title for the resource.');
                e.preventDefault();
                return;
            }
            
            if (!resourceType) {
                alert('Please select a resource type.');
                e.preventDefault();
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '💾 Creating Resource...';
        });
        
        // Auto-resize textarea
        document.getElementById('description').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(120, this.scrollHeight) + 'px';
        });
        
        // Character counter for title
        document.getElementById('title').addEventListener('input', function() {
            const maxLength = 255;
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;
            
            let counter = this.parentNode.querySelector('.char-counter');
            if (!counter) {
                counter = document.createElement('div');
                counter.className = 'form-help char-counter';
                this.parentNode.appendChild(counter);
            }
            
            if (remaining < 20) {
                counter.style.color = remaining < 0 ? '#dc3545' : '#ffc107';
                counter.textContent = `${remaining} characters remaining`;
            } else {
                counter.textContent = 'A clear, descriptive title for the resource';
                counter.style.color = '#6c757d';
            }
        });
        
        // Duration input validation
        document.getElementById('duration_minutes').addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value && (value < 1 || value > 1440)) {
                this.setCustomValidity('Duration must be between 1 and 1440 minutes (24 hours)');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>