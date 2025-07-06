<?php
require_once __DIR__ . '/config.php';

// Crear conexión PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para verificar permisos
function gym_check_permission($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login.php');
        exit();
    }
    
    if ($required_role && isset($_SESSION['user_role'])) {
        $user_roles = ['admin', 'entrenador', 'cliente'];
        $required_index = array_search($required_role, $user_roles);
        $user_index = array_search($_SESSION['user_role'], $user_roles);
        
        if ($user_index === false || $user_index > $required_index) {
            header('Location: ../../access_denied.php');
            exit();
        }
    }
}

// Otras funciones utilitarias que puedas necesitar
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}
?>