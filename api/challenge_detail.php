<?php
/**
 * API Endpoint: Obtener Detalle de Desafío
 * GET /api/challenge_detail.php?id=X
 * 
 * Response: { 
 *   success: bool, 
 *   challenge: {...}, 
 *   hints: [...], 
 *   user_status: 'no_iniciado'|'en_progreso'|'completado',
 *   can_submit: bool
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

$challengeId = (int)($_GET['id'] ?? 0);

if ($challengeId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de desafío inválido']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $challenge = getChallengeById($challengeId);
    
    if (!$challenge) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Desafío no encontrado']);
        exit;
    }
    
    $hints = getHintsForChallenge($challengeId);
    $statuses = getUserChallengeStatuses($userId);
    $userStatus = $statuses[$challengeId] ?? 'no_iniciado';
    $canSubmit = $userStatus !== 'completado';
    
    // No enviar el hash de la flag al frontend
    unset($challenge['flag_hash']);
    
    $response = [
        'success' => true,
        'challenge' => $challenge,
        'hints' => $hints,
        'user_status' => $userStatus,
        'can_submit' => $canSubmit
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error en challenge_detail: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}