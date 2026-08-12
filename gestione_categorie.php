<?php
require_once 'auth_check.php';
require_once 'db.php';
require_once 'includes/csrf.php';

$successo = '';
$errore = '';

// Gestione aggiunta/eliminazione categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'])) {
    // Validazione CSRF per tutte le azioni
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } elseif ($_POST['azione'] === 'aggiungi') {
        $nome = trim($_POST['nome'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        
        if (!empty($nome)) {
            $slug = strtolower(preg_replace('/[^a-z0-9\u00c0-\u017f]/u', '-', $nome));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            try {
                $stmt = $pdo->prepare("INSERT INTO categorie (nome, slug, descrizione) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $slug, $descrizione]);
                $successo = 'Categoria aggiunta con successo!';
            } catch (PDOException $e) {
                error_log("Errore aggiunta categoria: " . $e->getMessage());
                $errore = 'Errore durante l\'aggiunta della categoria.';
            }
        } else {
            $errore = 'Il nome della categoria e\' obbligatorio.';
        }
    } elseif ($_POST['azione'] === 'elimina' && isset($_POST['id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM categorie WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            $successo = 'Categoria eliminata con successo!';
        } catch (PDOException $e) {
            error_log("Errore eliminazione categoria: " . $e->getMessage());
            $errore = 'Impossibile eliminare la categoria (potrebbero esserci post associati).';
        }
    }
}

// Carica tutte le categorie
$stmt = $pdo->query("SELECT * FROM categorie ORDER BY nome");
$categorie = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Categorie - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Admin-specific CSS variables */
        :root {
            --admin-header-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --admin-border-color: rgba(102, 126, 234, 0.2);
        }
        
        .admin-header {
            background: var(--admin-header-bg);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        /* Admin dark mode - sfondo scuro completo */
        [data-theme="dark"] body,
        [data-theme="dark"] body.bg-light {
            background-color: #0f172a !important;
        }

        [data-theme="dark"] .admin-header {
            background: linear-gradient(135deg, #1e293b 0%, #312e81 100%);
            border-bottom: 1px solid rgba(0, 255, 255, 0.2);
        }

        /* Card sfondo scuro */
        [data-theme="dark"] .card {
            background-color: #1e293b !important;
            border-color: rgba(71, 85, 105, 0.6) !important;
        }

        /* Card header sfondo scuro */
        [data-theme="dark"] .card-header.bg-white {
            background-color: #334155 !important;
            border-bottom: 1px solid rgba(71, 85, 105, 0.6);
        }

        /* Card header bg-primary in dark mode */
        [data-theme="dark"] .card-header.bg-primary {
            background-color: rgba(79, 70, 229, 0.15) !important;
            border-bottom: 1px solid rgba(71, 85, 105, 0.6);
            color: #e2e8f0;
        }

        /* Table dark mode */
        [data-theme="dark"] .table {
            color: #e2e8f0;
            background-color: transparent;
        }

        [data-theme="dark"] .table thead th,
        [data-theme="dark"] .table th {
            background-color: rgba(51, 65, 85, 0.9) !important;
            border-color: rgba(71, 85, 105, 0.6);
            color: #f1f5f9;
        }

        [data-theme="dark"] .table td {
            border-color: rgba(71, 85, 105, 0.35);
            color: #cbd5e1;
        }

        /* Righe tabella - sfondo scuro uniforme */
        [data-theme="dark"] .table > tbody > tr {
            background-color: rgba(30, 41, 59, 0.7) !important;
        }

        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd),
        [data-theme="dark"] .table > tbody > tr:nth-of-type(odd) {
            background-color: rgba(30, 41, 59, 0.85) !important;
            color: #cbd5e1;
        }

        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(even),
        [data-theme="dark"] .table > tbody > tr:nth-of-type(even) {
            background-color: rgba(30, 41, 59, 0.7) !important;
            color: #cbd5e1;
        }

        /* Hover su righe */
        [data-theme="dark"] .table > tbody > tr:hover {
            background-color: rgba(56, 189, 248, 0.1) !important;
            color: #f1f5f9;
        }

        /* Card body sfondo scuro */
        [data-theme="dark"] .card-body {
            background-color: transparent;
        }

        /* Form inputs dark mode */
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: #1e293b;
            border-color: rgba(71, 85, 105, 0.6);
            color: #e2e8f0;
        }

        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: #1e293b;
            border-color: #3b82f6;
            color: #e2e8f0;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        /* Form labels dark mode */
        [data-theme="dark"] .form-label {
            color: #e2e8f0;
        }

        /* Buttons dark mode */
        [data-theme="dark"] .btn-primary {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }

        [data-theme="dark"] .btn-primary:hover {
            background-color: #6366f1;
            border-color: #6366f1;
        }

        [data-theme="dark"] .btn-outline-light {
            color: #e2e8f0;
            border-color: rgba(226, 232, 240, 0.3);
        }

        [data-theme="dark"] .btn-outline-light:hover {
            background-color: rgba(226, 232, 240, 0.1);
            color: #f1f5f9;
        }

        [data-theme="dark"] .btn-outline-danger {
            color: #f87171;
            border-color: rgba(248, 113, 113, 0.5);
        }

        [data-theme="dark"] .btn-outline-danger:hover {
            background-color: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border-color: #ef4444;
        }

        /* Alerts dark mode */
        [data-theme="dark"] .alert-success {
            background-color: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        [data-theme="dark"] .alert-danger {
            background-color: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* Code tags dark mode */
        [data-theme="dark"] code {
            background-color: rgba(51, 65, 85, 0.8);
            color: #7dd3fc;
        }

        /* Dark Mode Toggle Button */
        .btn-dark-mode-toggle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .btn-dark-mode-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        [data-theme="dark"] .btn-dark-mode-toggle {
            background-color: #374151;
            color: #e2e8f0;
            border-color: #4b5563;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .btn-dark-mode-toggle:hover {
            background-color: #4b5563;
        }
        
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="bi bi-folder"></i> Gestione Categorie
                </h1>
                <div class="d-flex gap-2 align-items-center">
                    <a href="admin.php" class="btn btn-outline-light d-inline-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i> Torna al Pannello
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($successo): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($successo) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errore): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($errore) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Form aggiunta categoria -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Nuova Categoria</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="azione" value="aggiungi">
                            
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descrizione" class="form-label">Descrizione</label>
                                <textarea class="form-control" id="descrizione" name="descrizione" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i> Aggiungi Categoria
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista categorie -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Categorie Esistenti</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>Slug</th>
                                        <th>Descrizione</th>
                                        <th class="text-end">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categorie as $cat): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($cat['nome']) ?></strong></td>
                                            <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                                            <td><?= htmlspecialchars($cat['descrizione'] ?? '-') ?></td>
                                            <td class="text-end">
                                                <form method="POST" style="display: inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="azione" value="elimina">
                                                    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Eliminare questa categoria?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
