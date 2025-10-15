<?php
require_once __DIR__ . '/../includes/auth.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantu krātuve - Garīgā Uzlāde</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="back-link">← Atpakaļ</a>
            <h1>💎 Pantu krātuve</h1>
            <span><?= escape($user['nickname']) ?></span>
        </div>
    </nav>

    <div class="container main-content">
        <div class="treasury-header">
            <img src="/assets/images/dargumi.png" alt="Dārgumi" class="treasury-icon">
            <p>Zemāk neliela kolekcija ar Svēto Rakstu pantiem, kas uzrunājuši citus vīrus.</p>
            <p>Plašākam kontekstam atver <a href="https://bibele.lv" target="_blank">bibele.lv</a></p>
        </div>

        <!-- Today's verses -->
        <div class="treasury-section">
            <h2>📅 Šodienas panti</h2>
            <div id="todayVerses" class="verses-container">
                <div class="loading">Ielādē...</div>
            </div>
        </div>

        <!-- Yesterday's verses -->
        <div class="treasury-section">
            <h2>📅 Vakardienas panti</h2>
            <div id="yesterdayVerses" class="verses-container">
                <div class="loading">Ielādē...</div>
            </div>
        </div>

        <!-- Calendar for older verses -->
        <div class="treasury-section">
            <h2>📆 Vecāki panti</h2>
            <p>Izvēlies datumu, lai redzētu pantus no konkrētas dienas:</p>
            <input type="date" 
                   id="dateSelector" 
                   max="<?= date('Y-m-d', strtotime('-2 days')) ?>"
                   onchange="loadVersesByDate(this.value)">
            
            <div id="selectedDateVerses" class="verses-container" style="display:none;">
                <div class="loading">Ielādē...</div>
            </div>
        </div>
    </div>

    <script>
        async function loadVerses(date, containerId) {
            const container = document.getElementById(containerId);
            
            try {
                const response = await fetch(`/api/get_verses.php?date=${date}`);
                const data = await response.json();
                
                if (data.verses && data.verses.length > 0) {
                    const html = data.verses.map(verse => `
                        <div class="verse-card ${verse.is_current_user ? 'own-verse' : ''}">
                            <div class="verse-header">
                                <span class="verse-user">${verse.nickname}</span>
                                <span class="verse-time">${verse.relative_time}</span>
                            </div>
                            <div class="verse-reference">${verse.verse_reference}</div>
                            ${verse.verse_text ? `<div class="verse-text">${verse.verse_text}</div>` : ''}
                            ${verse.is_first_of_day ? '<span class="badge">Pirmais šodien</span>' : ''}
                        </div>
                    `).join('');
                    
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p class="no-data">Šajā dienā nav pievienotu pantu</p>';
                }
            } catch (error) {
                console.error('Error loading verses:', error);
                container.innerHTML = '<p class="error">Kļūda ielādējot pantus</p>';
            }
        }
        
        function loadVersesByDate(date) {
            const container = document.getElementById('selectedDateVerses');
            container.style.display = 'block';
            container.innerHTML = '<div class="loading">Ielādē...</div>';
            loadVerses(date, 'selectedDateVerses');
        }
        
        // Load today and yesterday on page load
        loadVerses('today', 'todayVerses');
        loadVerses('yesterday', 'yesterdayVerses');
    </script>
</body>
</html>