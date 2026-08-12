<?php
require_once 'config.php';

// === Gzip Compression (se abilitata dal server/client) ===
if (!ob_get_level() && extension_loaded('zlib')) {
    if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
        ob_start('ob_gzhandler');
    } else {
        ob_start();
    }
}

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // In produzione: non mostrare messaggi di errore dettagliati
    // Logga l'errore per debug
    error_log("Database connection error: " . $e->getMessage());
    
    if (defined('DEBUG') && DEBUG) {
        echo "<div class='alert alert-danger'>Errore di connessione al database: " . htmlspecialchars($e->getMessage()) . "</div>";
    } else {
        echo "<div class='alert alert-danger'>Errore di connessione al database. Si prega di riprovare piu' tardi.</div>";
    }
    exit;
}