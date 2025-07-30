<?php
require_once('../../config/config.php');
require_once('../../config/User.php');

// Verificar permisos
gym_check_permission('cliente');

// Inicializar objeto User
$user = new User();

// Obtener categoría seleccionada
$categoria_seleccionada = isset($_GET['categoria']) ? gym_sanitize($_GET['categoria']) : 'todas';

// Obtener búsqueda
$busqueda = isset($_GET['buscar']) ? gym_sanitize($_GET['buscar']) : '';

// Función para obtener tips
function obtener_tips($categoria = null, $busqueda = '') {
    global $pdo;
    
    $query = "SELECT t.*, u.nombre as autor_nombre, u.apellido as autor_apellido 
              FROM tips t 
              LEFT JOIN usuarios u ON t.id_autor = u.id 
              WHERE t.activo = 1";
    
    $params = [];
    
    if ($categoria && $categoria !== 'todas') {
        $query .= " AND t.categoria = :categoria";
        $params[':categoria'] = $categoria;
    }
    
    if (!empty($busqueda)) {
        $query .= " AND (t.titulo LIKE :busqueda OR t.contenido LIKE :busqueda)";
        $params[':busqueda'] = '%' . $busqueda . '%';
    }
    
    $query .= " ORDER BY t.fecha_creacion DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener conteo por categoría
function obtener_conteo_categorias() {
    global $pdo;
    
    $query = "SELECT categoria, COUNT(*) as total 
              FROM tips 
              WHERE activo = 1 
              GROUP BY categoria";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $conteos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $conteos[$row['categoria']] = $row['total'];
    }
    
    return $conteos;
}

// Obtener tips
$tips = obtener_tips($categoria_seleccionada, $busqueda);
$conteos = obtener_conteo_categorias();

// Función para formatear categorías
function formatear_categoria($categoria) {
    $categorias = [
        'nutricion' => 'Nutrición',
        'ejercicio' => 'Ejercicio',
        'mentalidad' => 'Mentalidad',
        'recovery' => 'Recuperación',
        'general' => 'General'
    ];
    
    return $categorias[$categoria] ?? ucfirst($categoria);
}

// Función para obtener icono de categoría
function obtener_icono_categoria($categoria) {
    $iconos = [
        'nutricion' => '🥗',
        'ejercicio' => '💪',
        'mentalidad' => '🧠',
        'recovery' => '😴',
        'general' => '💡'
    ];
    
    return $iconos[$categoria] ?? '💡';
}

// Función para obtener color de categoría - Paleta azul
function obtener_color_categoria($categoria) {
    $colores = [
        'nutricion' => 'category-nutricion',
        'ejercicio' => 'category-ejercicio',
        'mentalidad' => 'category-mentalidad',
        'recovery' => 'category-recovery',
        'general' => 'category-general'
    ];
    
    return $colores[$categoria] ?? 'category-general';
}

$usuario_actual = gym_get_logged_in_user();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tips de Entrenamiento - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
            min-height: 100vh;
            color: #1e40af;
            padding-top: 120px; /* Espaciado para el header fijo */
        }

        /* Header con efecto glass mejorado */
        .glass-header {
            background: linear-gradient(135deg, rgb(20, 66, 233) 0%, rgba(52, 76, 161, 0.95) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 100px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-section img {
            height: 60px;
            width: auto;
        }

        .site-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            margin: 0;
        }

        .header-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            border-radius: 10px;
            padding: 12px 20px;
            text-decoration: none;
            color: white;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            backdrop-filter: blur(10px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Contenedor principal */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Filtros y búsqueda */
        .filter-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1);
            padding: 32px;
            margin-bottom: 32px;
            border: 1px solid #dbeafe;
            backdrop-filter: blur(10px);
        }

        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .filter-row {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        @media (min-width: 768px) {
            .filter-row {
                flex-direction: row;
            }
        }

        .search-container {
            flex: 1;
        }

        .category-container {
            width: 100%;
        }

        @media (min-width: 768px) {
            .category-container {
                width: 256px;
            }
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #1d4ed8;
            margin-bottom: 8px;
        }

        .form-label i {
            margin-right: 8px;
        }

        .search-input-container {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            padding-left: 40px;
            border: 2px solid #bfdbfe;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #60a5fa;
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #bfdbfe;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            transition: all 0.2s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .button-row {
            display: flex;
            gap: 8px;
        }

        .btn-filter, .btn-clear {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            border: none;
            font-size: 16px;
            transition: all 0.2s ease;
        }

        .btn-filter {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }

        .btn-filter:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
            transform: translateY(-1px);
        }

        .btn-clear {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(100, 116, 139, 0.2);
        }

        .btn-clear:hover {
            background: linear-gradient(135deg, #475569 0%, #334155 100%);
            box-shadow: 0 10px 15px -3px rgba(100, 116, 139, 0.3);
            transform: translateY(-1px);
        }

        /* Estadísticas rápidas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        @media (min-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        .stat-card {
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            text-align: center;
            color: white;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .stat-total {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .stat-nutricion {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        }

        .stat-ejercicio {
            background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
        }

        .stat-mentalidad {
            background: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 100%);
        }

        .stat-recovery {
            background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
        }

        .stat-general {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-large {
            font-size: 48px;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }

        .stat-emoji {
            font-size: 32px;
            margin-bottom: 4px;
        }

        /* Resultados */
        .results-info {
            margin-bottom: 24px;
            color: #1d4ed8;
            font-weight: 500;
        }

        .results-highlight {
            color: #1e3a8a;
            font-weight: 700;
        }

        /* Lista de tips */
        .tips-grid {
            display: grid;
            gap: 32px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .tips-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .tips-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .tip-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1);
            border: 1px solid #dbeafe;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .tip-card:hover {
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.15);
            border-color: #bfdbfe;
            transform: translateY(-4px);
        }

        .tip-header {
            padding: 24px 24px 16px;
        }

        .tip-meta {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid;
            border-opacity: 0.2;
        }

        .category-nutricion {
            background-color: #dbeafe;
            color: #1e40af;
            border-color: #1e40af;
        }

        .category-ejercicio {
            background-color: #e0e7ff;
            color: #3730a3;
            border-color: #3730a3;
        }

        .category-mentalidad {
            background-color: #e0f2fe;
            color: #0c4a6e;
            border-color: #0c4a6e;
        }

        .category-recovery {
            background-color: #cffafe;
            color: #164e63;
            border-color: #164e63;
        }

        .category-general {
            background-color: #f1f5f9;
            color: #334155;
            border-color: #334155;
        }

        .tip-date {
            font-size: 12px;
            color: #3b82f6;
            font-weight: 500;
        }

        .tip-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 12px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .tip-content {
            padding: 0 24px 16px;
        }

        .tip-text {
            color: #1d4ed8;
            font-size: 14px;
            line-height: 1.6;
        }

        .tip-content-full {
            display: none;
        }

        .tip-content-full.show {
            display: block;
        }

        .tip-content-preview.hide {
            display: none;
        }

        .toggle-content {
            background: none;
            border: none;
            color: #2563eb;
            font-size: 14px;
            font-weight: 500;
            margin-top: 12px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .toggle-content:hover {
            color: #1d4ed8;
        }

        .tip-footer {
            padding: 16px 24px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            border-top: 1px solid #dbeafe;
        }

        .tip-author {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #2563eb;
        }

        .tip-author i {
            margin-right: 8px;
            color: #3b82f6;
        }

        /* Estado vacío */
        .empty-state {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1);
            padding: 48px;
            text-align: center;
            border: 1px solid #dbeafe;
        }

        .empty-icon {
            font-size: 96px;
            color: #bfdbfe;
            margin-bottom: 24px;
        }

        .empty-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 12px;
        }

        .empty-text {
            color: #2563eb;
        }

        /* Responsive adjustments */
        @media (max-width: 767px) {
            .main-container {
                padding: 0 12px;
            }
            
            .filter-section {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .tip-header, .tip-content, .tip-footer {
                padding-left: 16px;
                padding-right: 16px;
            }
            
            .site-title {
                font-size: 1.4rem;
            }
            
            .header-container {
                padding: 0 12px;
            }
        }
    </style>
</head>

<body>
    <!-- Header con efecto glass mejorado -->
    <header class="glass-header">
        <div class="header-container">
            <!-- Logo y título -->
            <div class="logo-section">
                <img src="/assets/images/gym (1).png" alt="Logo">
                <h1 class="site-title">Tips de Entrenamiento</h1>
            </div>
            
            <!-- Botones -->
            <div class="header-buttons">
                <a href="/dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
                    <span>Volver</span>
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <!-- Filtros y búsqueda -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <!-- Búsqueda -->
                    <div class="search-container">
                        <label class="form-label">
                            <i class="fas fa-search"></i>Buscar tips
                        </label>
                        <div class="search-input-container">
                            <input 
                                type="text" 
                                name="buscar" 
                                value="<?php echo htmlspecialchars($busqueda); ?>"
                                placeholder="Buscar por título o contenido..."
                                class="form-input"
                            >
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                    
                    <!-- Filtro por categoría -->
                    <div class="category-container">
                        <label class="form-label">
                            <i class="fas fa-filter"></i>Categoría
                        </label>
                        <select name="categoria" class="form-select">
                            <option value="todas" <?php echo $categoria_seleccionada === 'todas' ? 'selected' : ''; ?>>
                                Todas las categorías
                            </option>
                            <option value="nutricion" <?php echo $categoria_seleccionada === 'nutricion' ? 'selected' : ''; ?>>
                                🥗 Nutrición <?php echo isset($conteos['nutricion']) ? '(' . $conteos['nutricion'] . ')' : ''; ?>
                            </option>
                            <option value="ejercicio" <?php echo $categoria_seleccionada === 'ejercicio' ? 'selected' : ''; ?>>
                                💪 Ejercicio <?php echo isset($conteos['ejercicio']) ? '(' . $conteos['ejercicio'] . ')' : ''; ?>
                            </option>
                            <option value="mentalidad" <?php echo $categoria_seleccionada === 'mentalidad' ? 'selected' : ''; ?>>
                                🧠 Mentalidad <?php echo isset($conteos['mentalidad']) ? '(' . $conteos['mentalidad'] . ')' : ''; ?>
                            </option>
                            <option value="recovery" <?php echo $categoria_seleccionada === 'recovery' ? 'selected' : ''; ?>>
                                😴 Recuperación <?php echo isset($conteos['recovery']) ? '(' . $conteos['recovery'] . ')' : ''; ?>
                            </option>
                            <option value="general" <?php echo $categoria_seleccionada === 'general' ? 'selected' : ''; ?>>
                                💡 General <?php echo isset($conteos['general']) ? '(' . $conteos['general'] . ')' : ''; ?>
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="button-row">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter" style="margin-right: 8px;"></i>
                        Filtrar
                    </button>
                    <a href="tips.php" class="btn-clear">
                        <i class="fas fa-times" style="margin-right: 8px;"></i>
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-number stat-large"><?php echo array_sum($conteos); ?></div>
                <div class="stat-label">Total Tips</div>
            </div>
            <div class="stat-card stat-nutricion">
                <div class="stat-emoji">🥗</div>
                <div class="stat-number"><?php echo $conteos['nutricion'] ?? 0; ?></div>
                <div class="stat-label">Nutrición</div>
            </div>
            <div class="stat-card stat-ejercicio">
                <div class="stat-emoji">💪</div>
                <div class="stat-number"><?php echo $conteos['ejercicio'] ?? 0; ?></div>
                <div class="stat-label">Ejercicio</div>
            </div>
            <div class="stat-card stat-mentalidad">
                <div class="stat-emoji">🧠</div>
                <div class="stat-number"><?php echo $conteos['mentalidad'] ?? 0; ?></div>
                <div class="stat-label">Mentalidad</div>
            </div>
            <div class="stat-card stat-recovery">
                <div class="stat-emoji">😴</div>
                <div class="stat-number"><?php echo $conteos['recovery'] ?? 0; ?></div>
                <div class="stat-label">Recuperación</div>
            </div>
            <div class="stat-card stat-general">
                <div class="stat-emoji">💡</div>
                <div class="stat-number"><?php echo $conteos['general'] ?? 0; ?></div>
                <div class="stat-label">General</div>
            </div>
        </div>

        <!-- Resultados -->
        <div class="results-info">
            <?php if ($busqueda): ?>
                Mostrando <?php echo count($tips); ?> resultado(s) para "<span class="results-highlight"><?php echo htmlspecialchars($busqueda); ?></span>"
            <?php else: ?>
                Mostrando <?php echo count($tips); ?> tip(s)
            <?php endif; ?>
            
            <?php if ($categoria_seleccionada !== 'todas'): ?>
                en la categoría <span class="results-highlight"><?php echo formatear_categoria($categoria_seleccionada); ?></span>
            <?php endif; ?>
        </div>

        <!-- Lista de tips -->
        <?php if (empty($tips)): ?>
            <div class="empty-state">
                <i class="fas fa-lightbulb empty-icon"></i>
                <h3 class="empty-title">No se encontraron tips</h3>
                <p class="empty-text">
                    <?php if ($busqueda || $categoria_seleccionada !== 'todas'): ?>
                        Intenta cambiar los filtros o realizar una búsqueda diferente.
                    <?php else: ?>
                        Aún no hay tips disponibles. ¡Vuelve pronto!
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="tips-grid">
                <?php foreach ($tips as $tip): ?>
                    <div class="tip-card">
                        <!-- Header del tip -->
                        <div class="tip-header">
                            <div class="tip-meta">
                                <span class="category-badge <?php echo obtener_color_categoria($tip['categoria']); ?>">
                                    <?php echo obtener_icono_categoria($tip['categoria']); ?> 
                                    <?php echo formatear_categoria($tip['categoria']); ?>
                                </span>
                                <span class="tip-date">
                                    <i class="fas fa-calendar-alt" style="margin-right: 4px;"></i>
                                    <?php echo date('d/m/Y', strtotime($tip['fecha_creacion'])); ?>
                                </span>
                            </div>
                            
                            <h3 class="tip-title">
                                <?php echo htmlspecialchars($tip['titulo']); ?>
                            </h3>
                        </div>

                        <!-- Contenido del tip -->
                        <div class="tip-content">
                            <div class="tip-text">
                                <?php 
                                $contenido = htmlspecialchars($tip['contenido']);
                                $contenido_corto = strlen($contenido) > 150 ? substr($contenido, 0, 150) . '...' : $contenido;
                                ?>
                                <p class="tip-content-preview"><?php echo $contenido_corto; ?></p>
                                
                                <?php if (strlen($tip['contenido']) > 150): ?>
                                    <p class="tip-content-full"><?php echo $contenido; ?></p>
                                    <button class="toggle-content">
                                        Leer más <i class="fas fa-chevron-down" style="margin-left: 4px;"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Footer del tip -->
                        <?php if ($tip['autor_nombre']): ?>
                            <div class="tip-footer">
                                <div class="tip-author">
                                    <i class="fas fa-user-circle"></i>
                                    <span>Por <strong><?php echo htmlspecialchars($tip['autor_nombre'] . ' ' . $tip['autor_apellido']); ?></strong></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Funcionalidad para expandir/contraer contenido
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-content');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const card = this.closest('.tip-card');
                    const preview = card.querySelector('.tip-content-preview');
                    const full = card.querySelector('.tip-content-full');
                    
                    if (full.classList.contains('show')) {
                        preview.classList.remove('hide');
                        full.classList.remove('show');
                        this.innerHTML = 'Leer más <i class="fas fa-chevron-down" style="margin-left: 4px;"></i>';
                    } else {
                        preview.classList.add('hide');
                        full.classList.add('show');
                        this.innerHTML = 'Leer menos <i class="fas fa-chevron-up" style="margin-left: 4px;"></i>';
                    }
                });
            });
        });

        // Auto-submit del formulario cuando cambia la categoría
        document.querySelector('select[name="categoria"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>