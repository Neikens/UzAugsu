<?php
/**
 * PRODUCTION: Import Bible Verse Texts
 * Optimized import with resume capability
 * Run time: 6-10 hours for all 3 Bibles
 * 
 * USAGE:
 *   php production_import_texts.php [bible_id]
 * 
 * Examples:
 *   php production_import_texts.php    # Import all 3 Bibles
 *   php production_import_texts.php 04da588535022707-01  # Import specific Bible
 */

require_once __DIR__ . '/../config/database.php';

$api_key = '2922b97a4bced86b969531fdfe951e22';

$latvian_bibles = [
    '04da588535022707-01' => 'Jauna Pārstrādāta 2024',
    '456c9d7c8a234d22-01' => 'Glika Bībele',
    '592420522e16049f-01' => '1965. gada izdevums',
];

// Command line argument for specific Bible
$target_bible = $argv[1] ?? null;
if ($target_bible && !isset($latvian_bibles[$target_bible])) {
    die("Unknown Bible ID: {$target_bible}\n");
}

$log_file = '/var/log/uzaugsu/import_texts.log';
@mkdir(dirname($log_file), 0755, true);

function logMessage($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] {$msg}\n";
    echo $line;
    @file_put_contents($log_file, $line, FILE_APPEND);
}

function apiBibleRequest($endpoint, $api_key, $retry = 3) {
    for ($attempt = 1; $attempt <= $retry; $attempt++) {
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
            logMessage("Attempt {$attempt}: cURL Error - " . curl_error($ch));
            curl_close($ch);
            sleep(5 * $attempt);
            continue;
        }
        
        if ($http_code === 429) {
            logMessage("Rate limit - waiting 60s (attempt {$attempt})");
            curl_close($ch);
            sleep(60);
            continue;
        }
        
        if ($http_code === 200) {
            curl_close($ch);
            return json_decode($response, true);
        }
        
        logMessage("HTTP {$http_code} (attempt {$attempt})");
        curl_close($ch);
        sleep(5);
    }
    
    return null;
}

logMessage("=== BIBLE TEXTS IMPORT START ===");

// Test API
$test = apiBibleRequest("/bibles", $api_key);
if (!$test) {
    logMessage("FATAL: API connection failed");
    exit(1);
}
logMessage("✓ API connection verified\n");

$total_imported = 0;
$total_skipped = 0;
$total_errors = 0;
$api_calls = 0;

// Filter Bibles if specific one requested
$bibles_to_process = $target_bible 
    ? [$target_bible => $latvian_bibles[$target_bible]]
    : $latvian_bibles;

foreach ($bibles_to_process as $bible_id => $bible_name) {
    logMessage("\n" . str_repeat("=", 60));
    logMessage("📖 Processing: {$bible_name}");
    logMessage(str_repeat("=", 60));
    
    // Get verses that need text for this Bible version
    $stmt = $db->prepare("
        SELECT id, book_code, chapter, verse, full_reference
        FROM bible_references
        WHERE bible_version = ?
          AND (verse_text IS NULL OR verse_text = '')
        ORDER BY id
    ");
    $stmt->execute([$bible_id]);
    $verses_to_fetch = $stmt->fetchAll();
    
    $total_verses = count($verses_to_fetch);
    logMessage("Found {$total_verses} verses needing text\n");
    
    if ($total_verses === 0) {
        logMessage("✓ All verses already have text for this Bible\n");
        continue;
    }
    
    $current = 0;
    $batch_start = time();
    
    foreach ($verses_to_fetch as $verse_data) {
        $current++;
        $progress = round(($current / $total_verses) * 100, 1);
        
        // Build verse ID for API (format: "GEN.1.1" or similar)
        $verse_id = "{$verse_data['book_code']}.{$verse_data['chapter']}.{$verse_data['verse']}";
        
        // Fetch verse with text
        $verse_response = apiBibleRequest(
            "/bibles/{$bible_id}/verses/{$verse_id}?content-type=text&include-notes=false&include-titles=false&include-chapter-numbers=false&include-verse-numbers=false",
            $api_key
        );
        $api_calls++;
        
        if (!$verse_response || !isset($verse_response['data']['content'])) {
            $total_errors++;
            logMessage("  [{$progress}%] ERROR: {$verse_data['full_reference']}");
            continue;
        }
        
        $verse_text = trim(strip_tags($verse_response['data']['content']));
        
        if (empty($verse_text)) {
            $total_skipped++;
            continue;
        }
        
        try {
            $update = $db->prepare("
                UPDATE bible_references 
                SET verse_text = ? 
                WHERE id = ?
            ");
            $update->execute([$verse_text, $verse_data['id']]);
            $total_imported++;
            
            // Progress reporting every 50 verses
            if ($current % 50 === 0) {
                $elapsed = time() - $batch_start;
                $rate = $elapsed > 0 ? round(50 / $elapsed, 1) : 0;
                logMessage("  [{$progress}%] {$current}/{$total_verses} | {$rate} verses/sec | Last: {$verse_data['full_reference']}");
                $batch_start = time();
            }
            
        } catch (PDOException $e) {
            $total_errors++;
            logMessage("  DB ERROR: {$verse_data['full_reference']} - " . $e->getMessage());
        }
        
        // Rate limiting: 10 requests/second = 100ms sleep
        usleep(100000);
        
        // Every 500 verses, take a longer break
        if ($current % 500 === 0) {
            logMessage("  Taking 5s break after 500 verses...");
            sleep(5);
        }
    }
    
    logMessage("\n✓ Completed {$bible_name}");
    logMessage("  Imported: {$current} verses");
}

logMessage("\n" . str_repeat("=", 60));
logMessage("=== TEXT IMPORT COMPLETE ===");
logMessage(str_repeat("=", 60));
logMessage("✓ Texts imported: {$total_imported}");
logMessage("⊘ Skipped (empty): {$total_skipped}");
logMessage("❌ Errors: {$total_errors}");
logMessage("📡 API calls made: {$api_calls}");

// Final stats
try {
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            COUNT(verse_text) as with_text,
            COUNT(CASE WHEN verse_text IS NULL THEN 1 END) as without_text
        FROM bible_references
    ");
    $stats = $stmt->fetch();
    
    $completion = $stats['total'] > 0 
        ? round(($stats['with_text'] / $stats['total']) * 100, 1) 
        : 0;
    
    logMessage("\n📊 Final Database Stats:");
    logMessage("   Total references: {$stats['total']}");
    logMessage("   With text: {$stats['with_text']}");
    logMessage("   Without text: {$stats['without_text']}");
    logMessage("   Completion: {$completion}%");
    
} catch (Exception $e) {
    logMessage("Could not fetch final stats");
}

logMessage("\n🎉 Bible import complete!");