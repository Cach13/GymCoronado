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
            $result = $user->toggle_user_status($_POST['user_id']);
            echo json_encode($result);
            exit;
            
        case 'delete_user':
            $db->query('UPDATE usuarios SET activo = 0 WHERE id = :id');
            $db->bind(':id', $_POST['user_id']);
            if ($db->execute()) {
                echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
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
                'objetivo' => $_POST['objetivo']
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

// Obtener lista de usuarios
function getUsuarios($db, $tipo = null, $page = 1, $limit = 10) {
    $offset = ($page - 1) * $limit;
    
    $query = 'SELECT id, email, nombre, apellido, telefono, tipo, activo, fecha_registro FROM usuarios';
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg,rgb(6, 21, 66) 0%,rgb(13, 37, 132) 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
        }

        .metric-card.users { border-left-color: #3b82f6; }
        .metric-card.new-users { border-left-color: #10b981; }
        .metric-card.active-today { border-left-color: #f59e0b; }
        .metric-card.revenue { border-left-color: #8b5cf6; }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .metric-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .metric-card.users .metric-icon { background: #3b82f6; }
        .metric-card.new-users .metric-icon { background: #10b981; }
        .metric-card.active-today .metric-icon { background: #f59e0b; }
        .metric-card.revenue .metric-icon { background: #8b5cf6; }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }

        .metric-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .filters {
            padding: 1rem 1.5rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .table tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .role-admin {
            background: #fef3c7;
            color: #92400e;
        }

        .role-entrenador {
            background: #dbeafe;
            color: #1e40af;
        }

        .role-cliente {
            background: #e0e7ff;
            color: #3730a3;
        }

        .pagination {
            padding: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }

        .pagination a {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
        }

        .pagination a.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
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
                                <span class="status-badge <?php echo $usuario['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
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

    <script>
        // Funciones del modal
        function openCreateUserModal() {
            document.getElementById('createUserModal').style.display = 'block';
        }

        function closeCreateUserModal() {
            document.getElementById('createUserModal').style.display = 'none';
            document.getElementById('createUserForm').reset();
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('createUserModal');
            if (event.target === modal) {
                closeCreateUserModal();
            }
        }

        // Crear usuario
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_user');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Usuario creado exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear usuario');
            });
        });

        // Cambiar estado de usuario
        function toggleUserStatus(userId) {
            if (confirm('¿Estás seguro de cambiar el estado de este usuario?')) {
                const formData = new FormData();
                formData.append('action', 'toggle_user_status');
                formData.append('user_id', userId);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar estado');
                });
            }
        }

        // Eliminar usuario
        function deleteUser(userId) {
            if (confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar usuario');
                });
            }
        }

        // Filtrar usuarios
        function filterUsers(tipo) {
            window.location.href = `?tipo=${tipo}&page=1`;
        }

        // Auto-refresh de métricas cada 30 segundos
        setInterval(function() {
            // Aquí podrías hacer una llamada AJAX para actualizar solo las métricas
            // sin recargar toda la página
        }, 30000);
    </script>
</body>
</html>