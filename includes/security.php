<?php
// ============================================================
// Security Module: CSRF, TOTP (2FA), Rate Limiting, Headers
// ============================================================

// Production hardening
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', '1800');
ini_set('session.cookie_lifetime', '0');

// --- CSRF Protection ---
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Requisição inválida.');
    }
    return true;
}

// --- Rate Limiting ---
function rate_limit_check($key, $maxAttempts = 5, $windowMinutes = 15) {
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    $file = $dir . '/rll_' . md5($key) . '.tmp';
    $now = time();
    $attempts = [];

    if (file_exists($file)) {
        $data = @file_get_contents($file);
        $attempts = json_decode($data, true) ?: [];
        $attempts = array_filter($attempts, fn($t) => $t > $now - ($windowMinutes * 60));
    }

    if (count($attempts) >= $maxAttempts) {
        http_response_code(429);
        die('Muitas tentativas. Aguarde alguns minutos.');
    }

    $attempts[] = $now;
    file_put_contents($file, json_encode($attempts), LOCK_EX);
}

function rate_limit_clear($key) {
    $dir = __DIR__ . '/../data';
    $file = $dir . '/rll_' . md5($key) . '.tmp';
    if (file_exists($file)) unlink($file);
}

// --- TOTP (2FA) ---
function totp_generate_secret($length = 16) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, 31)];
    }
    return $secret;
}

function totp_get_code($secret, $timeSlice = null) {
    if ($timeSlice === null) {
        $timeSlice = floor(time() / 30);
    }
    $secret = base32_decode($secret);
    $time = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('sha1', $time, $secret, true);
    $offset = ord($hash[19]) & 0xf;
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

function totp_verify($secret, $code) {
    $now = floor(time() / 30);
    for ($i = -1; $i <= 1; $i++) {
        if (hash_equals(totp_get_code($secret, $now + $i), $code)) {
            return true;
        }
    }
    return false;
}

function totp_get_otpauth_url($secret, $label = 'Financas') {
    return 'otpauth://totp/' . rawurlencode($label) . '?secret=' . $secret . '&issuer=Financas';
}

function base32_decode($data) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper($data);
    $data = str_replace('=', '', $data);
    $bits = '';
    for ($i = 0; $i < strlen($data); $i++) {
        $pos = strpos($chars, $data[$i]);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $result = '';
    for ($i = 0; $i + 7 < strlen($bits); $i += 8) {
        $result .= chr(bindec(substr($bits, $i, 8)));
    }
    return $result;
}

// --- Security Headers ---
function security_headers() {
    if (headers_sent()) return;
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
}

// --- Database path outside web root ---
function secure_db_path() {
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    $htaccess = $dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Deny from all\n");
    }
    $webconfig = $dir . '/web.config';
    if (!file_exists($webconfig)) {
        @file_put_contents($webconfig, '<?xml version="1.0"?><configuration><system.webServer><handlers><clear/></handlers></system.webServer></configuration>');
    }
    return $dir . '/financas.db';
}

// --- Backup do banco ---
function backup_db() {
    $dbPath = secure_db_path();
    $backupDir = __DIR__ . '/../backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0700, true);
    }
    if (!file_exists($dbPath)) return;

    $date = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . '/backup_' . $date . '.db';
    copy($dbPath, $backupFile);

    // Keep only last 30 backups
    $backups = glob($backupDir . '/backup_*.db');
    if (count($backups) > 30) {
        usort($backups, fn($a, $b) => filemtime($a) - filemtime($b));
        foreach (array_slice($backups, 0, count($backups) - 30) as $old) {
            @unlink($old);
        }
    }
}

function listar_backups() {
    $backupDir = __DIR__ . '/../backups';
    if (!is_dir($backupDir)) return [];
    $backups = glob($backupDir . '/backup_*.db');
    usort($backups, fn($a, $b) => filemtime($b) - filemtime($a));
    $result = [];
    foreach ($backups as $b) {
        $result[] = [
            'arquivo' => basename($b),
            'caminho' => $b,
            'data' => date('d/m/Y H:i:s', filemtime($b)),
            'tamanho' => filesize($b)
        ];
    }
    return $result;
}

function restaurar_backup($arquivo) {
    $backupDir = __DIR__ . '/../backups';
    $caminho = $backupDir . '/' . basename($arquivo);
    if (!file_exists($caminho)) return false;

    $dbPath = secure_db_path();
    return copy($caminho, $dbPath);
}
