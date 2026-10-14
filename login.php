<?php
/**
 * Página de Login - CTF IPSS
 */

require_once __DIR__ . '/includes/config.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
$email = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Intenta de nuevo.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($email === '' || $password === '') {
            $error = 'Email y contraseña son requeridos';
        } else {
            $user = dbFetchOne(
                "SELECT id, nombre, email, password_hash, rol, activo FROM usuarios WHERE email = ?",
                [$email]
            );
            
            if ($user && $user['activo'] && verifyPassword($password, $user['password_hash'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_rol'] = $user['rol'];
                
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);
                
                // Redirigir según rol
                if ($user['rol'] === 'admin' || $user['rol'] === 'docente') {
                    header('Location: /admin/dashboard.php');
                } else {
                    header('Location: /dashboard.php');
                }
                exit;
            } else {
                $error = 'Credenciales inválidas';
            }
        }
    }
}

$csrfToken = generateCsrfToken();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CTF IPSS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="48" height="48" rx="12" fill="var(--primary)"/>
                        <path d="M14 24L21 31L34 17" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h1>CTF IPSS</h1>
                <p class="subtitle">Hacking Game - Plataforma Educativa</p>
                <p class="institution">Instituto Profesional San Sebastián</p>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="usuario@ipss.cl" required autocomplete="email" autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="toggle-password" aria-label="Mostrar/ocultar contraseña">
                            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <span>Iniciar Sesión</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                </button>
            </form>
            
            <div class="login-footer">
                <p>¿Primera vez? Contacta a tu docente para obtener credenciales.</p>
                <div class="demo-credentials">
                    <details>
                        <summary>Credenciales de prueba (solo desarrollo)</summary>
                        <ul>
                            <li><strong>Admin:</strong> admin@ipss.cl / admin123</li>
                            <li><strong>Docente:</strong> docente@ipss.cl / admin123</li>
                            <li><strong>Estudiante:</strong> estudiante@ipss.cl / admin123</li>
                        </ul>
                    </details>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/login.js"></script>
</body>
</html>