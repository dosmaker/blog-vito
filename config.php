<?php
// Configurazione - Carica da .env se presente, altrimenti usa default
require_once __DIR__ . '/includes/env_loader.php';

// Configurazione Database
$env = load_env(__DIR__ . '/.env');

define('DB_HOST', $env['DB_HOST'] ?? '127.0.0.1');
define('DB_NAME', $env['DB_NAME'] ?? 'blog_vito');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// Configurazione Sito
define('SITE_NAME', $env['SITE_NAME'] ?? 'Blog Tecnico di Vito');
define('SITE_URL', $env['SITE_URL'] ?? 'http://localhost');
define('TINYMCE_API_KEY', $env['TINYMCE_API_KEY'] ?? 'no-api-key');

// Origini autorizzate a incorporare le pagine pubbliche in un iframe.
// Esempio piu' restrittivo: 'self' https://cloud.example.com
$frameAncestors = trim($env['FRAME_ANCESTORS'] ?? "'self'");
if ($frameAncestors === '' || preg_match('/[\r\n]/', $frameAncestors)) {
    $frameAncestors = "'self'";
}
define('FRAME_ANCESTORS', $frameAncestors);

// Configurazione Upload
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', (int)($env['MAX_FILE_SIZE'] ?? 5242880)); // 5MB

// Timezone
date_default_timezone_set('Europe/Rome');

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Il blog pubblico deve poter essere mostrato dal plugin "External website" di
// Nextcloud. Le pagine di autenticazione e amministrazione restano invece
// incorporabili solo dallo stesso sito per ridurre il rischio di clickjacking.
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($currentScript !== 'index.php') {
    header('Content-Security-Policy: frame-ancestors \'self\'');
    header('X-Frame-Options: SAMEORIGIN');
}
