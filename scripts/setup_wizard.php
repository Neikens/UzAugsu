<?php
// Increase limits
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '256M');
ignore_user_abort(true);

// Simple password protection
$SETUP_PASSWORD = 'setup2024'; // Change this!
$authenticated = isset($_POST['auth_password']) && $_POST['auth_password'] === $SETUP_PASSWORD;

if (!$authenticated && !isset($_POST['action'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Setup Wizard - Garīgā Uzlāde</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                max-width: 800px; 
                margin: 50px auto; 
                padding: 20px;
                background: #1e1e1e;
                color: #fff;
            }
            .container {
                background: #2d2d2d;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            }
            input[type="password"] {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                font-size: 16px;
                border: 1px solid #444;
                background: #333;
                color: #fff;
                border-radius: 5px;
            }
            button {
                background: #4CAF50;
                color: white;
                padding: 12px 30px;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                margin: 10px 5px;
            }
            button:hover { background: #45a049; }
            .warning { color: #ff9800; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 Setup Wizard Access</h1>
            <p class="warning">⚠️ This setup tool will modify your database!</p>
            <form method="POST">
                <label>Enter Setup Password:</label>
                <input type="password" name="auth_password" required autofocus>
                <button type="submit">🔓 Access Setup</button>
            </form>
            <hr>
            <p><small>Default password: <code>setup2024</code> (change in setup_wizard.php)</small></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no');
ob_implicit_flush(true);
ob_end_flush();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Setup Wizard - Garīgā Uzlāde</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            padding: 20px; 
            background: #1e1e1e; 
            color: #fff;
            line-height: 1.6;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #4CAF50; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #2196F3; margin-top: 30px; }
        .step { 
            background: #2d2d2d; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }
        .log { 
            background: #000; 
            padding: 15px; 
            border: 1px solid #333; 
            margin: 15px 0;
            max-height: 400px;
            overflow-y: auto;
            font-size: 13px;
        }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
        .warning { color: #ff0; }
        button { 
            padding: 12px 25px; 
            font-size: 16px; 
            margin: 10px 5px; 
            cursor: pointer;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        button:hover { background: #45a049; transform: scale(1.05); }
        button:disabled { 
            background: #666; 
            cursor: not-allowed;
            transform: none;
        }
        .status { 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 5px;
        }
        .status.success { background: #1b5e20; border: 1px solid #4CAF50; }
        .status.error { background: #b71c1c; border: 1px solid #f44336; }
        .status.pending { background: #e65100; border: 1px solid #ff9800; }
        code { 
            background: #333; 
            padding: 2px 6px; 
            border-radius: 3px;
            color: #ffa726;
        }
        .progress {
            width: 100%;
            height: 30px;
            background: #333;
            border-radius: 5px;
            margin: 10px 0;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Setup Wizard - Garīgā Uzlāde</h1>
        <p>This wizard will set up your database and import Bible data.</p>

        <!-- STEP 1: Test Connection -->
        <div class="step">
            <h2>Step 1: Test Database Connection</h2>
            <button onclick="testConnection()">🔌 Test Connection</button>
            <div id="log-connection" class="log"></div>
        </div>

        <!-- STEP 2: Create Tables -->
        <div class="step">
            <h2>Step 2: Create Database Tables</h2>
            <p>This will create all required tables (users, verses, pullups, etc.)</p>
            <button onclick="createTables()">🗄️ Create Tables</button>
            <div id="log-tables" class="log"></div>
        </div>

        <!-- STEP 3: Import Popular Verses -->
        <div class="step">
            <h2>Step 3: Import Popular Verses</h2>
            <p>Import 70 popular Bible verses (~10 seconds)</p>
            <button onclick="runImport('popular')">📖 Import Popular Verses</button>
            <div id="log-popular" class="log"></div>
        </div>

        <!-- STEP 4: Import Bible Structure -->
        <div class="step">
            <h2>Step 4: Import Bible References</h2>
            <p class="warning">⏱️ This will take 30-60 minutes. Keep this tab open!</p>
            <p>This imports ~30,000 Bible verse references for autocomplete.</p>
            <button onclick="runImport('structure')">📚 Start Import</button>
            <div class="progress" id="progress-structure" style="display:none;">
                <div class="progress-bar" id="progress-bar-structure">0%</div>
            </div>
            <div id="log-structure" class="log"></div>
        </div>

        <!-- STEP 5: Verify -->
        <div class="step">
            <h2>Step 5: Verify Installation</h2>
            <button onclick="verifySetup()">✅ Verify Setup</button>
            <div id="log-verify" class="log"></div>
        </div>

        <hr style="margin: 40px 0; border-color: #444;">
        
        <div class="step">
            <h2>🎉 Setup Complete!</h2>
            <p>Once all steps are complete, you can:</p>
            <ol>
                <li>Visit <a href="/" style="color: #4CAF50;">Homepage</a></li>
                <li>Create an account at <a href="/auth/register.html" style="color: #4CAF50;">Register</a></li>
                <li>Delete this file: <code>scripts/setup_wizard.php</code></li>
            </ol>
        </div>
    </div>

    <script>
        function logMessage(msg, type, logId) {
            const colors = {
                'success': '#0f0',
                'error': '#f00',
                'info': '#0ff',
                'warning': '#ff0'
            };
            const color = colors[type] || '#fff';
            const logDiv = document.getElementById(logId);
            const timestamp = new Date().toLocaleTimeString();
            logDiv.innerHTML += `<span style='color: ${color}'>[${timestamp}] ${msg}</span><br>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        async function testConnection() {
            const logId = 'log-connection';
            const btn = event.target;
            btn.disabled = true;
            document.getElementById(logId).innerHTML = '';
            
            logMessage('Testing database connection...', 'info', logId);
            
            try {
                const response = await fetch('setup_actions.php?action=test_connection');
                const data = await response.json();
                
                if (data.success) {
                    logMessage('✅ Database connection successful!', 'success', logId);
                    logMessage(`Database: ${data.database}`, 'info', logId);
                    logMessage(`MySQL Version: ${data.mysql_version}`, 'info', logId);
                } else {
                    logMessage('❌ Connection failed: ' + data.message, 'error', logId);
                }
            } catch (error) {
                logMessage('❌ Error: ' + error.message, 'error', logId);
            } finally {
                btn.disabled = false;
            }
        }

        async function createTables() {
            const logId = 'log-tables';
            const btn = event.target;
            btn.disabled = true;
            document.getElementById(logId).innerHTML = '';
            
            logMessage('Creating database tables...', 'info', logId);
            
            try {
                const response = await fetch('setup_actions.php?action=create_tables');
                const data = await response.json();
                
                if (data.success) {
                    logMessage('✅ All tables created successfully!', 'success', logId);
                    data.tables.forEach(table => {
                        logMessage(`  ✓ ${table}`, 'success', logId);
                    });
                } else {
                    logMessage('❌ Error: ' + data.message, 'error', logId);
                }
            } catch (error) {
                logMessage('❌ Error: ' + error.message, 'error', logId);
            } finally {
                btn.disabled = false;
            }
        }

        async function runImport(type) {
            const logId = `log-${type}`;
            const btn = event.target;
            const progressBar = document.getElementById(`progress-bar-${type}`);
            const progressContainer = document.getElementById(`progress-${type}`);
            
            btn.disabled = true;
            btn.textContent = '⏳ Running...';
            document.getElementById(logId).innerHTML = '';
            
            if (progressContainer) {
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
            }
            
            logMessage('Starting import...', 'info', logId);
            
            try {
                const response = await fetch(`setup_actions.php?action=import_${type}`, {
                    method: 'GET',
                    cache: 'no-cache'
                });
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                
                while (true) {
                    const {done, value} = await reader.read();
                    if (done) break;
                    
                    buffer += decoder.decode(value, {stream: true});
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // Keep incomplete line in buffer
                    
                    lines.forEach(line => {
                        if (line.trim()) {
                            try {
                                const data = JSON.parse(line);
                                
                                if (data.type === 'log') {
                                    logMessage(data.message, data.level || 'info', logId);
                                } else if (data.type === 'progress' && progressBar) {
                                    const percent = Math.round(data.percent);
                                    progressBar.style.width = percent + '%';
                                    progressBar.textContent = percent + '%';
                                }
                            } catch (e) {
                                // Not JSON, regular log message
                                logMessage(line, 'info', logId);
                            }
                        }
                    });
                }
                
                if (progressBar) {
                    progressBar.style.width = '100%';
                    progressBar.textContent = '100%';
                }
                
                logMessage('✅ Import complete!', 'success', logId);
                btn.textContent = '✅ Complete';
                btn.style.background = 'green';
                
            } catch (error) {
                logMessage('❌ Error: ' + error.message, 'error', logId);
                btn.disabled = false;
                btn.textContent = '🔄 Retry';
            }
        }

        async function verifySetup() {
            const logId = 'log-verify';
            const btn = event.target;
            btn.disabled = true;
            document.getElementById(logId).innerHTML = '';
            
            logMessage('Verifying installation...', 'info', logId);
            
            try {
                const response = await fetch('setup_actions.php?action=verify');
                const data = await response.json();
                
                if (data.success) {
                    logMessage('✅ Verification complete!', 'success', logId);
                    logMessage('', 'info', logId);
                    logMessage('📊 Database Statistics:', 'info', logId);
                    Object.entries(data.stats).forEach(([key, value]) => {
                        const status = value > 0 ? 'success' : 'warning';
                        logMessage(`  ${key}: ${value.toLocaleString()} rows`, status, logId);
                    });
                    
                    if (data.all_ready) {
                        logMessage('', 'info', logId);
                        logMessage('🎉 Everything is ready!', 'success', logId);
                        logMessage('You can now register and use the site.', 'success', logId);
                    } else {
                        logMessage('', 'info', logId);
                        logMessage('⚠️ Some tables are empty. Run the import steps above.', 'warning', logId);
                    }
                } else {
                    logMessage('❌ Verification failed: ' + data.message, 'error', logId);
                }
            } catch (error) {
                logMessage('❌ Error: ' + error.message, 'error', logId);
            } finally {
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>