<?php
/**
 * Pagina di Login Admin
 * Con session fixation protection, rate limiting e CSRF
 */

require_once __DIR__ . '/includes/session.php';
start_secure_session();

require_once 'config.php';
require_once 'db.php';
require_once 'includes/csrf.php';

// Se già autenticato, reindirizza alla admin
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header("Location: admin.php");
    exit;
}

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione CSRF
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errore = "Token di sicurezza non valido. Riprova.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $errore = "Inserisci username e password.";
        } else {
            // Rate limiting: massimo 5 tentativi ogni 15 minuti
            $now = time();
            $lockout_period = 900; // 15 minuti
            $max_attempts = 5;

            if (!isset($_SESSION['login_attempts'])) {
                $_SESSION['login_attempts'] = 0;
                $_SESSION['login_last_attempt'] = 0;
            }

            // Reset se passato il periodo di lockout
            if ($now - $_SESSION['login_last_attempt'] > $lockout_period) {
                $_SESSION['login_attempts'] = 0;
            }

            if ($_SESSION['login_attempts'] >= $max_attempts) {
                $remaining = ceil(($lockout_period - ($now - $_SESSION['login_last_attempt'])) / 60);
                $errore = "Troppi tentativi falliti. Riprova tra {$remaining} minuti.";
            } else {
                // Recupera l'utente dalla tabella admin
                $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Login riuscito - reset tentativi
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['login_last_attempt'] = 0;
                    
                    // Rigenera session ID per prevenire fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['admin_logged'] = true;
                    $_SESSION['utente_id'] = (int)$user['id'];
                    $_SESSION['utente_username'] = $user['username'];
                    $_SESSION['login_time'] = $now;
                    $_SESSION['session_created'] = $now;
                    
                    header("Location: admin.php");
                    exit;
                } else {
                    $_SESSION['login_attempts']++;
                    $_SESSION['login_last_attempt'] = $now;
                    $errore = "Credenziali non valide.";
                }
            }
        }
    }
}

// Genera token per il form
generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= csrf_meta() ?>
    <title>Login - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-card {
            background: var(--bg-content, white);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }

        /* Dark mode styles for login page */
        [data-theme="dark"] body.login-page {
            background-color: #1a1a2e;
        }
        [data-theme="dark"] .login-card {
            background-color: #16213e;
            border-color: #0f3460;
        }
        [data-theme="dark"] .alert-danger {
            background-color: #4a1e1e;
            border-color: #6a2d2d;
            color: #d5a8a8;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
        <div class="login-header">
            <h2 class="mb-0" style="color: var(--text-primary);">🔐 Admin Login</h2>
            <p class="mt-1 mb-0" style="color: var(--text-secondary);"><?= SITE_NAME ?></p>
        </div>
        
        <?php if ($errore): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errore) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus autocomplete="username" style="background: var(--bg-input, white); color: var(--text-primary); border-color: rgba(102, 126, 234, 0.2);">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" style="background: var(--bg-input, white); color: var(--text-primary); border-color: rgba(102, 126, 234, 0.2);">
            </div>
            
            <button type="submit" class="btn btn-primary btn-login w-100">Accedi</button>
        </form>
        
        <hr class="my-4" style="border-color: var(--border-color, rgba(102, 126, 234, 0.2));">
        
        <div class="text-center">
            <small style="color: var(--text-secondary);">
                <a href="index.php" class="text-decoration-none" style="color: var(--primary-color);">← Torna al blog</a>
            </small>
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
