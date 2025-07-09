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
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <a href="/pantalla_admin.php" class="text-gray-500 hover:text-gray-700 mr-4">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                            Gestión de Tips
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500">
                            Bienvenido, <?php echo $_SESSION['user_nombre']; ?>
                        </span>
                        
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Mensajes de alerta -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                    <div class="flex">
                        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulario para agregar/editar tip -->
            <div class="bg-white rounded-lg shadow-sm border mb-8">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-900">
                        <i class="fas fa-plus-circle text-blue-500 mr-2"></i>
                        <?php echo $edit_tip ? 'Editar Tip' : 'Agregar Nuevo Tip'; ?>
                    </h2>
                </div>
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="<?php echo $edit_tip ? 'edit_tip' : 'add_tip'; ?>">
                        <?php if ($edit_tip): ?>
                            <input type="hidden" name="tip_id" value="<?php echo $edit_tip['id']; ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="titulo" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-heading text-gray-400 mr-1"></i>
                                    Título del Tip
                                </label>
                                <input type="text" 
                                       id="titulo" 
                                       name="titulo" 
                                       value="<?php echo $edit_tip ? htmlspecialchars($edit_tip['titulo']) : ''; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       required>
                            </div>

                            <div>
                                <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-tags text-gray-400 mr-1"></i>
                                    Categoría
                                </label>
                                <select id="categoria" 
                                        name="categoria" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                    <option value="">Seleccionar categoría</option>
                                    <option value="nutricion" <?php echo ($edit_tip && $edit_tip['categoria'] === 'nutricion') ? 'selected' : ''; ?>>Nutrición</option>
                                    <option value="ejercicio" <?php echo ($edit_tip && $edit_tip['categoria'] === 'ejercicio') ? 'selected' : ''; ?>>Ejercicio</option>
                                    <option value="mentalidad" <?php echo ($edit_tip && $edit_tip['categoria'] === 'mentalidad') ? 'selected' : ''; ?>>Mentalidad</option>
                                    <option value="recovery" <?php echo ($edit_tip && $edit_tip['categoria'] === 'recovery') ? 'selected' : ''; ?>>Recuperación</option>
                                    <option value="general" <?php echo ($edit_tip && $edit_tip['categoria'] === 'general') ? 'selected' : ''; ?>>General</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="contenido" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-align-left text-gray-400 mr-1"></i>
                                Contenido del Tip
                            </label>
                            <textarea id="contenido" 
                                      name="contenido" 
                                      rows="6"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Escribe el contenido del tip aquí..."
                                      required><?php echo $edit_tip ? htmlspecialchars($edit_tip['contenido']) : ''; ?></textarea>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <?php if ($edit_tip): ?>
                                <a href="admin_tips.php" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                                    <i class="fas fa-times mr-1"></i>
                                    Cancelar
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-1"></i>
                                <?php echo $edit_tip ? 'Actualizar Tip' : 'Agregar Tip'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de tips existentes -->
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-900">
                        <i class="fas fa-list text-gray-500 mr-2"></i>
                        Tips Existentes (<?php echo count($tips); ?>)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Autor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($tips)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-lightbulb text-4xl text-gray-300 mb-4"></i>
                                        <p>No hay tips registrados</p>
                                        <p class="text-sm">Agrega el primer tip usando el formulario de arriba</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tips as $tip): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($tip['titulo']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <?php echo htmlspecialchars(substr($tip['contenido'], 0, 100)); ?>...
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo get_category_color($tip['categoria']); ?>">
                                                <?php echo get_category_name($tip['categoria']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($tip['nombre'] . ' ' . $tip['apellido']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $tip['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <i class="fas <?php echo $tip['activo'] ? 'fa-check' : 'fa-times'; ?> mr-1"></i>
                                                <?php echo $tip['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($tip['fecha_creacion'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="?edit=<?php echo $tip['id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de cambiar el estado?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="tip_id" value="<?php echo $tip['id']; ?>">
                                                    <button type="submit" 
                                                            class="<?php echo $tip['activo'] ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>"
                                                            title="<?php echo $tip['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="fas <?php echo $tip['activo'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este tip?')">
                                                    <input type="hidden" name="action" value="delete_tip">
                                                    <input type="hidden" name="tip_id" value="<?php echo $tip['id']; ?>">
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-900"
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