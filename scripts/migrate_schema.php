<?php
require_once __DIR__ . '/../config/database.php';

echo "=== DATABASE MIGRATION ===\n\n";

// Columns to add
$columns = [
    'book_code' => "ALTER TABLE bible_references ADD COLUMN book_code VARCHAR(10) DEFAULT NULL",
    'bible_version' => "ALTER TABLE bible_references ADD COLUMN bible_version VARCHAR(50) DEFAULT NULL",
    'verse_text' => "ALTER TABLE bible_references ADD COLUMN verse_text TEXT DEFAULT NULL"
];

echo "📋 Adding columns...\n";
foreach ($columns as $name => $sql) {
    echo "   • {$name}... ";
    try {
        $db->exec($sql);
        echo "✓ Added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⊘ Already exists\n";
        } else {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
}

// Indexes to create
$indexes = [
    'idx_bible_version' => "CREATE INDEX idx_bible_version ON bible_references(bible_version)",
    'idx_book_code' => "CREATE INDEX idx_book_code ON bible_references(book_code)",
    'idx_full_reference' => "CREATE INDEX idx_full_reference ON bible_references(full_reference(100))",
    'idx_needs_text' => "CREATE INDEX idx_needs_text ON bible_references(bible_version, verse_text(10))"
];

echo "\n🔍 Adding indexes...\n";
foreach ($indexes as $name => $sql) {
    echo "   • {$name}... ";
    try {
        $db->exec($sql);
        echo "✓ Created\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "⊘ Already exists\n";
        } else {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== VERIFICATION ===\n\n";

// Show final structure
echo "📋 Current columns:\n";
$stmt = $db->query("DESCRIBE bible_references");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    $nullable = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
    $default = $col['Default'] ? " DEFAULT {$col['Default']}" : '';
    printf("   %-20s %-20s %s%s\n", $col['Field'], $col['Type'], $nullable, $default);
}

echo "\n🔍 Current indexes:\n";
$stmt = $db->query("SHOW INDEX FROM bible_references");
$indexes_grouped = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $idx) {
    $indexes_grouped[$idx['Key_name']][] = $idx['Column_name'];
}
foreach ($indexes_grouped as $name => $cols) {
    echo "   • {$name}: " . implode(', ', $cols) . "\n";
}

echo "\n✅ Migration complete!\n";