<?php
// Database moved outside public web root for security
require_once __DIR__ . '/../includes/security.php';

function getDBPath() {
    return secure_db_path();
}

function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $path = getDBPath();
            $db = new PDO('sqlite:' . $path);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec('PRAGMA journal_mode=WAL');
            $db->exec('PRAGMA foreign_keys=ON');
            initDB($db);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Erro interno.');
        }
    }
    return $db;
}

function initDB($db) {
    $sql = file_get_contents(__DIR__ . '/../sql/schema.sql');
    $db->exec($sql);
    
    // Add 2FA columns if needed
    $cols = $db->query("PRAGMA table_info(usuarios)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('totp_secret', $cols)) $db->exec("ALTER TABLE usuarios ADD COLUMN totp_secret TEXT");
    if (!in_array('totp_ativo', $cols)) $db->exec("ALTER TABLE usuarios ADD COLUMN totp_ativo INTEGER DEFAULT 0");
}
