<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ignore_user_abort(true);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no'); // Disable nginx buffering
ob_implicit_flush(true);
ob_end_flush();

function logMessage($msg, $type = 'info') {
    $colors = [
        'success' => '#0f0',
        'error' => '#f00',
        'info' => '#0ff',
        'warning' => '#ff0'
    ];
    $color = $colors[$type] ?? '#fff';
    echo "<span style='color: {$color}'>" . htmlspecialchars($msg) . "</span><br>\n";
    flush();
}

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'popular':
        importPopularVerses();
        break;
    case 'structure':
        importBibleStructure();
        break;
    case 'texts':
        importBibleTexts();
        break;
    default:
        logMessage('Invalid import type', 'error');
}

function importPopularVerses() {
    global $pdo;
    
    logMessage("Starting popular verses import...", 'info');
    
    $verses = [
        ['Jāņa 3:16', 'Jo tik ļoti Dievs pasauli mīlējis...', 1],
        ['Psalmi 23:1', 'Tas Kungs ir mans gans, man netrūkst nekā', 2],
        ['Filipiešiem 4:13', 'Es visu spēju Tā spēkā, kas mani dara stipru', 3],
        // Add more verses from your SQL file...
    ];
    
    $count = 0;
    foreach ($verses as $verse) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO popular_verses (reference, text_lv, sort_order) 
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE text_lv = VALUES(text_lv)"
            );
            $stmt->execute($verse);
            $count++;
            
            if ($count % 10 === 0) {
                logMessage("Imported {$count} verses...", 'info');
            }
        } catch (PDOException $e) {
            logMessage("Error: " . $e->getMessage(), 'error');
        }
    }
    
    logMessage("✅ Completed! Imported {$count} popular verses", 'success');
}

function importBibleStructure() {
    global $pdo;
    
    logMessage("Starting Bible structure import...", 'info');
    logMessage("This will take 30-60 minutes. Please keep this page open.", 'warning');
    
    $api_key = '2922b97a4bced86b969531fdfe951e22';
    $bibles = [
        '04da588535022707-01' => 'Jauna Pārstrādāta 2024',
        '456c9d7c8a234d22-01' => 'Glika Bībele',
        '592420522e16049f-01' => '1965. gada izdevums'
    ];
    
    $total_imported = 0;
    
    foreach ($bibles as $bible_id => $bible_name) {
        logMessage("\n📖 Processing: {$bible_name}", 'info');
        
        // Get books
        $books_data = apiBibleRequest("/bibles/{$bible_id}/books", $api_key);
        if (!$books_data || !isset($books_data['data'])) {
            logMessage("Failed to fetch books for {$bible_name}", 'error');
            continue;
        }
        
        foreach ($books_data['data'] as $book) {
            $book_id = $book['id'];
            $book_name_lv = $book['name'];
            
            logMessage(" 📚 {$book_name_lv}", 'info');
            
            // Get chapters
            $chapters_data = apiBibleRequest(
                "/bibles/{$bible_id}/books/{$book_id}/chapters", 
                $api_key
            );
            
            if (!$chapters_data || !isset($chapters_data['data'])) continue;
            
            foreach ($chapters_data['data'] as $chapter) {
                if (!is_numeric($chapter['number'])) continue;
                
                $chapter_num = (int)$chapter['number'];
                $chapter_id = $chapter['id'];
                
                // Get verses
                $verses_data = apiBibleRequest(
                    "/bibles/{$bible_id}/chapters/{$chapter_id}/verses",
                    $api_key
                );
                
                if (!$verses_data || !isset($verses_data['data'])) continue;
                
                foreach ($verses_data['data'] as $verse) {
                    if (!is_numeric($verse['number'])) continue;
                    
                    $verse_num = (int)$verse['number'];
                    $full_ref = "{$book_name_lv} {$chapter_num}:{$verse_num}";
                    
                    try {
                        $stmt = $pdo->prepare("
                            INSERT IGNORE INTO bible_references
                            (book_lv, book_en, chapter, verse, full_reference)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $book_name_lv,
                            $book['name'],
                            $chapter_num,
                            $verse_num,
                            $full_ref
                        ]);
                        
                        if ($stmt->rowCount() > 0) {
                            $total_imported++;
                        }
                        
                    } catch (PDOException $e) {
                        logMessage("Error: {$full_ref} - " . $e->getMessage(), 'error');
                    }
                }
                
                usleep(150000); // 0.15s delay (API rate limit)
                
                if ($total_imported % 100 === 0) {
                    logMessage("  Progress: {$total_imported} verses imported...", 'info');
                }
            }
        }
        
        logMessage("✓ {$bible_name}: complete\n", 'success');
    }
    
    logMessage("✅ Import complete! Total: {$total_imported} verses", 'success');
}

function importBibleTexts() {
    logMessage("⚠️ Text import takes 6-10 hours", 'warning');
    logMessage("This feature is optional. Skipping for now.", 'info');
    logMessage("Contact admin if you need full text search.", 'info');
}

function apiBibleRequest($endpoint, $api_key) {
    $url = "https://api.scripture.api.bible/v1{$endpoint}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ["api-key: {$api_key}"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        logMessage("cURL Error: " . curl_error($ch), 'error');
        curl_close($ch);
        return null;
    }
    
    if ($http_code === 429) {
        logMessage("Rate limit hit - waiting 60s...", 'warning');
        sleep(60);
        curl_close($ch);
        return apiBibleRequest($endpoint, $api_key);
    }
    
    if ($http_code !== 200) {
        logMessage("HTTP {$http_code} for {$endpoint}", 'error');
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}