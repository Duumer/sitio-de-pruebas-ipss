<?php
/**
 * Logout - CTF IPSS
 */

require_once __DIR__ . '/includes/config.php';

// Destruir sesión
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirigir a login con mensaje
redirectWithFlash('/login.php', 'Sesión cerrada correctamente', 'info');