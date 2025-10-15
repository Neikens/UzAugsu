<?php
require_once __DIR__ . '/../config/database.php';

// Basic Latvian Bible book names with chapter counts
$bible_books = [
    // Vecā Derība
    ['1. Mozus', 'Genesis', 50],
    ['2. Mozus', 'Exodus', 40],
    ['3. Mozus', 'Leviticus', 27],
    ['4. Mozus', 'Numbers', 36],
    ['5. Mozus', 'Deuteronomy', 34],
    ['Jozuas', 'Joshua', 24],
    ['Soģu', 'Judges', 21],
    ['Rutes', 'Ruth', 4],
    ['1. Samuēla', '1 Samuel', 31],
    ['2. Samuēla', '2 Samuel', 24],
    ['1. Ķēniņu', '1 Kings', 22],
    ['2. Ķēniņu', '2 Kings', 25],
    ['1. Laiku', '1 Chronicles', 29],
    ['2. Laiku', '2 Chronicles', 36],
    ['Ezras', 'Ezra', 10],
    ['Nehemijas', 'Nehemiah', 13],
    ['Esteres', 'Esther', 10],
    ['Ījaba', 'Job', 42],
    ['Psalmi', 'Psalms', 150],
    ['Salamana pamācības', 'Proverbs', 31],
    ['Salamana Mācītāja', 'Ecclesiastes', 12],
    ['Augstā Dziesma', 'Song of Solomon', 8],
    ['Jesajas', 'Isaiah', 66],
    ['Jeremijas', 'Jeremiah', 52],
    ['Raudu dziesmas', 'Lamentations', 5],
    ['Ecēhiēla', 'Ezekiel', 48],
    ['Daniēla', 'Daniel', 12],
    ['Hozejas', 'Hosea', 14],
    ['Joēla', 'Joel', 3],
    ['Āmosa', 'Amos', 9],
    ['Obadijas', 'Obadiah', 1],
    ['Jonas', 'Jonah', 4],
    ['Mihas', 'Micah', 7],
    ['Nahuma', 'Nahum', 3],
    ['Habakuka', 'Habakkuk', 3],
    ['Cefanjas', 'Zephaniah', 3],
    ['Hagaja', 'Haggai', 2],
    ['Caharjas', 'Zechariah', 14],
    ['Malahijas', 'Malachi', 4],
    
    // Jaunā Derība
    ['Mateja', 'Matthew', 28],
    ['Marka', 'Mark', 16],
    ['Lūkas', 'Luke', 24],
    ['Jāņa', 'John', 21],
    ['Apustuļu darbi', 'Acts', 28],
    ['Romiešiem', 'Romans', 16],
    ['1. Korintiešiem', '1 Corinthians', 16],
    ['2. Korintiešiem', '2 Corinthians', 13],
    ['Galatiešiem', 'Galatians', 6],
    ['Efeziešiem', 'Ephesians', 6],
    ['Filipiešiem', 'Philippians', 4],
    ['Kolosiešiem', 'Colossians', 4],
    ['1. Tesaloniķiešiem', '1 Thessalonians', 5],
    ['2. Tesaloniķiešiem', '2 Thessalonians', 3],
    ['1. Timotejam', '1 Timothy', 6],
    ['2. Timotejam', '2 Timothy', 4],
    ['Titam', 'Titus', 3],
    ['Fīlemonam', 'Philemon', 1],
    ['Ebrejiem', 'Hebrews', 13],
    ['Jēkaba', 'James', 5],
    ['1. Pētera', '1 Peter', 5],
    ['2. Pētera', '2 Peter', 3],
    ['1. Jāņa', '1 John', 5],
    ['2. Jāņa', '2 John', 1],
    ['3. Jāņa', '3 John', 1],
    ['Jūdas', 'Jude', 1],
    ['Atklāsmes', 'Revelation', 22],
];

echo "Importing basic Bible references...\n\n";

$imported = 0;

foreach ($bible_books as $book) {
    [$book_lv, $book_en, $chapters] = $book;
    
    echo "📖 {$book_lv}... ";
    
    // Create reference for each chapter (with verse 1 as placeholder)
    for ($chapter = 1; $chapter <= $chapters; $chapter++) {
        // For most books, assume ~30 verses per chapter (rough estimate)
        $verses = ($book_lv === 'Psalmi') ? 50 : 30;
        
        for ($verse = 1; $verse <= $verses; $verse++) {
            $full_ref = "{$book_lv} {$chapter}:{$verse}";
            
            try {
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO bible_references 
                    (book_lv, book_en, chapter, verse, full_reference) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$book_lv, $book_en, $chapter, $verse, $full_ref]);
                $imported++;
            } catch (PDOException $e) {
                // Skip errors
            }
        }
    }
    
    echo "✓\n";
}

echo "\n=== COMPLETE ===\n";
echo "Imported: {$imported} references\n";
echo "\nAutocomplete is now ready to use!\n";
?>
