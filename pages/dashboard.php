<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$user = getCurrentUser();

// Get user stats
$stmt = $pdo->prepare("SELECT SUM(count) as total_km FROM pullups WHERE user_id = ?");
$stmt->execute([$user['id']]);
$pullup_stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT entry_date) as total_days 
    FROM verses 
    WHERE user_id = ? AND is_first_of_day = 1
");
$stmt->execute([$user['id']]);
$verse_stats = $stmt->fetch();

// Get today's totals
$stmt = $pdo->prepare("
    SELECT SUM(count) as today_km 
    FROM pullups 
    WHERE user_id = ? AND entry_date = CURDATE()
");
$stmt->execute([$user['id']]);
$today_pullups = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as today_verses 
    FROM verses 
    WHERE user_id = ? AND entry_date = CURDATE()
");
$stmt->execute([$user['id']]);
$today_verses = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galvenā - Garīgā Uzlāde</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>💪 Garīgā Uzlāde</h1>
            <div class="user-info">
                <span>Sveiks, <strong><?= escape($user['nickname']) ?></strong>!</span>
                <a href="/api/logout.php" class="btn btn-sm">Iziet</a>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">💪</div>
                <div class="stat-value"><?= number_format($pullup_stats['total_km'] ?? 0) ?></div>
                <div class="stat-label">Pievilkšanās kopā</div>
                <div class="stat-sub">Šodien: <?= number_format($today_pullups['today_km'] ?? 0) ?> pievilkšanās</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-value"><?= $verse_stats['total_days'] ?? 0 ?></div>
                <div class="stat-label">Dienas ar pantiem</div>
                <div class="stat-sub">Šodien: <?= $today_verses['today_verses'] ?? 0 ?> panti</div>
            </div>
        </div>

        <!-- Main Form Section -->
        <div class="dashboard-grid">
            <!-- Left: Pullups Form -->
            <div class="form-section">
                <div class="clickable-icon" onclick="showPullupsForm()">
                    <img src="/assets/images/runner.png" alt="Skrējējs">
                    <h2>Pievienot pievilkšanās</h2>
                </div>
                
                <div id="pullupsForm" class="form-container" style="display:none;">
                    <h3>Cik reizes šodien pievilkies?</h3>
                    <form id="addPullups">
                        <input type="text" 
                               name="vards" 
                               value="<?= escape($user['nickname']) ?>" 
                               readonly 
                               class="readonly-input">
                        
                        <input type="number" 
                               name="count" 
                               placeholder="Pievilkšanās skaits" 
                               min="1" 
                               max="1000"
                               required>
                        
                        <button type="submit" class="btn btn-primary">Pievienot</button>
                    </form>
                    <div id="pullupMessage" class="message"></div>
                </div>
            </div>

            <!-- Right: Verse Form -->
            <div class="form-section">
                <div class="clickable-icon" onclick="showVerseForm()">
                    <img src="/assets/images/dargumi.png" alt="Dārgumi">
                    <h2>Pievienot pantu</h2>
                </div>
                
                <div id="verseForm" class="form-container" style="display:none;">
                    <h3>Šodienas pants</h3>
                    <form id="addVerse">
                        <input type="text" 
                               name="vards" 
                               value="<?= escape($user['nickname']) ?>" 
                               readonly 
                               class="readonly-input">
                        
                        <div class="autocomplete-wrapper">
                            <input type="text" 
                                   id="verseSearch" 
                                   name="reference" 
                                   placeholder="Sāc rakstīt: Jāņa 3..." 
                                   autocomplete="off"
                                   required>
                            <ul id="suggestions" class="suggestions-list"></ul>
                        </div>
                        
                        <button type="button" 
                                class="btn btn-secondary" 
                                onclick="getRandomVerse()">
                            🎲 Nejauši izvēlēties
                        </button>
                        
                        <textarea name="text" 
                                  id="verseText"
                                  placeholder="Panta teksts (neobligāti)" 
                                  rows="4"></textarea>
                        
                        <button type="submit" class="btn btn-primary">Pievienot pantu</button>
                    </form>
                    <div id="verseMessage" class="message"></div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <a href="/pages/leaderboard.php" class="quick-link">
                <span class="icon">🏆</span>
                <span>Rezultātu tabula</span>
            </a>
            <a href="/pages/treasury.php" class="quick-link">
                <span class="icon">💎</span>
                <span>Pantu krātuve</span>
            </a>
            <a href="/pages/profile.php" class="quick-link">
                <span class="icon">⚙️</span>
                <span>Profils</span>
            </a>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/autocomplete.js"></script>
</body>
</html>