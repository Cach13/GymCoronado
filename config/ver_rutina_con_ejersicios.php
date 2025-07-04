<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';

header('Content-Type: application/json');

try {
    // Verificar autenticación
    if (!gym_is_logged_in()) {
        throw new Exception('No autenticado', 401);
    }

    $user = gym_get_logged_in_user();
    $db = new Database();

    // Validar ID de rutina
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID de rutina no válido', 400);
    }

    $rutina_id = (int)$_GET['id'];

    // Obtener información de la rutina
    $db->query('SELECT r.*, u.nombre as entrenador_nombre, u.apellido as entrenador_apellido 
                FROM rutinas r
                JOIN usuarios u ON r.id_entrenador = u.id
                WHERE r.id = :rutina_id');
    $db->bind(':rutina_id', $rutina_id);
    $rutina = $db->single();

    if (!$rutina) {
        throw new Exception('Rutina no encontrada', 404);
    }

    // Verificar permisos (admin/entrenador pueden ver todas, clientes solo las asignadas)
    if ($user['tipo'] === 'cliente') {
        $db->query('SELECT 1 FROM usuario_rutinas 
                   WHERE id_rutina = :rutina_id AND id_usuario = :user_id AND activa = 1');
        $db->bind(':rutina_id', $rutina_id);
        $db->bind(':user_id', $user['id']);
        
        if (!$db->single()) {
            throw new Exception('No tienes permiso para ver esta rutina', 403);
        }
    }

    // Decodificar ejercicios JSON
    $ejercicios = json_decode($rutina['ejercicios'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $ejercicios = [];
        error_log("Error decodificando ejercicios para rutina ID: $rutina_id");
    }

    // Preparar respuesta
    $response = [
        'success' => true,
        'titulo' => $rutina['titulo'],
        'descripcion' => $rutina['descripcion'],
        'categoria' => ucfirst($rutina['categoria']),
        'objetivo' => $rutina['objetivo'],
        'duracion_minutos' => $rutina['duracion_minutos'],
        'entrenador_nombre' => $rutina['entrenador_nombre'],
        'entrenador_apellido' => $rutina['entrenador_apellido'],
        'ejercicios' => $ejercicios
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}