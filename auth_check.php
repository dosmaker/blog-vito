<?php
/**
 * Autenticazione e Session Security
 * Da includere in tutte le pagine admin protette
 */

require_once __DIR__ . '/includes/session.php';
start_secure_session();

// Session timeout: 30 minuti di inattivita'
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    start_secure_session();
    $_SESSION['session_expired'] = true;
    header('Location: login.php?expired=1');
    exit;
}
$_SESSION['last_activity'] = time();

// Controllo autenticazione
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}

// Proteggere session fixation
if (!isset($_SESSION['session_created'])) {
    $_SESSION['session_created'] = time();
} elseif (time() - $_SESSION['session_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['session_created'] = time();
}
