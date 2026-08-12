<?php
require_once 'auth_check.php';
require_once 'db.php';
require_once 'includes/csrf.php';

$successo = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione CSRF
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        $password_attuale = $_POST['password_attuale'] ?? '';
        $password_nuova = $_POST['password_nuova'] ?? '';
        $password_conferma = $_POST['password_conferma'] ?? '';
    
    // Validazione
        if (empty($password_attuale) || empty($password_nuova) || empty($password_conferma)) {
            $errore = 'Tutti i campi sono obbligatori';
        } elseif ($password_nuova !== $password_conferma) {
            $errore = 'Le nuove password non corrispondono';
        } elseif (strlen($password_nuova) < 6) {
            $errore = 'La nuova password deve essere di almeno 6 caratteri';
        } else {
            // Verifica password attuale (usa utente_id come in login.php)
            $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = ?");
            $stmt->execute([$_SESSION['utente_id']]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password_attuale, $admin['password'])) {
                // Password attuale corretta, procedi con l'aggiornamento
                $hash_nuova = password_hash($password_nuova, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
                    $stmt->execute([$hash_nuova, $_SESSION['utente_id']]);
                    $successo = 'Password modificata con successo!';
                    
                    // Registra il cambio password nel log (opzionale)
                    error_log("Password cambiata per utente ID: " . $_SESSION['utente_id'] . " - " . date('Y-m-d H:i:s'));
                } catch (PDOException $e) {
                    $errore = 'Errore durante l\'aggiornamento della password';
                    error_log("Errore cambio password: " . $e->getMessage());
                }
            } else {
                $errore = 'La password attuale non è corretta';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambia Password - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        
        /* Dark mode */
        body.dark-mode {
            background-color: #1a1a2e;
            color: #e0e0e0;
        }
        body.dark-mode .admin-header {
            background: linear-gradient(135deg, #4a5a8a 0%, #3d2f6b 100%);
        }
        body.dark-mode .card {
            background-color: #16213e;
            border-color: #0f3460;
            color: #e0e0e0;
        }
        body.dark-mode .card-header {
            background-color: #0f3460;
            border-color: #0a2851;
        }
        body.dark-mode .bg-light {
            background-color: #1a1a2e !important;
        }
        body.dark-mode .bg-light.border-0 {
            background-color: #16213e !important;
        }
        body.dark-mode .form-control {
            background-color: #16213e;
            border-color: #0f3460;
            color: #e0e0e0;
        }
        body.dark-mode .form-control:focus {
            background-color: #16213e;
            border-color: #533483;
            color: #e0e0e0;
            box-shadow: 0 0 0 0.2rem rgba(83, 52, 131, 0.25);
        }
        body.dark-mode .form-label {
            color: #c0c0c0;
        }
        body.dark-mode .btn-primary {
            background-color: #533483;
            border-color: #533483;
        }
        body.dark-mode .btn-primary:hover {
            background-color: #6a42a0;
            border-color: #6a42a0;
        }
        body.dark-mode .alert-success {
            background-color: #1e4a2e;
            border-color: #2d6a3e;
            color: #a8d5b8;
        }
        body.dark-mode .alert-danger {
            background-color: #4a1e1e;
            border-color: #6a2d2d;
            color: #d5a8a8;
        }
        body.dark-mode .text-muted,
        body.dark-mode .small.text-muted {
            color: #9ca3af !important;
        }
        body.dark-mode .btn-outline-secondary {
            color: #9ca3af;
            border-color: #4b5563;
        }
        body.dark-mode .btn-outline-secondary:hover {
            background-color: #374151;
            color: #e0e0e0;
            border-color: #4b5563;
        }
        body.dark-mode .btn-light {
            background-color: #374151;
            border-color: #4b5563;
            color: #e0e0e0;
        }
        body.dark-mode .invalid-feedback {
            color: #f87171;
        }

        /* Dark mode toggle button - bottom right */
        .btn-dark-mode-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-dark-mode-toggle:hover {
            background-color: #e9ecef;
            color: #212529;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* Dark mode per il toggle button */
        [data-theme="dark"] .btn-dark-mode-toggle {
            background-color: #374151;
            color: #e2e8f0;
            border-color: #4b5563;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        [data-theme="dark"] .btn-dark-mode-toggle:hover {
            background-color: #4b5563;
            color: #f8f9fa;
        }

        /* Admin header dark mode */
        [data-theme="dark"] .admin-header {
            background: linear-gradient(135deg, #1e293b 0%, #312e81 100%);
            border-bottom: 1px solid rgba(71, 85, 105, 0.4);
        }

        /* Form labels dark mode */
        [data-theme="dark"] .form-label {
            color: #e2e8f0;
        }

        /* Card header dark mode */
        [data-theme="dark"] .card-header {
            background-color: rgba(79, 70, 229, 0.1) !important;
            border-color: rgba(71, 85, 105, 0.6) !important;
        }

        /* Body & background dark mode */
        [data-theme="dark"] body,
        [data-theme="dark"] .bg-light {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
        }
    </style>

    <script>
        // Dark mode toggle - bottom right button (shared with all pages)
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('darkModeToggle');
            if (!toggle) return;

            function applyTheme(isDark) {
                document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
                localStorage.setItem('darkMode', isDark ? 'true' : 'false');
                toggle.textContent = isDark ? '☀️' : '🌙';
            }

            const isDark = localStorage.getItem('darkMode') === 'true';
            applyTheme(isDark);

            toggle.addEventListener('click', function() {
                const current = document.documentElement.getAttribute('data-theme') === 'dark';
                applyTheme(!current);
            });
        });
    </script>
    
</head>
<body class="bg-light">
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="bi bi-key"></i> Cambia Password
                </h1>
                <div class="d-flex align-items-center gap-2">
                    <a href="admin.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Torna al Pannello
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dark Mode Toggle - Bottom Right -->
    <button id="darkModeToggle" 
            class="btn btn-dark-mode-toggle" 
            title="Modalità Notte">
        🌙
    </button>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
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

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-lock"></i> Modifica Password di Accesso
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-4">
                            <i class="bi bi-info-circle"></i> 
                            Utilizza una password sicura di almeno 6 caratteri. Ti consigliamo di usare lettere maiuscole, minuscole, numeri e simboli.
                        </p>

                        <form method="POST" action="" id="passwordForm">
                            <?= csrf_field() ?>
                            <div class="mb-4">
                                <label for="password_attuale" class="form-label fw-bold">
                                    <i class="bi bi-lock"></i> Password Attuale *
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control form-control-lg" 
                                    id="password_attuale" 
                                    name="password_attuale" 
                                    required
                                    autocomplete="current-password"
                                >
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <label for="password_nuova" class="form-label fw-bold">
                                    <i class="bi bi-key"></i> Nuova Password *
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control form-control-lg" 
                                    id="password_nuova" 
                                    name="password_nuova" 
                                    required
                                    minlength="6"
                                    autocomplete="new-password"
                                >
                                <div class="password-strength" id="strengthBar"></div>
                                <small class="text-muted" id="strengthText">Minimo 6 caratteri</small>
                            </div>

                            <div class="mb-4">
                                <label for="password_conferma" class="form-label fw-bold">
                                    <i class="bi bi-key-fill"></i> Conferma Nuova Password *
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control form-control-lg" 
                                    id="password_conferma" 
                                    name="password_conferma" 
                                    required
                                    minlength="6"
                                    autocomplete="new-password"
                                >
                                <div class="invalid-feedback" id="matchError">
                                    Le password non corrispondono
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="bi bi-check-circle"></i> Cambia Password
                                </button>
                                <a href="admin.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Annulla
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Suggerimenti sicurezza -->
                <div class="card mt-4 bg-light border-0">
                    <div class="card-body">
                        <h6 class="fw-bold">
                            <i class="bi bi-lightbulb"></i> Suggerimenti per una Password Sicura
                        </h6>
                        <ul class="small mb-0">
                            <li>Usa almeno 8-12 caratteri</li>
                            <li>Combina lettere maiuscole e minuscole</li>
                            <li>Includi numeri e simboli speciali (!@#$%^&*)</li>
                            <li>Evita parole comuni o dati personali</li>
                            <li>Non riutilizzare password di altri account</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Verifica forza password
        const passwordInput = document.getElementById('password_nuova');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const confermaInput = document.getElementById('password_conferma');
        const submitBtn = document.getElementById('submitBtn');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength';
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Password debole';
                strengthText.className = 'text-danger small';
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Password media';
                strengthText.className = 'text-warning small';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Password forte';
                strengthText.className = 'text-success small';
            }
        });

        // Verifica corrispondenza password
        confermaInput.addEventListener('input', function() {
            if (passwordInput.value !== this.value) {
                this.classList.add('is-invalid');
                submitBtn.disabled = true;
            } else {
                this.classList.remove('is-invalid');
                submitBtn.disabled = false;
            }
        });

        // Previeni submit se password non corrispondono
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            if (passwordInput.value !== confermaInput.value) {
                e.preventDefault();
                confermaInput.classList.add('is-invalid');
                submitBtn.disabled = true;
            }
        });
    </script>
</body>
</html>
