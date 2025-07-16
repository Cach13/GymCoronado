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
        'nutricion' => 'bg-blue-100 text-blue-800',
        'ejercicio' => 'bg-indigo-100 text-indigo-800',
        'mentalidad' => 'bg-sky-100 text-sky-800',
        'recovery' => 'bg-cyan-100 text-cyan-800',
        'general' => 'bg-slate-100 text-slate-800'
    ];
    
    return $colores[$categoria] ?? 'bg-blue-100 text-blue-800';
}

$usuario_actual = gym_get_logged_in_user();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tips de Entrenamiento - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-600 to-blue-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <a href="/dashboard.php" class="text-blue-100 hover:text-white mr-4 transition-colors">
                        <i class="fas fa-arrow-left text-lg"></i>
                    </a>
                    <h1 class="text-3xl font-bold text-white">Tips de Entrenamiento</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-blue-100">
                        Hola, <strong class="text-white"><?php echo htmlspecialchars($usuario_actual['nombre']); ?></strong>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Filtros y búsqueda -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border border-blue-100">
            <form method="GET" class="space-y-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Búsqueda -->
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-blue-700 mb-2">
                            <i class="fas fa-search mr-2"></i>Buscar tips
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                name="buscar" 
                                value="<?php echo htmlspecialchars($busqueda); ?>"
                                placeholder="Buscar por título o contenido..."
                                class="w-full pl-10 pr-4 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            >
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-blue-400"></i>
                        </div>
                    </div>
                    
                    <!-- Filtro por categoría -->
                    <div class="md:w-64">
                        <label class="block text-sm font-medium text-blue-700 mb-2">
                            <i class="fas fa-filter mr-2"></i>Categoría
                        </label>
                        <select 
                            name="categoria" 
                            class="w-full px-3 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
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
                
                <div class="flex gap-2">
                    <button 
                        type="submit"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md hover:shadow-lg"
                    >
                        <i class="fas fa-filter mr-2"></i>
                        Filtrar
                    </button>
                    <a 
                        href="tips.php"
                        class="bg-gradient-to-r from-slate-500 to-slate-600 text-white px-6 py-3 rounded-lg hover:from-slate-600 hover:to-slate-700 transition-all duration-200 shadow-md hover:shadow-lg"
                    >
                        <i class="fas fa-times mr-2"></i>
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-xl shadow-lg text-center text-white">
                <div class="text-3xl font-bold"><?php echo array_sum($conteos); ?></div>
                <div class="text-sm text-blue-100">Total Tips</div>
            </div>
            <?php 
            $gradient_colors = [
                'nutricion' => 'from-blue-400 to-blue-500',
                'ejercicio' => 'from-indigo-400 to-indigo-500',
                'mentalidad' => 'from-sky-400 to-sky-500',
                'recovery' => 'from-cyan-400 to-cyan-500',
                'general' => 'from-slate-400 to-slate-500'
            ];
            foreach (['nutricion', 'ejercicio', 'mentalidad', 'recovery', 'general'] as $cat): ?>
                <div class="bg-gradient-to-br <?php echo $gradient_colors[$cat]; ?> p-6 rounded-xl shadow-lg text-center text-white hover:shadow-xl transition-shadow">
                    <div class="text-2xl mb-1"><?php echo obtener_icono_categoria($cat); ?></div>
                    <div class="text-2xl font-bold"><?php echo $conteos[$cat] ?? 0; ?></div>
                    <div class="text-xs text-white opacity-90"><?php echo formatear_categoria($cat); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Resultados -->
        <div class="mb-6">
            <p class="text-blue-700 font-medium">
                <?php if ($busqueda): ?>
                    Mostrando <?php echo count($tips); ?> resultado(s) para "<strong class="text-blue-900"><?php echo htmlspecialchars($busqueda); ?></strong>"
                <?php else: ?>
                    Mostrando <?php echo count($tips); ?> tip(s)
                <?php endif; ?>
                
                <?php if ($categoria_seleccionada !== 'todas'): ?>
                    en la categoría <strong class="text-blue-900"><?php echo formatear_categoria($categoria_seleccionada); ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <!-- Lista de tips -->
        <?php if (empty($tips)): ?>
            <div class="bg-white rounded-xl shadow-lg p-12 text-center border border-blue-100">
                <i class="fas fa-lightbulb text-6xl text-blue-300 mb-6"></i>
                <h3 class="text-xl font-semibold text-blue-900 mb-3">No se encontraron tips</h3>
                <p class="text-blue-600">
                    <?php if ($busqueda || $categoria_seleccionada !== 'todas'): ?>
                        Intenta cambiar los filtros o realizar una búsqueda diferente.
                    <?php else: ?>
                        Aún no hay tips disponibles. ¡Vuelve pronto!
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($tips as $tip): ?>
                    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-blue-100 hover:border-blue-200 transform hover:-translate-y-1">
                        <!-- Header del tip -->
                        <div class="p-6 pb-4">
                            <div class="flex items-start justify-between mb-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo obtener_color_categoria($tip['categoria']); ?> border border-opacity-20">
                                    <?php echo obtener_icono_categoria($tip['categoria']); ?> 
                                    <?php echo formatear_categoria($tip['categoria']); ?>
                                </span>
                                <span class="text-xs text-blue-500 font-medium">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    <?php echo date('d/m/Y', strtotime($tip['fecha_creacion'])); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-bold text-blue-900 mb-3 line-clamp-2">
                                <?php echo htmlspecialchars($tip['titulo']); ?>
                            </h3>
                        </div>

                        <!-- Contenido del tip -->
                        <div class="px-6 pb-4">
                            <div class="text-blue-700 text-sm leading-relaxed">
                                <?php 
                                $contenido = htmlspecialchars($tip['contenido']);
                                $contenido_corto = strlen($contenido) > 150 ? substr($contenido, 0, 150) . '...' : $contenido;
                                ?>
                                <p class="tip-content-preview"><?php echo $contenido_corto; ?></p>
                                
                                <?php if (strlen($tip['contenido']) > 150): ?>
                                    <p class="tip-content-full hidden"><?php echo $contenido; ?></p>
                                    <button class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-3 toggle-content transition-colors">
                                        Leer más <i class="fas fa-chevron-down ml-1"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Footer del tip -->
                        <?php if ($tip['autor_nombre']): ?>
                            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-b-xl border-t border-blue-100">
                                <div class="flex items-center text-sm text-blue-600">
                                    <i class="fas fa-user-circle mr-2 text-blue-500"></i>
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
                    const card = this.closest('.bg-white');
                    const preview = card.querySelector('.tip-content-preview');
                    const full = card.querySelector('.tip-content-full');
                    const icon = this.querySelector('i');
                    
                    if (full.classList.contains('hidden')) {
                        preview.classList.add('hidden');
                        full.classList.remove('hidden');
                        this.innerHTML = 'Leer menos <i class="fas fa-chevron-up ml-1"></i>';
                    } else {
                        preview.classList.remove('hidden');
                        full.classList.add('hidden');
                        this.innerHTML = 'Leer más <i class="fas fa-chevron-down ml-1"></i>';
                    }
                });
            });
        });

        // Auto-submit del formulario cuando cambia la categoría
        document.querySelector('select[name="categoria"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Efectos adicionales para el tema azul */
        .bg-white {
            backdrop-filter: blur(10px);
        }
        
        /* Animaciones suaves */
        * {
            transition: all 0.2s ease;
        }
        
        /* Hover effects mejorados */
        .bg-white:hover {
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.1), 0 10px 10px -5px rgba(59, 130, 246, 0.04);
        }
    </style>
</body>
</html>