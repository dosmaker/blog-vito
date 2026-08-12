<?php
require_once __DIR__ . '/includes/session.php';
start_secure_session();
require_once 'db.php';
require_once 'includes/csrf.php';

// === Content Security Policy Headers ===
header_remove('X-Frame-Options');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src * data: blob:; font-src https://cdn.jsdelivr.net; frame-src https://www.youtube.com https://youtube.com; frame-ancestors " . FRAME_ANCESTORS . ";");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// === Rate limiting commenti (anti-spam) ===
$errore_commento = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'aggiungi_commento') {
    if (isset($_SESSION['ultimo_commento']) && (time() - $_SESSION['ultimo_commento']) < 5) {
        $errore_commento = "⚠️ Attendi almeno 5 secondi tra un commento e l'altro.";
    }
}

// Gestione filtro categoria e vista
$categoria_filtro = $_GET['cat'] ?? null;
$post_singolo_id = $_GET['post'] ?? null;
$vista_recenti = !$categoria_filtro && !$post_singolo_id; // Home = Recenti

$post_singolo = null;

// Gestione invio commenti
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'aggiungi_commento') {
    // Validazione CSRF
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errore_commento = "Token di sicurezza non valido. Riprova.";
    } elseif (isset($_SESSION['ultimo_commento']) && (time() - $_SESSION['ultimo_commento']) < 5) {
        $errore_commento = "⚠️ Attendi almeno 5 secondi tra un commento e l'altro.";
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $contenuto = trim($_POST['contenuto'] ?? '');
        $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

        if (!empty($nome) && !empty($contenuto) && $post_id > 0) {
            // Verifica che il post esista ed è pubblicato
            $stmt_check = $pdo->prepare("SELECT id FROM post WHERE id = ? AND pubblicato = 1");
            $stmt_check->execute([$post_id]);
            if ($stmt_check->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO commenti (post_id, nome, contenuto) VALUES (?, ?, ?)");
                $stmt->execute([$post_id, $nome, $contenuto]);
                $_SESSION['ultimo_commento'] = time();
            }
        } else {
            $errore_commento = "Compila tutti i campi obbligatori.";
        }
    }
}

// Se richiesto un post singolo
if ($post_singolo_id) {
    $stmt = $pdo->prepare("
        SELECT post.*, categorie.nome as cat_nome, categorie.slug as cat_slug
        FROM post 
        LEFT JOIN categorie ON post.categoria_id = categorie.id 
        WHERE post.id = ? AND post.pubblicato = 1
    ");
    $stmt->execute([$post_singolo_id]);
    $post_singolo = $stmt->fetch();

    // Carica i commenti per questo post (ordinati dal più recente)
    $stmt_commenti = $pdo->prepare("SELECT * FROM commenti WHERE post_id = ? ORDER BY creato_il DESC");
    $stmt_commenti->execute([$post_singolo_id]);
    $commenti = $stmt_commenti->fetchAll();
}

// Query post
if ($vista_recenti) {
    // Vista Recenti: ultimi 5 post completi
    $sql = "SELECT post.*, categorie.nome as cat_nome, categorie.slug as cat_slug
            FROM post 
            LEFT JOIN categorie ON post.categoria_id = categorie.id 
            WHERE post.pubblicato = 1
            ORDER BY post.data_creazione DESC LIMIT 5";
    $stmt = $pdo->query($sql);
} elseif ($categoria_filtro) {
    // Vista Categoria: lista preview
    $sql = "SELECT post.*, categorie.nome as cat_nome, categorie.slug as cat_slug
            FROM post 
            LEFT JOIN categorie ON post.categoria_id = categorie.id 
            WHERE post.pubblicato = 1 AND post.categoria_id = ?
            ORDER BY post.data_creazione DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoria_filtro]);
}
$posts = $stmt->fetchAll();

// Carica i commenti per ogni post nella lista Recenti
if ($vista_recenti) {
    foreach ($posts as &$post) {
        $stmt_c = $pdo->prepare("SELECT * FROM commenti WHERE post_id = ? ORDER BY creato_il DESC");
        $stmt_c->execute([$post['id']]);
        $post['commenti'] = $stmt_c->fetchAll();
    }
    unset($post);
}

// Carica categorie per sidebar
$stmt_cat = $pdo->query("SELECT * FROM categorie ORDER BY nome");
$categorie = $stmt_cat->fetchAll();

// Conta post per categoria
$stmt_count = $pdo->query("
    SELECT categoria_id, COUNT(*) as count 
    FROM post 
    WHERE pubblicato = 1 
    GROUP BY categoria_id
");
$post_count = [];
while ($row = $stmt_count->fetch()) {
    $post_count[$row['categoria_id']] = $row['count'];
}

// Conta totale post pubblicati (per badge "Recenti")
$stmt_total = $pdo->query("SELECT COUNT(*) as total FROM post WHERE pubblicato = 1");
$totale_post = $stmt_total->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post_singolo ? htmlspecialchars($post_singolo['titolo']) . ' - ' : '' ?><?= SITE_NAME ?></title>
    <meta name="description" content="Blog tecnico su programmazione, tutorial e tecnologia">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-code-slash"></i> <?= SITE_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= !$post_singolo_id && !$categoria_filtro ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-house-fill"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="bi bi-shield-lock-fill"></i> Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <?php if (!$post_singolo_id): ?>
    <?php
    // Determina titolo e descrizione in base alla sezione
    if ($categoria_filtro) {
        // Sezione categoria
        $hero_title = '';
        $hero_description = '';
        foreach ($categorie as $c) {
            if ($c['id'] == $categoria_filtro) {
                $hero_title = htmlspecialchars($c['nome']);
                $hero_description = htmlspecialchars($c['descrizione']);
                break;
            }
        }
    } else {
        // Sezione Recenti (home)
        $hero_title = 'Benvenuto nel mio Blog Tecnico';
        $hero_description = 'Programmazione, tutorial e news dal mondo tech';
    }
    ?>
    <div class="hero-section text-white text-center">
        <div class="container">
            <h1 class="fw-bold"><?= $hero_title ?></h1>
            <p class="lead"><?= $hero_description ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contenuto Principale -->
    <div class="container content-container my-5">
        <div class="row">
            <!-- Sidebar Sinistra - Categorie -->
            <div class="col-lg-3 mb-4">
                <div class="card sidebar-card shadow-lg sticky-sidebar">
                    <div class="card-header text-white">
                        <h5 class="mb-0"><i class="bi bi-folder-fill"></i> Categorie</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item <?= !$categoria_filtro ? 'active' : '' ?>">
                            <a href="index.php" class="text-decoration-none d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-clock-history"></i> Recenti</span>
                                <span class="badge bg-primary rounded-pill"><?= $totale_post ?></span>
                            </a>
                        </li>
                        <?php foreach ($categorie as $cat): ?>
                            <li class="list-group-item <?= $categoria_filtro == $cat['id'] ? 'active' : '' ?>">
                                <a href="?cat=<?= $cat['id'] ?>" class="text-decoration-none d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($cat['nome']) ?></span>
                                    <span class="badge bg-secondary rounded-pill">
                                        <?= $post_count[$cat['id']] ?? 0 ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Colonna Principale - Post -->
            <div class="col-lg-9">
                <?php if ($post_singolo): ?>
                    <!-- Vista Post Singolo -->
                    <article class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Torna al blog
                                </a>
                            </div>
                            
                            <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($post_singolo['titolo']) ?></h1>
                            
                            <div class="post-meta text-muted mb-4">
                                <i class="bi bi-calendar3"></i> 
                                <?= date('d F Y', strtotime($post_singolo['data_creazione'])) ?>
                                
                                <?php if ($post_singolo['cat_nome']): ?>
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-folder"></i>
                                    <a href="?cat=<?= $post_singolo['categoria_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($post_singolo['cat_nome']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-content">
                                <?= $post_singolo['contenuto'] ?>
                            </div>

                            <!-- Sezione Commenti -->
                            <hr class="my-4">
                            
                            <h3 class="mb-4"><i class="bi bi-chat-dots-fill"></i> Commenti (<?= count($commenti ?? []) ?>)</h3>

                            <?php if (!empty($commenti)): ?>
                                <div class="row justify-content-center">
                                    <div class="col-md-8">
                                        <?php foreach ($commenti as $c): ?>
                                            <div class="card border-light mb-3">
                                                <div class="card-body p-3">
                                                    <h6 class="fw-bold text-primary">
                                                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>
                                                    </h6>
                                                    <small class="text-muted mb-2 d-block">
                                                        <i class="bi bi-clock"></i> 
                                                        <?= date('d/m/Y H:i', strtotime($c['creato_il'])) ?>
                                                    </small>
                                                    <div class="comment-content"><?= nl2br(htmlspecialchars($c['contenuto'], ENT_QUOTES, 'UTF-8')) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Form per aggiungere commento -->
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <h5 class="mb-3"><i class="bi bi-pencil-square"></i> Lascia un commento</h5>
                                    <?php if (!empty($errore_commento)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show mb-3">
                                            <?= htmlspecialchars($errore_commento) ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>
                                     <form method="POST" action="">
                                         <?= csrf_field() ?>
                                         <input type="hidden" name="action" value="aggiungi_commento">
                                         <input type="hidden" name="post_id" value="<?= (int)$post_singolo['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label for="nome" class="form-label"><i class="bi bi-person"></i> Nome (a piacere)</label>
                                            <input type="text" class="form-control" id="nome" name="nome" required placeholder="Come vuoi farti chiamare?" maxlength="100">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="contenuto" class="form-label"><i class="bi bi-chat-text"></i> Commento</label>
                                            <textarea class="form-control" id="contenuto" name="contenuto" rows="4" required placeholder="Scrivi il tuo commento..." maxlength="5000"></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send-fill"></i> Invia Commento
                                        </button>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </article>
                
                <?php else: ?>
                    <!-- Lista Post -->
                    <?php if (empty($posts)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            Nessun post disponibile. Inizia a scrivere dal pannello admin!
                        </div>
                    <?php else: ?>
                        <?php if ($vista_recenti): ?>
                            <!-- Vista Recenti: Post Completi -->
                            <h2 class="mb-4"><i class="bi bi-clock-history"></i> Ultimi Post</h2>
                            <?php foreach ($posts as $post): ?>
                                <article class="card shadow-sm mb-5 post-card">
                                    <div class="card-body p-4">
                                        <h2 class="h2 fw-bold mb-3"><?= htmlspecialchars($post['titolo']) ?></h2>
                                        
                                        <div class="post-meta text-muted mb-4">
                                            <i class="bi bi-calendar3"></i> 
                                            <?= date('d F Y', strtotime($post['data_creazione'])) ?>
                                            
                                            <?php if ($post['cat_nome']): ?>
                                                <span class="mx-2">•</span>
                                                <i class="bi bi-folder"></i>
                                                <a href="?cat=<?= $post['categoria_id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($post['cat_nome']) ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="post-content">
                                            <?= $post['contenuto'] ?>
                                        </div>

                                        <!-- Sezione Commenti -->
                                        <hr class="my-4">
                                        
                                        <h5 class="mb-3"><i class="bi bi-chat-dots-fill"></i> 
                                            Commenti (<?= count($post['commenti'] ?? []) ?>)</h5>

                                        <?php if (!empty($post['commenti'])): ?>
                                            <div class="row justify-content-center">
                                                <div class="col-md-8">
                                                    <?php foreach ($post['commenti'] as $c): ?>
                                                        <div class="card border-light mb-3">
                                                            <div class="card-body p-3">
                                                                <h6 class="fw-bold text-primary">
                                                                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>
                                                                </h6>
                                                                <small class="text-muted mb-2 d-block">
                                                                    <i class="bi bi-clock"></i> 
                                                                    <?= date('d/m/Y H:i', strtotime($c['creato_il'])) ?>
                                                                </small>
                                                                <div class="comment-content"><?= nl2br(htmlspecialchars($c['contenuto'], ENT_QUOTES, 'UTF-8')) ?></div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Form per aggiungere commento -->
                                        <?php if (!empty($errore_commento)): ?>
                                            <div class="alert alert-danger alert-dismissible fade show mb-3">
                                                <?= htmlspecialchars($errore_commento) ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        <?php endif; ?>
                                        <form method="POST" action="">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="aggiungi_commento">
                                            <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label for="nome<?= $post['id'] ?>" class="form-label"><i class="bi bi-person"></i> Nome (a piacere)</label>
                                                <input type="text" class="form-control" id="nome<?= $post['id'] ?>" name="nome" required placeholder="Come vuoi farti chiamare?" maxlength="100">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="contenuto<?= $post['id'] ?>" class="form-label"><i class="bi bi-chat-text"></i> Commento</label>
                                                <textarea class="form-control" id="contenuto<?= $post['id'] ?>" name="contenuto" rows="3" required placeholder="Scrivi il tuo commento..." maxlength="5000"></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-send-fill"></i> Invia Commento
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Vista Categoria: Lista Preview -->
                            <h2 class="mb-4">
                                <i class="bi bi-folder-fill"></i> 
                                <?php 
                                $cat_nome = '';
                                foreach ($categorie as $c) {
                                    if ($c['id'] == $categoria_filtro) {
                                        $cat_nome = $c['nome'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($cat_nome);
                                ?>
                            </h2>
                            <?php foreach ($posts as $post): ?>
                                <article class="card shadow-sm mb-4 post-card">
                                    <div class="card-body p-4">
                                        <h3 class="h4 mb-3">
                                            <a href="?post=<?= $post['id'] ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($post['titolo']) ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="post-meta text-muted mb-3">
                                            <i class="bi bi-calendar3"></i> 
                                            <?= date('d F Y', strtotime($post['data_creazione'])) ?>
                                        </div>
                                        
                                        <div class="post-excerpt">
                                            <?php
                                            $excerpt = strip_tags($post['contenuto']);
                                            if (mb_strlen($excerpt) > 300) {
                                                echo mb_substr($excerpt, 0, 300) . '...';
                                            } else {
                                                echo $excerpt;
                                            }
                                            ?>
                                        </div>
                                        
                                        <a href="?post=<?= $post['id'] ?>" class="btn btn-primary mt-3">
                                            Leggi tutto <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dark Mode Toggle -->
    <button id="darkModeToggle" 
            class="btn btn-dark-mode-toggle position-fixed"
            style="bottom: 2rem; right: 2rem; z-index: 9999;"
            title="Modalità Notte">
        🌙
    </button>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?> - Tutti i diritti riservati</p>
            <p class="small text-muted">Realizzato con PHP, MariaDB e Bootstrap</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Dark Mode Script (shared with all pages) -->
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
