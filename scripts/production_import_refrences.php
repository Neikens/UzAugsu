<?php
/**
 * PRODUCTION: Import Bible References Structure
 * Fast import of all book/chapter/verse combinations from 3 Latvian Bibles
 * Run time: ~30-60 minutes
 */

require_once __DIR__ . '/../config/database.php';

$api_key = '2922b97a4bced86b969531fdfe951e22';

$latvian_bibles = [
    '04da588535022707-01' => 'Jauna Pārstrādāta latviešu Bībele 2024',
    '456c9d7c8a234d22-01' => 'Glika Bībele 8. izdevums',
    '592420522e16049f-01' => '1965. gada Bībeles izdevuma revidētais teksts',
];

$log_file = '/var/log/uzaugsu/import_references.log';
@mkdir(dirname($log_file), 0755, true);

function logMessage($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] {$msg}\n";
    echo $line;
    @file_put_contents($log_file, $line, FILE_APPEND);
}

function apiBibleRequest($endpoint, $api_key) {
    $ch = curl_init("https://api.scripture.api.bible/v1{$endpoint}");
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
        logMessage("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    
    if ($http_code === 429) {
        logMessage("Rate limit hit - waiting 60s...");
        sleep(60);
        curl_close($ch);
        return apiBibleRequest($endpoint, $api_key); // Retry
    }
    
    if ($http_code !== 200) {
        logMessage("HTTP {$http_code} for {$endpoint}");
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

logMessage("=== BIBLE REFERENCES IMPORT START ===");
logMessage("Importing structure from 3 Latvian Bibles");

// Test API
$test = apiBibleRequest("/bibles", $api_key);
if (!$test) {
    logMessage("FATAL: API connection failed");
    exit(1);
}
logMessage("✓ API connection verified");

$total_imported = 0;
$total_errors = 0;

foreach ($latvian_bibles as $bible_id => $bible_name) {
    logMessage("\n📖 Processing: {$bible_name}");
    
    $books_response = apiBibleRequest("/bibles/{$bible_id}/books", $api_key);
    
    if (!$books_response || !isset($books_response['data'])) {
        logMessage("ERROR: Could not fetch books for {$bible_name}");
        continue;
    }
    
    foreach ($books_response['data'] as $book) {
        $book_id = $book['id'];
        $book_name_lv = $book['name'];
        $book_abbr = $book['abbreviation'] ?? $book_id;
        
        logMessage("  📚 {$book_name_lv}");
        
        $chapters_response = apiBibleRequest(
            "/bibles/{$bible_id}/books/{$book_id}/chapters",
            $api_key
        );
        
        if (!$chapters_response || !isset($chapters_response['data'])) {
            logMessage("    WARNING: No chapters found");
            continue;
        }
        
        foreach ($chapters_response['data'] as $chapter) {
            if (!is_numeric($chapter['number'])) continue;
            
            $chapter_num = (int)$chapter['number'];
            $chapter_id = $chapter['id'];
            
            $verses_response = apiBibleRequest(
                "/bibles/{$bible_id}/chapters/{$chapter_id}/verses",
                $api_key
            );
            
            if (!$verses_response || !isset($verses_response['data'])) {
                continue;
            }
            
            $verse_count = 0;
            foreach ($verses_response['data'] as $verse) {
                if (!is_numeric($verse['number'])) continue;
                
                $verse_num = (int)$verse['number'];
                $full_ref = "{$book_name_lv} {$chapter_num}:{$verse_num}";
                
                try {
                    $stmt = $db->prepare("
                        INSERT IGNORE INTO bible_references 
                        (book_lv, book_en, book_code, chapter, verse, 
                         full_reference, bible_version) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $book_name_lv,
                        $book['name'],
                        $book_abbr,
                        $chapter_num,
                        $verse_num,
                        $full_ref,
                        $bible_id
                    ]);
                    
                    if ($stmt->rowCount() > 0) {
                        $total_imported++;
                        $verse_count++;
                    }
                    
                } catch (PDOException $e) {
                    $total_errors++;
                    logMessage("    ERROR: {$full_ref} - " . $e->getMessage());
                }
            }
            
            if ($verse_count > 0) {
                logMessage("    Ch.{$chapter_num}: {$verse_count} verses");
            }
            
            usleep(150000); // 0.15s between requests (safe rate)
        }
    }
}

logMessage("\n=== REFERENCES IMPORT COMPLETE ===");
logMessage("✓ Total imported: {$total_imported}");
logMessage("❌ Errors: {$total_errors}");

// Stats
try {
    $stmt = $db->query("
        SELECT COUNT(*) as total,
               COUNT(DISTINCT book_lv) as books,
               COUNT(DISTINCT bible_version) as versions
        FROM bible_references
    ");
    $stats = $stmt->fetch();
    
    logMessage("\n📊 Database Stats:");
    logMessage("   Total references: {$stats['total']}");
    logMessage("   Unique books: {$stats['books']}");
    logMessage("   Bible versions: {$stats['versions']}");
} catch (Exception $e) {
    logMessage("Could not fetch stats");
}

logMessage("\n✅ Ready for text import!");