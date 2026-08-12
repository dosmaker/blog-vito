<?php
/**
 * Simple .env loader
 * Legge un file .env e restituisce un array associativo chiave=valore
 */

function load_env($filepath) {
    $result = [];

    if (!file_exists($filepath)) {
        // Se il file .env non esiste, restituisce array vuoto (usa i default in config.php)
        return $result;
    }

    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Salta le commenti e le righe vuote
        $trimmed = trim($line);
        if (empty($trimmed) || strpos($trimmed, '#') === 0) {
            continue;
        }

        // Divide al primo = solo la chiave
        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // Rimuove eventuali doppi apici
        if (substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
            $value = substr($value, 1, -1);
        }

        if (substr($value, 0, 1) === "'" && substr($value, -1) === "'") {
            $value = substr($value, 1, -1);
        }

        $result[$key] = $value;
    }

    return $result;
}