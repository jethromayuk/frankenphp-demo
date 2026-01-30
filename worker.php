<?php
// ---------------------------------------------------------
// Prevent the default PHP info page from ever showing
// ---------------------------------------------------------
if (php_sapi_name() !== 'cli') {
    // If we are not in CLI mode, just run the logic below.
}

$bootStart = microtime(true);
$heavyArray = [];
for ($i = 0; $i < 10000; $i++) $heavyArray[] = md5($i);
//for ($i = 0; $i < 1000000; $i++) $heavyArray[] = md5($i);
$bootTime = (microtime(true) - $bootStart) * 1000;
error_log("Worker Ready! Boot time: " . round($bootTime, 2) . "ms");

// Worker loop
$handler = static function () use ($bootTime) {

    // CHECK: Is this an API request?
    if (isset($_GET['act']) && $_GET['act'] === 'json') {
        $reqStart = microtime(true);
        // Simulate work
        usleep(1000);
        $processingTime = (microtime(true) - $reqStart) * 1000;

        header('Content-Type: application/json');
        // FIX: 'echo' instead of 'return'
        echo json_encode([
            'boot_time_saved_ms' => $bootTime,
            'request_processing_ms' => $processingTime
        ]);
        return; // Stop here
    }

    // DEFAULT: Serve the Dashboard HTML
    header('Content-Type: text/html');
    // FIX: 'echo' instead of 'return'
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>FrankenPHP Demo</title>
    <style>
        body { font-family: sans-serif; background: #000; color: #fff; text-align: center; padding-top: 50px; }
        .box { background: #222; padding: 30px; display: inline-block; border-radius: 10px; border: 1px solid #444; }
        h1 { color: #d0f; margin-bottom: 5px; }
        .metric { font-size: 50px; font-weight: bold; color: #0f0; margin: 20px 0; }
        button { background: #d0f; color: white; border: none; padding: 15px 30px; font-size: 18px; cursor: pointer; border-radius: 5px; }
        button:hover { background: #b0d; }
    </style>
</head>
<body>
    <div class="box">
        <h1>FrankenPHP Speed</h1>
        <div id="time" class="metric">-- ms</div>
        <p>Boot Time Saved: <span id="saved">--</span> ms</p>
        <button onclick="ping()">Hit Server</button>
    </div>

    <script>
        async function ping() {
            // We request the SAME file but with ?act=json
            const res = await fetch('/?act=json');
            const data = await res.json();
            
            document.getElementById('time').innerText = data.request_processing_ms.toFixed(2) + " ms";
            document.getElementById('saved').innerText = data.boot_time_saved_ms.toFixed(2);
        }
    </script>
</body>
</html>
HTML;
};

// 3. Start the Loop
$maxRequests = (int)($_SERVER['MAX_REQUESTS'] ?? 0);
for ($nbRequests = 0; !$maxRequests || $nbRequests < $maxRequests; ++$nbRequests) {
    $keepRunning = \frankenphp_handle_request($handler);
    if ($nbRequests % 10 === 0) gc_collect_cycles();
    if (!$keepRunning) break;
}