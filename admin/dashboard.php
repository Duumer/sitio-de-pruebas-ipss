<?php
/**
 * Dashboard Admin/Docente - CTF IPSS
 */

require_once __DIR__ . '/../includes/config.php';
requireTeacher();

$user = getCurrentUser();
$isAdmin = isAdmin();

// Estadísticas generales
$totalUsers = dbFetchOne("SELECT COUNT(*) as c FROM usuarios WHERE rol = 'estudiante' AND activo = 1")['c'] ?? 0;
$totalChallenges = dbFetchOne("SELECT COUNT(*) as c FROM desafios WHERE activo = 1")['c'] ?? 0;
$totalSubmissions = dbFetchOne("SELECT COUNT(*) as c FROM soluciones_usuarios")['c'] ?? 0;
$correctSubmissions = dbFetchOne("SELECT COUNT(*) as c FROM soluciones_usuarios WHERE es_correcta = 1")['c'] ?? 0;

// Usuarios recientes
$recentUsers = dbFetchAll(
    "SELECT id, nombre, email, created_at, 
            (SELECT COUNT(*) FROM soluciones_usuarios WHERE usuario_id = u.id AND es_correcta = 1) as solved
     FROM usuarios u 
     WHERE rol = 'estudiante' AND activo = 1
     ORDER BY created_at DESC LIMIT 10"
);

// Intentos recientes
$recentAttempts = dbFetchAll(
    "SELECT su.*, u.nombre, u.email, d.titulo as desafio_titulo
     FROM soluciones_usuarios su
     JOIN usuarios u ON su.usuario_id = u.id
     JOIN desafios d ON su.desafio_id = d.id
     ORDER BY su.fecha_intento DESC LIMIT 20"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente - CTF IPSS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="admin-page">
    <header class="topbar">
        <div class="topbar-left">
            <a href="/admin/dashboard.php" class="logo-link">
                <svg width="32" height="32" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="48" height="48" rx="12" fill="var(--primary)"/>
                    <path d="M14 24L21 31L34 17" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="logo-text">CTF IPSS</span>
                <span class="badge-admin">Admin</span>
            </a>
            <nav class="topbar-nav" aria-label="Navegación admin">
                <a href="/admin/dashboard.php" class="active">Dashboard</a>
                <a href="/dashboard.php">Vista Estudiante</a>
            </nav>
        </div>
        
        <div class="topbar-right">
            <button id="theme-toggle" class="icon-btn" aria-label="Cambiar tema" title="Cambiar tema">
                <svg class="icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/>
                    <line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
                <svg class="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>
            
            <div class="user-menu">
                <button class="user-menu-trigger" aria-expanded="false" aria-haspopup="true">
                    <div class="user-avatar"><?= e(strtoupper($user['nombre'][0])) ?></div>
                    <span class="user-name"><?= e($user['nombre']) ?></span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>
                <div class="user-dropdown" role="menu">
                    <div class="user-dropdown-header">
                        <span><?= e($user['nombre']) ?></span>
                        <small><?= e($user['email']) ?></small>
                        <span class="role-badge role-<?= e($user['rol']) ?>"><?= e(ucfirst($user['rol'])) ?></span>
                    </div>
                    <a href="/dashboard.php" role="menuitem">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M9 9l6 6M15 9l-6 6"/>
                        </svg>
                        Ver Vista Estudiante
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="/logout.php" role="menuitem" class="logout-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content admin-content">
        <!-- Stats Cards -->
        <section class="stats-grid" aria-label="Estadísticas generales">
            <article class="stat-card">
                <div class="stat-icon stat-users">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value" id="stat-users"><?= number_format($totalUsers) ?></div>
                    <div class="stat-label">Estudiantes Activos</div>
                </div>
            </article>
            
            <article class="stat-card">
                <div class="stat-icon stat-challenges">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value" id="stat-challenges"><?= $totalChallenges ?></div>
                    <div class="stat-label">Desafíos Activos</div>
                </div>
            </article>
            
            <article class="stat-card">
                <div class="stat-icon stat-submissions">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value" id="stat-submissions"><?= number_format($totalSubmissions) ?></div>
                    <div class="stat-label">Total Intentos</div>
                </div>
            </article>
            
            <article class="stat-card">
                <div class="stat-icon stat-success">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value" id="stat-success"><?= number_format($correctSubmissions) ?></div>
                    <div class="stat-label">Resoluciones Correctas</div>
                </div>
            </article>
        </section>

        <!-- Tabs para diferentes vistas -->
        <div class="admin-tabs" role="tablist">
            <button class="admin-tab-btn active" role="tab" data-tab="recent-users" aria-selected="true">Estudiantes Recientes</button>
            <button class="admin-tab-btn" role="tab" data-tab="recent-attempts" aria-selected="false">Intentos Recientes</button>
            <button class="admin-tab-btn" role="tab" data-tab="challenge-stats" aria-selected="false">Estadísticas por Desafío</button>
        </div>

        <!-- Tab: Estudiantes Recientes -->
        <div class="admin-tab-panel active" id="tab-recent-users" role="tabpanel">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Email</th>
                            <th>Registro</th>
                            <th>Desafíos Resueltos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?= e($u['nombre']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                            <td><span class="badge badge-info"><?= $u['solved'] ?>/<?= $totalChallenges ?></span></td>
                            <td>
                                <button class="icon-btn view-student" data-user-id="<?= $u['id'] ?>" title="Ver detalle">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Intentos Recientes -->
        <div class="admin-tab-panel" id="tab-recent-attempts" role="tabpanel" hidden>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Estudiante</th>
                            <th>Desafío</th>
                            <th>Flag Enviada</th>
                            <th>Resultado</th>
                            <th>Puntos</th>
                            <th>Evidencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAttempts as $a): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i:s', strtotime($a['fecha_intento'])) ?></td>
                            <td><?= e($a['nombre']) ?> <small>(<?= e($a['email']) ?>)</small></td>
                            <td><?= e($a['desafio_titulo']) ?></td>
                            <td><code class="flag-display"><?= e(substr($a['flag_enviada_hash'], 0, 16)) ?>...</code></td>
                            <td>
                                <span class="badge badge-<?= $a['es_correcta'] ? 'success' : 'error' ?>">
                                    <?= $a['es_correcta'] ? 'Correcto' : 'Incorrecto' ?>
                                </span>
                            </td>
                            <td><?= $a['puntos_obtenidos'] > 0 ? '+' . $a['puntos_obtenidos'] : '-' ?></td>
                            <td>
                                <?php if ($a['evidencia_texto']): ?>
                                <button class="icon-btn view-evidence" data-evidence="<?= e(htmlspecialchars($a['evidencia_texto'])) ?>" title="Ver evidencia">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                </button>
                                <?php else: ?>
                                <span class="text-muted">Sin evidencia</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Estadísticas por Desafío -->
        <div class="admin-tab-panel" id="tab-challenge-stats" role="tabpanel" hidden>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Desafío</th>
                            <th>Categoría</th>
                            <th>Dificultad</th>
                            <th>Puntos</th>
                            <th>Intentos</th>
                            <th>Resueltos</th>
                            <th>Tasa Éxito</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $challengeStats = dbFetchAll(
                            "SELECT d.*, c.nombre as categoria_nombre,
                                    COUNT(su.id) as total_intentos,
                                    SUM(CASE WHEN su.es_correcta = 1 THEN 1 ELSE 0 END) as resueltos
                             FROM desafios d
                             JOIN categorias c ON d.categoria_id = c.id
                             LEFT JOIN soluciones_usuarios su ON d.id = su.desafio_id
                             WHERE d.activo = 1
                             GROUP BY d.id
                             ORDER BY c.orden, d.orden"
                        );
                        foreach ($challengeStats as $cs):
                            $rate = $cs['total_intentos'] > 0 ? round(($cs['resueltos'] / $cs['total_intentos']) * 100) : 0;
                        ?>
                        <tr>
                            <td><?= e($cs['titulo']) ?></td>
                            <td><span class="category-badge" style="background: <?= e($cs['categoria_color']) ?>20; color: <?= e($cs['categoria_color']) ?>"><?= e($cs['categoria_nombre']) ?></span></td>
                            <td><span class="badge badge-<?= $cs['dificultad'] === 'baja' ? 'success' : ($cs['dificultad'] === 'media' ? 'warning' : 'error') ?>"><?= ucfirst($cs['dificultad']) ?></span></td>
                            <td><?= $cs['puntos'] ?></td>
                            <td><?= $cs['total_intentos'] ?></td>
                            <td><?= $cs['resueltos'] ?></td>
                            <td>
                                <div class="success-rate">
                                    <div class="success-rate-bar" style="width: <?= $rate ?>%"></div>
                                    <span><?= $rate ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Ver Estudiante -->
    <div id="student-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="student-modal-title" hidden>
        <div class="modal-overlay"></div>
        <div class="modal-container modal-large">
            <div class="modal-header">
                <h3 id="student-modal-title">Detalle del Estudiante</h3>
                <button class="modal-close" aria-label="Cerrar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="student-modal-body">
                <!-- Cargado dinámicamente -->
            </div>
        </div>
    </div>

    <!-- Modal Ver Evidencia -->
    <div id="evidence-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="evidence-modal-title" hidden>
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="evidence-modal-title">Evidencia / Mitigación</h3>
                <button class="modal-close" aria-label="Cerrar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <pre id="evidence-content" class="evidence-text"></pre>
            </div>
        </div>
    </div>

    <div id="toast-container" class="toast-container" aria-live="polite"></div>

    <script>
        window.CTF_ADMIN = {
            userId: <?= $user['id'] ?>,
            isAdmin: <?= $isAdmin ? 'true' : 'false' ?>,
            apiBase: '/api'
        };
    </script>
    <script src="/assets/js/admin.js"></script>
</body>
</html>