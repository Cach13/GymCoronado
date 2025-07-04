<?php

require_once '../config/config.php';
require_once '../config/User.php';

// Verificar permisos antes de cualquier acción
gym_check_permission('entrenador'); // Requiere al menos rol de entrenador

// Inicializar objeto User
$user = new User();

// Agregar un ejercicio preestablecido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar_ejercicio') {
    // Sanitizar datos de entrada
    $data = [
        'nombre' => gym_sanitize($_POST['nombre']),
        'grupo_muscular' => gym_sanitize($_POST['grupo_muscular']),
        'series' => intval($_POST['series']),
        'repeticiones' => gym_sanitize($_POST['repeticiones']),
        'tiempo_descanso' => gym_sanitize($_POST['tiempo_descanso']),
        'instrucciones' => gym_sanitize($_POST['instrucciones'] ?? ''),
        'dificultad' => gym_sanitize($_POST['dificultad'] ?? 'intermedio'),
        'equipamiento_necesario' => gym_sanitize($_POST['equipamiento'] ?? ''),
        'imagen_url' => filter_var($_POST['imagen_url'] ?? '', FILTER_SANITIZE_URL)
    ];
    
    $result = $user->agregar_ejercicio_preestablecido($data);
    echo json_encode($result);
    exit;
}

// Editar un ejercicio preestablecido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_ejercicio') {
    $id = intval($_POST['id']);
    $data = [
        'nombre' => gym_sanitize($_POST['nombre']),
        'grupo_muscular' => gym_sanitize($_POST['grupo_muscular']),
        'series' => intval($_POST['series']),
        'repeticiones' => gym_sanitize($_POST['repeticiones']),
        'tiempo_descanso' => gym_sanitize($_POST['tiempo_descanso']),
        'instrucciones' => gym_sanitize($_POST['instrucciones'] ?? ''),
        'dificultad' => gym_sanitize($_POST['dificultad'] ?? 'intermedio'),
        'equipamiento_necesario' => gym_sanitize($_POST['equipamiento'] ?? ''),
        'imagen_url' => filter_var($_POST['imagen_url'] ?? '', FILTER_SANITIZE_URL)
    ];
    
    $result = $user->editar_ejercicio_preestablecido($id, $data);
    echo json_encode($result);
    exit;
}

// Eliminar un ejercicio preestablecido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar_ejercicio') {
    $id = intval($_POST['id']);
    $result = $user->eliminar_ejercicio_preestablecido($id);
    echo json_encode($result);
    exit;
}

// Crear una rutina preestablecida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_rutina') {
    // Sanitizar datos de entrada
    $data = [
        'titulo' => gym_sanitize($_POST['titulo']),
        'descripcion' => gym_sanitize($_POST['descripcion'] ?? ''),
        'categoria' => gym_sanitize($_POST['categoria']),
        'objetivo' => gym_sanitize($_POST['objetivo']),
        'duracion_minutos' => intval($_POST['duracion'] ?? 60)
    ];
    
    // Validar y sanitizar IDs de ejercicios
    $ejercicios_ids = [];
    if (isset($_POST['ejercicios']) && is_array($_POST['ejercicios'])) {
        foreach ($_POST['ejercicios'] as $id) {
            $ejercicios_ids[] = intval($id);
        }
    }
    
    $result = $user->crear_rutina_preestablecida($data, $ejercicios_ids);
    echo json_encode($result);
    exit;
}

// Editar una rutina preestablecida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_rutina') {
    $id = intval($_POST['id']);
    $data = [
        'titulo' => gym_sanitize($_POST['titulo']),
        'descripcion' => gym_sanitize($_POST['descripcion'] ?? ''),
        'categoria' => gym_sanitize($_POST['categoria']),
        'objetivo' => gym_sanitize($_POST['objetivo']),
        'duracion_minutos' => intval($_POST['duracion'] ?? 60)
    ];
    
    $ejercicios_ids = [];
    if (isset($_POST['ejercicios']) && is_array($_POST['ejercicios'])) {
        foreach ($_POST['ejercicios'] as $id_ejercicio) {
            $ejercicios_ids[] = intval($id_ejercicio);
        }
    }
    
    $result = $user->editar_rutina_preestablecida($id, $data, $ejercicios_ids);
    echo json_encode($result);
    exit;
}

// Eliminar una rutina preestablecida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar_rutina') {
    $id = intval($_POST['id']);
    $result = $user->eliminar_rutina_preestablecida($id);
    echo json_encode($result);
    exit;
}

// Obtener ejercicios para mostrar
$ejercicios_pierna = $user->obtener_ejercicios_preestablecidos('piernas');
$ejercicios_pecho = $user->obtener_ejercicios_preestablecidos('pecho');
$ejercicios_espalda = $user->obtener_ejercicios_preestablecidos('espalda');
$ejercicios_brazos = $user->obtener_ejercicios_preestablecidos('brazos');
$ejercicios_hombros = $user->obtener_ejercicios_preestablecidos('hombros');
$ejercicios_core = $user->obtener_ejercicios_preestablecidos('core');
$ejercicios_cardio = $user->obtener_ejercicios_preestablecidos('cardio');
$ejercicios_fullbody = $user->obtener_ejercicios_preestablecidos('fullbody');

// Obtener rutinas existentes
$rutinas = $user->obtener_rutinas_preestablecidas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Rutinas y Ejercicios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/rutina.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 sidebar">
                <div class="sidebar-sticky">
                    <h5>Gestión de Rutinas</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="#ejercicios" data-toggle="tab">
                                <i class="fas fa-dumbbell"></i> Ejercicios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#rutinas" data-toggle="tab">
                                <i class="fas fa-calendar-alt"></i> Rutinas
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 content">
                <div class="tab-content">
                    <!-- Sección de Ejercicios -->
                    <div class="tab-pane fade show active" id="ejercicios">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Gestión de Ejercicios</h2>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalEjercicio">
                                <i class="fas fa-plus"></i> Nuevo Ejercicio
                            </button>
                        </div>

                        <!-- Filtros -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <select id="filtroGrupo" class="form-select">
                                    <option value="">Todos los grupos musculares</option>
                                    <option value="piernas">Piernas</option>
                                    <option value="pecho">Pecho</option>
                                    <option value="espalda">Espalda</option>
                                    <option value="brazos">Brazos</option>
                                    <option value="hombros">Hombros</option>
                                    <option value="core">Core</option>
                                    <option value="cardio">Cardio</option>
                                    <option value="fullbody">Full Body</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select id="filtroDificultad" class="form-select">
                                    <option value="">Todas las dificultades</option>
                                    <option value="principiante">Principiante</option>
                                    <option value="intermedio">Intermedio</option>
                                    <option value="avanzado">Avanzado</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" id="buscarEjercicio" class="form-control" placeholder="Buscar ejercicio...">
                            </div>
                        </div>

                        <!-- Lista de ejercicios por grupo muscular -->
                        <?php
                        $grupos = [
                            'piernas' => ['ejercicios' => $ejercicios_pierna, 'titulo' => 'Piernas', 'icon' => 'fas fa-running'],
                            'pecho' => ['ejercicios' => $ejercicios_pecho, 'titulo' => 'Pecho', 'icon' => 'fas fa-expand-arrows-alt'],
                            'espalda' => ['ejercicios' => $ejercicios_espalda, 'titulo' => 'Espalda', 'icon' => 'fas fa-chevron-up'],
                            'brazos' => ['ejercicios' => $ejercicios_brazos, 'titulo' => 'Brazos', 'icon' => 'fas fa-fist-raised'],
                            'hombros' => ['ejercicios' => $ejercicios_hombros, 'titulo' => 'Hombros', 'icon' => 'fas fa-angle-double-up'],
                            'core' => ['ejercicios' => $ejercicios_core, 'titulo' => 'Core', 'icon' => 'fas fa-circle-notch'],
                            'cardio' => ['ejercicios' => $ejercicios_cardio, 'titulo' => 'Cardio', 'icon' => 'fas fa-heartbeat'],
                            'fullbody' => ['ejercicios' => $ejercicios_fullbody, 'titulo' => 'Full Body', 'icon' => 'fas fa-user']
                        ];

                        foreach ($grupos as $grupo => $data):
                        ?>
                        <div class="card mb-4 grupo-ejercicios" data-grupo="<?php echo $grupo; ?>">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="<?php echo $data['icon']; ?>"></i>
                                    <?php echo $data['titulo']; ?>
                                    <span class="badge bg-primary ms-2"><?php echo count($data['ejercicios']); ?></span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if (empty($data['ejercicios'])): ?>
                                        <div class="col-12">
                                            <p class="text-muted">No hay ejercicios registrados para este grupo muscular.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($data['ejercicios'] as $ejercicio): ?>
                                        <div class="col-md-6 col-lg-4 mb-3 ejercicio-card" data-grupo="<?php echo $grupo; ?>" data-dificultad="<?php echo strtolower($ejercicio['dificultad']); ?>">
                                            <div class="card h-100">
                                                <?php if (!empty($ejercicio['imagen_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($ejercicio['imagen_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($ejercicio['nombre']); ?>" style="height: 200px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($ejercicio['nombre']); ?></h6>
                                                    <p class="card-text small">
                                                        <strong>Series:</strong> <?php echo $ejercicio['series']; ?><br>
                                                        <strong>Repeticiones:</strong> <?php echo htmlspecialchars($ejercicio['repeticiones']); ?><br>
                                                        <strong>Descanso:</strong> <?php echo htmlspecialchars($ejercicio['tiempo_descanso']); ?><br>
                                                        <strong>Dificultad:</strong> 
                                                        <span class="badge bg-<?php echo $ejercicio['dificultad'] === 'principiante' ? 'success' : ($ejercicio['dificultad'] === 'intermedio' ? 'warning' : 'danger'); ?>">
                                                            <?php echo ucfirst($ejercicio['dificultad']); ?>
                                                        </span>
                                                    </p>
                                                    <?php if (!empty($ejercicio['equipamiento_necesario'])): ?>
                                                    <p class="card-text small">
                                                        <strong>Equipamiento:</strong> <?php echo htmlspecialchars($ejercicio['equipamiento_necesario']); ?>
                                                    </p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($ejercicio['instrucciones'])): ?>
                                                    <p class="card-text small text-muted">
                                                        <?php echo htmlspecialchars(substr($ejercicio['instrucciones'], 0, 100)) . (strlen($ejercicio['instrucciones']) > 100 ? '...' : ''); ?>
                                                    </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-footer bg-transparent">
                                                    <div class="btn-group w-100">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarEjercicio(<?php echo $ejercicio['id']; ?>)">
                                                            <i class="fas fa-edit"></i> Editar
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarEjercicio(<?php echo $ejercicio['id']; ?>)">
                                                            <i class="fas fa-trash"></i> Eliminar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Sección de Rutinas -->
                    <div class="tab-pane fade" id="rutinas">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Gestión de Rutinas</h2>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalRutina">
                                <i class="fas fa-plus"></i> Nueva Rutina
                            </button>
                        </div>

                        <!-- Filtros de rutinas -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <select id="filtroCategoria" class="form-select">
                                    <option value="">Todas las categorías</option>
                                    <option value="fuerza">Fuerza</option>
                                    <option value="cardio">Cardio</option>
                                    <option value="hiit">HIIT</option>
                                    <option value="funcional">Funcional</option>
                                    <option value="flexibilidad">Flexibilidad</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select id="filtroObjetivo" class="form-select">
                                    <option value="">Todos los objetivos</option>
                                    <option value="perdida_peso">Pérdida de peso</option>
                                    <option value="ganancia_muscular">Ganancia muscular</option>
                                    <option value="definicion">Definición</option>
                                    <option value="resistencia">Resistencia</option>
                                    <option value="fuerza">Fuerza</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" id="buscarRutina" class="form-control" placeholder="Buscar rutina...">
                            </div>
                        </div>

                        <!-- Lista de rutinas -->
                        <div class="row">
                            <?php if (empty($rutinas)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No hay rutinas registradas. ¡Crea tu primera rutina!
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($rutinas as $rutina): ?>
                                <div class="col-md-6 col-lg-4 mb-4 rutina-card" data-categoria="<?php echo strtolower($rutina['categoria']); ?>" data-objetivo="<?php echo strtolower($rutina['objetivo']); ?>">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($rutina['titulo']); ?></h5>
                                            <p class="card-text"><?php echo htmlspecialchars($rutina['descripcion']); ?></p>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-tag"></i> <?php echo ucfirst($rutina['categoria']); ?>
                                                </small>
                                            </div>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-bullseye"></i> <?php echo ucfirst(str_replace('_', ' ', $rutina['objetivo'])); ?>
                                                </small>
                                            </div>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> <?php echo $rutina['duracion_minutos']; ?> minutos
                                                </small>
                                            </div>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-dumbbell"></i> <?php echo isset($rutina['total_ejercicios']) ? $rutina['total_ejercicios'] : 0; ?> ejercicios
                                                </small>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarRutina(<?php echo $rutina['id']; ?>)">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="verRutina(<?php echo $rutina['id']; ?>)">
                                                    <i class="fas fa-eye"></i> Ver
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarRutina(<?php echo $rutina['id']; ?>)">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para agregar/editar ejercicio -->
    <div class="modal fade" id="modalEjercicio" tabindex="-1" aria-labelledby="modalEjercicioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEjercicioLabel">Agregar Ejercicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEjercicio">
                    <div class="modal-body">
                        <input type="hidden" id="ejercicioId" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombreEjercicio" class="form-label">Nombre del Ejercicio *</label>
                                    <input type="text" class="form-control" id="nombreEjercicio" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="grupoMuscular" class="form-label">Grupo Muscular *</label>
                                    <select class="form-select" id="grupoMuscular" name="grupo_muscular" required>
                                        <option value="">Seleccionar grupo</option>
                                        <option value="piernas">Piernas</option>
                                        <option value="pecho">Pecho</option>
                                        <option value="espalda">Espalda</option>
                                        <option value="brazos">Brazos</option>
                                        <option value="hombros">Hombros</option>
                                        <option value="core">Core</option>
                                        <option value="cardio">Cardio</option>
                                        <option value="fullbody">Full Body</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="series" class="form-label">Series *</label>
                                    <input type="number" class="form-control" id="series" name="series" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="repeticiones" class="form-label">Repeticiones *</label>
                                    <input type="text" class="form-control" id="repeticiones" name="repeticiones" placeholder="ej: 10-12" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="tiempoDescanso" class="form-label">Tiempo de Descanso *</label>
                                    <input type="text" class="form-control" id="tiempoDescanso" name="tiempo_descanso" placeholder="ej: 60 seg" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dificultad" class="form-label">Dificultad</label>
                                    <select class="form-select" id="dificultad" name="dificultad">
                                        <option value="principiante">Principiante</option>
                                        <option value="intermedio" selected>Intermedio</option>
                                        <option value="avanzado">Avanzado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="equipamiento" class="form-label">Equipamiento Necesario</label>
                                    <input type="text" class="form-control" id="equipamiento" name="equipamiento" placeholder="ej: Mancuernas, Barra">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="imagenUrl" class="form-label">URL de la Imagen</label>
                            <input type="url" class="form-control" id="imagenUrl" name="imagen_url" placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label for="instrucciones" class="form-label">Instrucciones</label>
                            <textarea class="form-control" id="instrucciones" name="instrucciones" rows="4" placeholder="Describe cómo realizar el ejercicio..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Ejercicio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para agregar/editar rutina -->
    <div class="modal fade" id="modalRutina" tabindex="-1" aria-labelledby="modalRutinaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRutinaLabel">Agregar Rutina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formRutina">
                    <div class="modal-body">
                        <input type="hidden" id="rutinaId" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tituloRutina" class="form-label">Título de la Rutina *</label>
                                    <input type="text" class="form-control" id="tituloRutina" name="titulo" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duracionRutina" class="form-label">Duración (minutos) *</label>
                                    <input type="number" class="form-control" id="duracionRutina" name="duracion" min="1" value="60" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="categoriaRutina" class="form-label">Categoría *</label>
                                    <select class="form-select" id="categoriaRutina" name="categoria" required>
                                        <option value="">Seleccionar categoría</option>
                                        <option value="fuerza">Fuerza</option>
                                        <option value="cardio">Cardio</option>
                                        <option value="hiit">HIIT</option>
                                        <option value="funcional">Funcional</option>
                                        <option value="flexibilidad">Flexibilidad</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="objetivoRutina" class="form-label">Objetivo *</label>
                                    <select class="form-select" id="objetivoRutina" name="objetivo" required>
                                        <option value="">Seleccionar objetivo</option>
                                        <option value="perdida_peso">Pérdida de peso</option>
                                        <option value="ganancia_muscular">Ganancia muscular</option>
                                        <option value="definicion">Definición</option>
                                        <option value="resistencia">Resistencia</option>
                                        <option value="fuerza">Fuerza</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descripcionRutina" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcionRutina" name="descripcion" rows="3" placeholder="Describe el objetivo y características de la rutina..."></textarea>
                        </div>
                        
                        <!-- Selección de ejercicios -->
                        <div class="mb-3">
                            <label class="form-label">Ejercicios de la Rutina *</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Ejercicios Disponibles</h6>
                                    <div class="border p-3" style="height: 300px; overflow-y: auto;">
                                        <div class="mb-2">
                                            <input type="text" class="form-control form-control-sm" id="buscarEjerciciosModal" placeholder="Buscar ejercicios...">
                                        </div>
                                        <div id="listaEjerciciosDisponibles">
                                            <?php
                                            $todos_ejercicios = array_merge(
                                                $ejercicios_pierna, $ejercicios_pecho, $ejercicios_espalda, 
                                                $ejercicios_brazos, $ejercicios_hombros, $ejercicios_core, 
                                                $ejercicios_cardio, $ejercicios_fullbody
                                            );
                                            foreach ($todos_ejercicios as $ejercicio):
                                            ?>
                                            <div class="form-check ejercicio-disponible" data-nombre="<?php echo strtolower($ejercicio['nombre']); ?>">
                                                <input class="form-check-input" type="checkbox" value="<?php echo $ejercicio['id']; ?>" id="ejercicio_<?php echo $ejercicio['id']; ?>">
                                                <label class="form-check-label" for="ejercicio_<?php echo $ejercicio['id']; ?>">
                                                    <strong><?php echo htmlspecialchars($ejercicio['nombre']); ?></strong><br>
                                                    <small class="text-muted"><?php echo ucfirst($ejercicio['grupo_muscular']); ?> - <?php echo $ejercicio['series']; ?> series</small>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Ejercicios Seleccionados</h6>
                                    <div class="border p-3" style="height: 300px; overflow-y: auto;">
                                        <div id="listaEjerciciosSeleccionados">
                                            <p class="text-muted">Selecciona ejercicios de la lista de la izquierda</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Rutina</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para ver rutina -->
    <div class="modal fade" id="modalVerRutina" tabindex="-1" aria-labelledby="modalVerRutinaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerRutinaLabel">Detalles de la Rutina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="contenidoVerRutina">
                    <!-- Contenido dinámico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let ejerciciosSeleccionados = [];
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            inicializarEventos();
            configurarFiltros();
        });

        function inicializarEventos() {
            // Tabs
            document.querySelectorAll('[data-toggle="tab"]').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = this.getAttribute('href');
                    
                    // Remover active de todos los tabs
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('show', 'active');
                    });
                    document.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    
                    // Activar tab seleccionado
                    document.querySelector(target).classList.add('show', 'active');
                    this.classList.add('active');
                });
            });

            // Formulario ejercicio
            document.getElementById('formEjercicio').addEventListener('submit', function(e) {
                e.preventDefault();
                guardarEjercicio();
            });

            // Formulario rutina
            document.getElementById('formRutina').addEventListener('submit', function(e) {
                e.preventDefault();
                guardarRutina();
            });

            // Checkboxes de ejercicios
            document.querySelectorAll('#listaEjerciciosDisponibles input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        agregarEjercicioSeleccionado(this.value, this.nextElementSibling.querySelector('strong').textContent);
                    } else {
                        removerEjercicioSeleccionado(this.value);
                    }
                });
            });

            // Búsqueda en modal de rutina
            document.getElementById('buscarEjerciciosModal').addEventListener('input', function() {
                filtrarEjerciciosModal(this.value);
            });
        }

        function configurarFiltros() {
            // Filtros de ejercicios
            document.getElementById('filtroGrupo').addEventListener('change', aplicarFiltrosEjercicios);
            document.getElementById('filtroDificultad').addEventListener('change', aplicarFiltrosEjercicios);
            document.getElementById('buscarEjercicio').addEventListener('input', aplicarFiltrosEjercicios);

            // Filtros de rutinas
            document.getElementById('filtroCategoria').addEventListener('change', aplicarFiltrosRutinas);
            document.getElementById('filtroObjetivo').addEventListener('change', aplicarFiltrosRutinas);
            document.getElementById('buscarRutina').addEventListener('input', aplicarFiltrosRutinas);
        }

        function aplicarFiltrosEjercicios() {
            const grupo = document.getElementById('filtroGrupo').value;
            const dificultad = document.getElementById('filtroDificultad').value;
            const busqueda = document.getElementById('buscarEjercicio').value.toLowerCase();

            document.querySelectorAll('.grupo-ejercicios').forEach(grupoCard => {
                let mostrarGrupo = false;

                if (grupo && grupoCard.dataset.grupo !== grupo) {
                    grupoCard.style.display = 'none';
                    return;
                }

                grupoCard.querySelectorAll('.ejercicio-card').forEach(ejercicio => {
                    let mostrar = true;

                    if (dificultad && ejercicio.dataset.dificultad !== dificultad) {
                        mostrar = false;
                    }

                    if (busqueda) {
                        const nombre = ejercicio.querySelector('.card-title').textContent.toLowerCase();
                        if (!nombre.includes(busqueda)) {
                            mostrar = false;
                        }
                    }

                    ejercicio.style.display = mostrar ? 'block' : 'none';
                    if (mostrar) mostrarGrupo = true;
                });

                grupoCard.style.display = mostrarGrupo ? 'block' : 'none';
            });
        }

        function aplicarFiltrosRutinas() {
            const categoria = document.getElementById('filtroCategoria').value;
            const objetivo = document.getElementById('filtroObjetivo').value;
            const busqueda = document.getElementById('buscarRutina').value.toLowerCase();

            document.querySelectorAll('.rutina-card').forEach(rutina => {
                let mostrar = true;

                if (categoria && rutina.dataset.categoria !== categoria) {
                    mostrar = false;
                }

                if (objetivo && rutina.dataset.objetivo !== objetivo) {
                    mostrar = false;
                }

                if (busqueda) {
                    const titulo = rutina.querySelector('.card-title').textContent.toLowerCase();
                    const descripcion = rutina.querySelector('.card-text').textContent.toLowerCase();
                    if (!titulo.includes(busqueda) && !descripcion.includes(busqueda)) {
                        mostrar = false;
                    }
                }

                rutina.style.display = mostrar ? 'block' : 'none';
            });
        }

        function filtrarEjerciciosModal(busqueda) {
            const busquedaLower = busqueda.toLowerCase();
            document.querySelectorAll('.ejercicio-disponible').forEach(item => {
                const nombre = item.dataset.nombre;
                item.style.display = nombre.includes(busquedaLower) ? 'block' : 'none';
            });
        }

        function agregarEjercicioSeleccionado(id, nombre) {
            if (!ejerciciosSeleccionados.includes(id)) {
                ejerciciosSeleccionados.push(id);
                actualizarListaSeleccionados();
            }
        }

        function removerEjercicioSeleccionado(id) {
            ejerciciosSeleccionados = ejerciciosSeleccionados.filter(ejercicioId => ejercicioId !== id);
            actualizarListaSeleccionados();
        }

        function actualizarListaSeleccionados() {
            const lista = document.getElementById('listaEjerciciosSeleccionados');
            if (ejerciciosSeleccionados.length === 0) {
                lista.innerHTML = '<p class="text-muted">Selecciona ejercicios de la lista de la izquierda</p>';
                return;
            }

            let html = '';
            ejerciciosSeleccionados.forEach(id => {
                const checkbox = document.getElementById(`ejercicio_${id}`);
                const label = checkbox.nextElementSibling;
                const nombre = label.querySelector('strong').textContent;
                const detalles = label.querySelector('small').textContent;
                
                html += `
                    <div class="alert alert-light d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${nombre}</strong><br>
                            <small class="text-muted">${detalles}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerEjercicioDeRutina('${id}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            lista.innerHTML = html;
        }

        function removerEjercicioDeRutina(id) {
            document.getElementById(`ejercicio_${id}`).checked = false;
            removerEjercicioSeleccionado(id);
        }

        function guardarEjercicio() {
            const formData = new FormData(document.getElementById('formEjercicio'));
            const esEdicion = document.getElementById('ejercicioId').value !== '';
            
            formData.append('action', esEdicion ? 'editar_ejercicio' : 'agregar_ejercicio');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(esEdicion ? 'Ejercicio actualizado correctamente' : 'Ejercicio agregado correctamente');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar el ejercicio'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el ejercicio');
            });
        }

        function guardarRutina() {
            if (ejerciciosSeleccionados.length === 0) {
                alert('Debes seleccionar al menos un ejercicio para la rutina');
                return;
            }

            const formData = new FormData(document.getElementById('formRutina'));
            const esEdicion = document.getElementById('rutinaId').value !== '';
            
            formData.append('action', esEdicion ? 'editar_rutina' : 'crear_rutina');
            
            // Agregar ejercicios seleccionados
            ejerciciosSeleccionados.forEach(id => {
                formData.append('ejercicios[]', id);
            });

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(esEdicion ? 'Rutina actualizada correctamente' : 'Rutina creada correctamente');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar la rutina'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar la rutina');
            });
        }

        function editarEjercicio(id) {
            // Aquí deberías hacer una petición para obtener los datos del ejercicio
            // y llenar el formulario modal
            document.getElementById('modalEjercicioLabel').textContent = 'Editar Ejercicio';
            document.getElementById('ejercicioId').value = id;
            
            // Mostrar modal
            new bootstrap.Modal(document.getElementById('modalEjercicio')).show();
        }

        function eliminarEjercicio(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este ejercicio?')) {
                const formData = new FormData();
                formData.append('action', 'eliminar_ejercicio');
                formData.append('id', id);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Ejercicio eliminado correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo eliminar el ejercicio'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el ejercicio');
                });
            }
        }

        function editarRutina(id) {
            // Aquí deberías hacer una petición para obtener los datos de la rutina
            // y llenar el formulario modal
            document.getElementById('modalRutinaLabel').textContent = 'Editar Rutina';
            document.getElementById('rutinaId').value = id;
            
            // Mostrar modal
            new bootstrap.Modal(document.getElementById('modalRutina')).show();
        }

        function verRutina(id) {
            // Aquí deberías hacer una petición para obtener los detalles completos de la rutina
            // y mostrarlos en el modal
            document.getElementById('contenidoVerRutina').innerHTML = '<p>Cargando detalles de la rutina...</p>';
            new bootstrap.Modal(document.getElementById('modalVerRutina')).show();
        }

        function eliminarRutina(id) {
            if (confirm('¿Estás seguro de que quieres eliminar esta rutina?')) {
                const formData = new FormData();
                formData.append('action', 'eliminar_rutina');
                formData.append('id', id);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Rutina eliminada correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo eliminar la rutina'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la rutina');
                });
            }
        }

        // Limpiar formularios al cerrar modales
        document.getElementById('modalEjercicio').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formEjercicio').reset();
            document.getElementById('ejercicioId').value = '';
            document.getElementById('modalEjercicioLabel').textContent = 'Agregar Ejercicio';
        });

        document.getElementById('modalRutina').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formRutina').reset();
            document.getElementById('rutinaId').value = '';
            document.getElementById('modalRutinaLabel').textContent = 'Agregar Rutina';
            ejerciciosSeleccionados = [];
            document.querySelectorAll('#listaEjerciciosDisponibles input[type="checkbox"]').forEach(cb => cb.checked = false);
            actualizarListaSeleccionados();
        });
    </script>

   
</body>
</html>