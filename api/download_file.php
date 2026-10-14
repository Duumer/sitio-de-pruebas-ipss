<?php
/**
 * API Endpoint: Descargar Archivo de Desafío
 * GET /api/download_file.php?id=X
 * 
 * Sirve archivos adjuntos de desafíos de forma segura
 */

require_once __DIR__ . '/../includes/config.php';

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo 'Método no permitido';
    exit;
}

// Requerir autenticación
requireLogin();

$challengeId = (int)($_GET['id'] ?? 0);

if ($challengeId <= 0) {
    http_response_code(400);
    echo 'ID de desafío inválido';
    exit;
}

try {
    $challenge = getChallengeById($challengeId);
    
    if (!$challenge) {
        http_response_code(404);
        echo 'Desafío no encontrado';
        exit;
    }
    
    if (empty($challenge['archivo_adjunto'])) {
        http_response_code(404);
        echo 'Este desafío no tiene archivo adjunto';
        exit;
    }
    
    // Verificar que el archivo existe
    $filePath = __DIR__ . '/../' . $challenge['archivo_adjunto'];
    
    if (!file_exists($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        echo 'Archivo no encontrado en el servidor';
        exit;
    }
    
    // Validar que el archivo está dentro del directorio permitido (prevención path traversal)
    $realBasePath = realpath(__DIR__ . '/../challenges/files');
    $realFilePath = realpath($filePath);
    
    if ($realFilePath === false || strpos($realFilePath, $realBasePath) !== 0) {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
    
    // Determinar MIME type
    $mimeTypes = [
        'php' => 'text/plain',
        'txt' => 'text/plain',
        'log' => 'text/plain',
        'conf' => 'text/plain',
        'svg' => 'image/svg+xml',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
    ];
    
    $ext = strtolower(pathinfo($challenge['archivo_nombre'] ?? $challenge['archivo_adjunto'], PATHINFO_EXTENSION));
    $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
    
    // Headers para descarga
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($challenge['archivo_nombre'] ?? $challenge['archivo_adjunto']) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Enviar archivo
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    error_log("Error en download_file: " . $e->getMessage());
    http_response_code(500);
    echo 'Error interno del servidor';
}