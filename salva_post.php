<?php
require_once 'auth_check.php';
require_once 'db.php';
require_once 'includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

// Validazione CSRF
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['errore'] = 'Token di sicurezza non valido. Riprova.';
    header('Location: nuovo_post.php');
    exit;
}

$titolo = trim($_POST['titolo'] ?? '');
$contenuto = $_POST['contenuto'] ?? '';
$categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
$pubblicato = isset($_POST['pubblicato']) ? 1 : 0;
$post_id = !empty($_POST['post_id']) ? (int)$_POST['post_id'] : null;

// Validazione
if (empty($titolo) || empty($contenuto)) {
    $_SESSION['errore'] = 'Titolo e contenuto sono obbligatori';
    header('Location: nuovo_post.php');
    exit;
}

// Genera slug dal titolo (con supporto caratteri italiani accentati)
function genera_slug($stringa) {
    $slug = strtolower(trim($stringa));
    // Sostituisce i caratteri accentati italiani con le loro controparti ASCII
    $accenti = ['à', 'è', 'é', 'ì', 'ò', 'ù', 'â', 'ê', 'î', 'ô', 'û', 'ä', 'ë', 'ï', 'ö', 'ü', 'ã', 'ñ', 'ç', 'ð', 'þ', 'æ', 'œ'];
    $ascii   = ['a', 'e', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'a', 'n', 'c', 'd', 'th', 'ae', 'oe'];
    $slug = str_replace($accenti, $ascii, $slug);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

$slug = genera_slug($titolo);

// Verifica unicità slug
$slug_originale = $slug;
$counter = 1;
while (true) {
    $stmt = $pdo->prepare("SELECT id FROM post WHERE slug = ? AND id != ?");
    $stmt->execute([$slug, $post_id ?? 0]);
    if ($stmt->rowCount() == 0) break;
    $slug = $slug_originale . '-' . $counter++;
}

try {
    if ($post_id) {
        // Aggiornamento post esistente
        $stmt = $pdo->prepare("
            UPDATE post 
            SET titolo = ?, slug = ?, contenuto = ?, categoria_id = ?, pubblicato = ?
            WHERE id = ?
        ");
        $stmt->execute([$titolo, $slug, $contenuto, $categoria_id, $pubblicato, $post_id]);
        $_SESSION['successo'] = 'Post aggiornato con successo!';
    } else {
        // Creazione nuovo post
        $stmt = $pdo->prepare("
            INSERT INTO post (titolo, slug, contenuto, categoria_id, pubblicato) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$titolo, $slug, $contenuto, $categoria_id, $pubblicato]);
        $_SESSION['successo'] = 'Post pubblicato con successo!';
    }
    
    header('Location: admin.php');
    exit;
    
} catch (PDOException $e) {
    error_log("Errore salvataggio post: " . $e->getMessage());
    $_SESSION['errore'] = 'Errore durante il salvataggio del post. Riprova.';
    header('Location: nuovo_post.php' . ($post_id ? '?edit=' . $post_id : ''));
    exit;
}
