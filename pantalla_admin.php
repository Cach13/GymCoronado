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
            // Cambiar estado activo del usuario
            $db->query('UPDATE usuarios SET activo = NOT activo WHERE id = :id');
            $db->bind(':id', $_POST['user_id']);
            if ($db->execute()) {
                echo json_encode(['success' => true, 'message' => 'Estado del usuario actualizado']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cambiar estado']);
            }
            exit;
            
        case 'toggle_access_status':
            // Cambiar estado de acceso del usuario
            $db->query('UPDATE usuarios SET puede_acceder = NOT puede_acceder WHERE id = :id');
            $db->bind(':id', $_POST['user_id']);
            if ($db->execute()) {
                echo json_encode(['success' => true, 'message' => 'Estado de acceso actualizado']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cambiar estado de acceso']);
            }
            exit;
            
        case 'delete_user':
            try {
                $db->beginTransaction();
                
                // 1. Primero eliminar registros en tablas que referencian al usuario
                $tablesToClean = [
                    'medidas' => 'id_usuario',
                    'rachas' => 'id_usuario',
                    'registro_comidas' => 'id_usuario',
                    'objetivos_nutricionales' => 'id_usuario',
                    'alimentos_favoritos' => 'id_usuario',
                    'historial_acceso' => 'id_usuario',
                    'usuario_rutinas' => 'id_usuario'
                ];
                
                foreach ($tablesToClean as $table => $column) {
                    $db->query("DELETE FROM $table WHERE $column = :id");
                    $db->bind(':id', $_POST['user_id']);
                    $db->execute();
                }
                
                // 2. Eliminar rutinas creadas por el usuario si es entrenador
                $db->query("DELETE FROM rutinas WHERE id_entrenador = :id");
                $db->bind(':id', $_POST['user_id']);
                $db->execute();
                
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
                'objetivo' => $_POST['objetivo'] ?? 'mantener',
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
                'genero' => $_POST['genero'] ?? null
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

// Obtener métricas del dashboard (solo usuarios)
function getDashboardMetrics($db) {
    $db->query('SELECT COUNT(*) as total FROM usuarios WHERE activo = 1');
    $totalUsuarios = $db->single()['total'];
    
    $db->query('SELECT COUNT(*) as total FROM usuarios WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE()) AND activo = 1');
    $nuevosUsuarios = $db->single()['total'];
    
    $db->query('SELECT COUNT(*) as total FROM usuarios WHERE DATE(fecha_registro) = CURDATE() AND activo = 1');
    $usuariosHoy = $db->single()['total'];
    
    return [
        'total_usuarios' => $totalUsuarios,
        'nuevos_usuarios' => $nuevosUsuarios,
        'usuarios_hoy' => $usuariosHoy,
    ];
}

// Obtener lista de usuarios (sin joins a vistas eliminadas)
function getUsuarios($db, $tipo = null, $page = 1, $limit = 10) {
    $offset = ($page - 1) * $limit;
    
    $query = 'SELECT id, email, nombre, apellido, telefono, tipo, activo, puede_acceder, fecha_registro
              FROM usuarios';
    $countQuery = 'SELECT COUNT(*) as total FROM usuarios';
    
    if ($tipo && $tipo !== 'todos') {
        $query .= ' WHERE tipo = :tipo';
        $countQuery .= ' WHERE tipo = :tipo';
    }
    
    $query .= ' ORDER BY fecha_registro DESC LIMIT :limit OFFSET :offset';
    
    $db->query($query);
    if ($tipo && $tipo !== 'todos') {
        $db->bind(':tipo', $tipo);
    }
    $db->bind(':limit', $limit);
    $db->bind(':offset', $offset);
    $usuarios = $db->resultset();
    
    $db->query($countQuery);
    if ($tipo && $tipo !== 'todos') {
        $db->bind(':tipo', $tipo);
    }
    $total = $db->single()['total'];
    
    return ['usuarios' => $usuarios, 'total' => $total];
}

// Datos para la vista
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Panel de Administración - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/admin.css" rel="stylesheet" />
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
                    <div><?php echo htmlspecialchars($currentUser['nombre'] . ' ' . $currentUser['apellido']); ?></div>
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
                    <div class="metric-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="metric-value"><?php echo number_format($metrics['total_usuarios']); ?></div>
                <div class="metric-label">Total Usuarios</div>
            </div>

            <div class="metric-card new-users">
                <div class="metric-header">
                    <div class="metric-icon"><i class="fas fa-user-plus"></i></div>
                </div>
                <div class="metric-value"><?php echo number_format($metrics['nuevos_usuarios']); ?></div>
                <div class="metric-label">Nuevos Este Mes</div>
            </div>

            <div class="metric-card active-today">
                <div class="metric-header">
                    <div class="metric-icon"><i class="fas fa-calendar-day"></i></div>
                </div>
                <div class="metric-value"><?php echo number_format($metrics['usuarios_hoy']); ?></div>
                <div class="metric-label">Registros Hoy</div>
            </div>
        </div>

        <!-- Gestión de Usuarios -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Gestión de Usuarios</h2>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="openCreateUserModal()">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </button>
                    <a href="admin/rutinas.php" class="btn btn-secondary">
                        <i class="fas fa-dumbbell"></i> Gestionar Rutinas
                    </a>
                    <a href="admin/tips.php" class="btn btn-secondary">
                        <i class="fas fa-lightbulb"></i> Gestionar Tips
                    </a>
                </div>
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
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuariosData['usuarios'] as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo !empty($usuario['telefono']) ? htmlspecialchars($usuario['telefono']) : 'No especificado'; ?></td>
                            <td>
                                <span class="role-badge role-<?php echo htmlspecialchars($usuario['tipo']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($usuario['tipo'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $usuario['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-warning btn-sm" onclick="toggleUserStatus(<?php echo intval($usuario['id']); ?>)" 
                                            title="Cambiar estado">
                                        <i class="fas fa-toggle-<?php echo $usuario['activo'] ? 'on' : 'off'; ?>"></i>
                                    </button>
                                    <button class="btn btn-info btn-sm" onclick="toggleAccessStatus(<?php echo intval($usuario['id']); ?>)" 
                                            title="Cambiar acceso">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo intval($usuario['id']); ?>)" 
                                            title="Eliminar usuario">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
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
</body>
</html>


    <!-- Modal para crear usuario -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Crear Nuevo Usuario</h3>
                <span class="close" onclick="closeModal('createUserModal')">&times;</span>
            </div>
            <form id="createUserForm">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" required>
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono:</label>
                    <input type="text" id="telefono" name="telefono">
                </div>
                <div class="form-group">
                    <label for="tipo">Tipo de Usuario:</label>
                    <select id="tipo" name="tipo" required>
                        <option value="cliente">Cliente</option>
                        <option value="entrenador">Entrenador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
                </div>
                <div class="form-group">
                    <label for="genero">Género:</label>
                    <select id="genero" name="genero">
                        <option value="">Seleccionar</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createUserModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>


    <script src="/assets/js/admin.js"></script>
</body>
</html>