<?php
/**
 * API Endpoint: Enviar Bandera (Flag)
 * POST /api/submit_flag.php
 * 
 * Request: { challenge_id: int, flag: string, evidence: string?, hint_id: int? }
 * Response: { success: bool, message: string, points: int, new_score: int, new_rank: int }
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

// Validar campos requeridos
$challengeId = (int)($input['challenge_id'] ?? 0);
$flag = trim($input['flag'] ?? '');
$evidence = trim($input['evidence'] ?? '');
$hintId = isset($input['hint_id']) ? (int)$input['hint_id'] : null;

if ($challengeId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de desafío inválido']);
    exit;
}

if ($flag === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La bandera no puede estar vacía']);
    exit;
}

// Validar longitud de flag (prevención DoS)
if (strlen($flag) > 200) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bandera demasiado larga']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Procesar envío de bandera
    $result = submitFlag($userId, $challengeId, $flag, $evidence ?: null, $hintId);
    
    // Obtener estadísticas actualizadas
    $newScore = getUserScore($userId);
    $newRank = getUserRank($userId);
    
    $response = [
        'success' => $result['success'],
        'message' => $result['message'],
        'points' => $result['points'],
        'new_score' => $newScore,
        'new_rank' => $newRank
    ];
    
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error en submit_flag: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}