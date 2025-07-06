<?php
require_once 'config/config.php';
require_once 'config/User.php';

// Verificar permisos y autenticación
gym_check_permission('cliente');

if (!gym_is_logged_in()) {
    gym_redirect('login.php');
}

$user = gym_get_logged_in_user();
$userObj = new User();

// Obtener estadísticas del usuario
$userStats = $userObj->get_user_stats($user['id']);

// Obtener suscripción activa del usuario
$subscription = $userObj->get_active_subscription($user['id']);

// Calcular días restantes de membresía
$diasRestantes = 0;
$estadoMembresia = 'Sin suscripción activa';

if ($subscription) {
    $fechaVencimiento = new DateTime($subscription['fecha_fin']);
    $fechaActual = new DateTime();
    $diferencia = $fechaActual->diff($fechaVencimiento);
    
    if ($fechaVencimiento > $fechaActual) {
        $diasRestantes = $diferencia->days;
        $estadoMembresia = 'Activa';
    } else {
        $diasRestantes = -$diferencia->days; // Negativo si ya venció
        $estadoMembresia = 'Vencida';
    }
    
    // Actualizar datos en sesión para consistencia
    $_SESSION['user_tipo_suscripcion'] = $subscription['tipo_suscripcion'];
    $_SESSION['user_fecha_fin_suscripcion'] = $subscription['fecha_fin'];
    $_SESSION['user_estado_suscripcion'] = $subscription['estado'];
    $_SESSION['user_modalidad_pago'] = $subscription['modalidad_pago'];
}

// Obtener rutinas asignadas al usuario
$rutinasAsignadas = [];
if (isset($user['id'])) {
    $db = new Database();
    $db->query('SELECT r.* FROM rutinas r 
               JOIN usuario_rutinas ur ON r.id = ur.id_rutina 
               WHERE ur.id_usuario = :user_id AND ur.activa = 1');
    $db->bind(':user_id', $user['id']);
    $rutinasAsignadas = $db->resultset();
}

// Obtener medidas corporales recientes
$medidasRecientes = [];
$db = new Database();
$db->query('SELECT * FROM medidas 
           WHERE id_usuario = :user_id 
           ORDER BY fecha_medicion DESC LIMIT 5');
$db->bind(':user_id', $user['id']);
$medidasRecientes = $db->resultset();

// Obtener objetivos nutricionales
$objetivosNutricionales = [];
$db->query('SELECT * FROM objetivos_nutricionales 
           WHERE id_usuario = :user_id AND activo = 1 
           ORDER BY fecha_inicio DESC LIMIT 1');
$db->bind(':user_id', $user['id']);
$objetivosNutricionales = $db->single();

// Obtener historial de pagos
$historialPagos = [];
$db->query('SELECT p.*, s.tipo_suscripcion 
           FROM pagos p
           JOIN suscripciones s ON p.id = s.id_pago
           WHERE p.id_usuario = :user_id
           ORDER BY p.fecha_pago DESC LIMIT 5');
$db->bind(':user_id', $user['id']);
$historialPagos = $db->resultset();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg,rgb(15, 46, 185) 0%,rgb(33, 60, 179) 100%);
            --sidebar-width: 250px;
        }
        
        body {
            background-color:rgb(198, 201, 205);
            padding-top: 76px; /* Altura del navbar fijo */
        }
        
        .navbar {
            background: var(--primary-gradient);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            height: 76px;
        }
        
        /* Sidebar Desktop */
        .sidebar {
            position: fixed;
            top: 76px;
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - 76px);
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1020;
        }
        
        .sidebar .nav-link {
            color: #495057;
            padding: 1rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--primary-gradient);
            color: white;
        }
        
        /* Main content ajustado para sidebar */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: calc(100vh - 76px);
        }
        
        /* Mobile sidebar */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 76px;
            left: 0;
            width: 100%;
            height: calc(100vh - 76px);
            background: rgba(0,0,0,0.5);
            z-index: 1019;
        }
        
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            padding: 0.5rem;
        }

            /* Estilos para el menú desplegable */
        .sidebar .dropdown-menu {
            background-color: #f8f9fa;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-left: 1.5rem;
            border-radius: 0 8px 8px 8px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            padding: 0;
            display: block !important; /* Forzar display block */
        }

        .sidebar .dropdown-menu.show {
            max-height: 200px;
            padding: 0.5rem 0;
        }

        .sidebar .dropdown-item {
            padding: 0.5rem 1.5rem;
            color: #333;
            transition: all 0.2s;
            display: block;
        }

        .sidebar .dropdown-item:hover {
            background-color: #e9ecef;
            color: #0d6efd;
        }

        .sidebar .dropdown-icon {
            transition: transform 0.3s;
            font-size: 0.8rem;
        }

        .sidebar .dropdown-toggle.active .dropdown-icon {
            transform: rotate(180deg);
        }

        /* Comportamiento en desktop */
        @media (min-width: 992px) {
            .sidebar .dropdown:hover .dropdown-menu {
                max-height: 200px;
                padding: 0.5rem 0;
            }
            
            .sidebar .dropdown:hover .dropdown-icon {
                transform: rotate(180deg);
            }
        }
/* Asegurar que el submenú se muestre al pasar el mouse */
@media (min-width: 992px) {
    .sidebar .dropdown:hover .dropdown-menu {
        display: block;
    }
}
        
        /* Estilos de las tarjetas */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }
        
        .welcome-card {
            background: var(--primary-gradient);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .quick-action-btn {
            border-radius: 10px;
            padding: 1rem;
            border: 2px solid #e9ecef;
            background: white;
            transition: all 0.3s;
            text-decoration: none;
            color: #495057;
            display: block;
            height: 100%;
        }
        
        .quick-action-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }
        
        .membresia-vencida {
            background: linear-gradient(135deg, #dc3545, #c82333) !important;
        }
        .membresia-pronto-vencer {
            background: linear-gradient(135deg, #ffc107, #e0a800) !important;
        }
        .membresia-activa {
            background: linear-gradient(135deg, #28a745, #1e7e34) !important;
        }
        
        /* Responsive Design */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar-backdrop.show {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .welcome-card {
                padding: 1.5rem;
                text-align: center;
            }
            
            .welcome-card .row {
                align-items: center !important;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
            
            .stat-card .d-flex {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .stat-card .ms-3 {
                margin-left: 0 !important;
            }
        }
        
        @media (max-width: 575.98px) {
            body {
                padding-top: 70px;
            }
            
            .navbar {
                height: 70px;
            }
            
            .sidebar,
            .sidebar-backdrop {
                top: 70px;
                height: calc(100vh - 70px);
            }
            
            .main-content {
                padding: 1rem 0.5rem;
            }
            
            .welcome-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .welcome-card h2 {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .quick-action-btn {
                padding: 0.8rem;
            }
            
            .quick-action-btn i {
                font-size: 1.5rem !important;
            }
        }
        
        /* Mejoras adicionales para móvil */
        @media (max-width: 991.98px) {
            .col-md-3 {
                margin-bottom: 1rem;
            }
            
            .card {
                margin-bottom: 1rem;
            }
        }
        /* Estilos específicos para móvil */
@media (max-width: 991.98px) {
    .sidebar .dropdown-menu {
        position: static;
        transform: none;
        width: auto;
        margin-left: 2rem;
        border-left: 2px solid #e9ecef;
        box-shadow: none;
    }
    
    .sidebar .dropdown-item {
        padding: 0.5rem 1rem;
    }
    
    .sidebar .dropdown-toggle {
        cursor: pointer;
    }
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="sidebar-toggle me-2" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-dumbbell me-2"></i>
                <span class="d-none d-sm-inline"><?php echo SITE_NAME; ?></span>
            </a>
            
            <div class="ms-auto">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <span class="d-none d-md-inline"><?php echo $user['nombre'] . ' ' . $user['apellido']; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="pagos.php"><i class="fas fa-credit-card me-2"></i>Pagos</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-home me-2"></i>
                    Dashboard
                </a>
            </li>

            <!-- Menú desplegable para Rutinas -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="rutinasDropdown">
                    <i class="fas fa-running me-2"></i>
                    Rutinas
                    <i class="fas fa-chevron-down dropdown-icon ms-1"></i>
                </a>
                <ul class="dropdown-menu" id="rutinasSubmenu">
                    <li>
                        <a class="dropdown-item" href="../user/rutinas/rutinas_user.php">
                            <i class="fas fa-list me-2"></i>
                            Ver Rutinas
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="user/rutinas/crear.php">
                            <i class="fas fa-plus-circle me-2"></i>
                            Crear Rutina
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="nutricion.php">
                    <i class="fas fa-apple-alt me-2"></i>
                    Nutrición
                </a>
            </li>

            <!-- Menú desplegable para Progreso -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="progresoDropdown">
                    <i class="fas fa-chart-line me-2"></i>
                    Progreso
                    <i class="fas fa-chevron-down dropdown-icon ms-1"></i>
                </a>
                <ul class="dropdown-menu" id="progresoSubmenu">
                    <li>
                        <a class="dropdown-item" href="../user/progreso/progres.php">
                            <i class="fas fa-eye me-2"></i>
                            Ver Progreso
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../user/progreso/metricas.php">
                            <i class="fas fa-sliders-h me-2"></i>
                            Métricas
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="../user/pagos/pagos.php">
                    <i class="fas fa-credit-card me-2"></i>
                    Pagos
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="tips.php">
                    <i class="fas fa-lightbulb me-2"></i>
                    Tips
                </a>
            </li>

            <?php if ($user['tipo'] == 'admin' || $user['tipo'] == 'entrenador'): ?>
            <li class="nav-item">
                <a class="nav-link" href="admin.php">
                    <i class="fas fa-users-cog me-2"></i>
                    Administración
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>


    <!-- Main content -->
    <main class="main-content">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3">¡Bienvenido/a, <?php echo $user['nombre']; ?>! 💪</h2>
                    <p class="mb-0">
                        Estás en el camino correcto para alcanzar tus objetivos. 
                        Tu meta actual: <strong><?php echo ucfirst(str_replace('_', ' ', $user['objetivo'])); ?></strong>
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-trophy" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?php echo $userStats['dias_asistencia']; ?></h3>
                            <small class="text-muted">Días de Asistencia</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #17a2b8, #6f42c1);">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0">5</h3>
                            <small class="text-muted">Racha Actual</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <?php
                        $iconClass = 'membresia-activa';
                        $iconSymbol = 'fas fa-calendar-alt';
                        
                        if ($diasRestantes < 0) {
                            $iconClass = 'membresia-vencida';
                            $iconSymbol = 'fas fa-exclamation-triangle';
                        } elseif ($diasRestantes <= 7) {
                            $iconClass = 'membresia-pronto-vencer';
                            $iconSymbol = 'fas fa-clock';
                        }
                        ?>
                        <div class="stat-icon <?php echo $iconClass; ?>">
                            <i class="<?php echo $iconSymbol; ?>"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0">
                                <?php 
                                if ($diasRestantes < 0) {
                                    echo 'Vencida';
                                } else {
                                    echo $diasRestantes;
                                }
                                ?>
                            </h3>
                            <small class="text-muted">
                                <?php 
                                if ($diasRestantes < 0) {
                                    echo 'Membresía';
                                } else {
                                    echo 'Días Restantes';
                                }
                                ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #e83e8c);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="mb-0">-2.5kg</h3>
                            <small class="text-muted">Progreso</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerta de membresía si está próxima a vencer o vencida -->
        <?php if ($diasRestantes <= 7): ?>
        <div class="alert <?php echo ($diasRestantes < 0) ? 'alert-danger' : 'alert-warning'; ?> alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>
                <?php if ($diasRestantes < 0): ?>
                    ¡Tu membresía ha vencido!
                <?php else: ?>
                    ¡Tu membresía vence pronto!
                <?php endif; ?>
            </strong>
            <?php if ($diasRestantes < 0): ?>
                Tu membresía venció hace <?php echo abs($diasRestantes); ?> días. Renueva tu membresía para seguir disfrutando de todos los servicios.
            <?php else: ?>
                Te quedan solo <?php echo $diasRestantes; ?> días. Renueva tu membresía antes de que expire.
            <?php endif; ?>
            <a href="pagos.php" class="btn btn-sm <?php echo ($diasRestantes < 0) ? 'btn-light' : 'btn-warning'; ?> ms-2">
                Renovar Ahora
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">Acciones Rápidas</h4>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="marcar_asistencia.php" class="quick-action-btn">
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-2x mb-2" style="color: #28a745;"></i>
                        <h6>Marcar Asistencia</h6>
                        <small class="text-muted">Registra tu día de gym</small>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="nueva_rutina.php" class="quick-action-btn">
                    <div class="text-center">
                        <i class="fas fa-plus-circle fa-2x mb-2" style="color: #007bff;"></i>
                        <h6>Nueva Rutina</h6>
                        <small class="text-muted">Empezar entrenamiento</small>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="registrar_comida.php" class="quick-action-btn">
                    <div class="text-center">
                        <i class="fas fa-utensils fa-2x mb-2" style="color: #fd7e14;"></i>
                        <h6>Registrar Comida</h6>
                        <small class="text-muted">Trackea tus macros</small>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="pagos.php" class="quick-action-btn">
                    <div class="text-center">
                        <i class="fas fa-credit-card fa-2x mb-2" style="color: #6f42c1;"></i>
                        <h6>Renovar Membresía</h6>
                        <small class="text-muted">Gestionar pagos</small>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activity & Tips -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Actividad Reciente</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success rounded-circle p-2 me-3">
                                <i class="fas fa-check text-white"></i>
                            </div>
                            <div>
                                <strong>Asistencia registrada</strong>
                                <br><small class="text-muted">Hace 2 horas</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary rounded-circle p-2 me-3">
                                <i class="fas fa-dumbbell text-white"></i>
                            </div>
                            <div>
                                <strong>Rutina de piernas completada</strong>
                                <br><small class="text-muted">Ayer</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-warning rounded-circle p-2 me-3">
                                <i class="fas fa-apple-alt text-white"></i>
                            </div>
                            <div>
                                <strong>Meta de proteína alcanzada</strong>
                                <br><small class="text-muted">Hace 3 días</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tip del Día</h5>
                    </div>
                    <div class="card-body">
                        <h6>💧 Hidratación</h6>
                        <p class="mb-0">Toma al menos 8 vasos de agua al día. Tu rendimiento mejora un 25% con buena hidratación.</p>
                        <hr>
                        <a href="tips.php" class="btn btn-sm btn-outline-primary">Ver más tips</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
       // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarBackdrop.classList.toggle('show');
        });

        sidebarBackdrop.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarBackdrop.classList.remove('show');
        });

        // Close sidebar when clicking on a link (mobile)
        document.querySelectorAll('.sidebar .nav-link:not(.dropdown-toggle)').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('show');
                sidebarBackdrop.classList.remove('show');
            }
        });

  // Rutinas y Progreso dropdown functionality
document.addEventListener('DOMContentLoaded', function () {
    const dropdowns = [
        {
            toggle: document.querySelector('#rutinasDropdown'),
            menu: document.querySelector('#rutinasSubmenu')
        },
        {
            toggle: document.querySelector('#progresoDropdown'),
            menu: document.querySelector('#progresoSubmenu')
        }
    ];

    function isMobile() {
        return window.innerWidth < 992;
    }

    dropdowns.forEach(({ toggle, menu }) => {
        if (!toggle || !menu) return;

        // Toggle para móviles
        function toggleMenu(e) {
            if (isMobile()) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const isOpen = menu.classList.contains('show');
                menu.classList.toggle('show', !isOpen);
                toggle.classList.toggle('active', !isOpen);
                return false;
            }
        }

        toggle.addEventListener('click', toggleMenu, true);

        // Hover para escritorio
        if (!isMobile()) {
            toggle.addEventListener('mouseenter', function () {
                menu.classList.add('show');
                toggle.classList.add('active');
            });

            toggle.parentElement.addEventListener('mouseleave', function () {
                menu.classList.remove('show');
                toggle.classList.remove('active');
            });
        }

        // Cerrar en resize a escritorio
        window.addEventListener('resize', function () {
            if (!isMobile()) {
                menu.classList.remove('show');
                toggle.classList.remove('active');
            }
        });
    });

    // Cerrar dropdowns al hacer clic fuera (solo móviles)
    document.addEventListener('click', function (e) {
        if (isMobile() && 
            !e.target.closest('.dropdown') &&
            !e.target.closest('.sidebar-toggle')) {
            dropdowns.forEach(({ toggle, menu }) => {
                menu.classList.remove('show');
                toggle.classList.remove('active');
            });
        }
    }, true);
            
            // Hover para desktop
            if (!isMobile()) {
                dropdownToggle.addEventListener('mouseenter', function() {
                    dropdownMenu.classList.add('show');
                    this.classList.add('active');
                });
                
                dropdownToggle.parentElement.addEventListener('mouseleave', function() {
                    dropdownMenu.classList.remove('show');
                    this.querySelector('.dropdown-toggle').classList.remove('active');
                });
            }
            
            // Cerrar menús al cambiar a desktop
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    dropdownMenu.classList.remove('show');
                    dropdownToggle.classList.remove('active');
                }
            });
        });

        
    </script>
</body>
</html>