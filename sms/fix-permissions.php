<?php
/**
 * SMS Switch — one-time permission repair script.
 *
 * Fixes "Failed to open stream: Permission denied" errors after uploading
 * the package to cPanel (some FTP clients / zip extractors strip read bits).
 *
 * USAGE:  https://your-domain.com/fix-permissions.php?key=CHANGE_ME
 * Then DELETE this file.
 */

// --- Change this key before running! ---
$ACCESS_KEY = 'sms-switch-fix-2026';

if (!isset($_GET['key']) || !hash_equals($ACCESS_KEY, (string) $_GET['key'])) {
    http_response_code(403);
    die('Forbidden');
}
?>
<!DOCTYPE html>
<html><head><title>SMS Switch — Permission Repair</title>
<style>
body{font-family:system-ui,sans-serif;background:#f4f6f8;margin:2rem;max-width:720px}
.ok{color:#0a7d32}.bad{color:#b3261e}.box{background:#fff;border:1px solid #ddd;border-radius:8px;padding:1rem 1.25rem;margin:1rem 0}
code{background:#eef;background:padding:.1rem .3rem;border-radius:4px}
</style></head><body>
<h1>SMS Switch — Permission Repair</h1>
<div class="box">
<?php
$root = __DIR__;
$fixedDirs = $fixedFiles = $errors = 0;

echo "<p>Scanning <code>" . htmlspecialchars($root) . "</code> …</p>";

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($rii as $item) {
    $path = $item->getPathname();
    // Never touch files we did not ship (e.g. live .env stays as-is below).
    if ($item->isDir()) {
        if (!is_executable($path) || !is_readable($path)) {
            if (@chmod($path, 0755)) { $fixedDirs++; }
            else { $errors++; echo "<p class='bad'>Could not chmod dir: <code>$path</code></p>"; }
        }
    } else {
        if (!is_readable($path)) {
            if (@chmod($path, 0644)) { $fixedFiles++; }
            else { $errors++; echo "<p class='bad'>Could not chmod file: <code>$path</code></p>"; }
        }
    }
}

// Root dir itself must be traversable
@chmod($root, 0755); $fixedDirs++;

// Ensure writable dirs are group-writable (PHP-FPM often runs as the owner)
foreach (['tmp', 'uploads'] as $w) {
    $d = $root . DIRECTORY_SEPARATOR . $w;
    if (is_dir($d)) { @chmod($d, 0775); }
}

// Lock .env down if present
$env = $root . '/.env';
if (file_exists($env)) { @chmod($env, 0600); }

echo "<p class='ok'>✔ Fixed permissions on <strong>$fixedDirs</strong> directories and <strong>$fixedFiles</strong> files.</p>";
if ($errors === 0) {
    echo "<p class='ok'>No errors. Your site should work now — try reloading it.</p>";
} else {
    echo "<p class='bad'>$errors items could not be fixed automatically (hosting-level restriction). Use cPanel → Terminal with:</p>";
    echo "<pre>cd " . htmlspecialchars($root) . "\nfind . -type d -exec chmod 755 {} \\;\nfind . -type f -exec chmod 644 {} \\;</pre>";
}
?>
</div>
<p><strong>NOW DELETE THIS FILE</strong> (<code>fix-permissions.php</code>) — leaving it is a security risk.</p>
</body></html>
