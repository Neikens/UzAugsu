<?php
require_once __DIR__ . '/../includes/auth.php';
$user = getCurrentUser();

if (!$user) {
    header('Location: /auth/login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezultātu tabula - Garīgā Uzlāde</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="back-link">← Atpakaļ</a>
            <h1>🏆 Rezultātu tabula</h1>
            <span><?= escape($user['nickname']) ?></span>
        </div>
    </nav>

    <div class="container main-content">
        <div class="leaderboard-container">
            <!-- Pullups Leaderboard -->
            <div class="leaderboard-section">
                <h2>💪 Pievilkšanās (kopā)</h2>
                <div id="pullupsLeaderboard" class="leaderboard-table">
                    <div class="loading">Ielādē...</div>
                </div>
            </div>

            <!-- Verses Leaderboard -->
            <div class="leaderboard-section">
                <h2>📖 Panti (unikālas dienas)</h2>
                <div id="versesLeaderboard" class="leaderboard-table">
                    <div class="loading">Ielādē...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadLeaderboard() {
            console.log('Loading leaderboard...');
            
            try {
                const response = await fetch('/api/get_leaderboard.php');
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Leaderboard data:', data);
                
                if (!data.pullups || !data.verses) {
                    throw new Error('Invalid data structure');
                }
                
                // Render pullups leaderboard
                const pullupsContainer = document.getElementById('pullupsLeaderboard');
                
                if (data.pullups.length === 0) {
                    pullupsContainer.innerHTML = '<p class="no-data">Vēl nav datu</p>';
                } else {
                    const pullupsHtml = data.pullups.map(user => `
                        <div class="leaderboard-row ${user.is_current_user ? 'current-user' : ''}">
                            <span class="rank">${getRankIcon(user.rank)}</span>
                            <span class="nickname">${user.nickname}</span>
                            <span class="score">${user.total_km} pievilkšanās</span>
                            <span class="detail">${user.active_days} diena(s)</span>
                        </div>
                    `).join('');
                    
                    pullupsContainer.innerHTML = pullupsHtml;
                    
                    // Add current user if not in top 50
                    if (data.current_user_pullups) {
                        pullupsContainer.innerHTML += `
                            <div class="leaderboard-divider">...</div>
                            <div class="leaderboard-row current-user">
                                <span class="rank">#${data.current_user_pullups.rank}</span>
                                <span class="nickname">${data.current_user_pullups.nickname}</span>
                                <span class="score">${data.current_user_pullups.total_km} pievilkšanās</span>
                                <span class="detail">${data.current_user_pullups.active_days} diena(s)</span>
                            </div>
                        `;
                    }
                }
                
                // Render verses leaderboard
                const versesContainer = document.getElementById('versesLeaderboard');
                
                if (data.verses.length === 0) {
                    versesContainer.innerHTML = '<p class="no-data">Vēl nav datu</p>';
                } else {
                    const versesHtml = data.verses.map(user => `
                        <div class="leaderboard-row ${user.is_current_user ? 'current-user' : ''}">
                            <span class="rank">${getRankIcon(user.rank)}</span>
                            <span class="nickname">${user.nickname}</span>
                            <span class="score">${user.streak_days} dienas</span>
                            <span class="detail">${user.last_entry}</span>
                        </div>
                    `).join('');
                    
                    versesContainer.innerHTML = versesHtml;
                    
                    // Add current user if not in top 50
                    if (data.current_user_verses) {
                        versesContainer.innerHTML += `
                            <div class="leaderboard-divider">...</div>
                            <div class="leaderboard-row current-user">
                                <span class="rank">#${data.current_user_verses.rank}</span>
                                <span class="nickname">${data.current_user_verses.nickname}</span>
                                <span class="score">${data.current_user_verses.streak_days} dienas</span>
                                <span class="detail">${data.current_user_verses.last_entry}</span>
                            </div>
                        `;
                    }
                }
                
            } catch (error) {
                console.error('Error loading leaderboard:', error);
                document.getElementById('pullupsLeaderboard').innerHTML = 
                    `<p class="error">Kļūda: ${error.message}</p>`;
                document.getElementById('versesLeaderboard').innerHTML = 
                    `<p class="error">Kļūda: ${error.message}</p>`;
            }
        }
        
        function getRankIcon(rank) {
            if (rank === 1) return '🥇';
            if (rank === 2) return '🥈';
            if (rank === 3) return '🥉';
            return `#${rank}`;
        }
        
        // Load on page load
        loadLeaderboard();
    </script>
</body>
</html>
