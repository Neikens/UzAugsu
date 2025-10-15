<?php
// Increase limits for long-running script
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '256M');

// Prevent timeout
ignore_user_abort(true);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bible Import Tool</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #fff; }
        .log { background: #000; padding: 10px; border: 1px solid #333; margin: 10px 0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
        button { padding: 10px 20px; font-size: 16px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>📖 Bible Data Import Tool</h1>
    <div id="status"></div>
    
    <h2>Step 1: Import Popular Verses</h2>
    <button onclick="runImport('popular')">▶️ Import 70 Popular Verses</button>
    <div id="log-popular" class="log"></div>
    
    <h2>Step 2: Import Bible Structure</h2>
    <p>⏱️ This will take ~30-60 minutes. Keep this tab open!</p>
    <button onclick="runImport('structure')">▶️ Import Bible References</button>
    <div id="log-structure" class="log"></div>
    
    <h2>Step 3: Import Bible Texts (Optional)</h2>
    <p>⏱️ This will take ~6-10 hours. Only run if you need full text search.</p>
    <button onclick="runImport('texts')">▶️ Import Bible Texts</button>
    <div id="log-texts" class="log"></div>

    <script>
        async function runImport(type) {
            const logDiv = document.getElementById(`log-${type}`);
            const btn = event.target;
            
            btn.disabled = true;
            btn.textContent = '⏳ Running...';
            logDiv.innerHTML = '<span class="info">Starting import...</span><br>';
            
            try {
                const response = await fetch(`import_runner.php?type=${type}`, {
                    method: 'GET',
                    cache: 'no-cache'
                });
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                
                while (true) {
                    const {done, value} = await reader.read();
                    if (done) break;
                    
                    const text = decoder.decode(value);
                    logDiv.innerHTML += text;
                    logDiv.scrollTop = logDiv.scrollHeight;
                }
                
                btn.textContent = '✅ Complete';
                btn.style.background = 'green';
                
            } catch (error) {
                logDiv.innerHTML += `<span class="error">❌ Error: ${error.message}</span><br>`;
                btn.disabled = false;
                btn.textContent = '🔄 Retry';
            }
        }
    </script>
</body>
</html>