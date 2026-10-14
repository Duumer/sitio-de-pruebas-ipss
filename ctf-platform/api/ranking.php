<?php
/**
 * API Endpoint: Ranking Completo (Docente/Admin)
 * GET /api/ranking.php
 * 
 * Response: { success: bool, ranking: [...] }
 */

require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Requerir rol docente
requireTeacher();

try {
    $limit = (int)($_GET['limit'] ?? 100);
    $limit = min(max($limit, 1), 500); // Entre 1 y 500
    
    $ranking = getRanking($limit);
    
    // Agregar detalles de desafíos completados por usuario
    foreach ($ranking as &$student) {
        $solutions = dbFetchAll(
            "SELECT su.desafio_id, d.titulo, d.puntos, su.fecha_resolucion, su.puntos_obtenidos, su.evidencia_texto
             FROM soluciones_usuarios su
             JOIN desafios d ON su.desafio_id = d.id
             WHERE su.usuario_id = ? AND su.es_correcta = 1
             ORDER BY su.fecha_resolucion",
            [$student['usuario_id']]
        );
        $student['soluciones'] = $solutions;
    }
    
    echo json_encode([
        'success' => true,
        'ranking' => $ranking
    ]);
    
} catch (Exception $e) {
    error_log("Error en ranking: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}