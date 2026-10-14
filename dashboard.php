<?php
/**
 * Dashboard Principal - CTF IPSS (Vista Estudiante)
 */

require_once __DIR__ . '/includes/config.php';
requireLogin();

// Solo estudiantes acceden a este dashboard
if (isTeacher()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$user = getCurrentUser();
$stats = getDashboardStats($user['id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CTF IPSS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="dashboard-page">
    <!-- Header / Navbar -->
    <header class="topbar">
        <div class="topbar-left">
            <a href="/dashboard.php" class="logo-link">
                <svg width="32" height="32" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="48" height="48" rx="12" fill="var(--primary)"/>
                    <path d="M14 24L21 31L34 17" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="logo-text">CTF IPSS</span>
            </a>
            <nav class="topbar-nav" aria-label="Navegación principal">
                <a href="/dashboard.php" class="active" data-tooltip="Dashboard">Dashboard</a>
                <a href="#challenges" data-tooltip="Desafíos">Desafíos</a>
                <a href="#scoreboard" data-tooltip="Ranking">Ranking</a>
            </nav>
        </div>
        
        <div class="topbar-right">
            <!-- Theme Toggle -->
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
            
            <!-- User Menu -->
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

    <main class="main-content">
        <!-- Player Status Bar -->
        <section class="player-status" aria-label="Estado del participante">
            <div class="status-grid">
                <div class="status-item status-info">
                    <div class="status-label">Participante</div>
                    <div class="status-value"><?= e($user['nombre']) ?></div>
                    <div class="status-role role-<?= e($user['rol']) ?>"><?= e(ucfirst($user['rol'])) ?></div>
                </div>
                
                <div class="status-item status-score">
                    <div class="status-label">Puntaje Total</div>
                    <div class="status-value score-value" id="total-score"><?= number_format($stats['score']) ?></div>
                    <div class="status-sub">Posición #<span id="current-rank"><?= $stats['rank'] ?></span></div>
                </div>
                
                <div class="status-item status-progress">
                    <div class="status-label">Progreso General</div>
                    <div class="progress-container">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progress-bar" style="width: <?= $stats['progress_percent'] ?>%"></div>
                        </div>
                        <div class="progress-text">
                            <span id="completed-count"><?= $stats['completed'] ?></span> / <span id="total-challenges"><?= $stats['total_challenges'] ?></span> desafíos
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Challenges Matrix -->
        <section class="challenges-section" id="challenges" aria-label="Matriz de desafíos">
            <div class="section-header">
                <h2>Desafíos CTF</h2>
                <p class="section-subtitle">8 desafíos organizados en 4 categorías técnicas</p>
            </div>
            
            <div class="categories-tabs" role="tablist" aria-label="Categorías de desafíos">
                <button class="tab-btn active" role="tab" data-category="all" aria-selected="true">Todos</button>
                <button class="tab-btn" role="tab" data-category="analisis-web" aria-selected="false">Análisis Web</button>
                <button class="tab-btn" role="tab" data-category="analisis-vulnerabilidades" aria-selected="false">Vulnerabilidades</button>
                <button class="tab-btn" role="tab" data-category="criptografia" aria-selected="false">Criptografía</button>
                <button class="tab-btn" role="tab" data-category="analisis-forense" aria-selected="false">Forense</button>
            </div>
            
            <div class="challenges-grid" id="challenges-grid" role="tabpanel">
                <!-- Los desafíos se renderizan vía JavaScript -->
            </div>
        </section>

        <!-- Scoreboard / Ranking -->
        <section class="scoreboard-section" id="scoreboard" aria-label="Clasificación">
            <div class="section-header">
                <h2>Ranking / Scoreboard</h2>
                <p class="section-subtitle">Top 10 participantes - Actualización en tiempo real</p>
            </div>
            
            <div class="scoreboard-container">
                <table class="scoreboard-table" id="scoreboard-table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Participante</th>
                            <th scope="col">Puntaje</th>
                            <th scope="col">Completados</th>
                            <th scope="col">Última Resolución</th>
                        </tr>
                    </thead>
                    <tbody id="scoreboard-body">
                        <!-- Se llena vía JavaScript -->
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Challenge Detail Modal -->
    <div id="challenge-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden>
        <div class="modal-overlay" tabindex="-1"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modal-title">Detalle del Desafío</h3>
                <button class="modal-close" aria-label="Cerrar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container" aria-live="polite"></div>

    <!-- Data attributes for JS -->
    <script>
        window.CTF_CONFIG = {
            userId: <?= $user['id'] ?>,
            userRole: '<?= e($user['rol']) ?>',
            apiBase: '/api'
        };
    </script>
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>