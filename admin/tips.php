<?php
require_once '../config/config.php';
require_once '../config/User.php';
// Verificar permisos de administrador


$user = new User();
$message = '';
$message_type = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_tip':
                $result = add_tip($_POST);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
            
            case 'edit_tip':
                $result = edit_tip($_POST);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
            
            case 'toggle_status':
                $result = toggle_tip_status($_POST['tip_id']);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
            
            case 'delete_tip':
                $result = delete_tip($_POST['tip_id']);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Obtener tips existentes
$tips = get_all_tips();

// Obtener tip para editar si se especifica
$edit_tip = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_tip = get_tip_by_id($_GET['edit']);
}

// Funciones para manejar tips
function add_tip($data) {
    global $pdo;
    
    $required_fields = ['titulo', 'contenido', 'categoria'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "El campo $field es requerido"];
        }
    }
    
    $valid_categories = ['nutricion', 'ejercicio', 'mentalidad', 'recovery', 'general'];
    if (!in_array($data['categoria'], $valid_categories)) {
        return ['success' => false, 'message' => 'Categoría inválida'];
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tips (titulo, contenido, categoria, id_autor) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            gym_sanitize($data['titulo']),
            gym_sanitize($data['contenido']),
            $data['categoria'],
            $_SESSION['user_id']
        ]);
        
        return ['success' => true, 'message' => 'Tip agregado exitosamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al agregar el tip: ' . $e->getMessage()];
    }
}

function edit_tip($data) {
    global $pdo;
    
    $required_fields = ['tip_id', 'titulo', 'contenido', 'categoria'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "El campo $field es requerido"];
        }
    }
    
    $valid_categories = ['nutricion', 'ejercicio', 'mentalidad', 'recovery', 'general'];
    if (!in_array($data['categoria'], $valid_categories)) {
        return ['success' => false, 'message' => 'Categoría inválida'];
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE tips SET titulo = ?, contenido = ?, categoria = ? WHERE id = ?");
        $stmt->execute([
            gym_sanitize($data['titulo']),
            gym_sanitize($data['contenido']),
            $data['categoria'],
            $data['tip_id']
        ]);
        
        return ['success' => true, 'message' => 'Tip actualizado exitosamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al actualizar el tip: ' . $e->getMessage()];
    }
}

function toggle_tip_status($tip_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE tips SET activo = NOT activo WHERE id = ?");
        $stmt->execute([$tip_id]);
        
        return ['success' => true, 'message' => 'Estado del tip actualizado'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al actualizar el estado: ' . $e->getMessage()];
    }
}

function delete_tip($tip_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM tips WHERE id = ?");
        $stmt->execute([$tip_id]);
        
        return ['success' => true, 'message' => 'Tip eliminado exitosamente'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al eliminar el tip: ' . $e->getMessage()];
    }
}

function get_all_tips() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT t.*, u.nombre, u.apellido 
                            FROM tips t 
                            LEFT JOIN usuarios u ON t.id_autor = u.id 
                            ORDER BY t.fecha_creacion DESC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function get_tip_by_id($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM tips WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

function get_category_name($category) {
    $categories = [
        'nutricion' => 'Nutrición',
        'ejercicio' => 'Ejercicio',
        'mentalidad' => 'Mentalidad',
        'recovery' => 'Recuperación',
        'general' => 'General'
    ];
    return $categories[$category] ?? $category;
}

function get_category_color($category) {
    $colors = [
        'nutricion' => 'bg-green-100 text-green-800',
        'ejercicio' => 'bg-blue-100 text-blue-800',
        'mentalidad' => 'bg-purple-100 text-purple-800',
        'recovery' => 'bg-yellow-100 text-yellow-800',
        'general' => 'bg-gray-100 text-gray-800'
    ];
    return $colors[$category] ?? 'bg-gray-100 text-gray-800';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tips - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #163ff4ff 0%, #144afcff 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1b44fcff 0%, #2137fbff 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(245, 87, 108, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(79, 172, 254, 0.4);
        }
        
        .input-focus {
            transition: all 0.3s ease;
        }
        
        .input-focus:focus {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.2);
        }
        
        .table-row {
            transition: all 0.3s ease;
        }
        
        .table-row:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            transform: translateX(4px);
        }
        
        .category-badge {
            position: relative;
            overflow: hidden;
        }
        
        .category-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .category-badge:hover::before {
            left: 100%;
        }
        
        .floating-action {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 50;
        }
        
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        
        .slide-up {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .icon-bounce {
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header mejorado -->
        <header class="gradient-bg shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <a href="/pantalla_admin.php" class="text-white/80 hover:text-white mr-4 transition-colors duration-300">
                            <i class="fas fa-arrow-left text-xl"></i>
                        </a>
                        <h1 class="text-3xl font-bold text-white">
                            <i class="fas fa-lightbulb text-yellow-300 mr-3 icon-bounce"></i>
                            Gestión de Tips
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-full px-4 py-2">
                            <span class="text-sm text-white/90">
                                <i class="fas fa-user-circle mr-2"></i>
                                Bienvenido, <?php echo $_SESSION['user_nombre']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Mensajes de alerta mejorados -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl <?php echo $message_type === 'success' ? 'bg-gradient-to-r from-green-50 to-emerald-50 text-green-800 border border-green-200' : 'bg-gradient-to-r from-red-50 to-rose-50 text-red-800 border border-red-200'; ?> slide-up">
                    <div class="flex items-center">
                        <div class="<?php echo $message_type === 'success' ? 'bg-green-100' : 'bg-red-100'; ?> rounded-full p-2 mr-3">
                            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-triangle text-red-600'; ?>"></i>
                        </div>
                        <span class="font-medium"><?php echo $message; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulario para agregar/editar tip mejorado -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 mb-8 card-hover slide-up">
                <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-t-2xl">
                    <h2 class="text-2xl font-bold text-gray-900">
                        <div class="bg-blue-100 rounded-full p-3 inline-block mr-3">
                            <i class="fas fa-plus-circle text-blue-600 text-xl"></i>
                        </div>
                        <?php echo $edit_tip ? 'Editar Tip' : 'Agregar Nuevo Tip'; ?>
                    </h2>
                </div>
                <div class="p-8">
                    <form method="POST" class="space-y-8">
                        <input type="hidden" name="action" value="<?php echo $edit_tip ? 'edit_tip' : 'add_tip'; ?>">
                        <?php if ($edit_tip): ?>
                            <input type="hidden" name="tip_id" value="<?php echo $edit_tip['id']; ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-heading text-indigo-500 mr-2"></i>
                                    Título del Tip
                                </label>
                                <input type="text" 
                                       id="titulo" 
                                       name="titulo" 
                                       value="<?php echo $edit_tip ? htmlspecialchars($edit_tip['titulo']) : ''; ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent input-focus"
                                       placeholder="Ingresa un título atractivo"
                                       required>
                            </div>

                            <div>
                                <label for="categoria" class="block text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-tags text-purple-500 mr-2"></i>
                                    Categoría
                                </label>
                                <select id="categoria" 
                                        name="categoria" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent input-focus"
                                        required>
                                    <option value="">Seleccionar categoría</option>
                                    <option value="nutricion" <?php echo ($edit_tip && $edit_tip['categoria'] === 'nutricion') ? 'selected' : ''; ?>>🥗 Nutrición</option>
                                    <option value="ejercicio" <?php echo ($edit_tip && $edit_tip['categoria'] === 'ejercicio') ? 'selected' : ''; ?>>💪 Ejercicio</option>
                                    <option value="mentalidad" <?php echo ($edit_tip && $edit_tip['categoria'] === 'mentalidad') ? 'selected' : ''; ?>>🧠 Mentalidad</option>
                                    <option value="recovery" <?php echo ($edit_tip && $edit_tip['categoria'] === 'recovery') ? 'selected' : ''; ?>>💆 Recuperación</option>
                                    <option value="general" <?php echo ($edit_tip && $edit_tip['categoria'] === 'general') ? 'selected' : ''; ?>>⭐ General</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="contenido" class="block text-sm font-semibold text-gray-700 mb-3">
                                <i class="fas fa-align-left text-green-500 mr-2"></i>
                                Contenido del Tip
                            </label>
                            <textarea id="contenido" 
                                      name="contenido" 
                                      rows="8"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent input-focus resize-none"
                                      placeholder="Escribe un tip valioso e inspirador..."
                                      required><?php echo $edit_tip ? htmlspecialchars($edit_tip['contenido']) : ''; ?></textarea>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <?php if ($edit_tip): ?>
                                <a href="admin_tips.php" class="px-6 py-3 text-gray-600 hover:text-gray-800 font-medium rounded-xl transition-colors duration-300">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancelar
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="px-8 py-3 btn-primary text-white rounded-xl font-semibold">
                                <i class="fas fa-save mr-2"></i>
                                <?php echo $edit_tip ? 'Actualizar Tip' : 'Agregar Tip'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de tips existentes mejorada -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 card-hover slide-up">
                <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-slate-50 rounded-t-2xl">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">
                            <div class="bg-gray-100 rounded-full p-3 inline-block mr-3">
                                <i class="fas fa-list text-gray-600 text-xl"></i>
                            </div>
                            Tips Existentes
                        </h2>
                        <div class="bg-indigo-100 text-indigo-800 px-4 py-2 rounded-full font-semibold">
                            <?php echo count($tips); ?> tips
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                    <i class="fas fa-heading mr-2"></i>Título
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                    <i class="fas fa-tags mr-2"></i>Categoría
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                    <i class="fas fa-user mr-2"></i>Autor
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                    <i class="fas fa-toggle-on mr-2"></i>Estado
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                    <i class="fas fa-calendar mr-2"></i>Fecha
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                    <i class="fas fa-cog mr-2"></i>Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($tips)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center text-gray-500">
                                        <div class="mb-4">
                                            <i class="fas fa-lightbulb text-6xl text-gray-300 pulse-animation"></i>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No hay tips registrados</h3>
                                        <p class="text-sm text-gray-500">Agrega el primer tip usando el formulario de arriba</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tips as $tip): ?>
                                    <tr class="table-row">
                                        <td class="px-6 py-6">
                                            <div class="text-sm font-semibold text-gray-900 mb-1">
                                                <?php echo htmlspecialchars($tip['titulo']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 bg-gray-50 rounded-lg p-2">
                                                <?php echo htmlspecialchars(substr($tip['contenido'], 0, 100)); ?>...
                                            </div>
                                        </td>
                                        <td class="px-6 py-6">
                                            <span class="category-badge inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo get_category_color($tip['categoria']); ?>">
                                                <?php echo get_category_name($tip['categoria']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-6">
                                            <div class="flex items-center">
                                                <div class="bg-indigo-100 rounded-full p-2 mr-3">
                                                    <i class="fas fa-user text-indigo-600"></i>
                                                </div>
                                                <span class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($tip['nombre'] . ' ' . $tip['apellido']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-6">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $tip['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <i class="fas <?php echo $tip['activo'] ? 'fa-check' : 'fa-times'; ?> mr-1"></i>
                                                <?php echo $tip['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-6">
                                            <div class="text-sm text-gray-900 font-medium">
                                                <i class="fas fa-calendar-alt text-gray-400 mr-2"></i>
                                                <?php echo date('d/m/Y', strtotime($tip['fecha_creacion'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-6">
                                            <div class="flex space-x-3">
                                                <a href="?edit=<?php echo $tip['id']; ?>" 
                                                   class="bg-blue-100 text-blue-700 p-2 rounded-lg hover:bg-blue-200 transition-colors duration-300"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de cambiar el estado?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="tip_id" value="<?php echo $tip['id']; ?>">
                                                    <button type="submit" 
                                                            class="<?php echo $tip['activo'] ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'; ?> p-2 rounded-lg transition-colors duration-300"
                                                            title="<?php echo $tip['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="fas <?php echo $tip['activo'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este tip?')">
                                                    <input type="hidden" name="action" value="delete_tip">
                                                    <input type="hidden" name="tip_id" value="<?php echo $tip['id']; ?>">
                                                    <button type="submit" 
                                                            class="bg-red-100 text-red-700 p-2 rounded-lg hover:bg-red-200 transition-colors duration-300"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón flotante para agregar tip -->
    <div class="floating-action">
        <a href="#" onclick="document.getElementById('titulo').focus(); return false;" 
           class="btn-success text-white p-4 rounded-full shadow-lg hover:shadow-xl transition-all duration-300">
            <i class="fas fa-plus text-xl"></i>
        </a>
    </div>
</body>
</html>
    <script>
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]');
            alerts.forEach(alert => {
                if (alert.parentElement) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>