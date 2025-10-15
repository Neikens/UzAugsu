<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '256M');
ignore_user_abort(true);

require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

// For streaming responses
if (in_array($action, ['import_popular', 'import_structure'])) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Accel-Buffering: no');
    ob_implicit_flush(true);
    ob_end_flush();
} else {
    header('Content-Type: application/json; charset=utf-8');
}

function streamLog($message, $level = 'info', $percent = null) {
    $data = array(
        'type' => 'log',
        'message' => $message,
        'level' => $level
    );
    echo json_encode($data) . "\n";
    flush();
    
    if ($percent !== null) {
        echo json_encode(array(
            'type' => 'progress',
            'percent' => $percent
        )) . "\n";
        flush();
    }
}

switch ($action) {
    case 'test_connection':
        testConnection();
        break;
    case 'create_tables':
        createTables();
        break;
    case 'import_popular':
        importPopularVerses();
        break;
    case 'import_structure':
        importBibleStructure();
        break;
    case 'verify':
        verifySetup();
        break;
    default:
        echo json_encode(array('success' => false, 'message' => 'Invalid action'));
}

function testConnection() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT DATABASE() as db, VERSION() as version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(array(
            'success' => true,
            'database' => $result['db'],
            'mysql_version' => $result['version']
        ));
    } catch (PDOException $e) {
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
    }
}

function createTables() {
    global $pdo;
    
    try {
        // Users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                nickname VARCHAR(100) NOT NULL,
                animal_name VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                is_active TINYINT(1) DEFAULT 1,
                INDEX idx_email (email),
                INDEX idx_nickname (nickname)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Pullups table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pullups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                count INT NOT NULL,
                entry_date DATE NOT NULL,
                entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_date (user_id, entry_date),
                INDEX idx_entry_date (entry_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Verses table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS verses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                verse_reference VARCHAR(150) NOT NULL,
                verse_text TEXT,
                entry_date DATE NOT NULL,
                entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_first_of_day TINYINT(1) DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_date (user_id, entry_date),
                INDEX idx_entry_date (entry_date),
                INDEX idx_first_of_day (is_first_of_day)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Bible references table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bible_references (
                id INT AUTO_INCREMENT PRIMARY KEY,
                book_lv VARCHAR(100) NOT NULL,
                book_en VARCHAR(100),
                chapter INT NOT NULL,
                verse INT NOT NULL,
                full_reference VARCHAR(150) NOT NULL,
                verse_text TEXT,
                INDEX idx_reference (full_reference),
                INDEX idx_book (book_lv)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Popular verses table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS popular_verses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reference VARCHAR(150) NOT NULL,
                text_lv TEXT NOT NULL,
                sort_order INT DEFAULT 0,
                INDEX idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo json_encode(array(
            'success' => true,
            'tables' => array('users', 'pullups', 'verses', 'bible_references', 'popular_verses')
        ));
        
    } catch (PDOException $e) {
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
    }
}

function importPopularVerses() {
    global $pdo;
    
    streamLog("Starting popular verses import...");
    
    $verses = array(
        array('Jāņa 3:16', 'Jo tik ļoti Dievs pasauli mīlējis, ka Viņš devis Savu vienpiedzimušo Dēlu, lai neviens, kas Viņam tic, nepazustu, bet dabūtu mūžīgo dzīvību', 1),
        array('Psalmi 23:1', 'Tas Kungs ir mans gans, man netrūkst nekā', 2),
        array('Filipiešiem 4:13', 'Es visu spēju Tā spēkā, kas mani dara stipru', 3),
        array('Romiešiem 8:28', 'Un mēs zinām, ka tiem, kas mīl Dievu, visas lietas nāk par labu', 4),
        array('Jesajas 41:10', 'Nebīsties, jo Es esmu ar tevi! Neatkāpies, jo Es esmu tavs Dievs!', 5),
        array('Mateja 11:28', 'Nāciet šurp pie Manis visi, kas esat bēdīgi un grūtsirdīgi, Es jūs gribu atvieglināt', 6),
        array('Jozuas 1:9', 'Vai Es neesmu tev pavēlējis: esi stiprs un drošs, nebīsties un nebaiļojies!', 7),
        array('Psalmi 46:2', 'Dievs ir mūsu patvērums un spēks, palīgs bēdās, kas vienmēr ir uzticams', 8),
        array('2. Korintiešiem 12:9', 'Bet Viņš man sacīja: Manas žēlastības tev pietiek', 9),
        array('1. Korintiešiem 10:13', 'Dievs ir uzticīgs, Viņš neļaus jūs pārbaudīt pāri par jūsu spējām', 10),
        array('Salamana pamācības 3:5-6', 'Paļaujies uz To Kungu no visas sirds un nepaļaujies uz sava prāta gudrību', 11),
        array('Ebrejiem 11:1', 'Jo ticība ir stipra paļaušanās uz to, kas cerams, pārliecība par neredzamām lietām', 12),
        array('Jēkaba 1:12', 'Svētīgs tas vīrs, kas pastāv kārdinājumā', 13),
        array('Psalmi 91:11', 'Jo Viņš pavēlēs Saviem eņģeļiem tevi sargāt visos tavos ceļos', 14),
        array('1. Pētera 5:7', 'Visas savas rūpes uzveliet uz Viņu, jo Viņš par jums rūpējas', 15),
        array('Romiešiem 12:2', 'Un netopiet šai pasaulei līdzīgi, bet pārvērtieties', 16),
        array('Galatiešiem 5:22-23', 'Bet Gara auglis ir: mīlestība, prieks, miers, pacietība', 17),
        array('Efeziešiem 6:10', 'Visbeidzot: topiet stipri Kungā un Viņa varenajā spēkā', 18),
        array('1. Korintiešiem 16:13', 'Esiet nomodā, stāviet stipri ticībā, esiet drosmīgi, esiet stipri', 19),
        array('Kolosiešiem 3:23', 'Un ko jūs darāt, to dariet no sirds kā Tam Kungam', 20),
        // Add all 70 verses here...
    );
    
    $count = 0;
    $total = count($verses);
    
    foreach ($verses as $verse) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO popular_verses (reference, text_lv, sort_order) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE text_lv = VALUES(text_lv)
            ");
            $stmt->execute($verse);
            $count++;
            
            if ($count % 10 === 0) {
                $percent = ($count / $total) * 100;
                streamLog("Imported {$count}/{$total} verses...", 'info', $percent);
            }
        } catch (PDOException $e) {
            streamLog("Error: " . $e->getMessage(), 'error');
        }
    }
    
    streamLog("✅ Completed! Imported {$count} popular verses", 'success', 100);
}

function importBibleStructure() {
    global $pdo;
    
    streamLog("Starting Bible structure import...");
    streamLog("This will take 30-60 minutes. Please keep this page open.", 'warning');
    
    $api_key = '2922b97a4bced86b969531fdfe951e22';
    $bibles = array(
        '04da588535022707-01' => 'Jauna Pārstrādāta 2024',
        '456c9d7c8a234d22-01' => 'Glika Bībele',
        '592420522e16049f-01' => '1965. gada izdevums'
    );
    
    $total_imported = 0;
    $estimated_total = 31000; // Rough estimate
    
    foreach ($bibles as $bible_id => $bible_name) {
        streamLog("\n📖 Processing: {$bible_name}");
        
        $books_data = apiBibleRequest("/bibles/{$bible_id}/books", $api_key);
        if (!$books_data || !isset($books_data['data'])) {
            streamLog("Failed to fetch books for {$bible_name}", 'error');
            continue;
        }
        
        foreach ($books_data['data'] as $book) {
            $book_id = $book['id'];
            $book_name_lv = $book['name'];
            
            streamLog(" 📚 {$book_name_lv}");
            
            $chapters_data = apiBibleRequest(
                "/bibles/{$bible_id}/books/{$book_id}/chapters", 
                $api_key
            );
            
            if (!$chapters_data || !isset($chapters_data['data'])) continue;
            
            foreach ($chapters_data['data'] as $chapter) {
                if (!is_numeric($chapter['number'])) continue;
                
                $chapter_num = (int)$chapter['number'];
                $chapter_id = $chapter['id'];
                
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
                        $stmt->execute(array(
                            $book_name_lv,
                            $book['name'],
                            $chapter_num,
                            $verse_num,
                            $full_ref
                        ));
                        
                        if ($stmt->rowCount() > 0) {
                            $total_imported++;
                        }
                        
                    } catch (PDOException $e) {
                        streamLog("Error: {$full_ref}", 'error');
                    }
                }
                
                usleep(150000); // 0.15s delay
                
                if ($total_imported % 100 === 0) {
                    $percent = min(($total_imported / $estimated_total) * 100, 99);
                    streamLog("Progress: {$total_imported} verses imported...", 'info', $percent);
                }
            }
        }
        
        streamLog("✓ {$bible_name}: complete", 'success');
    }
    
    streamLog("✅ Import complete! Total: {$total_imported} verses", 'success', 100);
}

function verifySetup() {
    global $pdo;
    
    try {
        $stats = array();
        
        $tables = array('users', 'pullups', 'verses', 'bible_references', 'popular_verses');
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats[$table] = (int)$result['count'];
        }
        
        $all_ready = ($stats['bible_references'] > 1000 && $stats['popular_verses'] > 0);
        
        echo json_encode(array(
            'success' => true,
            'stats' => $stats,
            'all_ready' => $all_ready
        ));
        
    } catch (PDOException $e) {
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
    }
}

function apiBibleRequest($endpoint, $api_key) {
    $url = "https://api.scripture.api.bible/v1{$endpoint}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_HTTPHEADER => array("api-key: {$api_key}"),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        streamLog("cURL Error: " . curl_error($ch), 'error');
        curl_close($ch);
        return null;
    }
    
    if ($http_code === 429) {
        streamLog("Rate limit hit - waiting 60s...", 'warning');
        sleep(60);
        curl_close($ch);
        return apiBibleRequest($endpoint, $api_key);
    }
    
    if ($http_code !== 200) {
        streamLog("HTTP {$http_code} for {$endpoint}", 'error');
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}