<?php
/**
 * Script per gestire l'upload delle immagini da TinyMCE
 * Questo file viene chiamato automaticamente dall'editor quando si carica un'immagine
 */

// Abilita error reporting per debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'auth_check.php';
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Crea la cartella uploads se non esiste
if (!file_exists(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Impossibile creare la cartella uploads']);
        exit;
    }
    chmod(UPLOAD_DIR, 0755);
}

// Verifica che la cartella sia scrivibile
if (!is_writable(UPLOAD_DIR)) {
    http_response_code(500);
    echo json_encode(['error' => 'La cartella uploads non è scrivibile']);
    exit;
}

// Verifica che sia stata caricata un'immagine
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nessun file caricato']);
    exit;
}

$file = $_FILES['file'];

// Verifica errori di upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Errore durante l\'upload del file']);
    exit;
}

// Verifica dimensione file
if ($file['size'] > MAX_FILE_SIZE) {
    http_response_code(400);
    echo json_encode(['error' => 'File troppo grande. Massimo 5MB']);
    exit;
}

// Verifica tipo file (solo immagini)
$allowed_types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!isset($allowed_types[$mime_type]) || getimagesize($file['tmp_name']) === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo file non supportato. Solo immagini JPEG, PNG, GIF, WebP']);
    exit;
}

// Genera nome file univoco
$extension = $allowed_types[$mime_type];
$filename = uniqid('img_', true) . '.' . $extension;
$filepath = UPLOAD_DIR . $filename;

// Sposta il file nella cartella uploads
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Imposta permessi corretti sul file
    chmod($filepath, 0644);
    
    // Ottimizza l'immagine se possibile
    try {
        optimizeImage($filepath, $mime_type);
    } catch (Throwable $e) {
        // L'ottimizzazione è fallita, ma il file è stato caricato
        error_log("Errore ottimizzazione immagine: " . $e->getMessage());
    }
    
    // Restituisce l'URL dell'immagine per TinyMCE
    // Usa percorso relativo dalla root del sito
    $url = 'uploads/' . $filename;
    echo json_encode(['location' => $url]);
} else {
    http_response_code(500);
    $error_msg = 'Errore durante il salvataggio del file';
    if (!is_writable(UPLOAD_DIR)) {
        $error_msg .= ' - Cartella uploads non scrivibile';
    }
    echo json_encode(['error' => $error_msg]);
}

/**
 * Funzione per ottimizzare le immagini caricate
 */
function optimizeImage($filepath, $mime_type) {
    $max_width = 1200;
    $max_height = 1200;
    $quality = 85;
    
    list($width, $height) = getimagesize($filepath);
    
    // Se l'immagine è già piccola, non fare nulla
    if ($width <= $max_width && $height <= $max_height) {
        return;
    }
    
    // Calcola nuove dimensioni mantenendo l'aspect ratio
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Crea l'immagine dalla sorgente
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filepath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filepath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($filepath);
            break;
        default:
            return;
    }
    
    if (!$source) return;
    
    // Crea nuova immagine ridimensionata
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Preserva la trasparenza per PNG e GIF
    if ($mime_type == 'image/png' || $mime_type == 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Ridimensiona
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Salva immagine ottimizzata
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            imagejpeg($destination, $filepath, $quality);
            break;
        case 'image/png':
            imagepng($destination, $filepath, 9);
            break;
        case 'image/gif':
            imagegif($destination, $filepath);
            break;
        case 'image/webp':
            imagewebp($destination, $filepath, $quality);
            break;
    }
    
    // Pulisci memoria
    imagedestroy($source);
    imagedestroy($destination);
}
