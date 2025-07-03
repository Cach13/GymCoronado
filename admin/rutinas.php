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

// Obtener ejercicios para mostrar
$ejercicios_pierna = $user->obtener_ejercicios_preestablecidos('piernas');
$ejercicios_pecho = $user->obtener_ejercicios_preestablecidos('pecho');
$ejercicios_espalda = $user->obtener_ejercicios_preestablecidos('espalda');
$ejercicios_brazos = $user->obtener_ejercicios_preestablecidos('brazos');
$ejercicios_hombros = $user->obtener_ejercicios_preestablecidos('hombros');
$ejercicios_core = $user->obtener_ejercicios_preestablecidos('core');
$ejercicios_cardio = $user->obtener_ejercicios_preestablecidos('cardio');
$ejercicios_fullbody = $user->obtener_ejercicios_preestablecidos('fullbody');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Rutinas - Panel Administrativo</title>
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

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
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

        .section-content {
            padding: 1.5rem;
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

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            font-size: 1rem;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
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

        .exercise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .exercise-group {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
        }

        .exercise-group h5 {
            color: #1f2937;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .form-check {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .form-check-input {
            width: 16px;
            height: 16px;
            margin-top: 0.25rem;
            accent-color: #3b82f6;
        }

        .form-check-label {
            font-size: 0.9rem;
            color: #374151;
            line-height: 1.4;
        }

        .difficulty-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .difficulty-principiante {
            background: #dcfce7;
            color: #166534;
        }

        .difficulty-intermedio {
            background: #fef3c7;
            color: #92400e;
        }

        .difficulty-avanzado {
            background: #fee2e2;
            color: #991b1b;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 1rem;
            color: #6b7280;
        }

        .loading.show {
            display: block;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .exercise-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Gestión de Rutinas</h1>
        </div>
    </div>

    <div class="container">
        <?php 
        // Mostrar alertas si existen
        $alert = gym_get_alert();
        if ($alert): ?>
            <div class="alert alert-<?= $alert['type'] ?>">
                <?= $alert['message'] ?>
            </div>
        <?php endif; ?>

        <!-- Sección: Agregar Ejercicio -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Agregar Nuevo Ejercicio</h2>
            </div>
            <div class="section-content">
                <form id="formAgregarEjercicio" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="agregar_ejercicio">
                    
                    <div class="form-group">
                        <label for="nombre" class="form-label">Nombre del Ejercicio</label>
                        <input type="text" class="form-input" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="grupo_muscular" class="form-label">Grupo Muscular</label>
                        <select class="form-select" id="grupo_muscular" name="grupo_muscular" required>
                            <option value="">Seleccionar...</option>
                            <option value="pecho">Pecho</option>
                            <option value="espalda">Espalda</option>
                            <option value="piernas">Piernas</option>
                            <option value="brazos">Brazos</option>
                            <option value="hombros">Hombros</option>
                            <option value="core">Core</option>
                            <option value="cardio">Cardio</option>
                            <option value="fullbody">Full Body</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="series" class="form-label">Series</label>
                            <input type="number" class="form-input" id="series" name="series" min="1" max="10" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="repeticiones" class="form-label">Repeticiones</label>
                            <input type="text" class="form-input" id="repeticiones" name="repeticiones" placeholder="Ej: 8-12" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="tiempo_descanso" class="form-label">Descanso</label>
                            <input type="text" class="form-input" id="tiempo_descanso" name="tiempo_descanso" placeholder="Ej: 60 seg" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="dificultad" class="form-label">Dificultad</label>
                        <select class="form-select" id="dificultad" name="dificultad">
                            <option value="principiante">Principiante</option>
                            <option value="intermedio" selected>Intermedio</option>
                            <option value="avanzado">Avanzado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="equipamiento" class="form-label">Equipamiento Necesario</label>
                        <input type="text" class="form-input" id="equipamiento" name="equipamiento" placeholder="Ej: Mancuernas, Barra, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="instrucciones" class="form-label">Instrucciones Adicionales</label>
                        <textarea class="form-textarea" id="instrucciones" name="instrucciones" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagen" class="form-label">URL de Imagen (opcional)</label>
                        <input type="url" class="form-input" id="imagen" name="imagen_url" placeholder="https://...">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Agregar Ejercicio</button>
                    <div class="loading" id="loadingEjercicio">Guardando ejercicio...</div>
                </form>
            </div>
        </div>

        <!-- Sección: Crear Rutina -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Crear Nueva Rutina</h2>
            </div>
            <div class="section-content">
                <form id="formCrearRutina" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="crear_rutina">
                    
                    <div class="form-group">
                        <label for="titulo" class="form-label">Título de la Rutina</label>
                        <input type="text" class="form-input" id="titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-textarea" id="descripcion" name="descripcion" rows="2"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <option value="">Seleccionar...</option>
                                <option value="fuerza">Fuerza</option>
                                <option value="hipertrofia">Hipertrofia</option>
                                <option value="resistencia">Resistencia</option>
                                <option value="cardio">Cardio</option>
                                <option value="flexibilidad">Flexibilidad</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="objetivo" class="form-label">Objetivo</label>
                            <select class="form-select" id="objetivo" name="objetivo" required>
                                <option value="">Seleccionar...</option>
                                <option value="perdida_grasa">Pérdida de grasa</option>
                                <option value="ganancia_muscular">Ganancia muscular</option>
                                <option value="mantenimiento">Mantenimiento</option>
                                <option value="mejora_rendimiento">Mejora de rendimiento</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="duracion" class="form-label">Duración (minutos)</label>
                            <input type="number" class="form-input" id="duracion" name="duracion" min="10" max="180" value="60">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ejercicios a Incluir</label>
                        <div class="exercise-grid">
                            <?php 
                            $grupos_musculares = [
                                'piernas' => $ejercicios_pierna,
                                'pecho' => $ejercicios_pecho,
                                'espalda' => $ejercicios_espalda,
                                'brazos' => $ejercicios_brazos,
                                'hombros' => $ejercicios_hombros,
                                'core' => $ejercicios_core,
                                'cardio' => $ejercicios_cardio,
                                'fullbody' => $ejercicios_fullbody
                            ];
                            
                            foreach ($grupos_musculares as $grupo => $ejercicios): 
                                if (!empty($ejercicios)): ?>
                                    <div class="exercise-group">
                                        <h5><?= ucfirst($grupo) ?></h5>
                                        <?php foreach ($ejercicios as $ejercicio): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ejercicios[]" 
                                                       value="<?= $ejercicio['id'] ?>" id="ejercicio_<?= $ejercicio['id'] ?>">
                                                <label class="form-check-label" for="ejercicio_<?= $ejercicio['id'] ?>">
                                                    <strong><?= $ejercicio['nombre'] ?></strong><br>
                                                    <small><?= $ejercicio['series'] ?>x<?= $ejercicio['repeticiones'] ?> - <?= $ejercicio['tiempo_descanso'] ?></small>
                                                    <span class="difficulty-badge difficulty-<?= $ejercicio['dificultad'] ?? 'intermedio' ?>">
                                                        <?= ucfirst($ejercicio['dificultad'] ?? 'intermedio') ?>
                                                    </span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif;
                            endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Crear Rutina</button>
                    <div class="loading" id="loadingRutina">Creando rutina...</div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Manejo de formularios con AJAX
        document.addEventListener('DOMContentLoaded', function() {
            // Formulario de agregar ejercicio
            document.getElementById('formAgregarEjercicio').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const loadingEjercicio = document.getElementById('loadingEjercicio');
                const submitBtn = this.querySelector('button[type="submit"]');
                
                loadingEjercicio.classList.add('show');
                submitBtn.disabled = true;
                
                fetch('', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        this.reset(); // Limpiar formulario
                        setTimeout(() => {
                            location.reload(); // Recargar para ver los cambios
                        }, 1000);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                })
                .finally(() => {
                    loadingEjercicio.classList.remove('show');
                    submitBtn.disabled = false;
                });
            });
            
            // Formulario de crear rutina
            if (document.getElementById('formCrearRutina')) {
                document.getElementById('formCrearRutina').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const loadingRutina = document.getElementById('loadingRutina');
                    const submitBtn = this.querySelector('button[type="submit"]');
                    
                    // Verificar que al menos un ejercicio esté seleccionado
                    const exercisesSelected = this.querySelectorAll('input[name="ejercicios[]"]:checked');
                    if (exercisesSelected.length === 0) {
                        alert('Por favor selecciona al menos un ejercicio para la rutina');
                        return;
                    }
                    
                    loadingRutina.classList.add('show');
                    submitBtn.disabled = true;
                    
                    fetch('', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            this.reset(); // Limpiar formulario
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al procesar la solicitud');
                    })
                    .finally(() => {
                        loadingRutina.classList.remove('show');
                        submitBtn.disabled = false;
                    });
                });
            }
        });
    </script>
</body>
</html>