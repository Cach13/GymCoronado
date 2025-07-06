<?php
require_once('../../config/config.php');
require_once('../../config/User.php');



// Verificar permisos
gym_check_permission('cliente');

// Verificar que la conexión PDO existe
if (!isset($pdo)) {
    die('Error: No se pudo establecer la conexión a la base de datos. Verifica tu archivo config.php');
}

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$user = new User();

// Obtener ID del usuario actual
$id_usuario = $_SESSION['user_id'];

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validar fecha
        $fecha_medicion = $_POST['fecha_medicion'];
        if (empty($fecha_medicion)) {
            throw new Exception('La fecha de medición es requerida.');
        }
        
        // Verificar si ya existe una medición para esta fecha
        $stmt = $pdo->prepare("SELECT id FROM medidas WHERE id_usuario = ? AND fecha_medicion = ?");
        $stmt->execute([$id_usuario, $fecha_medicion]);
        $medicion_existente = $stmt->fetch();
        
        if ($medicion_existente) {
            // Actualizar medición existente
            $sql = "UPDATE medidas SET 
                    peso = ?, altura = ?, grasa_corporal = ?, masa_muscular = ?,
                    cintura = ?, cadera = ?, pecho = ?, brazo_derecho = ?,
                    brazo_izquierdo = ?, pierna_derecha = ?, pierna_izquierda = ?,
                    cuello = ?, notas = ?
                    WHERE id_usuario = ? AND fecha_medicion = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                !empty($_POST['peso']) ? $_POST['peso'] : null,
                !empty($_POST['altura']) ? $_POST['altura'] : null,
                !empty($_POST['grasa_corporal']) ? $_POST['grasa_corporal'] : null,
                !empty($_POST['masa_muscular']) ? $_POST['masa_muscular'] : null,
                !empty($_POST['cintura']) ? $_POST['cintura'] : null,
                !empty($_POST['cadera']) ? $_POST['cadera'] : null,
                !empty($_POST['pecho']) ? $_POST['pecho'] : null,
                !empty($_POST['brazo_derecho']) ? $_POST['brazo_derecho'] : null,
                !empty($_POST['brazo_izquierdo']) ? $_POST['brazo_izquierdo'] : null,
                !empty($_POST['pierna_derecha']) ? $_POST['pierna_derecha'] : null,
                !empty($_POST['pierna_izquierda']) ? $_POST['pierna_izquierda'] : null,
                !empty($_POST['cuello']) ? $_POST['cuello'] : null,
                !empty($_POST['notas']) ? $_POST['notas'] : null,
                $id_usuario,
                $fecha_medicion
            ]);
            
            $mensaje = 'Métricas actualizadas exitosamente.';
            $tipo_mensaje = 'success';
        } else {
            // Insertar nueva medición
            $sql = "INSERT INTO medidas (
                    id_usuario, peso, altura, grasa_corporal, masa_muscular,
                    cintura, cadera, pecho, brazo_derecho, brazo_izquierdo,
                    pierna_derecha, pierna_izquierda, cuello, fecha_medicion, notas
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id_usuario,
                !empty($_POST['peso']) ? $_POST['peso'] : null,
                !empty($_POST['altura']) ? $_POST['altura'] : null,
                !empty($_POST['grasa_corporal']) ? $_POST['grasa_corporal'] : null,
                !empty($_POST['masa_muscular']) ? $_POST['masa_muscular'] : null,
                !empty($_POST['cintura']) ? $_POST['cintura'] : null,
                !empty($_POST['cadera']) ? $_POST['cadera'] : null,
                !empty($_POST['pecho']) ? $_POST['pecho'] : null,
                !empty($_POST['brazo_derecho']) ? $_POST['brazo_derecho'] : null,
                !empty($_POST['brazo_izquierdo']) ? $_POST['brazo_izquierdo'] : null,
                !empty($_POST['pierna_derecha']) ? $_POST['pierna_derecha'] : null,
                !empty($_POST['pierna_izquierda']) ? $_POST['pierna_izquierda'] : null,
                !empty($_POST['cuello']) ? $_POST['cuello'] : null,
                $fecha_medicion,
                !empty($_POST['notas']) ? $_POST['notas'] : null
            ]);
            
            $mensaje = 'Métricas registradas exitosamente.';
            $tipo_mensaje = 'success';
        }
        
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener el historial de mediciones del usuario
try {
    $stmt = $pdo->prepare("SELECT * FROM medidas WHERE id_usuario = ? ORDER BY fecha_medicion DESC LIMIT 10");
    $stmt->execute([$id_usuario]);
    $historial_mediciones = $stmt->fetchAll();
} catch (Exception $e) {
    $historial_mediciones = [];
    $mensaje = 'Error al obtener el historial: ' . $e->getMessage();
    $tipo_mensaje = 'warning';
}

// Obtener la última medición para prellenar el formulario
$ultima_medicion = !empty($historial_mediciones) ? $historial_mediciones[0] : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Métricas Corporales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .metric-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }
        .metric-input {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .metric-input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .section-title {
            color: #495057;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0b5ed7 0%, #520dc2 100%);
            transform: translateY(-2px);
        }
        .history-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #0d6efd;
        }
        .metric-label {
            font-weight: 500;
            color: #495057;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
        }
        .form-control.metric-input {
            border-left: none;
        }
        .progress-indicator {
            height: 8px;
            background: linear-gradient(90deg, #28a745 0%, #ffc107 50%, #dc3545 100%);
            border-radius: 4px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-5">
                <i class="fas fa-ruler-combined text-primary"></i> Registro de Métricas
            </h1>
            <a href="/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Formulario de registro -->
            <div class="col-lg-8">
                <div class="metric-card">
                    <h4 class="section-title">
                        <i class="fas fa-plus-circle"></i> Registrar Nuevas Métricas
                    </h4>
                    
                    <form method="POST" action="">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="fecha_medicion" class="form-label metric-label">
                                    <i class="fas fa-calendar-alt"></i> Fecha de Medición *
                                </label>
                                <input type="date" 
                                       class="form-control metric-input" 
                                       id="fecha_medicion" 
                                       name="fecha_medicion" 
                                       value="<?php echo date('Y-m-d'); ?>" 
                                       required>
                            </div>
                        </div>

                        <!-- Métricas Básicas -->
                        <h5 class="section-title">
                            <i class="fas fa-weight"></i> Métricas Básicas
                        </h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="peso" class="form-label metric-label">Peso</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="peso" 
                                           name="peso" 
                                           step="0.1" 
                                           min="0" 
                                           max="300"
                                           placeholder="Ej: 70.5">
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="altura" class="form-label metric-label">Altura</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="altura" 
                                           name="altura" 
                                           step="0.1" 
                                           min="0" 
                                           max="250"
                                           placeholder="Ej: 175.0">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                        </div>

                        <!-- Composición Corporal -->
                        <h5 class="section-title">
                            <i class="fas fa-chart-pie"></i> Composición Corporal
                        </h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="grasa_corporal" class="form-label metric-label">Grasa Corporal</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="grasa_corporal" 
                                           name="grasa_corporal" 
                                           step="0.1" 
                                           min="0" 
                                           max="50"
                                           placeholder="Ej: 15.5">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="masa_muscular" class="form-label metric-label">Masa Muscular</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="masa_muscular" 
                                           name="masa_muscular" 
                                           step="0.1" 
                                           min="0" 
                                           max="100"
                                           placeholder="Ej: 45.2">
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                        </div>

                        <!-- Medidas Corporales -->
                        <h5 class="section-title">
                            <i class="fas fa-ruler"></i> Medidas Corporales
                        </h5>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="cintura" class="form-label metric-label">Cintura</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="cintura" 
                                           name="cintura" 
                                           step="0.1" 
                                           min="0" 
                                           max="200"
                                           placeholder="Ej: 80.5">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="cadera" class="form-label metric-label">Cadera</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="cadera" 
                                           name="cadera" 
                                           step="0.1" 
                                           min="0" 
                                           max="200"
                                           placeholder="Ej: 95.0">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="pecho" class="form-label metric-label">Pecho</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="pecho" 
                                           name="pecho" 
                                           step="0.1" 
                                           min="0" 
                                           max="200"
                                           placeholder="Ej: 100.0">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                        </div>

                        <!-- Extremidades -->
                        <h5 class="section-title">
                            <i class="fas fa-hand-paper"></i> Extremidades
                        </h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="brazo_derecho" class="form-label metric-label">Brazo Derecho</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="brazo_derecho" 
                                           name="brazo_derecho" 
                                           step="0.1" 
                                           min="0" 
                                           max="100"
                                           placeholder="Ej: 35.0">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="brazo_izquierdo" class="form-label metric-label">Brazo Izquierdo</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="brazo_izquierdo" 
                                           name="brazo_izquierdo" 
                                           step="0.1" 
                                           min="0" 
                                           max="100"
                                           placeholder="Ej: 35.0">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="pierna_derecha" class="form-label metric-label">Pierna Derecha</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="pierna_derecha" 
                                           name="pierna_derecha" 
                                           step="0.1" 
                                           min="0" 
                                           max="100"
                                           placeholder="Ej: 55.0">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="pierna_izquierda" class="form-label metric-label">Pierna Izquierda</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="pierna_izquierda" 
                                           name="pierna_izquierda" 
                                           step="0.1" 
                                           min="0" 
                                           max="100"
                                           placeholder="Ej: 55.0">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="cuello" class="form-label metric-label">Cuello</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control metric-input" 
                                           id="cuello" 
                                           name="cuello" 
                                           step="0.1" 
                                           min="0" 
                                           max="100"
                                           placeholder="Ej: 38.0">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <h5 class="section-title">
                            <i class="fas fa-sticky-note"></i> Notas Adicionales
                        </h5>
                        <div class="mb-4">
                            <label for="notas" class="form-label metric-label">Notas</label>
                            <textarea class="form-control metric-input" 
                                      id="notas" 
                                      name="notas" 
                                      rows="3" 
                                      placeholder="Agrega cualquier observación adicional..."></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Guardar Métricas
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historial de mediciones -->
            <div class="col-lg-4">
                <div class="metric-card">
                    <h4 class="section-title">
                        <i class="fas fa-history"></i> Historial Reciente
                    </h4>
                    
                    <?php if (!empty($historial_mediciones)): ?>
                        <div class="timeline">
                            <?php foreach (array_slice($historial_mediciones, 0, 5) as $medicion): ?>
                                <div class="history-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong><?php echo date('d/m/Y', strtotime($medicion['fecha_medicion'])); ?></strong>
                                        <small class="text-muted">
                                            <?php echo date('H:i', strtotime($medicion['fecha_medicion'])); ?>
                                        </small>
                                    </div>
                                    <div class="row small">
                                        <?php if ($medicion['peso']): ?>
                                            <div class="col-6">
                                                <i class="fas fa-weight text-primary"></i> 
                                                <?php echo $medicion['peso']; ?> kg
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($medicion['grasa_corporal']): ?>
                                            <div class="col-6">
                                                <i class="fas fa-chart-pie text-warning"></i> 
                                                <?php echo $medicion['grasa_corporal']; ?>%
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($medicion['cintura']): ?>
                                            <div class="col-6">
                                                <i class="fas fa-ruler text-info"></i> 
                                                <?php echo $medicion['cintura']; ?> cm
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($medicion['masa_muscular']): ?>
                                            <div class="col-6">
                                                <i class="fas fa-dumbbell text-success"></i> 
                                                <?php echo $medicion['masa_muscular']; ?> kg
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($medicion['notas']): ?>
                                        <div class="mt-2 p-2 bg-white rounded small">
                                            <i class="fas fa-sticky-note text-muted"></i> 
                                            <?php echo htmlspecialchars($medicion['notas']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="historial_metricas.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chart-line"></i> Ver Historial Completo
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            No tienes métricas registradas aún. ¡Comienza registrando tus primeras mediciones!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calcular IMC automáticamente
        function calcularIMC() {
            const peso = parseFloat(document.getElementById('peso').value);
            const altura = parseFloat(document.getElementById('altura').value) / 100; // convertir cm a m
            
            if (peso && altura) {
                const imc = peso / (altura * altura);
                console.log('IMC calculado:', imc.toFixed(2));
                // Aquí puedes agregar lógica para mostrar el IMC en el formulario
            }
        }

        // Agregar event listeners para calcular IMC
        document.getElementById('peso').addEventListener('input', calcularIMC);
        document.getElementById('altura').addEventListener('input', calcularIMC);

        // Validación de formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const fecha = document.getElementById('fecha_medicion').value;
            if (!fecha) {
                e.preventDefault();
                alert('Por favor, selecciona una fecha de medición.');
                return;
            }
            
            // Verificar que al menos una métrica esté llena
            const inputs = document.querySelectorAll('input[type="number"]');
            let alMenosUna = false;
            
            inputs.forEach(input => {
                if (input.value.trim() !== '') {
                    alMenosUna = true;
                }
            });
            
            if (!alMenosUna) {
                e.preventDefault();
                alert('Por favor, ingresa al menos una métrica.');
                return;
            }
        });

        // Autocompletado inteligente basado en mediciones anteriores
        <?php if ($ultima_medicion): ?>
            // Prellenar algunos campos con la última medición si el usuario quiere
            document.addEventListener('DOMContentLoaded', function() {
                const confirmar = confirm('¿Deseas cargar los datos de tu última medición como referencia?');
                if (confirmar) {
                    <?php if ($ultima_medicion['altura']): ?>
                        document.getElementById('altura').value = '<?php echo $ultima_medicion['altura']; ?>';
                    <?php endif; ?>
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>