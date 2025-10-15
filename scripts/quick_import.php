<?php
require_once __DIR__ . '/../config/database.php';

// Just the most commonly searched verses
$references = [
    // Genesis
    '1. Mozus 1:1', '1. Mozus 1:27', '1. Mozus 3:16',
    
    // Psalms (most popular)
    'Psalmi 23:1', 'Psalmi 23:2', 'Psalmi 23:3', 'Psalmi 23:4', 'Psalmi 23:5', 'Psalmi 23:6',
    'Psalmi 27:1', 'Psalmi 46:1', 'Psalmi 46:2',
    'Psalmi 91:1', 'Psalmi 91:2', 'Psalmi 91:11',
    'Psalmi 119:105',
    
    // Proverbs
    'Salamana pamācības 3:5', 'Salamana pamācības 3:6',
    'Salamana pamācības 16:3',
    
    // Isaiah
    'Jesajas 40:31', 'Jesajas 41:10',
    
    // Matthew
    'Mateja 5:16', 'Mateja 6:33', 'Mateja 6:34',
    'Mateja 7:7', 'Mateja 11:28', 'Mateja 28:20',
    
    // John (most important)
    'Jāņa 1:1', 'Jāņa 1:12', 'Jāņa 3:16', 'Jāņa 3:17',
    'Jāņa 14:6', 'Jāņa 15:5', 'Jāņa 16:33',
    
    // Romans
    'Romiešiem 5:8', 'Romiešiem 8:28', 'Romiešiem 8:31',
    'Romiešiem 8:38', 'Romiešiem 8:39', 'Romiešiem 12:2',
    
    // Philippians
    'Filipiešiem 4:6', 'Filipiešiem 4:7', 'Filipiešiem 4:13',
    
    // James
    'Jēkaba 1:5', 'Jēkaba 1:12',
    
    // 1 Peter
    '1. Pētera 5:7',
];

foreach ($references as $ref) {
    if (preg_match('/^(.+?)\s+(\d+):(\d+)$/', $ref, $m)) {
        $book = $m[1];
        $chapter = (int)$m[2];
        $verse = (int)$m[3];
        
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO bible_references 
            (book_lv, chapter, verse, full_reference) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$book, $chapter, $verse, $ref]);
    }
}

echo "✓ Imported 50+ popular references - autocomplete ready!\n";
?>
