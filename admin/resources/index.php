<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        <a href="/MindDB/" class="btn">← Back to Dashboard</a>
    </body>
    </html>
    <?php
    exit;
}

// Initialize controller
require_once __DIR__ . '/../../app/controllers/AdminResourceController.php';
$controller = new AdminResourceController();

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get resources data
$data = $controller->index($page, 15);
$resources = $data['resources'];
$pagination = $data['pagination'];

$pageTitle = 'Resource Management';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #dc3545, #c82333);
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
        
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            color: #212529;
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .resources-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .resources-table th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        
        .resources-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        
        .resources-table tr:hover {
            background: #f8f9fa;
        }
        
        .resource-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .type-article { background: #e3f2fd; color: #1565c0; }
        .type-video { background: #f3e5f5; color: #7b1fa2; }
        .type-audio { background: #e8f5e8; color: #2e7d32; }
        .type-tool { background: #fff3e0; color: #ef6c00; }
        .type-exercise { background: #fce4ec; color: #c2185b; }
        .type-book { background: #e0f2f1; color: #00695c; }
        .type-app { background: #f1f8e9; color: #558b2f; }
        .type-other { background: #f5f5f5; color: #616161; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .featured-star {
            color: #ffc107;
            font-size: 1.2em;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #495057;
        }
        
        .pagination a:hover {
            background: #e9ecef;
        }
        
        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            color: #6c757d;
            margin-top: 5px;
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
            
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .resources-table {
                font-size: 0.9em;
            }
            
            .resources-table th,
            .resources-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Resource Management</h1>
            <p>Manage mental health resources and content</p>
        </div>
        
        <div class="nav-bar">
            <div class="nav-links">
                <a href="/MindDB/">🏠 Dashboard</a>
                <a href="/MindDB/admin/resources/" style="font-weight: bold;">🎯 Resources</a>
                <a href="/MindDB/public/journal.php">📖 Journals</a>
                <a href="/MindDB/reports/">📊 Reports</a>
            </div>
            <div>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>!</span>
                <a href="/MindDB/logout.php" class="btn btn-secondary btn-sm">Logout</a>
            </div>
        </div>
        
        <div class="main-content">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>
            
            <div class="actions-bar">
                <h2>Mental Health Resources (<?= $pagination['total_count'] ?? 0 ?>)</h2>
                <a href="/MindDB/admin/resources/create.php" class="btn btn-primary">
                    ➕ Add New Resource
                </a>
            </div>
            
            <?php if (!empty($resources)): ?>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?= count(array_filter($resources, function($r) { return $r['is_active']; })) ?></div>
                        <div class="stat-label">Active Resources</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= count(array_filter($resources, function($r) { return $r['is_featured']; })) ?></div>
                        <div class="stat-label">Featured Resources</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= array_sum(array_column($resources, 'view_count')) ?></div>
                        <div class="stat-label">Total Views</div>
                    </div>
                </div>
                
                <table class="resources-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resources as $resource): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <?php if ($resource['is_featured']): ?>
                                            <span class="featured-star" title="Featured Resource">⭐</span>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= htmlspecialchars($resource['title']) ?></strong>
                                            <?php if ($resource['url']): ?>
                                                <br><small><a href="<?= htmlspecialchars($resource['url']) ?>" target="_blank" style="color: #007bff;">🔗 View Resource</a></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="resource-type type-<?= $resource['resource_type'] ?>">
                                        <?= ucfirst($resource['resource_type']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($resource['category'] ?? 'General') ?></td>
                                <td><?= htmlspecialchars($resource['author'] ?? 'Unknown') ?></td>
                                <td>
                                    <span class="status-badge <?= $resource['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $resource['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= number_format($resource['view_count'] ?? 0) ?></td>
                                <td>
                                    <?= date('M j, Y', strtotime($resource['created_at'])) ?>
                                    <?php if ($resource['created_at'] !== $resource['updated_at']): ?>
                                        <br><small style="color: #6c757d;">Updated: <?= date('M j', strtotime($resource['updated_at'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="/MindDB/admin/resources/edit.php?id=<?= $resource['id'] ?>" 
                                           class="btn btn-warning btn-sm">✏️ Edit</a>
                                        <button onclick="confirmDelete(<?= $resource['id'] ?>, '<?= htmlspecialchars($resource['title']) ?>')" 
                                                class="btn btn-danger btn-sm">🗑️ Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?page=<?= $pagination['current_page'] - 1 ?>">← Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <?php if ($i == $pagination['current_page']): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?page=<?= $pagination['current_page'] + 1 ?>">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <h3>📚 No Resources Yet</h3>
                    <p>Start building your mental health resource library by adding your first resource.</p>
                    <a href="/MindDB/admin/resources/create.php" class="btn btn-primary">➕ Add First Resource</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id, title) {
            if (confirm(`Are you sure you want to delete "${title}"?\n\nThis action cannot be undone.`)) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/MindDB/public/admin_resource_action.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'destroy';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Auto-hide flash messages after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>