<?php
require_once __DIR__ . '/../config/database.php';

// Your API key
$api_key = '2922b97a4bced86b969531fdfe951e22';

// All Latvian Bible IDs from API.Bible
$latvian_bibles = [
    '04da588535022707-01' => 'Jauna Pārstrādāta latviešu Bībele 2024',
    '456c9d7c8a234d22-01' => 'Glika Bībele 8. izdevums',
    '592420522e16049f-01' => '1965. gada Bībeles izdevuma revidētais teksts',
];

function apiBibleRequest($endpoint, $api_key) {
    $ch = curl_init("https://api.scripture.api.bible/v1{$endpoint}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["api-key: {$api_key}"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($http_code !== 200) {
        echo "⚠️  API Error: HTTP {$http_code}\n";
        if ($response) {
            echo "Response: " . substr($response, 0, 200) . "...\n";
        }
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

echo "=== LATVIAN BIBLES IMPORT ===\n";
echo "API Key: " . substr($api_key, 0, 10) . "...\n\n";

// First, verify API key works
echo "Testing API connection...\n";
$test = apiBibleRequest("/bibles", $api_key);
if (!$test) {
    die("❌ API key test failed. Please check your key.\n");
}
echo "✓ API connection successful!\n\n";

$total_imported = 0;
$total_errors = 0;

// Import from each Bible version
foreach ($latvian_bibles as $bible_id => $bible_name) {
    echo "================================================\n";
    echo "📖 Starting import: {$bible_name}\n";
    echo "   ID: {$bible_id}\n";
    echo "================================================\n\n";
    
    // Get all books for this Bible
    $books_response = apiBibleRequest("/bibles/{$bible_id}/books", $api_key);
    
    if (!$books_response || !isset($books_response['data'])) {
        echo "❌ Failed to fetch books for {$bible_name}\n";
        echo "   Skipping to next Bible...\n\n";
        continue;
    }
    
    $bible_imported = 0;
    
    foreach ($books_response['data'] as $book) {
        $book_id = $book['id'];
        $book_name_lv = $book['name'];
        
        echo "  📚 {$book_name_lv}...\n";
        
        // Get chapters
        $chapters_response = apiBibleRequest(
            "/bibles/{$bible_id}/books/{$book_id}/chapters",
            $api_key
        );
        
        if (!$chapters_response || !isset($chapters_response['data'])) {
            echo "    ⚠️  No chapters found\n";
            continue;
        }
        
        foreach ($chapters_response['data'] as $chapter) {
            $chapter_id = $chapter['id'];
            $chapter_num = $chapter['number'];
            
            // Skip intro/non-numeric chapters
            if (!is_numeric($chapter_num)) {
                continue;
            }
            
            // Get verses
            $verses_response = apiBibleRequest(
                "/bibles/{$bible_id}/chapters/{$chapter_id}/verses",
                $api_key
            );
            
            if (!$verses_response || !isset($verses_response['data'])) {
                continue;
            }
            
            foreach ($verses_response['data'] as $verse) {
                $verse_num = $verse['number'];
                
                if (!is_numeric($verse_num)) {
                    continue;
                }
                
                $full_ref = "{$book_name_lv} {$chapter_num}:{$verse_num}";
                
                try {
                    // Check if reference already exists
                    $check = $pdo->prepare(
                        "SELECT id FROM bible_references 
                         WHERE full_reference = ? LIMIT 1"
                    );
                    $check->execute([$full_ref]);
                    
                    if ($check->fetch()) {
                        continue; // Skip duplicates
                    }
                    
                    // Insert new reference
                    $stmt = $pdo->prepare("
                        INSERT INTO bible_references 
                        (book_lv, chapter, verse, full_reference, book_en) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $book_name_lv,
                        (int)$chapter_num,
                        (int)$verse_num,
                        $full_ref,
                        $book['abbreviation'] ?? ''
                    ]);
                    
                    $bible_imported++;
                    $total_imported++;
                    
                } catch (PDOException $e) {
                    echo "    ❌ Error: {$full_ref}\n";
                    $total_errors++;
                }
            }
            
            // Rate limiting (API allows 100 req/min)
            usleep(100000); // 0.1 second pause
        }
        
        echo "    ✓ Complete\n";
    }
    
    echo "\n✓ {$bible_name}: {$bible_imported} verses imported\n\n";
}

echo "\n";
echo "================================================\n";
echo "=== IMPORT COMPLETE ===\n";
echo "================================================\n";
echo "✓ Total verses imported: {$total_imported}\n";
echo "❌ Total errors: {$total_errors}\n";
echo "\n";

// Show some stats
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bible_references");
    $result = $stmt->fetch();
    echo "📊 Total references in database: {$result['total']}\n";
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT book_lv) as books FROM bible_references");
    $result = $stmt->fetch();
    echo "📚 Total books: {$result['books']}\n";
} catch (Exception $e) {
    echo "Could not fetch stats\n";
}

echo "\n🎉 You can now use autocomplete!\n";
?>