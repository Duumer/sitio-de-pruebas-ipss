<?php
/**
 * Punto de entrada - CTF IPSS
 * Redirige según estado de autenticación y rol.
 */
require_once __DIR__ . '/includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
if ($user && in_array($user['rol'], ['docente', 'admin'], true)) {
    header('Location: admin/dashboard.php');
} else {
    header('Location: dashboard.php');
}
exit;
