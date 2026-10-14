<?php
/**
 * Configuración de Base de Datos - CTF IPSS
 * Archivo: includes/config.php
 * 
 * Copia este archivo y ajusta las credenciales para tu hosting compartido
 */

// (Bootstrap de sesión más abajo, después de los ini_set)

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================
// En hosting compartido cPanel, típicamente:
// Host: localhost
// Usuario: cpaneluser_dbuser
// Password: tu_password_db
// BD: cpaneluser_ctf_ipss

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'ctf_ipss');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// CONFIGURACIÓN DE LA APLICACIÓN
// ============================================
define('APP_NAME', 'CTF IPSS - Hacking Game');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/ctf-platform');
define('SESSION_NAME', 'ctf_ipss_session');
define('CSRF_TOKEN_NAME', 'ctf_csrf_token');

// Configuración de seguridad
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('FLAG_HASH_ALGO', 'sha256');
define('MAX_FLAG_ATTEMPTS_PER_HOUR', 10);
define('HINT_PENALTY_ENABLED', true);

// Zona horaria
date_default_timezone_set('America/Santiago');

// ============================================
// CONFIGURACIÓN DE SESIÓN SEGURA
// ============================================
// NOTA: los ajustes ini deben aplicarse ANTES de session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Solo true en HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 7200); // 2 horas

    // Nombre de sesión personalizado
    session_name(SESSION_NAME);
    session_start();
}

// ============================================
// FUNCIONES DE BASE DE DATOS
// ============================================

/**
 * Obtener conexión PDO a la base de datos
 * @return PDO
 * @throws Exception
 */
function getDbConnection(): PDO {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci"
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // En producción, loggear el error y mostrar mensaje genérico
        error_log("DB Connection Error: " . $e->getMessage());
        throw new Exception("Error de conexión a la base de datos");
    }
}

/**
 * Ejecutar query SELECT y retornar todos los resultados
 * @param string $sql
 * @param array $params
 * @return array
 */
function dbFetchAll(string $sql, array $params = []): array {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Ejecutar query SELECT y retornar un resultado
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function dbFetchOne(string $sql, array $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Ejecutar query INSERT/UPDATE/DELETE
 * @param string $sql
 * @param array $params
 * @return int|false (row count o lastInsertId para INSERT)
 */
function dbExecute(string $sql, array $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if (stripos(trim($sql), 'INSERT') === 0) {
        return $pdo->lastInsertId();
    }
    
    return $stmt->rowCount();
}

/**
 * Iniciar transacción
 */
function dbBeginTransaction(): void {
    getDbConnection()->beginTransaction();
}

/**
 * Confirmar transacción
 */
function dbCommit(): void {
    getDbConnection()->commit();
}

/**
 * Revertir transacción
 */
function dbRollback(): void {
    getDbConnection()->rollBack();
}

// ============================================
// FUNCIONES DE SEGURIDAD
// ============================================

/**
 * Generar token CSRF
 * @return string
 */
function generateCsrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verificar token CSRF
 * @param string $token
 * @return bool
 */
function verifyCsrfToken(string $token): bool {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Sanitizar salida HTML
 * @param string $string
 * @return string
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Hash de bandera para almacenamiento (SHA256)
 * @param string $flag
 * @return string
 */
function hashFlag(string $flag): string {
    return hash(FLAG_HASH_ALGO, $flag);
}

/**
 * Verificar bandera usando hash_equals (timing-safe)
 * @param string $storedHash
 * @param string $submittedFlag
 * @return bool
 */
function verifyFlag(string $storedHash, string $submittedFlag): bool {
    $submittedHash = hash(FLAG_HASH_ALGO, $submittedFlag);
    return hash_equals($storedHash, $submittedHash);
}

/**
 * Generar hash de contraseña
 * @param string $password
 * @return string
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_HASH_ALGO);
}

/**
 * Verificar contraseña
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Redirigir con mensaje flash
 * @param string $url
 * @param string $message
 * @param string $type (success, error, warning, info)
 */
function redirectWithFlash(string $url, string $message, string $type = 'info'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header("Location: $url");
    exit;
}

/**
 * Obtener y limpiar mensaje flash
 * @return array|null
 */
function getFlashMessage(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================
// FUNCIONES DE AUTENTICACIÓN
// ============================================

/**
 * Verificar si usuario está logueado
 * @return bool
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Obtener usuario actual
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    
    $user = dbFetchOne(
        "SELECT id, nombre, email, rol FROM usuarios WHERE id = ? AND activo = 1",
        [$_SESSION['user_id']]
    );
    
    return $user;
}

/**
 * Verificar si usuario es docente/admin
 * @return bool
 */
function isTeacher(): bool {
    $user = getCurrentUser();
    return $user && in_array($user['rol'], ['docente', 'admin']);
}

/**
 * Verificar si usuario es admin
 * @return bool
 */
function isAdmin(): bool {
    $user = getCurrentUser();
    return $user && $user['rol'] === 'admin';
}

/**
 * Requerir login (redirige si no logueado)
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirectWithFlash('/login.php', 'Debes iniciar sesión para acceder', 'warning');
    }
}

/**
 * Requerir rol docente/admin
 */
function requireTeacher(): void {
    requireLogin();
    if (!isTeacher()) {
        redirectWithFlash('/dashboard.php', 'Acceso denegado: se requiere rol docente', 'error');
    }
}

/**
 * Requerir rol admin
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        redirectWithFlash('/dashboard.php', 'Acceso denegado: se requiere rol administrador', 'error');
    }
}

// ============================================
// FUNCIONES DE CTF
// ============================================

/**
 * Obtener todos los desafíos activos con categorías
 * @return array
 */
function getAllChallenges(): array {
    return dbFetchAll(
        "SELECT d.*, c.nombre as categoria_nombre, c.slug as categoria_slug, c.color as categoria_color, c.icono as categoria_icono
         FROM desafios d
         JOIN categorias c ON d.categoria_id = c.id
         WHERE d.activo = 1
         ORDER BY c.orden, d.orden"
    );
}

/**
 * Obtener desafío por ID
 * @param int $id
 * @return array|null
 */
function getChallengeById(int $id): ?array {
    return dbFetchOne(
        "SELECT d.*, c.nombre as categoria_nombre, c.slug as categoria_slug, c.color as categoria_color, c.icono as categoria_icono
         FROM desafios d
         JOIN categorias c ON d.categoria_id = c.id
         WHERE d.id = ? AND d.activo = 1",
        [$id]
    );
}

/**
 * Obtener pistas de un desafío
 * @param int $challengeId
 * @return array
 */
function getHintsForChallenge(int $challengeId): array {
    return dbFetchAll(
        "SELECT * FROM pistas WHERE desafio_id = ? ORDER BY orden",
        [$challengeId]
    );
}

/**
 * Verificar si usuario ya resolvió un desafío
 * @param int $userId
 * @param int $challengeId
 * @return bool
 */
function isChallengeSolved(int $userId, int $challengeId): bool {
    $result = dbFetchOne(
        "SELECT 1 FROM soluciones_usuarios WHERE usuario_id = ? AND desafio_id = ? AND es_correcta = 1",
        [$userId, $challengeId]
    );
    return $result !== false;
}

/**
 * Obtener estado de desafíos para un usuario
 * @param int $userId
 * @return array [challenge_id => 'no_iniciado'|'en_progreso'|'completado']
 */
function getUserChallengeStatuses(int $userId): array {
    $results = dbFetchAll(
        "SELECT desafio_id, 
                MAX(CASE WHEN es_correcta = 1 THEN 'completado' ELSE 'en_progreso' END) as estado
         FROM soluciones_usuarios 
         WHERE usuario_id = ? 
         GROUP BY desafio_id",
        [$userId]
    );
    
    $statuses = [];
    foreach ($results as $row) {
        $statuses[$row['desafio_id']] = $row['estado'];
    }
    return $statuses;
}

/**
 * Obtener puntuación total de un usuario
 * @param int $userId
 * @return int
 */
function getUserScore(int $userId): int {
    $result = dbFetchOne(
        "SELECT COALESCE(SUM(puntos_obtenidos), 0) as total 
         FROM soluciones_usuarios 
         WHERE usuario_id = ? AND es_correcta = 1",
        [$userId]
    );
    return (int)($result['total'] ?? 0);
}

/**
 * Obtener ranking de estudiantes
 * @param int $limit
 * @return array
 */
function getRanking(int $limit = 50): array {
    return dbFetchAll(
        "SELECT usuario_id, nombre, email, puntaje_total, desafios_completados, ultima_resolucion,
                ROW_NUMBER() OVER (ORDER BY puntaje_total DESC, ultima_resolucion ASC) as posicion
         FROM ranking_estudiantes
         LIMIT ?",
        [$limit]
    );
}

/**
 * Obtener posición del usuario en ranking
 * @param int $userId
 * @return int
 */
function getUserRank(int $userId): int {
    $result = dbFetchOne(
        "SELECT COUNT(*) + 1 as rank 
         FROM ranking_estudiantes 
         WHERE puntaje_total > (SELECT puntaje_total FROM ranking_estudiantes WHERE usuario_id = ?)
            OR (puntaje_total = (SELECT puntaje_total FROM ranking_estudiantes WHERE usuario_id = ?) 
                AND ultima_resolucion < (SELECT ultima_resolucion FROM ranking_estudiantes WHERE usuario_id = ?))",
        [$userId, $userId, $userId]
    );
    return (int)($result['rank'] ?? 1);
}

/**
 * Registrar intento de bandera
 * @param int $userId
 * @param int $challengeId
 * @param string $submittedFlag
 * @param string|null $evidence
 * @param int|null $hintUsedId
 * @return array ['success' => bool, 'message' => string, 'points' => int]
 */
function submitFlag(int $userId, int $challengeId, string $submittedFlag, ?string $evidence = null, ?int $hintUsedId = null): array {
    $challenge = getChallengeById($challengeId);
    
    if (!$challenge) {
        return ['success' => false, 'message' => 'Desafío no encontrado', 'points' => 0];
    }
    
    // Verificar si ya lo resolvió
    if (isChallengeSolved($userId, $challengeId)) {
        return ['success' => false, 'message' => 'Ya has completado este desafío', 'points' => 0];
    }
    
    // Verificar límite de intentos por hora
    $recentAttempts = dbFetchOne(
        "SELECT COUNT(*) as count FROM soluciones_usuarios 
         WHERE usuario_id = ? AND desafio_id = ? AND fecha_intento > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        [$userId, $challengeId]
    );
    
    if (($recentAttempts['count'] ?? 0) >= MAX_FLAG_ATTEMPTS_PER_HOUR) {
        return ['success' => false, 'message' => 'Demasiados intentos. Espera una hora antes de volver a intentar.', 'points' => 0];
    }
    
    // Verificar bandera
    $isCorrect = verifyFlag($challenge['flag_hash'], $submittedFlag);
    $submittedHash = hashFlag($submittedFlag);
    $pointsEarned = 0;
    
    if ($isCorrect) {
        $pointsEarned = $challenge['puntos'];
        
        // Aplicar penalización si usó pista
        if ($hintUsedId && HINT_PENALTY_ENABLED) {
            $hint = dbFetchOne("SELECT penalizacion_puntos FROM pistas WHERE id = ?", [$hintUsedId]);
            if ($hint) {
                $pointsEarned = max(0, $pointsEarned - (int)$hint['penalizacion_puntos']);
            }
        }
    }
    
    // Registrar intento
    dbExecute(
        "INSERT INTO soluciones_usuarios (usuario_id, desafio_id, flag_enviada_hash, es_correcta, evidencia_texto, pista_usada_id, puntos_obtenidos, fecha_resolucion)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $userId, 
            $challengeId, 
            $submittedHash, 
            $isCorrect ? 1 : 0, 
            $evidence, 
            $hintUsedId, 
            $pointsEarned,
            $isCorrect ? date('Y-m-d H:i:s') : null
        ]
    );
    
    if ($isCorrect) {
        return [
            'success' => true, 
            'message' => '¡Correcto! Has obtenido ' . $pointsEarned . ' puntos.', 
            'points' => $pointsEarned
        ];
    } else {
        return [
            'success' => false, 
            'message' => 'Bandera incorrecta. Inténtalo de nuevo.', 
            'points' => 0
        ];
    }
}

/**
 * Registrar uso de pista
 * @param int $userId
 * @param int $challengeId
 * @param int $hintId
 * @return array
 */
function useHint(int $userId, int $challengeId, int $hintId): array {
    $hint = dbFetchOne("SELECT * FROM pistas WHERE id = ? AND desafio_id = ?", [$hintId, $challengeId]);
    
    if (!$hint) {
        return ['success' => false, 'message' => 'Pista no encontrada'];
    }
    
    // Registrar intento con pista usada (sin flag, solo para tracking)
    dbExecute(
        "INSERT INTO soluciones_usuarios (usuario_id, desafio_id, flag_enviada_hash, es_correcta, pista_usada_id, puntos_obtenidos)
         VALUES (?, ?, '', 0, ?, 0)",
        [$userId, $challengeId, $hintId]
    );
    
    return [
        'success' => true, 
        'message' => 'Pista revelada', 
        'hint' => $hint['texto_pista'],
        'penalty' => (int)$hint['penalizacion_puntos']
    ];
}

/**
 * Obtener estadísticas del dashboard para usuario
 * @param int $userId
 * @return array
 */
function getDashboardStats(int $userId): array {
    $challenges = getAllChallenges();
    $statuses = getUserChallengeStatuses($userId);
    $score = getUserScore($userId);
    $rank = getUserRank($userId);
    $completed = count(array_filter($statuses, fn($s) => $s === 'completado'));
    
    return [
        'total_challenges' => count($challenges),
        'completed' => $completed,
        'score' => $score,
        'rank' => $rank,
        'progress_percent' => count($challenges) > 0 ? round(($completed / count($challenges)) * 100) : 0,
        'challenges' => $challenges,
        'statuses' => $statuses
    ];
}