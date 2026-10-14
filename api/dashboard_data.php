<?php
/**
 * API Endpoint: Obtener Datos del Dashboard
 * GET /api/dashboard_data.php
 * 
 * Response: { 
 *   user: { id, nombre, email, rol, score, rank, completed, progress },
 *   challenges: [...],
 *   statuses: { challenge_id: 'no_iniciado'|'en_progreso'|'completado' },
 *   ranking: [...]
 * }
 */

require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Requerir autenticación
requireLogin();

$userId = $_SESSION['user_id'];

try {
    $user = getCurrentUser();
    $stats = getDashboardStats($userId);
    $ranking = getRanking(10); // Top 10 para el scoreboard
    
    $response = [
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'rol' => $user['rol'],
            'score' => $stats['score'],
            'rank' => $stats['rank'],
            'completed' => $stats['completed'],
            'progress' => $stats['progress_percent']
        ],
        'challenges' => $stats['challenges'],
        'statuses' => $stats['statuses'],
        'ranking' => $ranking
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error en dashboard_data: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}