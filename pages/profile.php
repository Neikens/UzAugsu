<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
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
    <title>Profils - Garīgā Uzlāde</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="back-link">← Atpakaļ</a>
            <h1>⚙️ Mans profils</h1>
        </div>
    </nav>

    <div class="container main-content">
        <div class="profile-container">
            <!-- Profile Info -->
            <div class="profile-info">
                <h2>Profila informācija</h2>
                <p><strong>E-pasts:</strong> <?= escape($user['email']) ?></p>
                <p><strong>Pašreizējais segvārds:</strong> <span id="displayNickname"><?= escape($user['nickname']) ?></span></p>
                <p><strong>Reģistrēts:</strong> <?= formatLatvianDate($user['created_at']) ?></p>
                <?php if (!empty($user['animal_name'])): ?>
                <p><strong>Sākotnējais segvārds:</strong> <?= escape($user['animal_name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Edit Nickname Form -->
            <div class="profile-edit">
                <h2>✏️ Mainīt segvārdu</h2>
                <form id="updateNickname">
                    <div class="form-group">
                        <label for="nickname">Jauns segvārds</label>
                        <input type="text" 
                               id="nickname" 
                               name="nickname" 
                               value="<?= escape($user['nickname']) ?>"
                               minlength="2"
                               maxlength="100"
                               placeholder="Ievadi jaunu segvārdu"
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Saglabāt</button>
                </form>
                <div id="profileMessage"></div>
            </div>

            <!-- Actions -->
            <div class="profile-actions">
                <a href="/api/logout.php" class="btn btn-danger" onclick="return confirm('Vai tiešām vēlies iziet?')">
                    🚪 Iziet no konta
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('updateNickname').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const messageDiv = document.getElementById('profileMessage');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const newNickname = document.getElementById('nickname').value.trim();
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saglabā...';
            
            try {
                const response = await fetch('/api/update_profile.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    messageDiv.className = 'message success';
                    messageDiv.textContent = '✅ Segvārds mainīts!';
                    
                    // Update display immediately
                    document.getElementById('displayNickname').textContent = newNickname;
                    
                    // Reload after 1 second
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '❌ ' + (data.message || 'Kļūda');
                }
            } catch (error) {
                console.error('Error:', error);
                messageDiv.className = 'message error';
                messageDiv.textContent = '❌ Kļūda savienojumā';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '💾 Saglabāt';
            }
        });
    </script>
</body>
</html>
