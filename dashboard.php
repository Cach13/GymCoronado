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
$db->query('SELECT * FROM medidas 
            WHERE id_usuario = :user_id 
            ORDER BY fecha_medicion DESC LIMIT 5');
$db->bind(':user_id', $user['id']);
$medidasRecientes = $db->resultset();

// Obtener objetivos nutricionales activos
$objetivosNutricionales = [];
$db->query('SELECT * FROM objetivos_nutricionales 
            WHERE id_usuario = :user_id AND activo = 1 
            ORDER BY fecha_inicio DESC LIMIT 1');
$db->bind(':user_id', $user['id']);
$objetivosNutricionales = $db->single();

// Obtener tip del día
$tip_del_dia = null;

try {
    $stmt = $pdo->prepare("SELECT id, titulo, contenido FROM tips WHERE activo = TRUE");
    $stmt->execute();
    $todos_tips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($todos_tips) {
        $semilla = date('Ymd');
        srand($semilla);
        shuffle($todos_tips);
        $tip_del_dia = $todos_tips[0];
        srand(); // restaurar aleatoriedad
    }
} catch (PDOException $e) {
    $tip_del_dia = [
        'titulo' => '¡Ups!',
        'contenido' => 'No se pudo cargar el tip del día. Inténtalo más tarde.'
    ];
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
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="sidebar-toggle me-2" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <img src="/assets/images/gym (1).png" alt="Logo" style="height: 65px;" class="me-2">
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
                        <li><a class="dropdown-item" href="user/pagos/pagos.php"><i class="fas fa-credit-card me-2"></i>Pagos</a></li>
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
                <a class="nav-link" href="/user/nutricion/nutricion.php">
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
                <a class="nav-link" href="../user/tips/tips.php">
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

        
        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">Acciones Rápidas</h4>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="/user/asitencia/registrar_asistencia.php" class="quick-action-btn">
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
                <a href="/user/nutricion/registrar_comida.php" class="quick-action-btn">
                    <div class="text-center">
                        <i class="fas fa-utensils fa-2x mb-2" style="color: #fd7e14;"></i>
                        <h6>Registrar Comida</h6>
                        <small class="text-muted">Trackea tus macros</small>
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
            
            <?php if ($tip_del_dia): ?>
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tip del Día</h5>
                        </div>
                        <div class="card-body">
                            <h6>💡 <?= htmlspecialchars($tip_del_dia['titulo']) ?></h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($tip_del_dia['contenido'])) ?></p>
                            <hr>
                            <a href="user/tips/tips.php" class="btn btn-sm btn-outline-primary">Ver más tips</a>
                        </div>
                    </div>
                </div>
<?php endif; ?>

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