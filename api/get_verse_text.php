<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['success' => false, 'text' => '']));
}

$reference = trim($_GET['reference'] ?? '');

if (empty($reference)) {
    die(json_encode(['success' => false, 'text' => '']));
}

try {
    // First, try to get from popular_verses table
    $stmt = $pdo->prepare("SELECT text FROM popular_verses WHERE reference = ? LIMIT 1");
    $stmt->execute([$reference]);
    $result = $stmt->fetch();
    
    if ($result && !empty($result['text'])) {
        echo json_encode([
            'success' => true,
            'reference' => $reference,
            'text' => $result['text']
        ]);
        exit;
    }
    
    // If not in popular_verses, try to fetch from Bible API
    $apiKey = '2922b97a4bced86b969531fdfe951e22'; // Your API key
    
    // Parse reference (e.g., "Jāņa 3:16")
    if (preg_match('/^(.+?)\s+(\d+):(\d+)$/', $reference, $matches)) {
        $book = $matches[1];
        $chapter = $matches[2];
        $verse = $matches[3];
        
        // Map Latvian book names to API book names
        $bookMap = [
            'Jāņa' => 'John',
            'Jāņa evaņģēlijs' => 'John',
            'Mateja' => 'Matthew',
            'Mateja evaņģēlijs' => 'Matthew',
            'Marka' => 'Mark',
            'Marka evaņģēlijs' => 'Mark',
            'Lūkas' => 'Luke',
            'Lūkas evaņģēlijs' => 'Luke',
            '1. Mozus' => 'Genesis',
            '2. Mozus' => 'Exodus',
            'Psalmi' => 'Psalms',
            'Salamana pamācības' => 'Proverbs',
            'Jesajas' => 'Isaiah',
            'Romiešiem' => 'Romans',
            'Vēstule romiešiem' => 'Romans',
            '1. Korintiešiem' => '1 Corinthians',
            '1. vēstule korintiešiem' => '1 Corinthians',
            '2. Korintiešiem' => '2 Corinthians',
            '2. vēstule korintiešiem' => '2 Corinthians',
            'Galatiešiem' => 'Galatians',
            'Vēstule galatiešiem' => 'Galatians',
            'Efeziešiem' => 'Ephesians',
            'Vēstule efeziešiem' => 'Ephesians',
            'Filipiešiem' => 'Philippians',
            'Vēstule filipiešiem' => 'Philippians',
            '1. Timotejam' => '1 Timothy',
            '1. vēstule Timotejam' => '1 Timothy',
            '2. Timotejam' => '2 Timothy',
            '2. vēstule Timotejam' => '2 Timothy',
            'Ebrejiem' => 'Hebrews',
            'Vēstule ebrejiem' => 'Hebrews',
            'Jēkaba' => 'James',
            'Jēkaba vēstule' => 'James',
            '1. Pētera' => '1 Peter',
            '1. Pētera vēstule' => '1 Peter',
            'Atklāsmes' => 'Revelation',
            'Jāņa atklāsmes grāmata' => 'Revelation',
        ];
        
        $apiBook = $bookMap[$book] ?? $book;
        
        // Fetch from API.Bible
        $url = "https://api.scripture.api.bible/v1/bibles/06125adad2d5898a-01/verses/{$apiBook}.{$chapter}.{$verse}?content-type=text";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "api-key: {$apiKey}"
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['data']['content'])) {
                $text = strip_tags($data['data']['content']);
                $text = trim($text);
                
                echo json_encode([
                    'success' => true,
                    'reference' => $reference,
                    'text' => $text
                ]);
                exit;
            }
        }
    }
    
    // If all fails, return empty
    echo json_encode([
        'success' => false,
        'reference' => $reference,
        'text' => ''
    ]);
    
} catch (Exception $e) {
    error_log("Get verse text error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'text' => ''
    ]);
}