<?php
declare(strict_types=1);

/**
 * Metrics & Logs Viewer — session login only (no Basic Auth)
 * Credentials: admin / devTeamRed
 */

// =================== AUTH CONFIG ===================
$users = [
    ['user' => 'admin', 'pass' => 'devTeamRed'],
    ['user' => 'admin2', 'pass' => 'devTeamBlue'],
];
$sessionName  = 'sd_admin';
// ===================================================

// ---------------------- SESSION --------------------
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name($sessionName);
session_start();

// Simple helper: constant-time compare
function same(string $a, string $b): bool {
    return hash_equals($a, $b);
}

// ---------------------- LOGOUT ---------------------
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), true, 302);
    exit;
}

// ---------------------- LOGIN ----------------------
$error = '';
if (empty($_SESSION['authed']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = (string)($_POST['u'] ?? '');
    $p = (string)($_POST['p'] ?? '');
    $authed = false;
    if ($u === '' || $p === '') {
        $error = 'Username and password are required.';
    } else {
        foreach ($users as $user) {
            if (same($user['user'], $u) && same($user['pass'], $p)) {
                $authed = true;
                break;
            }
        }
    }

    if ($authed) {
        $_SESSION['authed'] = 1;
        session_regenerate_id(true);
        // Continue to page without redirect
    } else {
        $error = 'Invalid credentials.';
    }
}

// If not authed, show the login UI and stop here
if (empty($_SESSION['authed'])) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    $action = htmlspecialchars(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), ENT_QUOTES, 'UTF-8');
    $msg = $error !== '' ? '<div class="err">'.$error.'</div>' : '';
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Authenticate</title>
<style>
 body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#f6f7f9;color:#222;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}
 .card{background:#fff;border:1px solid #ddd;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,.06);width:100%;max-width:420px;padding:22px}
 h1{margin:0 0 12px 0;font-size:20px}
 .row{margin:10px 0}
 label{display:block;font-size:13px;margin-bottom:6px;color:#555}
 input[type=text],input[type=password]{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-size:14px}
 .btn{margin-top:8px;width:100%;padding:10px;border:0;border-radius:8px;background:#111;color:#fff;font-weight:600;cursor:pointer}
 .help{font-size:12px;color:#666;margin-top:10px}
 .err{background:#fee;border:1px solid #f99;color:#900;border-radius:8px;padding:8px 10px;margin-bottom:8px;font-size:13px}
</style>
</head>
<body>
<div class="card">
  <h1>Restricted Area</h1>
  {$msg}
  <form method="post" action="{$action}" autocomplete="off">
    <div class="row">
      <label for="u">Username</label>
      <input id="u" name="u" type="text" required autofocus>
    </div>
    <div class="row">
      <label for="p">Password</label>
      <input id="p" name="p" type="password" required>
    </div>
    <button class="btn" type="submit">Sign in</button>
  </form>
  <div class="help">Authorized Personel Only</div>
</div>
</body>
</html>
HTML;
    exit;
}

// ------------- SECURITY HEADERS (PAGE) -------------
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
// ---------------------------------------------------

function formatBytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $size = (float)$bytes;
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return sprintf('%.2f %s', $size, $units[$i]);
}

function tailFile(string $path, int $lines, int $maxBytes = 524288): string {
    $fh = @fopen($path, 'rb');
    if ($fh === false) {
        return "Unable to open log file.";
    }

    $size = filesize($path);
    if ($size === false) {
        fclose($fh);
        return "Unable to read log file size.";
    }

    $readBytes = (int)min($size, $maxBytes);
    if ($readBytes > 0) {
        fseek($fh, -$readBytes, SEEK_END);
    }

    $data = (string)fread($fh, $readBytes);
    fclose($fh);

    $linesArr = preg_split('/\R/', $data);
    if ($linesArr === false) {
        return $data;
    }

    $slice = array_slice($linesArr, -$lines);
    return implode("\n", $slice);
}

$view = (string)($_GET['view'] ?? 'metrics');
$view = in_array($view, ['metrics', 'logs'], true) ? $view : 'metrics';

$lines = (int)($_GET['lines'] ?? 200);
$lines = max(50, min(500, $lines));

$root = realpath(__DIR__ . '/..') ?: __DIR__;
$logDir = realpath(__DIR__ . '/../logs');
$logFiles = [];
if ($logDir && is_dir($logDir)) {
    $paths = glob($logDir . '/*.log') ?: [];
    foreach ($paths as $path) {
        $base = basename($path);
        $logFiles[$base] = $path;
    }
    ksort($logFiles);
}

$selectedLog = (string)($_GET['log'] ?? '');
if ($selectedLog === '' || !isset($logFiles[$selectedLog])) {
    $selectedLog = array_key_first($logFiles) ?? '';
}

$metrics = [
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'php_sapi' => PHP_SAPI,
    'server_name' => php_uname('n'),
    'server_os' => php_uname('s') . ' ' . php_uname('r'),
    'memory_usage_bytes' => memory_get_usage(true),
    'memory_peak_bytes' => memory_get_peak_usage(true),
    'load_avg' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
    'disk_total_bytes' => @disk_total_space($root) ?: null,
    'disk_free_bytes' => @disk_free_space($root) ?: null,
];

if (($metrics['disk_total_bytes'] ?? 0) > 0 && ($metrics['disk_free_bytes'] ?? 0) >= 0) {
    $metrics['disk_used_bytes'] = $metrics['disk_total_bytes'] - $metrics['disk_free_bytes'];
} else {
    $metrics['disk_used_bytes'] = null;
}

// JSON responses for simple programmatic access
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=UTF-8');
    if ($view === 'metrics') {
        echo json_encode([
            'metrics' => $metrics,
            'memory_usage' => formatBytes((int)$metrics['memory_usage_bytes']),
            'memory_peak' => formatBytes((int)$metrics['memory_peak_bytes']),
            'disk_total' => $metrics['disk_total_bytes'] ? formatBytes((int)$metrics['disk_total_bytes']) : null,
            'disk_free' => $metrics['disk_free_bytes'] ? formatBytes((int)$metrics['disk_free_bytes']) : null,
            'disk_used' => $metrics['disk_used_bytes'] ? formatBytes((int)$metrics['disk_used_bytes']) : null,
        ], JSON_PRETTY_PRINT);
    } else {
        $logPath = $selectedLog !== '' ? $logFiles[$selectedLog] : '';
        $data = $logPath !== '' ? tailFile($logPath, $lines) : '';
        echo json_encode([
            'file' => $selectedLog,
            'lines' => $lines,
            'data' => $data,
        ], JSON_PRETTY_PRINT);
    }
    exit;
}

$logPath = $selectedLog !== '' ? $logFiles[$selectedLog] : '';
$logData = $logPath !== '' ? tailFile($logPath, $lines) : '';
$logSize = $logPath !== '' ? filesize($logPath) : false;
$logMtime = $logPath !== '' ? filemtime($logPath) : false;

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Metrics & Logs</title>
<style>
    :root { --card-border:#ddd; --muted:#666; --bg:#f7f7f8; --accent:#0f172a; }
    body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; line-height:1.6; color:#222; max-width:1200px; margin:20px auto; padding:0 15px; background:var(--bg); }
    h1 { color:#111; margin-bottom:.25rem; }
    .container { background:#fff; border:1px solid #ddd; padding:20px; border-radius:10px; box-shadow:0 8px 20px rgba(0,0,0,.04); }
    .meta { color:var(--muted); font-size:12px; margin-left:8px; }
    .tabs { display:flex; gap:10px; margin:12px 0 18px; }
    .tab { text-decoration:none; border:1px solid #cbd5e1; padding:6px 10px; border-radius:8px; color:#0f172a; background:#f8fafc; font-size:13px; }
    .tab.active { background:#0f172a; color:#fff; border-color:#0f172a; }
    .card { border:1px solid var(--card-border); border-radius:8px; background:#fff; padding:14px; margin-bottom:14px; }
    .grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; }
    @media (max-width:900px){ .grid{ grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width:640px){ .grid{ grid-template-columns:1fr; } }
    .label { font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
    .value { font-weight:600; color:#111; margin-top:4px; font-size:15px; }
    .logout { float:right; font-size:12px; }
    .logout a { color:#111; text-decoration:none; border:1px solid #cbd5e1; padding:3px 6px; border-radius:6px; }
    .toolbar { display:flex; gap:10px; align-items:center; margin-bottom:12px; flex-wrap:wrap; }
    .toolbar label { font-size:12px; color:var(--muted); }
    select, input[type=number] { border:1px solid #cbd5e1; border-radius:6px; padding:6px 8px; font-size:13px; }
    pre { background:#0b1020; color:#e2e8f0; padding:12px; border-radius:8px; overflow:auto; font-size:12px; line-height:1.5; }
    .pill { display:inline-block; font-size:11px; color:#0f172a; border:1px solid #cbd5e1; border-radius:999px; padding:2px 8px; margin-left:8px; background:#f8fafc; }
</style>
</head>
<body>
<div class="container">
    <h1>
        Metrics & Logs
        <span class="meta">(session protected)</span>
        <span class="logout"><a href="?logout=1">Log out</a></span>
    </h1>

    <div class="tabs">
        <a class="tab <?php echo $view === 'metrics' ? 'active' : ''; ?>" href="?view=metrics">Metrics</a>
        <a class="tab <?php echo $view === 'logs' ? 'active' : ''; ?>" href="?view=logs">Logs</a>
    </div>

    <?php if ($view === 'metrics'): ?>
        <div class="grid">
            <div class="card">
                <div class="label">Timestamp</div>
                <div class="value"><?php echo h($metrics['timestamp']); ?></div>
            </div>
            <div class="card">
                <div class="label">PHP Version</div>
                <div class="value"><?php echo h($metrics['php_version']); ?></div>
            </div>
            <div class="card">
                <div class="label">SAPI</div>
                <div class="value"><?php echo h($metrics['php_sapi']); ?></div>
            </div>
            <div class="card">
                <div class="label">Server</div>
                <div class="value"><?php echo h($metrics['server_name']); ?></div>
            </div>
            <div class="card">
                <div class="label">OS</div>
                <div class="value"><?php echo h($metrics['server_os']); ?></div>
            </div>
            <div class="card">
                <div class="label">Memory Usage</div>
                <div class="value"><?php echo h(formatBytes((int)$metrics['memory_usage_bytes'])); ?></div>
                <div class="pill">Peak: <?php echo h(formatBytes((int)$metrics['memory_peak_bytes'])); ?></div>
            </div>
            <div class="card">
                <div class="label">Disk Total</div>
                <div class="value"><?php echo $metrics['disk_total_bytes'] ? h(formatBytes((int)$metrics['disk_total_bytes'])) : 'n/a'; ?></div>
            </div>
            <div class="card">
                <div class="label">Disk Used</div>
                <div class="value"><?php echo $metrics['disk_used_bytes'] ? h(formatBytes((int)$metrics['disk_used_bytes'])) : 'n/a'; ?></div>
            </div>
            <div class="card">
                <div class="label">Disk Free</div>
                <div class="value"><?php echo $metrics['disk_free_bytes'] ? h(formatBytes((int)$metrics['disk_free_bytes'])) : 'n/a'; ?></div>
            </div>
            <div class="card">
                <div class="label">Load Average</div>
                <div class="value">
                    <?php
                        if (is_array($metrics['load_avg'])) {
                            echo h(implode(', ', array_map('strval', $metrics['load_avg'])));
                        } else {
                            echo 'n/a';
                        }
                    ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="label">Programmatic Access</div>
            <div class="value">Use <code>?view=metrics&amp;format=json</code> for JSON.</div>
        </div>
    <?php else: ?>
        <div class="card">
            <form method="get" class="toolbar">
                <input type="hidden" name="view" value="logs">
                <label for="log">Log file</label>
                <select name="log" id="log">
                    <?php foreach ($logFiles as $name => $path): ?>
                        <option value="<?php echo h($name); ?>" <?php echo $name === $selectedLog ? 'selected' : ''; ?>><?php echo h($name); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="lines">Lines</label>
                <input id="lines" name="lines" type="number" min="50" max="500" step="50" value="<?php echo (int)$lines; ?>">
                <button class="tab" type="submit">Refresh</button>
                <?php if ($selectedLog !== ''): ?>
                    <span class="pill">Size: <?php echo $logSize !== false ? h(formatBytes((int)$logSize)) : 'n/a'; ?></span>
                    <span class="pill">Updated: <?php echo $logMtime !== false ? h(date('Y-m-d H:i:s', (int)$logMtime)) : 'n/a'; ?></span>
                <?php endif; ?>
            </form>
            <pre><?php echo h($logData); ?></pre>
        </div>
        <div class="card">
            <div class="label">Programmatic Access</div>
            <div class="value">Use <code>?view=logs&amp;log=<?php echo h($selectedLog); ?>&amp;lines=<?php echo (int)$lines; ?>&amp;format=json</code> for JSON.</div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
