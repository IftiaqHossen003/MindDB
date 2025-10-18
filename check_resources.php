<?php
require_once __DIR__ . '/config.php';

echo "=== Resources Table Structure ===\n";

try {
    $db = getDb();
    $stmt = $db->prepare('DESCRIBE resources');
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        echo "{$col['Field']}: {$col['Type']} (Null: {$col['Null']}, Key: {$col['Key']})\n";
    }
    
    // Also check current data
    echo "\n=== Sample Resources Data ===\n";
    $stmt = $db->prepare('SELECT * FROM resources LIMIT 3');
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($resources as $resource) {
        echo "ID: {$resource['id']}, Title: " . substr($resource['title'] ?? 'N/A', 0, 30) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>