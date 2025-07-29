<?php
// Requerir archivos necesarios EN EL ORDEN CORRECTO
require_once '../../config/config.php';
require_once '../../config/User.php';
require_once '../../config/GymAttendanceManager.php';

// Verificar autenticación
if (!gym_is_logged_in()) {
    gym_redirect('/login.php');
}

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$resultado_asistencia = null;
$estadisticas = null;
$configuracion_tolerancia = null;

// Inicializar managers
$user = new User();
$attendance_manager = new GymAttendanceManager();

// Obtener datos del usuario actual
$user_data = gym_get_logged_in_user();
if (!$user_data) {
    gym_redirect('/login.php');
}

// Procesar registro de asistencia por código
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_asistencia'])) {
    $codigo = trim($_POST['codigo_asistencia']);
    
    if (!empty($codigo)) {
        // Validar primero si el código existe y es válido
        $validacion = $attendance_manager->validarCodigo($codigo);
        
        if (!$validacion['valid']) {
            $mensaje = $validacion['message'];
            $tipo_mensaje = 'error';
        } else {
            // Obtener información del cliente
            $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Registrar asistencia usando el método especificado
            $resultado_asistencia = $attendance_manager->registrarAsistenciaPorCodigo(
                $user_data['id'], 
                $codigo, 
                $ip_usuario, 
                $user_agent
            );
            
            if ($resultado_asistencia['success']) {
                $mensaje = $resultado_asistencia['message'];
                $tipo_mensaje = 'success';
                
                // Información adicional sobre la racha si está disponible
                if (isset($resultado_asistencia['nueva_racha'])) {
                    $mensaje .= " | Nueva racha: " . $resultado_asistencia['nueva_racha'];
                }
                
                if (isset($resultado_asistencia['tolerancia_aplicada']) && $resultado_asistencia['tolerancia_aplicada']) {
                    $mensaje .= " | Tolerancia aplicada";
                }
                
                // Actualizar datos de sesión con nueva información
                $user_actualizado = $user->get_user_by_id($user_data['id']);
                if ($user_actualizado) {
                    $_SESSION['user_racha_actual'] = $user_actualizado['racha_actual'] ?? 0;
                    $_SESSION['user_racha_maxima'] = $user_actualizado['racha_maxima'] ?? 0;
                    $_SESSION['user_fecha_ultima_asistencia'] = $user_actualizado['fecha_ultima_asistencia'];
                    $_SESSION['user_tolerancia_usada'] = $user_actualizado['tolerancia_usada'] ?? false;
                    $_SESSION['user_fecha_tolerancia'] = $user_actualizado['fecha_tolerancia'];
                }
                
            } else {
                $mensaje = $resultado_asistencia['message'];
                $tipo_mensaje = 'error';
            }
        }
    } else {
        $mensaje = 'Por favor ingresa un código válido.';
        $tipo_mensaje = 'error';
    }
}

// Obtener estadísticas del usuario con manejo de errores
try {
    $estadisticas = $attendance_manager->obtenerEstadisticasUsuario($user_data['id']);
    if (!$estadisticas) {
        $estadisticas = [
            'nombre' => $user_data['nombre'] ?? 'Usuario',
            'apellido' => $user_data['apellido'] ?? '',
            'racha_actual' => 0,
            'racha_maxima' => 0,
            'total_asistencias' => 0,
            'dias_desde_ultima' => null,
            'fecha_ultima_asistencia' => null
        ];
    }
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
    $estadisticas = [
        'nombre' => $user_data['nombre'] ?? 'Usuario',
        'apellido' => $user_data['apellido'] ?? '',
        'racha_actual' => 0,
        'racha_maxima' => 0,
        'total_asistencias' => 0,
        'dias_desde_ultima' => null,
        'fecha_ultima_asistencia' => null
    ];
}

// Obtener configuración de tolerancia con manejo de errores
try {
    $configuracion_tolerancia = $attendance_manager->obtenerConfiguracionTolerancia();
    if (!$configuracion_tolerancia) {
        $configuracion_tolerancia = [
            'dia_tolerancia' => 'domingo',
            'tolerancia_activa' => true,
            'racha_minima_premio' => 7
        ];
    }
} catch (Exception $e) {
    error_log("Error obteniendo configuración: " . $e->getMessage());
    $configuracion_tolerancia = [
        'dia_tolerancia' => 'domingo',
        'tolerancia_activa' => true,
        'racha_minima_premio' => 7
    ];
}

// DEBUG: Para verificar el proceso (eliminar en producción)
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    echo "<pre>";
    echo "User Data:\n";
    print_r($user_data);
    echo "\nEstadísticas:\n";
    print_r($estadisticas);
    echo "\nConfiguración:\n";
    print_r($configuracion_tolerancia);
    echo "</pre>";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia - <?php echo SITE_NAME; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .stats {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .stat-item {
            display: inline-block;
            margin: 10px 20px 10px 0;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registro de Asistencia</h1>
        <p>Bienvenido, <strong><?php echo htmlspecialchars($estadisticas['nombre'] . ' ' . $estadisticas['apellido']); ?></strong></p>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="codigo_asistencia">Código de Asistencia:</label>
                <input type="text" 
                       id="codigo_asistencia" 
                       name="codigo_asistencia" 
                       placeholder="Ingresa el código de asistencia" 
                       required
                       autocomplete="off">
            </div>
            <button type="submit">Registrar Asistencia</button>
        </form>
        
        <div class="stats">
            <h3>Tus Estadísticas</h3>
            <div class="stat-item">
                <div class="stat-number"><?php echo $estadisticas['racha_actual'] ?? 0; ?></div>
                <div class="stat-label">Racha Actual</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $estadisticas['racha_maxima'] ?? 0; ?></div>
                <div class="stat-label">Racha Máxima</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $estadisticas['total_asistencias'] ?? 0; ?></div>
                <div class="stat-label">Total Asistencias</div>
            </div>
            <?php if (isset($estadisticas['fecha_ultima_asistencia']) && $estadisticas['fecha_ultima_asistencia']): ?>
                <div class="stat-item">
                    <div class="stat-number"><?php echo date('d/m/Y', strtotime($estadisticas['fecha_ultima_asistencia'])); ?></div>
                    <div class="stat-label">Última Asistencia</div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($configuracion_tolerancia['tolerancia_activa']): ?>
            <div class="alert alert-info" style="background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; margin-top: 20px;">
                <strong>Sistema de Tolerancia Activo:</strong> 
                Si faltas un día puedes recuperar tu racha los <?php echo ucfirst($configuracion_tolerancia['dia_tolerancia']); ?>s.
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="/dashboard.php" style="color: #007bff; text-decoration: none;">← Volver al Dashboard</a>
        </div>
    </div>
    
    <script>
        // Auto-focus en el campo de código
        document.getElementById('codigo_asistencia').focus();
        
        // Limpiar el campo después de enviar
        <?php if (isset($_POST['codigo_asistencia'])): ?>
            document.getElementById('codigo_asistencia').value = '';
        <?php endif; ?>
    </script>
</body>
</html>