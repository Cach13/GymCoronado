<?php
require_once 'config/config.php';
require_once 'user/User.php';

gym_check_permission('cliente');

// Verificar que el usuario esté logueado
if (!gym_is_logged_in()) {
    gym_redirect('login.php');
}

$user = gym_get_logged_in_user();
$userObj = new User();
$userStats = $userObj->get_user_stats($user['id']);

// Calcular días restantes de membresía
$diasRestantes = 0;
if (!empty($user['fecha_vencimiento'])) {
    $fechaVencimiento = new DateTime($user['fecha_vencimiento']);
    $fechaActual = new DateTime();
    $diferencia = $fechaActual->diff($fechaVencimiento);
    
    if ($fechaVencimiento > $fechaActual) {
        $diasRestantes = $diferencia->days;
    } else {
        $diasRestantes = -$diferencia->days; // Negativo si ya venció
    }
}
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
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg,rgb(15, 46, 185) 0%,rgb(33, 60, 179) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .sidebar {
            min-height: calc(100vh - 76px);
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: #495057;
            padding: 1rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: linear-gradient(135deg,rgb(15, 46, 185) 0%,rgb(33, 60, 179) 100%);
            color: white;
        }
        .main-content {
            padding: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
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
        }
        .welcome-card {
            background: linear-gradient(135deg,rgb(15, 46, 185) 0%,rgb(33, 60, 179) 100%);
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-dumbbell me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo $user['nombre'] . ' ' . $user['apellido']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="pagos.php"><i class="fas fa-credit-card me-2"></i>Pagos</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="rutinas.php">
                                <i class="fas fa-running me-2"></i>
                                Rutinas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="nutricion.php">
                                <i class="fas fa-apple-alt me-2"></i>
                                Nutrición
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="progreso.php">
                                <i class="fas fa-chart-line me-2"></i>
                                Progreso
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pagos.php">
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
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
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
                    <div class="col-md-3 mb-3">
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
                    
                    <div class="col-md-3 mb-3">
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
                    
                    <div class="col-md-3 mb-3">
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
                    
                    <div class="col-md-3 mb-3">
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
                    <div class="col-md-3 mb-3">
                        <a href="marcar_asistencia.php" class="quick-action-btn">
                            <div class="text-center">
                                <i class="fas fa-check-circle fa-2x mb-2" style="color: #28a745;"></i>
                                <h6>Marcar Asistencia</h6>
                                <small class="text-muted">Registra tu día de gym</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="nueva_rutina.php" class="quick-action-btn">
                            <div class="text-center">
                                <i class="fas fa-plus-circle fa-2x mb-2" style="color: #007bff;"></i>
                                <h6>Nueva Rutina</h6>
                                <small class="text-muted">Empezar entrenamiento</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="registrar_comida.php" class="quick-action-btn">
                            <div class="text-center">
                                <i class="fas fa-utensils fa-2x mb-2" style="color: #fd7e14;"></i>
                                <h6>Registrar Comida</h6>
                                <small class="text-muted">Trackea tus macros</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
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
                    <div class="col-md-8">
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
                    
                    <div class="col-md-4">
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
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>