<?php
require_once 'config/config.php';
require_once 'config/User.php';

// Verificar permisos de administrador
gym_check_permission('admin');

$user = new User();
$db = new Database();

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'toggle_user_status':
            // Cambiar estado y actualizar campo para bloquear acceso
            $db->query('UPDATE usuarios SET activo = NOT activo, puede_acceder = NOT puede_acceder WHERE id = :id');
            $db->bind(':id', $_POST['user_id']);
            if ($db->execute()) {
                echo json_encode(['success' => true, 'message' => 'Estado del usuario actualizado']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cambiar estado']);
            }
            exit;
            
        case 'delete_user':
        try {
            $db->beginTransaction();
            
            // 1. Primero eliminar registros en tablas que referencian al usuario
            $tablesToClean = [
                'pagos' => 'id_usuario',
                'medidas' => 'id_usuario',
                'rachas' => 'id_usuario',
                'registro_comidas' => 'id_usuario',
                'objetivos_nutricionales' => 'id_usuario',
                'alimentos_favoritos' => 'id_usuario',
                'historial_acceso' => 'id_usuario'
            ];
            
            foreach ($tablesToClean as $table => $column) {
                $db->query("DELETE FROM $table WHERE $column = :id");
                $db->bind(':id', $_POST['user_id']);
                if (!$db->execute()) {
                    throw new Exception("Error al limpiar tabla $table");
                }
            }
            
            // 2. Eliminar rutinas creadas por el usuario si es entrenador
            $db->query("DELETE FROM rutinas WHERE id_entrenador = :id");
            $db->bind(':id', $_POST['user_id']);
            $db->execute(); // No verificamos error aquí ya que puede no ser entrenador
            
            // 3. Finalmente eliminar al usuario
            $db->query("DELETE FROM usuarios WHERE id = :id");
            $db->bind(':id', $_POST['user_id']);
            
            if ($db->execute()) {
                $db->endTransaction();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Usuario y todos sus datos asociados eliminados permanentemente'
                ]);
            } else {
                throw new Exception('No se pudo eliminar el usuario');
            }
        } catch (Exception $e) {
            $db->cancelTransaction();
            error_log("Error al eliminar usuario: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Error al eliminar usuario: ' . $e->getMessage()
            ]);
        }
        exit;
            
        case 'create_user':
            $userData = [
                'email' => gym_sanitize($_POST['email']),
                'password' => $_POST['password'],
                'nombre' => gym_sanitize($_POST['nombre']),
                'apellido' => gym_sanitize($_POST['apellido']),
                'telefono' => gym_sanitize($_POST['telefono']),
                'tipo' => $_POST['tipo'],
                'objetivo' => $_POST['objetivo'],
                'puede_acceder' => 1 // Nuevo usuario puede acceder por defecto
            ];
            
            $errors = $user->validate_registration($userData);
            if (empty($errors)) {
                $result = $user->register($userData);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            }
            exit;
    }
}

// Obtener métricas del dashboard
function getDashboardMetrics($db) {
    // Total usuarios activos
    $db->query('SELECT COUNT(*) as total FROM usuarios WHERE activo = 1');
    $totalUsuarios = $db->single()['total'];
    
    // Nuevos usuarios este mes
    $db->query('SELECT COUNT(*) as total FROM usuarios WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE()) AND activo = 1');
    $nuevosUsuarios = $db->single()['total'];
    
    // Usuarios activos hoy (simulado con registros recientes)
    $db->query('SELECT COUNT(*) as total FROM usuarios WHERE DATE(fecha_registro) = CURDATE() AND activo = 1');
    $usuariosHoy = $db->single()['total'];
    
    // Ingresos mensuales (simulado - necesitarías tabla de pagos)
    $ingresosMes = $totalUsuarios * 500; // Precio promedio de membresía
    
    return [
        'total_usuarios' => $totalUsuarios,
        'nuevos_usuarios' => $nuevosUsuarios,
        'usuarios_hoy' => $usuariosHoy,
        'ingresos_mes' => $ingresosMes
    ];
}

// Obtener lista de usuarios (actualizada para incluir datos de suscripción)
function getUsuarios($db, $tipo = null, $page = 1, $limit = 10) {
    $offset = ($page - 1) * $limit;
    
    $query = 'SELECT id, email, nombre, apellido, telefono, tipo, activo, fecha_registro, 
              tipo_suscripcion, fecha_fin_suscripcion, estado_suscripcion, modalidad_pago 
              FROM usuarios';
    $countQuery = 'SELECT COUNT(*) as total FROM usuarios';
    
    if ($tipo && $tipo !== 'todos') {
        $query .= ' WHERE tipo = :tipo';
        $countQuery .= ' WHERE tipo = :tipo';
    }
    
    $query .= ' ORDER BY fecha_registro DESC LIMIT :limit OFFSET :offset';
    
    // Obtener usuarios
    $db->query($query);
    if ($tipo && $tipo !== 'todos') {
        $db->bind(':tipo', $tipo);
    }
    $db->bind(':limit', $limit);
    $db->bind(':offset', $offset);
    $usuarios = $db->resultset();
    
    // Calcular días restantes para cada usuario
    foreach ($usuarios as &$usuario) {
        if ($usuario['fecha_fin_suscripcion']) {
            $fechaFin = new DateTime($usuario['fecha_fin_suscripcion']);
            $hoy = new DateTime();
            $diasRestantes = $hoy->diff($fechaFin)->days;
            $usuario['dias_restantes'] = $fechaFin >= $hoy ? $diasRestantes : 0;
        } else {
            $usuario['dias_restantes'] = null;
        }
    }
    
    // Obtener total para paginación
    $db->query($countQuery);
    if ($tipo && $tipo !== 'todos') {
        $db->bind(':tipo', $tipo);
    }
    $total = $db->single()['total'];
    
    return ['usuarios' => $usuarios, 'total' => $total];
}

$metrics = getDashboardMetrics($db);
$tipoFiltro = $_GET['tipo'] ?? 'todos';
$paginaActual = (int)($_GET['page'] ?? 1);
$usuariosData = getUsuarios($db, $tipoFiltro, $paginaActual);
$totalPaginas = ceil($usuariosData['total'] / 10);

$currentUser = gym_get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">

</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1><i class="fas fa-dumbbell"></i> <?php echo SITE_NAME; ?> - Admin</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div><?php echo $currentUser['nombre'] . ' ' . $currentUser['apellido']; ?></div>
                    <small>Administrador</small>
                </div>
                <a href="logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Métricas del Dashboard -->
        <div class="metrics-grid">
            <div class="metric-card users">
                <div class="metric-header">
                    <div class="metric-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="metric-value"><?php echo number_format($metrics['total_usuarios']); ?></div>
                <div class="metric-label">Total Usuarios</div>
            </div>
            
            <div class="metric-card new-users">
                <div class="metric-header">
                    <div class="metric-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
                <div class="metric-value"><?php echo number_format($metrics['nuevos_usuarios']); ?></div>
                <div class="metric-label">Nuevos Este Mes</div>
            </div>
            
            <div class="metric-card active-today">
                <div class="metric-header">
                    <div class="metric-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div class="metric-value"><?php echo number_format($metrics['usuarios_hoy']); ?></div>
                <div class="metric-label">Registros Hoy</div>
            </div>
            
            <div class="metric-card revenue">
                <div class="metric-header">
                    <div class="metric-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="metric-value">$<?php echo number_format($metrics['ingresos_mes']); ?></div>
                <div class="metric-label">Ingresos Estimados</div>
            </div>
        </div>

        <!-- Gestión de Usuarios -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Gestión de Usuarios</h2>
                <button class="btn btn-primary" onclick="openCreateUserModal()">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </button>
            </div>
            
            <div class="filters">
                <label>Filtrar por tipo:</label>
                <select class="filter-select" onchange="filterUsers(this.value)">
                    <option value="todos" <?php echo $tipoFiltro === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <option value="cliente" <?php echo $tipoFiltro === 'cliente' ? 'selected' : ''; ?>>Clientes</option>
                    <option value="entrenador" <?php echo $tipoFiltro === 'entrenador' ? 'selected' : ''; ?>>Entrenadores</option>
                    <option value="admin" <?php echo $tipoFiltro === 'admin' ? 'selected' : ''; ?>>Administradores</option>
                </select>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Tipo</th>
                            <th>Suscripción</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuariosData['usuarios'] as $usuario): ?>
                        <tr>
                            <td><?php echo $usuario['id']; ?></td>
                            <td><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></td>
                            <td><?php echo $usuario['email']; ?></td>
                            <td><?php echo $usuario['telefono'] ?? 'No especificado'; ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $usuario['tipo']; ?>">
                                    <?php echo ucfirst($usuario['tipo']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($usuario['tipo'] === 'cliente' && $usuario['tipo_suscripcion']): ?>
                                    <div>
                                        <strong><?php echo gym_format_subscription_type($usuario['tipo_suscripcion']); ?></strong>
                                    </div>
                                    <div>
                                        <?php if ($usuario['dias_restantes'] !== null): ?>
                                            <span class="days-remaining">
                                                <?php echo $usuario['dias_restantes']; ?> días
                                            </span>
                                        <?php else: ?>
                                            <span>Sin suscripción</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="payment-method">
                                            <?php echo ucfirst($usuario['modalidad_pago'] ?? 'No especificado'); ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span>N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $usuario['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                                <?php if ($usuario['tipo'] === 'cliente' && $usuario['estado_suscripcion']): ?>
                                    <br>
                                    <span class="subscription-badge 
                                        <?php echo $usuario['estado_suscripcion'] === 'activa' ? 'subscription-active' : 
                                              ($usuario['estado_suscripcion'] === 'vencida' ? 'subscription-expired' : 'subscription-pending'); ?>">
                                        <?php echo ucfirst($usuario['estado_suscripcion']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="toggleUserStatus(<?php echo $usuario['id']; ?>)">
                                    <i class="fas fa-toggle-<?php echo $usuario['activo'] ? 'on' : 'off'; ?>"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $usuario['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPaginas > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&tipo=<?php echo $tipoFiltro; ?>" 
                       class="<?php echo $i === $paginaActual ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para crear usuario -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <h3>Crear Nuevo Usuario</h3>
            <form id="createUserForm">
                <div class="form-group">
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña:</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre:</label>
                    <input type="text" name="nombre" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Apellido:</label>
                    <input type="text" name="apellido" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono:</label>
                    <input type="tel" name="telefono" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo:</label>
                    <select name="tipo" class="form-input" required>
                        <option value="cliente">Cliente</option>
                        <option value="entrenador">Entrenador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Objetivo:</label>
                    <select name="objetivo" class="form-input">
                        <option value="perder">Perder Peso</option>
                        <option value="ganar">Ganar Músculo</option>
                        <option value="mantener" selected>Mantener</option>
                        <option value="resistencia">Resistencia</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-danger" onclick="closeCreateUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>


    <script src="assets/js/admin.js"></script>
</body>
</html>