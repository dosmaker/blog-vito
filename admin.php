<?php
require_once 'auth_check.php';
require_once 'db.php';
require_once 'includes/csrf.php';

// Gestione eliminazione post (POST instead of GET for security)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['errore'] = 'Token di sicurezza non valido.';
    } elseif (is_numeric($_POST['delete_post'])) {
        $stmt = $pdo->prepare("DELETE FROM post WHERE id = ?");
        $stmt->execute([(int)$_POST['delete_post']]);
        $_SESSION['successo'] = 'Post eliminato con successo!';
    }
    header('Location: admin.php');
    exit;
}

// Gestione eliminazione commento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment']) && is_numeric($_POST['delete_comment'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['errore'] = 'Token di sicurezza non valido.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM commenti WHERE id = ?");
        $stmt->execute([(int)$_POST['delete_comment']]);
        $_SESSION['successo'] = 'Commento eliminato con successo!';
    }
    header('Location: admin.php');
    exit;
}

// Gestione modifica commento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment']) && is_numeric($_POST['edit_comment'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['errore'] = 'Token di sicurezza non valido.';
    } else {
        $comment_id = (int)$_POST['edit_comment'];
        $nuovo_contenuto = trim($_POST['contenuto'] ?? '');
        $nuovo_nome = trim($_POST['nome'] ?? '');
        if ($nuovo_contenuto && $nuovo_nome) {
            $stmt = $pdo->prepare("UPDATE commenti SET contenuto = ?, nome = ? WHERE id = ?");
            $stmt->execute([$nuovo_contenuto, $nuovo_nome, $comment_id]);
            $_SESSION['successo'] = 'Commento aggiornato con successo!';
        }
    }
    header('Location: admin.php');
    exit;
}

// Carica tutti i post
$stmt = $pdo->query("
    SELECT post.*, categorie.nome as cat_nome 
    FROM post 
    LEFT JOIN categorie ON post.categoria_id = categorie.id 
    ORDER BY post.data_creazione DESC
");
$posts = $stmt->fetchAll();

// Conta statistiche
$stmt_stats = $pdo->query("
    SELECT 
        COUNT(*) as totale,
        SUM(CASE WHEN pubblicato = 1 THEN 1 ELSE 0 END) as pubblicati,
        SUM(CASE WHEN pubblicato = 0 THEN 1 ELSE 0 END) as bozze
    FROM post
");
$stats = $stmt_stats->fetch();

$stmt_cat_count = $pdo->query("SELECT COUNT(*) as totale FROM categorie");
$cat_stats = $stmt_cat_count->fetch();

// Carica commenti per un post specifico (per modale)
$post_id_commenti = isset($_GET['commenti']) && is_numeric($_GET['commenti']) ? (int)$_GET['commenti'] : null;
$commenti_post = [];
$post_title_commenti = '';
if ($post_id_commenti) {
    $stmt_title = $pdo->prepare("SELECT titolo FROM post WHERE id = ?");
    $stmt_title->execute([$post_id_commenti]);
    $post_title_commenti = $stmt_title->fetchColumn();
    
    $stmt_c = $pdo->prepare("SELECT * FROM commenti WHERE post_id = ? ORDER BY creato_il DESC");
    $stmt_c->execute([$post_id_commenti]);
    $commenti_post = $stmt_c->fetchAll();
}

$successo = $_SESSION['successo'] ?? '';
$errore = $_SESSION['errore'] ?? '';
unset($_SESSION['successo'], $_SESSION['errore']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Admin - <?= SITE_NAME ?></title>
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
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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

        /* Stat cards in dark mode */
        [data-theme="dark"] .stat-card {
            background-color: #1e293b !important;
            border-color: rgba(71, 85, 105, 0.6) !important;
        }

        /* Card header sfondo scuro */
        [data-theme="dark"] .card-header.bg-white {
            background-color: #334155 !important;
            border-bottom: 1px solid rgba(71, 85, 105, 0.6);
        }

        /* Table dark mode */
        /* Table: sfondo scuro per righe e intestazioni */
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

        /* Card header bg-white in dark mode */
        [data-theme="dark"] .card-header.bg-white {
            background-color: rgba(51, 65, 85, 0.9) !important;
            border-bottom: 1px solid rgba(71, 85, 105, 0.6);
        }

        /* Card body sfondo scuro */
        [data-theme="dark"] .card-body {
            background-color: transparent;
        }

        /* Text muted per leggibilità su sfondo scuro */
        [data-theme="dark"] .text-muted,
        [data-theme="dark"] .opacity-75 {
            color: #94a3b8 !important;
        }

        [data-theme="dark"] .text-gray {
            color: #cbd5e1 !important;
        }

        /* Buttons dark mode */
        [data-theme="dark"] .btn-outline-info {
            border-color: #38bdf8;
            color: #38bdf8;
        }
        [data-theme="dark"] .btn-outline-info:hover {
            background-color: rgba(56, 189, 248, 0.2);
            border-color: #38bdf8;
            color: #38bdf8;
        }

        [data-theme="dark"] .btn-outline-primary {
            border-color: #60a5fa;
            color: #60a5fa;
        }
        [data-theme="dark"] .btn-outline-primary:hover {
            background-color: rgba(96, 165, 250, 0.2);
            border-color: #60a5fa;
            color: #60a5fa;
        }

        [data-theme="dark"] .btn-outline-secondary {
            border-color: #94a3b8;
            color: #94a3b8;
        }
        [data-theme="dark"] .btn-outline-secondary:hover {
            background-color: rgba(148, 163, 184, 0.2);
            border-color: #94a3b8;
            color: #94a3b8;
        }

        [data-theme="dark"] .btn-outline-danger {
            border-color: #f87171;
            color: #f87171;
        }
        [data-theme="dark"] .btn-outline-danger:hover {
            background-color: rgba(248, 113, 113, 0.2);
            border-color: #f87171;
            color: #f87171;
        }

        [data-theme="dark"] .btn-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        [data-theme="dark"] .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        /* Modal dark mode */
        [data-theme="dark"] .modal-content {
            background-color: #1e293b;
            color: #e2e8f0;
            border: 1px solid rgba(71, 85, 105, 0.6);
        }

        [data-theme="dark"] .modal-header.bg-light {
            background-color: #334155 !important;
            border-bottom: 1px solid rgba(71, 85, 105, 0.6);
        }

        [data-theme="dark"] .modal-header .btn-close {
            filter: invert(1) brightness(0.8);
        }

        [data-theme="dark"] .card.border-secondary-subtle {
            border-color: rgba(71, 85, 105, 0.6) !important;
            background-color: #1e293b !important;
        }

        /* Form controls dark mode */
        [data-theme="dark"] .form-control {
            background-color: #0f172a;
            border-color: rgba(71, 85, 105, 0.6);
            color: #e2e8f0;
        }

        [data-theme="dark"] .form-control:focus {
            background-color: #1e293b;
            border-color: rgba(71, 85, 105, 0.8);
            color: #e2e8f0;
        }

        /* Text dark mode */
        [data-theme="dark"] .text-muted {
            color: #94a3b8 !important;
        }

        /* Badge dark mode */
        [data-theme="dark"] .bg-secondary,
        [data-theme="dark"] .badge.bg-secondary {
            background-color: #475569 !important;
        }

        /* Shadow override */
        [data-theme="dark"] .shadow-sm {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4) !important;
        }

        /* Alert dark mode */
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

        /* Button group dark mode */
        [data-theme="dark"] .btn-outline-light {
            border-color: rgba(148, 163, 184, 0.5);
            color: #e2e8f0;
        }
        [data-theme="dark"] .btn-outline-light:hover {
            background-color: rgba(148, 163, 184, 0.2);
            border-color: rgba(148, 163, 184, 0.7);
            color: #e2e8f0;
        }

        /* ============================================
           ADMIN DARK MODE - COMPLETE STYLING
           ============================================ */
        
        /* 1. BODY & BACKGROUND - sfondo scuro completo */
        [data-theme="dark"] {
            background-color: #0f172a;
        }
        [data-theme="dark"] body,
        [data-theme="dark"] .bg-light {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
        }

        /* 2. ADMIN HEADER - gradient scuro */
        [data-theme="dark"] .admin-header {
            background: linear-gradient(135deg, #1e293b 0%, #312e81 100%);
            border-bottom: 1px solid rgba(71, 85, 105, 0.4);
        }

        /* 3. CARDS - sfondo e bordi scuri */
        [data-theme="dark"] .card {
            background-color: #1e293b !important;
            border-color: rgba(71, 85, 105, 0.6) !important;
        }

        /* Stat cards in dark mode */
        [data-theme="dark"] .stat-card {
            background-color: #1e293b !important;
            border-width: 4px !important;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        [data-theme="dark"] .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3) !important;
        }

        /* Stat card border colors in dark mode */
        [data-theme="dark"] .stat-card.border-primary {
            border-color: #3b82f6 !important;
        }
        [data-theme="dark"] .stat-card.border-success {
            border-color: #22c55e !important;
        }
        [data-theme="dark"] .stat-card.border-warning {
            border-color: #eab308 !important;
        }
        [data-theme="dark"] .stat-card.border-info {
            border-color: #06b6d4 !important;
        }

        /* Card header - bg-white override */
        [data-theme="dark"] .card-header.bg-white,
        [data-theme="dark"] .card-header {
            background-color: #334155 !important;
            border-bottom: 1px solid rgba(71, 85, 105, 0.6) !important;
        }

        /* Card body */
        [data-theme="dark"] .card-body {
            color: #e2e8f0 !important;
        }

        /* ============================================
           TABLES - dark mode completo
           ============================================ */
        
        /* Table base */
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
            color: #cbd5e1;
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

        /* ============================================
           BADGES - dark mode
           ============================================ */
        
        [data-theme="dark"] .badge.bg-secondary {
            background-color: #475569 !important;
            color: #e2e8f0;
        }
        [data-theme="dark"] .bg-success,
        [data-theme="dark"] .badge.bg-success {
            background-color: rgba(34, 197, 94, 0.2) !important;
            color: #86efac;
        }
        [data-theme="dark"] .bg-warning,
        [data-theme="dark"] .badge.bg-warning {
            background-color: rgba(234, 179, 8, 0.2) !important;
            color: #fde047;
        }

        /* ============================================
           ALERTS - dark mode
           ============================================ */
        
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

        /* ============================================
           BUTTONS - dark mode completo
           ============================================ */
        
        [data-theme="dark"] .btn-outline-info {
            border-color: #38bdf8;
            color: #38bdf8;
        }
        [data-theme="dark"] .btn-outline-info:hover {
            background-color: rgba(56, 189, 248, 0.2);
            border-color: #38bdf8;
            color: #38bdf8;
        }

        [data-theme="dark"] .btn-outline-primary {
            border-color: #60a5fa;
            color: #60a5fa;
        }
        [data-theme="dark"] .btn-outline-primary:hover {
            background-color: rgba(96, 165, 250, 0.2);
            border-color: #60a5fa;
            color: #60a5fa;
        }

        [data-theme="dark"] .btn-outline-secondary {
            border-color: #94a3b8;
            color: #94a3b8;
        }
        [data-theme="dark"] .btn-outline-secondary:hover {
            background-color: rgba(148, 163, 184, 0.2);
            border-color: #94a3b8;
            color: #94a3b8;
        }

        [data-theme="dark"] .btn-outline-danger {
            border-color: #f87171;
            color: #f87171;
        }
        [data-theme="dark"] .btn-outline-danger:hover {
            background-color: rgba(248, 113, 113, 0.2);
            border-color: #f87171;
            color: #f87171;
        }

        [data-theme="dark"] .btn-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
            color: #fff !important;
        }
        [data-theme="dark"] .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
            color: #fff !important;
        }

        /* Admin action buttons */
        [data-theme="dark"] .btn-admin-action,
        [data-theme="dark"] a.btn-admin-action {
            background-color: rgba(147, 51, 234, 0.15);
            border-color: rgba(147, 51, 234, 0.5);
            color: #c084fc;
        }
        [data-theme="dark"] .btn-admin-action:hover {
            background-color: rgba(147, 51, 234, 0.25);
            border-color: rgba(163, 118, 239, 0.7);
            color: #d8b4fe;
        }

        /* Light outline buttons */
        [data-theme="dark"] .btn-outline-light {
            border-color: rgba(148, 163, 184, 0.5);
            color: #e2e8f0;
        }
        [data-theme="dark"] .btn-outline-light:hover {
            background-color: rgba(148, 163, 184, 0.2);
            border-color: rgba(148, 163, 184, 0.7);
            color: #e2e8f0;
        }

        /* ============================================
           MODAL - dark mode
           ============================================ */
        
        [data-theme="dark"] .modal-content {
            background-color: #1e293b;
            color: #e2e8f0;
            border: 1px solid rgba(71, 85, 105, 0.6);
        }

        [data-theme="dark"] .modal-header.bg-light {
            background-color: #334155 !important;
            border-bottom: 1px solid rgba(71, 85, 105, 0.6);
        }

        [data-theme="dark"] .modal-header .btn-close {
            filter: invert(1) brightness(0.8);
        }

        [data-theme="dark"] .card.border-secondary-subtle {
            border-color: rgba(71, 85, 105, 0.6) !important;
            background-color: #1e293b !important;
        }

        /* ============================================
           FORMS - dark mode
           ============================================ */
        
        [data-theme="dark"] .form-control {
            background-color: #0f172a;
            border-color: rgba(71, 85, 105, 0.6);
            color: #e2e8f0;
        }

        [data-theme="dark"] .form-control:focus {
            background-color: #1e293b;
            border-color: rgba(71, 85, 105, 0.8);
            color: #e2e8f0;
            box-shadow: 0 0 0 0.2rem rgba(71, 85, 105, 0.3);
        }

        [data-theme="dark"] .form-control::placeholder {
            color: #64748b;
        }

        /* ============================================
           TEXT & UTILITIES - dark mode
           ============================================ */
        
        [data-theme="dark"] .text-muted,
        [data-theme="dark"] .opacity-75 {
            color: #94a3b8 !important;
        }

        [data-theme="dark"] .text-gray {
            color: #cbd5e1 !important;
        }

        [data-theme="dark"] .shadow-sm,
        [data-theme="dark"] .shadow {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
        }

        /* ============================================
           DARK MODE TOGGLE BUTTON - admin style
           ============================================ */
        
        [data-theme="dark"] .btn-dark-mode-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        /* ============================================
           LINKS - dark mode
           ============================================ */
        
        [data-theme="dark"] .text-primary {
            color: #60a5fa !important;
        }
        [data-theme="dark"] .text-success {
            color: #4ade80 !important;
        }
        [data-theme="dark"] .text-warning {
            color: #facc15 !important;
        }
        [data-theme="dark"] .text-info {
            color: #22d3ee !important;
        }

        /* ============================================
           ADMIN HEADER - dark mode links/buttons
           ============================================ */
        
        [data-theme="dark"] .admin-header .btn-light {
            background-color: rgba(51, 65, 85, 0.9);
            border-color: rgba(71, 85, 105, 0.6);
            color: #e2e8f0;
        }
        [data-theme="dark"] .admin-header .btn-light:hover {
            background-color: rgba(71, 85, 105, 1);
            border-color: rgba(100, 116, 139, 0.8);
            color: #f1f5f9;
        }

        /* ============================================
           DROPDOWN - dark mode
           ============================================ */
        
        [data-theme="dark"] .dropdown-menu {
            background-color: #1e293b;
            border-color: rgba(71, 85, 105, 0.6);
            color: #e2e8f0;
        }

        [data-theme="dark"] .dropdown-item {
            color: #cbd5e1;
        }
        [data-theme="dark"] .dropdown-item:hover {
            background-color: rgba(71, 85, 105, 0.4);
            color: #f1f5f9;
        }

        /* ============================================
           EMPTY STATE - dark mode
           ============================================ */
        
        [data-theme="dark"] .text-center i {
            color: #475569 !important;
        }

    </style>
</head>
<body class="bg-light">
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="bi bi-speedometer2"></i> Pannello Amministrazione
                    </h1>
                    <p class="mb-0 opacity-75">Benvenuto, <?= htmlspecialchars($_SESSION['utente_username'] ?? $_SESSION['admin_username'] ?? 'Admin') ?></p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-light me-2" target="_blank">
                        <i class="bi bi-eye"></i> Visualizza Blog
                    </a>
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($successo): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($successo) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errore): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($errore) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiche -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Post Totali</h6>
                                <h2 class="mb-0"><?= $stats['totale'] ?></h2>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-file-text" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card border-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Pubblicati</h6>
                                <h2 class="mb-0"><?= $stats['pubblicati'] ?></h2>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card border-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Bozze</h6>
                                <h2 class="mb-0"><?= $stats['bozze'] ?></h2>
                            </div>
                            <div class="text-warning">
                                <i class="bi bi-pencil" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card border-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Categorie</h6>
                                <h2 class="mb-0"><?= $cat_stats['totale'] ?></h2>
                            </div>
                            <div class="text-info">
                                <i class="bi bi-folder" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Azioni rapide -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-lightning"></i> Azioni Rapide
                        </h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="nuovo_post.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Nuovo Post
                            </a>
                            <a href="gestione_categorie.php" class="btn btn-admin-action">
                                <i class="bi bi-folder-plus"></i> Gestisci Categorie
                            </a>
                            <a href="cambia_password.php" class="btn btn-outline-warning">
                                <i class="bi bi-shield-lock"></i> Cambia Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista Post -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul"></i> I Tuoi Post
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">Nessun post ancora. Inizia a scrivere!</p>
                        <a href="nuovo_post.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Crea il tuo primo post
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Titolo</th>
                                    <th>Categoria</th>
                                    <th>Data</th>
                                    <th>Stato</th>
                                    <th class="text-end">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                    <tr class="post-row">
                                        <td>
                                            <strong><?= htmlspecialchars($post['titolo']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($post['cat_nome']): ?>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($post['cat_nome']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Nessuna</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($post['data_creazione'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($post['pubblicato']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Pubblicato
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-pencil"></i> Bozza
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <!-- Pulsante Commenti (sinistra) -->
                                                <a href="?commenti=<?= $post['id'] ?>" 
                                                   class="btn btn-outline-info"
                                                   title="Commenti">
                                                    <i class="bi bi-chat-left-text"></i>
                                                </a>
                                                <!-- Pulsante Anteprima -->
                                                <a href="index.php?post=<?= $post['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Visualizza"
                                                   target="_blank">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="nuovo_post.php?edit=<?= $post['id'] ?>" 
                                                   class="btn btn-outline-secondary"
                                                   title="Modifica">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <!-- Elimina post via POST form -->
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questo post?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="delete_post" value="<?= (int)$post['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Elimina">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dark Mode Toggle - Bottom Right -->
    <button id="darkModeToggle" 
            class="btn btn-dark-mode-toggle" 
            style="position:fixed; bottom:20px; right:20px; z-index:9999;"
            title="Modalità Notte">
        🌙
    </button>

    <!-- Comments Modal -->
    <?php if ($post_id_commenti): ?>
    <div class="modal fade show" id="commentsModal" tabindex="-1" style="display:block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="bi bi-chat-left-text"></i> Commenti a: <?= htmlspecialchars($post_title_commenti) ?>
                    </h5>
                    <a href="admin.php" class="btn btn-sm btn-outline-secondary">&times; Chiudi</a>
                </div>
                <div class="modal-body">
                    <?php if (empty($commenti_post)): ?>
                        <p class="text-muted text-center py-4">Nessun commento per questo articolo.</p>
                    <?php else: ?>
                        <?php foreach ($commenti_post as $c): ?>
                            <div class="card mb-3 border-secondary-subtle">
                                <div class="card-body">
                                    <h6 class="fw-bold">👤 <?= htmlspecialchars($c['nome']) ?></h6>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($c['creato_il'])) ?></small>
                                    <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($c['contenuto'])) ?></p>
                                    
                                     <!-- Form modifica commento -->
                                     <form method="POST" class="mt-3" id="edit_comment_<?= (int)$c['id'] ?>">
                                         <?= csrf_field() ?>
                                         <input type="hidden" name="edit_comment" value="<?= (int)$c['id'] ?>">
                                         <div class="mb-2">
                                             <textarea name="contenuto" class="form-control form-control-sm"><?= htmlspecialchars($c['contenuto']) ?></textarea>
                                         </div>
                                         <input type="text" name="nome" class="form-control form-control-sm mb-2" value="<?= htmlspecialchars($c['nome']) ?>" placeholder="Nome">
                                         <button type="submit" class="btn btn-sm btn-primary">Salva</button>
                                     </form>
                                     <!-- Form elimina commento (separato) -->
                                     <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminare questo commento?');">
                                         <?= csrf_field() ?>
                                         <input type="hidden" name="delete_comment" value="<?= (int)$c['id'] ?>">
                                         <button type="submit" class="btn btn-sm btn-outline-danger">
                                             Elimina
                                         </button>
                                     </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded', function(){ new bootstrap.Modal(document.getElementById('commentsModal'), {}); });</script>
    <?php endif; ?>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
