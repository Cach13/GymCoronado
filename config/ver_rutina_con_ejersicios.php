<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/User.php';

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

    // Las rutinas preestablecidas son visibles para todos los usuarios autenticados
    // Solo verificamos permisos especiales si es necesario
    
    // Decodificar ejercicios JSON
    $ejercicios = json_decode($rutina['ejercicios'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $ejercicios = [];
        error_log("Error decodificando ejercicios para rutina ID: $rutina_id - Error: " . json_last_error_msg());
    }

    // Si los ejercicios están en formato de IDs, obtener la información completa
    if (!empty($ejercicios)) {
        $ejercicios_completos = [];
        
        foreach ($ejercicios as $ejercicio) {
            // Si el ejercicio tiene un ID, obtener información completa
            if (isset($ejercicio['id'])) {
                $db->query('SELECT * FROM ejercicios_preestablecidos WHERE id = :id');
                $db->bind(':id', $ejercicio['id']);
                $ejercicio_completo = $db->single();
                
                if ($ejercicio_completo) {
                    $ejercicios_completos[] = [
                        'id' => $ejercicio_completo['id'],
                        'nombre' => $ejercicio_completo['nombre'],
                        'descripcion' => $ejercicio_completo['instrucciones'] ?? '',
                        'series' => $ejercicio['series'] ?? $ejercicio_completo['series'],
                        'repeticiones' => $ejercicio['repeticiones'] ?? $ejercicio_completo['repeticiones'],
                        'descanso' => $ejercicio['descanso'] ?? $ejercicio_completo['tiempo_descanso'],
                        'instrucciones' => $ejercicio['instrucciones'] ?? $ejercicio_completo['instrucciones'],
                        'grupo_muscular' => $ejercicio_completo['grupo_muscular'],
                        'imagen_url' => $ejercicio_completo['imagen_url']
                    ];
                }
            } else {
                // Si ya tiene toda la información, usarla directamente
                $ejercicios_completos[] = $ejercicio;
            }
        }
        
        $ejercicios = $ejercicios_completos;
    }

    // Preparar respuesta
    $response = [
        'success' => true,
        'titulo' => $rutina['titulo'],
        'descripcion' => $rutina['descripcion'],
        'categoria' => ucfirst($rutina['categoria']),
        'objetivo' => str_replace('_', ' ', ucfirst($rutina['objetivo'])),
        'duracion_minutos' => $rutina['duracion_minutos'],
        'entrenador_nombre' => $rutina['entrenador_nombre'],
        'entrenador_apellido' => $rutina['entrenador_apellido'],
        'ejercicios' => $ejercicios,
        'total_ejercicios' => count($ejercicios)
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_UNESCAPED_UNICODE);
}
?>