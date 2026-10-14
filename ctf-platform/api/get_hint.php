<?php
/**
 * API Endpoint: Solicitar Pista (Hint)
 * POST /api/get_hint.php
 * 
 * Request: { challenge_id: int, hint_id: int }
 * Response: { success: bool, message: string, hint: string, penalty: int }
 */

require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Requerir autenticación
requireLogin();

// Verificar Content-Type
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Content-Type debe ser application/json']);
    exit;
}

// Leer JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit;
}

$challengeId = (int)($input['challenge_id'] ?? 0);
$hintId = (int)($input['hint_id'] ?? 0);

if ($challengeId <= 0 || $hintId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'IDs inválidos']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Verificar que el desafío existe
    $challenge = getChallengeById($challengeId);
    if (!$challenge) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Desafío no encontrado']);
        exit;
    }
    
    // Verificar si ya resolvió el desafío
    if (isChallengeSolved($userId, $challengeId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ya completaste este desafío']);
        exit;
    }
    
    // Procesar uso de pista
    $result = useHint($userId, $challengeId, $hintId);
    
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error en get_hint: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}