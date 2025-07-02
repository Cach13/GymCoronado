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

// Mostrar alertas si existen
$alert = gym_get_alert();
if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
        <?= $alert['message'] ?>
    </div>
<?php endif; ?>

<form id="formAgregarEjercicio" class="needs-validation" novalidate>
    <input type="hidden" name="action" value="agregar_ejercicio">
    
    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre del Ejercicio</label>
        <input type="text" class="form-control" id="nombre" name="nombre" required>
    </div>
    
    <div class="mb-3">
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
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="series" class="form-label">Series</label>
            <input type="number" class="form-control" id="series" name="series" min="1" max="10" required>
        </div>
        
        <div class="col-md-4 mb-3">
            <label for="repeticiones" class="form-label">Repeticiones</label>
            <input type="text" class="form-control" id="repeticiones" name="repeticiones" placeholder="Ej: 8-12" required>
        </div>
        
        <div class="col-md-4 mb-3">
            <label for="tiempo_descanso" class="form-label">Descanso</label>
            <input type="text" class="form-control" id="tiempo_descanso" name="tiempo_descanso" placeholder="Ej: 60 seg" required>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="dificultad" class="form-label">Dificultad</label>
        <select class="form-select" id="dificultad" name="dificultad">
            <option value="principiante">Principiante</option>
            <option value="intermedio" selected>Intermedio</option>
            <option value="avanzado">Avanzado</option>
        </select>
    </div>
    
    <div class="mb-3">
        <label for="equipamiento" class="form-label">Equipamiento Necesario</label>
        <input type="text" class="form-control" id="equipamiento" name="equipamiento" placeholder="Ej: Mancuernas, Barra, etc.">
    </div>
    
    <div class="mb-3">
        <label for="instrucciones" class="form-label">Instrucciones Adicionales</label>
        <textarea class="form-control" id="instrucciones" name="instrucciones" rows="3"></textarea>
    </div>
    
    <div class="mb-3">
        <label for="imagen" class="form-label">URL de Imagen (opcional)</label>
        <input type="url" class="form-control" id="imagen" name="imagen_url" placeholder="https://...">
    </div>
    
    <button type="submit" class="btn btn-primary">Agregar Ejercicio</button>
</form>

<!-- Formulario para crear rutina (opcional) -->
<div class="mt-5">
    <h3>Crear Nueva Rutina</h3>
    <form id="formCrearRutina" class="needs-validation" novalidate>
        <input type="hidden" name="action" value="crear_rutina">
        
        <div class="mb-3">
            <label for="titulo" class="form-label">Título de la Rutina</label>
            <input type="text" class="form-control" id="titulo" name="titulo" required>
        </div>
        
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-3">
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
            
            <div class="col-md-4 mb-3">
                <label for="objetivo" class="form-label">Objetivo</label>
                <select class="form-select" id="objetivo" name="objetivo" required>
                    <option value="">Seleccionar...</option>
                    <option value="perdida_grasa">Pérdida de grasa</option>
                    <option value="ganancia_muscular">Ganancia muscular</option>
                    <option value="mantenimiento">Mantenimiento</option>
                    <option value="mejora_rendimiento">Mejora de rendimiento</option>
                </select>
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="duracion" class="form-label">Duración (minutos)</label>
                <input type="number" class="form-control" id="duracion" name="duracion" min="10" max="180" value="60">
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Ejercicios a Incluir</label>
            <div class="row">
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
                        <div class="col-md-6">
                            <h5><?= ucfirst($grupo) ?></h5>
                            <?php foreach ($ejercicios as $ejercicio): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ejercicios[]" 
                                           value="<?= $ejercicio['id'] ?>" id="ejercicio_<?= $ejercicio['id'] ?>">
                                    <label class="form-check-label" for="ejercicio_<?= $ejercicio['id'] ?>">
                                        <?= $ejercicio['nombre'] ?> 
                                        (<?= $ejercicio['series'] ?>x<?= $ejercicio['repeticiones'] ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif;
                endforeach; ?>
            </div>
        </div>
        
        <button type="submit" class="btn btn-success">Crear Rutina</button>
    </form>
</div>

<script>
// Manejo de formularios con AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Formulario de agregar ejercicio
    document.getElementById('formAgregarEjercicio').addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch('', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Recargar para ver los cambios
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
    
    // Formulario de crear rutina
    if (document.getElementById('formCrearRutina')) {
        document.getElementById('formCrearRutina').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Redirigir o recargar según necesidad
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});
</script>