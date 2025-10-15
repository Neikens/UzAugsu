<?php
require_once __DIR__ . '/../config/database.php';

echo "=== SYSTEM TEST ===\n\n";

// Test 1: Database connection
try {
    $stmt = $pdo->query("SELECT 1");
    echo "✓ Database connection: OK\n";
} catch (Exception $e) {
    echo "✗ Database connection: FAILED\n";
    echo "  Error: " . $e->getMessage() . "\n";
}

// Test 2: Tables exist
$tables = ['users', 'pullups', 'verses', 'bible_references', 'popular_verses'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "✓ Table '{$table}': OK ({$count} rows)\n";
    } catch (Exception $e) {
        echo "✗ Table '{$table}': MISSING\n";
    }
}

// Test 3: PHP extensions
$extensions = ['pdo_mysql', 'curl', 'mbstring', 'json'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ PHP extension '{$ext}': OK\n";
    } else {
        echo "✗ PHP extension '{$ext}': MISSING\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>