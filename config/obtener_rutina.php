<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/User.php';

// Configurar cabeceras para JSON
header('Content-Type: application/json');

// Verificar autenticación básica
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Gym App"');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Autenticación requerida']);
    exit;
}

// Crear instancia de User
$user = new User();

// Verificar credenciales
$email = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

if (!$user->login($email, $password)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
    exit;
}

// Obtener información del usuario autenticado
$usuario = $user->getUserByEmail($email);

// Verificar si el usuario tiene acceso (activo y puede_acceder)
if (!$usuario['activo'] || !$usuario['puede_acceder']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tu cuenta está desactivada o no tienes acceso']);
    exit;
}

// Verificar si el usuario tiene una suscripción activa (solo para clientes)
if ($usuario['tipo'] === 'cliente' && $usuario['estado_suscripcion'] !== 'activa') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Necesitas una suscripción activa para acceder a las rutinas']);
    exit;
}

// Procesar la solicitud según el método HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Obtener rutina específica o listado
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $rutina = $user->obtener_rutina_preestablecida($id);
            
            if ($rutina) {
                // Verificar permisos para ver esta rutina
                if ($usuario['tipo'] === 'cliente' && !$user->puede_ver_rutina($usuario['id'], $id)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'No tienes permiso para ver esta rutina']);
                    exit;
                }
                
                // Obtener ejercicios de la rutina
                $ejercicios = $user->obtener_ejercicios_de_rutina($id);
                $rutina['ejercicios'] = $ejercicios;
                
                echo json_encode([
                    'success' => true,
                    'rutina' => $rutina,
                    'permisos' => [
                        'editar' => in_array($usuario['tipo'], ['entrenador', 'admin']),
                        'eliminar' => in_array($usuario['tipo'], ['entrenador', 'admin'])
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Rutina no encontrada']);
            }
        } else {
            // Listar rutinas disponibles para el usuario
            if ($usuario['tipo'] === 'cliente') {
                $rutinas = $user->obtener_rutinas_disponibles($usuario['id']);
            } else {
                $rutinas = $user->obtener_todas_rutinas();
            }
            
            echo json_encode([
                'success' => true,
                'rutinas' => $rutinas,
                'total' => count($rutinas)
            ]);
        }
        break;
        
    case 'POST':
        // Solo entrenadores y admins pueden crear/modificar rutinas
        if (!in_array($usuario['tipo'], ['entrenador', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para esta acción']);
            exit;
        }
        
        // Procesar creación o actualización de rutina
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['id'])) {
            // Actualizar rutina existente
            $result = $user->editar_rutina_preestablecida($data['id'], $data);
        } else {
            // Crear nueva rutina
            $result = $user->crear_rutina_preestablecida($data);
        }
        
        echo json_encode($result);
        break;
        
    case 'DELETE':
        // Solo entrenadores y admins pueden eliminar rutinas
        if (!in_array($usuario['tipo'], ['entrenador', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para esta acción']);
            exit;
        }
        
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $result = $user->eliminar_rutina_preestablecida($id);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de rutina no proporcionado']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}