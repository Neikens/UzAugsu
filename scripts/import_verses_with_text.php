<?php
require_once __DIR__ . '/../config/database.php';

// Your API key
$api_key = '2922b97a4bced86b969531fdfe951e22';

// Choose ONE Bible version with the best Latvian translation
$bible_id = '592420522e16049f-01'; // 1965. gada Bībeles izdevuma revidētais teksts
$bible_name = '1965. gada Bībeles izdevuma revidētais teksts';

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
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

echo "=== IMPORTING BIBLE VERSES WITH TEXT ===\n";
echo "Bible: {$bible_name}\n";
echo "This will take 30-60 minutes...\n\n";

// Test API connection
$test = apiBibleRequest("/bibles", $api_key);
if (!$test) {
    die("❌ API connection failed. Check your key.\n");
}
echo "✓ API connection successful!\n\n";

$total_imported = 0;
$total_errors = 0;
$request_count = 0;

// Get all books
$books_response = apiBibleRequest("/bibles/{$bible_id}/books", $api_key);

if (!$books_response || !isset($books_response['data'])) {
    die("❌ Failed to fetch books\n");
}

foreach ($books_response['data'] as $book) {
    $book_id = $book['id'];
    $book_name = $book['name'];
    
    echo "📖 {$book_name}\n";
    
    // Get chapters
    $chapters_response = apiBibleRequest(
        "/bibles/{$bible_id}/books/{$book_id}/chapters",
        $api_key
    );
    $request_count++;
    
    if (!$chapters_response || !isset($chapters_response['data'])) {
        echo "   ⚠️  No chapters\n";
        continue;
    }
    
    foreach ($chapters_response['data'] as $chapter) {
        $chapter_id = $chapter['id'];
        $chapter_num = $chapter['number'];
        
        if (!is_numeric($chapter_num)) {
            continue;
        }
        
        echo "   Chapter {$chapter_num}... ";
        
        // Get verses with text
        $verses_response = apiBibleRequest(
            "/bibles/{$bible_id}/chapters/{$chapter_id}/verses?include-verse-spans=false",
            $api_key
        );
        $request_count++;
        
        if (!$verses_response || !isset($verses_response['data'])) {
            echo "⚠️\n";
            continue;
        }
        
        $chapter_imported = 0;
        
        foreach ($verses_response['data'] as $verse) {
            $verse_num = $verse['number'];
            
            if (!is_numeric($verse_num)) {
                continue;
            }
            
            // Get full verse content with text
            $verse_detail = apiBibleRequest(
                "/bibles/{$bible_id}/verses/{$verse['id']}?content-type=text&include-notes=false&include-titles=false",
                $api_key
            );
            $request_count++;
            
            if (!$verse_detail || !isset($verse_detail['data']['content'])) {
                $total_errors++;
                continue;
            }
            
            $verse_text = strip_tags($verse_detail['data']['content']);
            $verse_text = trim($verse_text);
            
            // Skip if text is empty
            if (empty($verse_text)) {
                continue;
            }
            
            $full_ref = "{$book_name} {$chapter_num}:{$verse_num}";
            
            try {
                // Check if already exists
                $check = $pdo->prepare(
                    "SELECT id FROM bible_references 
                     WHERE full_reference = ? LIMIT 1"
                );
                $check->execute([$full_ref]);
                
                if ($check->fetch()) {
                    // Update existing reference with text
                    $update = $pdo->prepare(
                        "UPDATE bible_references 
                         SET verse_text = ? 
                         WHERE full_reference = ?"
                    );
                    $update->execute([$verse_text, $full_ref]);
                } else {
                    // Insert new reference with text
                    $stmt = $pdo->prepare("
                        INSERT INTO bible_references 
                        (book_lv, book_en, chapter, verse, full_reference, verse_text) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $book_name,
                        $book['abbreviation'] ?? '',
                        (int)$chapter_num,
                        (int)$verse_num,
                        $full_ref,
                        $verse_text
                    ]);
                }
                
                $chapter_imported++;
                $total_imported++;
                
            } catch (PDOException $e) {
                $total_errors++;
            }
            
            // Rate limiting: API allows ~100 requests/minute
            // We're making 3 requests per verse, so sleep 2 seconds per verse
            usleep(2000000); // 2 seconds
            
            // Show progress every 10 verses
            if ($chapter_imported % 10 == 0) {
                echo ".";
            }
        }
        
        echo " ✓ ({$chapter_imported})\n";
        
        // Additional pause between chapters
        sleep(1);
    }
    
    echo "\n";
}

echo "\n";
echo "================================================\n";
echo "=== IMPORT COMPLETE ===\n";
echo "================================================\n";
echo "✓ Verses imported: {$total_imported}\n";
echo "❌ Errors: {$total_errors}\n";
echo "📊 API requests made: {$request_count}\n";
echo "\n";

// Show stats
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               COUNT(verse_text) as with_text
        FROM bible_references
    ");
    $result = $stmt->fetch();
    echo "📊 Total references: {$result['total']}\n";
    echo "📝 References with text: {$result['with_text']}\n";
} catch (Exception $e) {
    echo "Could not fetch stats\n";
}

echo "\n🎉 Autocomplete with verse texts is ready!\n";