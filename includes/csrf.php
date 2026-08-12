<?php
/**
 * CSRF Token Management
 * Genera e valida token anti-CSRF per tutti i form
 */

require_once __DIR__ . '/session.php';
start_secure_session();

/**
 * Genera un nuovo CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validazione del token CSRF
 * Restituisce true se valido, false altrimenti
 */
function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Token scade dopo 1 ora
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Restituisce il campo hidden HTML per i form
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Restituisce l'header HTML <meta> per AJAX
 */
function csrf_meta() {
    $token = generate_csrf_token();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}
