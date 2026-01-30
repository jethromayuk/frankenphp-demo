<?php
// ---------------------------------------------------------
// Prevent default PHP info
// ---------------------------------------------------------
if (php_sapi_name() !== 'cli') {
    // If we are not in CLI mode, just run the logic below.
}

// 1. SIMULATE HEAVY BOOT (The "Cost" of a Framework)
// We do this ONCE when the container starts.
$bootStart = microtime(true);
$heavyArray = [];
for ($i = 0; $i < 30000; $i++) $heavyArray[] = md5($i); // Increased load slightly to make it visible
$bootTime = (microtime(true) - $bootStart) * 1000;

error_log("Worker Ready! Boot cost was: " . round($bootTime, 2) . "ms");

// 2. THE WORKER
$handler = static function () use ($bootTime) {

    // API REQUEST
    if (isset($_GET['act']) && $_GET['act'] === 'json') {
        $reqStart = microtime(true);
        usleep(2000); // Simulate 2ms of actual logic (DB query etc)
        $processingTime = (microtime(true) - $reqStart) * 1000;

        header('Content-Type: application/json');
        echo json_encode([
            'processing_ms' => $processingTime,
            'boot_cost_ms' => $bootTime,
            'standard_total_ms' => $processingTime + $bootTime
        ]);
        return;
    }

    // DASHBOARD HTML
    header('Content-Type: text/html');
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>FrankenPHP Comparison</title>
    <style>
        body { font-family: sans-serif; background: #111; color: #fff; text-align: center; padding-top: 50px; }
        .container { display: flex; justify-content: center; gap: 20px; max-width: 800px; margin: 0 auto; }
        .box { background: #222; padding: 30px; border-radius: 12px; border: 1px solid #444; width: 45%; }
        
        /* The Old Way (Red/Orange) */
        .box.old { border-top: 4px solid #f59e0b; }
        .box.old h1 { color: #f59e0b; }
        
        /* The New Way (Green) */
        .box.new { border-top: 4px solid #10b981; }
        .box.new h1 { color: #10b981; }

        h1 { font-size: 18px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; }
        .metric { font-size: 50px; font-weight: bold; margin: 10px 0; }
        .sub { font-size: 14px; color: #888; margin-bottom: 5px; }
        .label { font-size: 12px; text-transform: uppercase; color: #666; margin-top: 15px; }

        button { 
            background: #2563eb; color: white; border: none; padding: 15px 40px; 
            font-size: 18px; cursor: pointer; border-radius: 8px; margin-top: 40px; 
            font-weight: bold; transition: 0.2s;
        }
        button:hover { background: #1d4ed8; transform: scale(1.05); }
    </style>
</head>
<body>

    <div class="container">
        <div class="box old">
            <h1>Standard PHP</h1>
            <div class="label">Total Latency</div>
            <div id="old-total" class="metric" style="color: #f59e0b">-- ms</div>
            
            <div class="sub">Processing: <span id="old-proc">--</span> ms</div>
            <div class="sub" style="color: #f59e0b; font-weight: bold;">+ Boot Tax: <span id="old-boot">--</span> ms</div>
        </div>

        <div class="box new">
            <h1>FrankenPHP</h1>
            <div class="label">Total Latency</div>
            <div id="new-total" class="metric" style="color: #10b981">-- ms</div>
            
            <div class="sub">Processing: <span id="new-proc">--</span> ms</div>
            <div class="sub" style="color: #10b981; font-weight: bold;">+ Boot Tax: 0 ms</div>
        </div>
    </div>

    <button onclick="ping()">⚡ Hit Server ⚡</button>

    <script>
        async function ping() {
            const res = await fetch('/?act=json');
            const data = await res.json();
            
            // 1. Update FrankenPHP (Real)
            document.getElementById('new-total').innerText = data.processing_ms.toFixed(2) + " ms";
            document.getElementById('new-proc').innerText = data.processing_ms.toFixed(2);
            
            // 2. Update Standard PHP (Simulated)
            // We show what it WOULD have cost if we paid the boot tax
            document.getElementById('old-total').innerText = data.standard_total_ms.toFixed(2) + " ms";
            document.getElementById('old-proc').innerText = data.processing_ms.toFixed(2);
            document.getElementById('old-boot').innerText = data.boot_cost_ms.toFixed(2);
        }
    </script>
</body>
</html>
HTML;
};

// 3. Start Loop
$maxRequests = (int)($_SERVER['MAX_REQUESTS'] ?? 0);
for ($nbRequests = 0; !$maxRequests || $nbRequests < $maxRequests; ++$nbRequests) {
    $keepRunning = \frankenphp_handle_request($handler);
    if ($nbRequests % 10 === 0) gc_collect_cycles();
    if (!$keepRunning) break;
}