<?php
/**
 * Logout - Distrugge la sessione in modo sicuro
 */
require_once __DIR__ . '/includes/session.php';
start_secure_session();

// Rimuovi tutte le variabili di sessione
$_SESSION = array();

// Se esiste un cookie di sessione, scadilo
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Distruggi la sessione
session_destroy();

header('Location: index.php');
exit;
