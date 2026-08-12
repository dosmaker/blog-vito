<?php
require_once 'auth_check.php';
require_once 'db.php';
require_once 'includes/csrf.php';

$successo = '';
$errore = '';

// Carica categorie
$stmt = $pdo->query("SELECT * FROM categorie ORDER BY nome");
$categorie = $stmt->fetchAll();

// Gestione modifica post esistente
$post_edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM post WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $post_edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post_edit ? 'Modifica' : 'Nuovo' ?> Post - <?= SITE_NAME ?></title>
    <?= csrf_meta() ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- TinyMCE Editor -->
    <script src="https://cdn.tiny.cloud/1/<?= rawurlencode(TINYMCE_API_KEY) ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#editor',
            height: 500,
            menubar: true,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image media | code | fullscreen',
            content_style: 'body { font-family: Arial, Helvetica, sans-serif; font-size: 14px }',
            
            // Configurazione upload immagini
            images_upload_url: 'upload.php',
            automatic_uploads: true,
            images_reuse_filename: true,
            
            // Supporto video YouTube/Vimeo
            media_live_embeds: true,
            
            // Lingua italiana
            language: 'it',
            language_url: 'https://cdn.jsdelivr.net/npm/tinymce-lang/langs/it.js'
        });
    </script>
    
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="bi bi-pencil-square"></i> 
                    <?= $post_edit ? 'Modifica Post' : 'Nuovo Post' ?>
                </h1>
                <div>
                    <a href="admin.php" class="btn btn-light btn-sm me-2">
                        <i class="bi bi-arrow-left"></i> Pannello Admin
                    </a>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($successo): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successo) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errore): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errore) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="salva_post.php" method="POST">
                    <?= csrf_field() ?>
                    <?php if ($post_edit): ?>
                        <input type="hidden" name="post_id" value="<?= (int)$post_edit['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="titolo" class="form-label fw-bold">
                                    <i class="bi bi-type"></i> Titolo dell'articolo
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg" 
                                    id="titolo" 
                                    name="titolo" 
                                    value="<?= $post_edit ? htmlspecialchars($post_edit['titolo']) : '' ?>"
                                    placeholder="Inserisci un titolo accattivante..."
                                    required
                                >
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="categoria_id" class="form-label fw-bold">
                                    <i class="bi bi-folder"></i> Categoria
                                </label>
                                <select name="categoria_id" id="categoria_id" class="form-select form-select-lg" required>
                                    <option value="">Seleziona categoria...</option>
                                    <?php foreach ($categorie as $cat): ?>
                                        <option 
                                            value="<?= $cat['id'] ?>"
                                            <?= ($post_edit && $post_edit['categoria_id'] == $cat['id']) ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($cat['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editor" class="form-label fw-bold">
                            <i class="bi bi-file-text"></i> Contenuto
                        </label>
                        <textarea 
                            id="editor" 
                            name="contenuto"
                        ><?= $post_edit ? $post_edit['contenuto'] : '' ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <div class="form-check form-switch">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    id="pubblicato" 
                                    name="pubblicato" 
                                    value="1"
                                    <?= (!$post_edit || $post_edit['pubblicato']) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="pubblicato">
                                    Pubblica subito
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <a href="admin.php" class="btn btn-secondary me-2">
                                <i class="bi bi-x-circle"></i> Annulla
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i> 
                                <?= $post_edit ? 'Aggiorna Post' : 'Pubblica Post' ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Guida rapida -->
        <div class="card mt-4 bg-light">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle"></i> Guida Rapida</h5>
                <div class="row">
                    <div class="col-md-4">
                        <strong>📷 Inserire immagini:</strong>
                        <p class="small mb-0">Clicca l'icona immagine nell'editor, carica il file o trascina direttamente nell'editor</p>
                    </div>
                    <div class="col-md-4">
                        <strong>🎥 Inserire video YouTube:</strong>
                        <p class="small mb-0">Clicca l'icona "Media", incolla l'URL del video YouTube e conferma</p>
                    </div>
                    <div class="col-md-4">
                        <strong>💾 Salvataggio automatico:</strong>
                        <p class="small mb-0">Le immagini vengono salvate automaticamente quando pubblichi il post</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="css/style.css">

    <!-- Dark Mode Toggle -->
    <button id="darkModeToggle" 
            class="btn btn-dark-mode-toggle position-fixed"
            style="bottom: 2rem; right: 2rem; z-index: 9999;"
            title="Modalità Notte">
        🌙
    </button>

    <!-- Dark Mode Script -->
    <script>
    (function() {
        const toggle = document.getElementById('darkModeToggle');
        if (!toggle) return;

        function applyTheme(isDark) {
            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
            localStorage.setItem('darkMode', isDark);
            toggle.textContent = isDark ? '☀️' : '🌙';
        }

        const isDark = localStorage.getItem('darkMode') === 'true';
        applyTheme(isDark);

        toggle.addEventListener('click', function() {
            const current = document.documentElement.getAttribute('data-theme') === 'dark';
            applyTheme(!current);
        });
    })();
    </script>
</body>
</html>
