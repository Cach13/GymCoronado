<?php
// Incluir las configuraciones y clases necesarias
require_once('../../config/config.php');
require_once('../../config/alimentos.php');

// Verificar autenticación
if (!gym_is_logged_in()) {
    gym_redirect('login.php');
}

$usuario = gym_get_logged_in_user();
$alimentosManager = new AlimentosManager($pdo);

// Procesar acciones AJAX
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'buscar_alimentos':
            $termino = $_POST['termino'] ?? '';
            $grupo = $_POST['grupo'] ?? null;
            $resultados = $alimentosManager->buscarAlimentos($termino, $grupo, 20);
            echo json_encode($resultados);
            exit;
            
        case 'calcular_macros':
            $resultado = $alimentosManager->calcularMacros(
                $_POST['alimento_id'],
                $_POST['cantidad'],
                $_POST['tipo_medida'] ?? null
            );
            echo json_encode($resultado);
            exit;
            
        case 'registrar_comida':
            $resultado = $alimentosManager->registrarComida(
                $usuario['id'],
                $_POST['alimento_id'],
                $_POST['fecha'],
                $_POST['comida'],
                $_POST['cantidad'],
                $_POST['tipo_medida'] ?? null
            );
            echo json_encode($resultado);
            exit;
            
        case 'eliminar_registro':
            $resultado = $alimentosManager->eliminarRegistro($_POST['id_registro'], $usuario['id']);
            echo json_encode($resultado);
            exit;
            
        case 'actualizar_objetivos':
            try {
                $stmt = $pdo->prepare("
                    UPDATE objetivos_nutricionales 
                    SET calorias_objetivo = ?, proteinas_objetivo = ?, carbohidratos_objetivo = ?, grasas_objetivo = ?
                    WHERE id_usuario = ? AND activo = TRUE
                ");
                $stmt->execute([
                    $_POST['calorias'],
                    $_POST['proteinas'],
                    $_POST['carbohidratos'],
                    $_POST['grasas'],
                    $usuario['id']
                ]);
                echo json_encode(['success' => true, 'mensaje' => 'Objetivos actualizados']);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Error al actualizar objetivos']);
            }
            exit;
    }
}

// Obtener datos para la página
$fecha_actual = $_GET['fecha'] ?? date('Y-m-d');
$resumen_dia = $alimentosManager->obtenerResumenDia($usuario['id'], $fecha_actual);
$grupos = $alimentosManager->obtenerGrupos();
$mas_consumidos = $alimentosManager->obtenerMasConsumidos($usuario['id'], 10);
$objetivos = $alimentosManager->obtenerObjetivos($usuario['id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento Nutricional</title>
    <link rel="stylesheet" href="/assets/css/contador.css">
    
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍎 Seguimiento Nutricional</h1>
            <p>Bienvenido, <?php echo htmlspecialchars($usuario['nombre']); ?></p>
        </div>

        <!-- Navigation de fechas -->
        <div class="date-nav">
            <button onclick="cambiarFecha(-1)">← Día Anterior</button>
            <input type="date" id="fecha-actual" value="<?php echo $fecha_actual; ?>" onchange="cambiarFechaManual()">
            <button onclick="cambiarFecha(1)">Día Siguiente →</button>
        </div>

        <div class="grid">
            <!-- Panel de búsqueda y agregar alimentos -->
            <div class="card">
                <h3>🔍 Buscar Alimentos</h3>
                
                <select id="grupo-filtro" onchange="filtrarPorGrupo()">
                    <option value="">Todos los grupos</option>
                    <?php foreach ($grupos as $grupo): ?>
                        <option value="<?php echo $grupo['id']; ?>"><?php echo htmlspecialchars($grupo['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" id="buscar-alimento" class="search-box" placeholder="Buscar alimento..." onkeyup="buscarAlimentos()">
                
                <div id="resultados-busqueda" class="food-list"></div>
                
                <h4 style="margin-top: 20px;">⭐ Más Consumidos</h4>
                <div id="mas-consumidos" class="food-list">
                    <?php foreach ($mas_consumidos as $alimento): ?>
                        <div class="food-item" onclick="abrirModalSeleccion(<?php echo $alimento['id']; ?>, '<?php echo addslashes($alimento['nombre']); ?>', '<?php echo addslashes($alimento['marca'] ?? ''); ?>', '<?php echo $alimento['tipo_medida']; ?>', <?php echo $alimento['calorias_100']; ?>, <?php echo $alimento['proteinas_100']; ?>, <?php echo $alimento['carbohidratos_100']; ?>, <?php echo $alimento['grasas_100']; ?>)">
                            <div class="food-name"><?php echo htmlspecialchars($alimento['nombre']); ?></div>
                            <?php if ($alimento['marca']): ?>
                                <div class="food-brand"><?php echo htmlspecialchars($alimento['marca']); ?></div>
                            <?php endif; ?>
                            <div class="food-macros">
                                <?php echo $alimento['calorias_100']; ?> cal | 
                                P: <?php echo $alimento['proteinas_100']; ?>g | 
                                C: <?php echo $alimento['carbohidratos_100']; ?>g | 
                                G: <?php echo $alimento['grasas_100']; ?>g
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Panel de objetivos -->
            <div class="card">
                <h3>🎯 Objetivos Nutricionales</h3>
                <form id="form-objetivos">
                    <div class="input-group">
                        <label>Calorías:</label>
                        <input type="number" id="obj-calorias" value="<?php echo $objetivos['calorias_objetivo'] ?? 2000; ?>">
                    </div>
                    <div class="input-group">
                        <label>Proteínas (g):</label>
                        <input type="number" id="obj-proteinas" value="<?php echo $objetivos['proteinas_objetivo'] ?? 150; ?>">
                    </div>
                    <div class="input-group">
                        <label>Carbohidratos (g):</label>
                        <input type="number" id="obj-carbohidratos" value="<?php echo $objetivos['carbohidratos_objetivo'] ?? 250; ?>">
                    </div>
                    <div class="input-group">
                        <label>Grasas (g):</label>
                        <input type="number" id="obj-grasas" value="<?php echo $objetivos['grasas_objetivo'] ?? 67; ?>">
                    </div>
                    <button type="button" class="btn" onclick="actualizarObjetivos()">Actualizar Objetivos</button>
                </form>
            </div>
        </div>

        <!-- Resumen del día -->
        <div class="card">
            <h3>📊 Resumen del Día - <?php echo date('d/m/Y', strtotime($fecha_actual)); ?></h3>
            
            <div class="macro-grid">
                <div class="macro-item">
                    <div class="macro-value" id="total-calorias"><?php echo $resumen_dia['totales']['calorias'] ?? 0; ?></div>
                    <div class="macro-label">Calorías</div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-calorias" style="width: <?php echo min(100, (($resumen_dia['totales']['calorias'] ?? 0) / ($objetivos['calorias_objetivo'] ?? 2000)) * 100); ?>%">
                            <?php echo round((($resumen_dia['totales']['calorias'] ?? 0) / ($objetivos['calorias_objetivo'] ?? 2000)) * 100); ?>%
                        </div>
                    </div>
                </div>
                
                <div class="macro-item">
                    <div class="macro-value" id="total-proteinas"><?php echo $resumen_dia['totales']['proteinas'] ?? 0; ?>g</div>
                    <div class="macro-label">Proteínas</div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-proteinas" style="width: <?php echo min(100, (($resumen_dia['totales']['proteinas'] ?? 0) / ($objetivos['proteinas_objetivo'] ?? 150)) * 100); ?>%">
                            <?php echo round((($resumen_dia['totales']['proteinas'] ?? 0) / ($objetivos['proteinas_objetivo'] ?? 150)) * 100); ?>%
                        </div>
                    </div>
                </div>
                
                <div class="macro-item">
                    <div class="macro-value" id="total-carbohidratos"><?php echo $resumen_dia['totales']['carbohidratos'] ?? 0; ?>g</div>
                    <div class="macro-label">Carbohidratos</div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-carbohidratos" style="width: <?php echo min(100, (($resumen_dia['totales']['carbohidratos'] ?? 0) / ($objetivos['carbohidratos_objetivo'] ?? 250)) * 100); ?>%">
                            <?php echo round((($resumen_dia['totales']['carbohidratos'] ?? 0) / ($objetivos['carbohidratos_objetivo'] ?? 250)) * 100); ?>%
                        </div>
                    </div>
                </div>
                
                <div class="macro-item">
                    <div class="macro-value" id="total-grasas"><?php echo $resumen_dia['totales']['grasas'] ?? 0; ?>g</div>
                    <div class="macro-label">Grasas</div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-grasas" style="width: <?php echo min(100, (($resumen_dia['totales']['grasas'] ?? 0) / ($objetivos['grasas_objetivo'] ?? 67)) * 100); ?>%">
                            <?php echo round((($resumen_dia['totales']['grasas'] ?? 0) / ($objetivos['grasas_objetivo'] ?? 67)) * 100); ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comidas del día -->
        <div id="comidas-container">
            <?php
            $comidas_orden = [
                'desayuno' => '🌅 Desayuno',
                'snack1' => '🍎 Colación Matutina',
                'almuerzo' => '🍽️ Almuerzo',
                'snack2' => '🥨 Colación Vespertina',
                'cena' => '🌙 Cena'
            ];

            $comidas_existentes = [];
            if (isset($resumen_dia['por_comida'])) {
                foreach ($resumen_dia['por_comida'] as $comida) {
                    $comidas_existentes[$comida['comida']] = $comida;
                }
            }

            foreach ($comidas_orden as $comida_key => $comida_nombre):
                $comida_data = $comidas_existentes[$comida_key] ?? null;
            ?>
                <div class="meal-section">
                    <div class="meal-header">
                        <span class="meal-title"><?php echo $comida_nombre; ?></span>
                        <span class="meal-calories">
                            <?php echo $comida_data ? $comida_data['calorias'] : 0; ?> cal
                        </span>
                        <button class="btn" onclick="abrirModalAgregar('<?php echo $comida_key; ?>')">+ Agregar</button>
                    </div>
                    
                    <ul class="meal-foods" id="<?php echo $comida_key; ?>-foods">
                        <?php if ($comida_data && isset($comida_data['alimentos'])): ?>
                            <?php foreach ($comida_data['alimentos'] as $alimento): ?>
                                <li class="meal-food">
                                    <div class="food-info">
                                        <div class="food-name"><?php echo htmlspecialchars($alimento['nombre']); ?></div>
                                        <div class="food-details">
                                            <?php echo $alimento['cantidad']; ?><?php echo $alimento['tipo_medida']; ?> - 
                                            <?php echo $alimento['calorias']; ?> cal |
                                            P: <?php echo $alimento['proteinas']; ?>g |
                                            C: <?php echo $alimento['carbohidratos']; ?>g |
                                            G: <?php echo $alimento['grasas']; ?>g
                                        </div>
                                    </div>
                                    <button class="btn btn-danger" onclick="eliminarAlimento(<?php echo $alimento['id']; ?>)">×</button>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal mejorado para seleccionar alimentos -->
    <div id="modal-seleccion" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Agregar Alimento</h3>
                <span class="close" onclick="cerrarModalSeleccion()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Información del alimento seleccionado -->
                <div id="food-selection-info" class="food-selection-card">
                    <div class="food-name-display" id="selected-food-name"></div>
                    <div class="food-brand-display" id="selected-food-brand"></div>
                    <div class="food-macros-display">
                        <div class="macro-display-item">
                            <div class="macro-display-value" id="base-calorias">0</div>
                            <div class="macro-display-label">Calorías/100g</div>
                        </div>
                        <div class="macro-display-item">
                            <div class="macro-display-value" id="base-proteinas">0g</div>
                            <div class="macro-display-label">Proteínas/100g</div>
                        </div>
                        <div class="macro-display-item">
                            <div class="macro-display-value" id="base-carbohidratos">0g</div>
                            <div class="macro-display-label">Carbohidratos/100g</div>
                        </div>
                        <div class="macro-display-item">
                            <div class="macro-display-value" id="base-grasas">0g</div>
                            <div class="macro-display-label">Grasas/100g</div>
                        </div>
                    </div>
                </div>

                <!-- Selección de comida -->
                <div class="meal-selection">
                    <label>¿En qué comida deseas agregarlo?</label>
                    <div class="meal-buttons">
                        <button class="meal-btn" data-meal="desayuno" onclick="seleccionarComida('desayuno')">
                            🌅<br>Desayuno
                        </button>
                        <button class="meal-btn" data-meal="snack1" onclick="seleccionarComida('snack1')">
                            🍎<br>Colación Matutina
                        </button>
                        <button class="meal-btn" data-meal="almuerzo" onclick="seleccionarComida('almuerzo')">
                            🍽️<br>Almuerzo
                        </button>
                        <button class="meal-btn" data-meal="snack2" onclick="seleccionarComida('snack2')">
                            🥨<br>Colación Vespertina
                        </button>
                        <button class="meal-btn" data-meal="cena" onclick="seleccionarComida('cena')">
                            🌙<br>Cena
                        </button>
                    </div>
                </div>

                <!-- Cantidad -->
                <div class="quantity-section">
                    <div class="quantity-inputs">
                        <div class="quantity-input-group">
                            <label>Cantidad:</label>
                            <input type="number" id="food-quantity" step="0.1" value="100" onchange="calcularMacrosPreview()" oninput="calcularMacrosPreview()">
                        </div>
                        <div class="quantity-input-group">
                            <label>Medida:</label>
                            <select id="food-measure" onchange="calcularMacrosPreview()">
                                <option value="gramos">gramos</option>
                                <option value="mililitros">mililitros</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Preview de macros -->
                <div class="preview-macros">
                    <div class="preview-title">Información Nutricional</div>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <div class="preview-value" id="preview-calorias">0</div>
                            <div class="preview-label">Calorías</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-value" id="preview-proteinas">0g</div>
                            <div class="preview-label">Proteínas</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-value" id="preview-carbohidratos">0g</div>
                            <div class="carbohidratos-label">Carbohidratos</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-value" id="preview-grasas">0g</div>
                            <div class="preview-label">Grasas</div>
                        </div>
                    </div>
                </div>

                <!-- Botón para agregar -->
                <button class="add-food-btn" onclick="confirmarAgregarAlimento()" id="btn-confirmar-agregar" disabled>
                    Agregar Alimento
                </button>
            </div>
        </div>
    </div>

    <!-- Loading overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <script>
        // Variables globales para el modal
        let alimentoSeleccionado = null;
        let comidaSeleccionada = null;

        // Función para buscar alimentos
       // Función corregida para buscar alimentos
    function buscarAlimentos() {
        const termino = document.getElementById('buscar-alimento').value;
        const grupo = document.getElementById('grupo-filtro').value;
        
        // Si no hay término de búsqueda Y no hay grupo seleccionado, limpiar resultados
        if (termino.length < 2 && !grupo) {
            document.getElementById('resultados-busqueda').innerHTML = '';
            return;
        }
        
        // Si hay un grupo seleccionado pero no hay término suficiente, buscar solo por grupo
        // Si hay término suficiente, buscar con ambos criterios
        if (termino.length < 2 && grupo) {
            // Buscar solo por grupo
            showLoading();
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=buscar_alimentos&termino=&grupo=${grupo}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                mostrarResultadosBusqueda(data);
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error al buscar alimentos', 'error');
            });
        } else if (termino.length >= 2) {
            // Buscar con término (y grupo si está seleccionado)
            showLoading();
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=buscar_alimentos&termino=${encodeURIComponent(termino)}&grupo=${grupo}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                mostrarResultadosBusqueda(data);
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error al buscar alimentos', 'error');
            });
        }
    }

    // Función específica para cuando cambia el filtro de grupo
    function filtrarPorGrupo() {
        const grupo = document.getElementById('grupo-filtro').value;
        const termino = document.getElementById('buscar-alimento').value;
        
        if (!grupo) {
            // Si no hay grupo seleccionado, limpiar resultados si tampoco hay término
            if (termino.length < 2) {
                document.getElementById('resultados-busqueda').innerHTML = '';
                return;
            }
        }
        
        // Llamar a la función de búsqueda normal
        buscarAlimentos();
    }


        // Función para mostrar resultados de búsqueda
        function mostrarResultadosBusqueda(alimentos) {
            const container = document.getElementById('resultados-busqueda');
            
            if (alimentos.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #718096; padding: 20px;">No se encontraron alimentos</p>';
                return;
            }

            let html = '';
            alimentos.forEach(alimento => {
                const marca = alimento.marca ? `<div class="food-brand">${alimento.marca}</div>` : '';
                html += `
                    <div class="food-item" onclick="abrirModalSeleccion(${alimento.id}, '${alimento.nombre.replace(/'/g, "\\'")}', '${(alimento.marca || '').replace(/'/g, "\\'")}', '${alimento.tipo_medida}', ${alimento.calorias_100}, ${alimento.proteinas_100}, ${alimento.carbohidratos_100}, ${alimento.grasas_100})">
                        <div class="food-name">${alimento.nombre}</div>
                        ${marca}
                        <div class="food-macros">
                            ${alimento.calorias_100} cal | 
                            P: ${alimento.proteinas_100}g | 
                            C: ${alimento.carbohidratos_100}g | 
                            G: ${alimento.grasas_100}g
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Función principal para abrir el modal de selección
        function abrirModalSeleccion(id, nombre, marca, tipoMedida, calorias, proteinas, carbohidratos, grasas) {
            // Guardar datos del alimento seleccionado
            alimentoSeleccionado = {
                id: id,
                nombre: nombre,
                marca: marca,
                tipoMedida: tipoMedida,
                calorias: parseFloat(calorias),
                proteinas: parseFloat(proteinas),
                carbohidratos: parseFloat(carbohidratos),
                grasas: parseFloat(grasas)
            };

            // Actualizar información del alimento en el modal
            document.getElementById('selected-food-name').textContent = nombre;
            document.getElementById('selected-food-brand').textContent = marca || '';
            document.getElementById('selected-food-brand').style.display = marca ? 'block' : 'none';
            
            // Mostrar macros base por 100g
            document.getElementById('base-calorias').textContent = calorias;
            document.getElementById('base-proteinas').textContent = proteinas + 'g';
            document.getElementById('base-carbohidratos').textContent = carbohidratos + 'g';
            document.getElementById('base-grasas').textContent = grasas + 'g';

            // Configurar tipo de medida
            const selectMedida = document.getElementById('food-measure');
            selectMedida.innerHTML = '';
            
            if (tipoMedida === 'gramos' || tipoMedida === 'ambos') {
                selectMedida.innerHTML += '<option value="gramos">gramos</option>';
            }
            if (tipoMedida === 'mililitros' || tipoMedida === 'ambos') {
                selectMedida.innerHTML += '<option value="mililitros">mililitros</option>';
            }

            // Reset valores
            document.getElementById('food-quantity').value = '100';
            comidaSeleccionada = null;
            
            // Reset selección de comida
            document.querySelectorAll('.meal-btn').forEach(btn => {
                btn.classList.remove('selected');
            });

            // Calcular preview inicial
            calcularMacrosPreview();
            
            // Mostrar modal
            document.getElementById('modal-seleccion').style.display = 'block';
            
            // Deshabilitar botón hasta que se seleccione comida
            document.getElementById('btn-confirmar-agregar').disabled = true;
        }

        // Función para seleccionar comida
        function seleccionarComida(comida) {
            comidaSeleccionada = comida;
            
            // Actualizar UI
            document.querySelectorAll('.meal-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            document.querySelector(`[data-meal="${comida}"]`).classList.add('selected');
            
            // Habilitar botón de confirmación
            document.getElementById('btn-confirmar-agregar').disabled = false;
        }

        // Función para calcular macros en tiempo real
        function calcularMacrosPreview() {
            if (!alimentoSeleccionado) return;

            const cantidad = parseFloat(document.getElementById('food-quantity').value) || 0;
            const factor = cantidad / 100; // Factor de conversión desde 100g base

            // Calcular macros proporcionales
            const calorias = Math.round(alimentoSeleccionado.calorias * factor);
            const proteinas = Math.round(alimentoSeleccionado.proteinas * factor * 10) / 10;
            const carbohidratos = Math.round(alimentoSeleccionado.carbohidratos * factor * 10) / 10;
            const grasas = Math.round(alimentoSeleccionado.grasas * factor * 10) / 10;

            // Actualizar preview
            document.getElementById('preview-calorias').textContent = calorias;
            document.getElementById('preview-proteinas').textContent = proteinas + 'g';
            document.getElementById('preview-carbohidratos').textContent = carbohidratos + 'g';
            document.getElementById('preview-grasas').textContent = grasas + 'g';
        }

        // Función para confirmar y agregar alimento
        function confirmarAgregarAlimento() {
            if (!alimentoSeleccionado || !comidaSeleccionada) {
                showNotification('Por favor selecciona una comida', 'error');
                return;
            }

            const cantidad = parseFloat(document.getElementById('food-quantity').value);
            const tipoMedida = document.getElementById('food-measure').value;

            if (!cantidad || cantidad <= 0) {
                showNotification('Por favor ingresa una cantidad válida', 'error');
                return;
            }

            showLoading();

            const formData = new FormData();
            formData.append('action', 'registrar_comida');
            formData.append('alimento_id', alimentoSeleccionado.id);
            formData.append('fecha', document.getElementById('fecha-actual').value);
            formData.append('comida', comidaSeleccionada);
            formData.append('cantidad', cantidad);
            formData.append('tipo_medida', tipoMedida);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Alimento agregado correctamente', 'success');
                    cerrarModalSeleccion();
                    // Recargar la página para mostrar los cambios
                    location.reload();
                } else {
                    showNotification(data.mensaje || 'Error al agregar alimento', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error al procesar la solicitud', 'error');
            });
        }

        // Función para cerrar el modal
        function cerrarModalSeleccion() {
            document.getElementById('modal-seleccion').style.display = 'none';
            alimentoSeleccionado = null;
            comidaSeleccionada = null;
        }

        // Funciones de utilidad para loading y notificaciones
        function showLoading() {
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 4000);
        }

        // Funciones existentes mantenidas para compatibilidad
        function abrirModalAgregar(comida) {
            // Esta función podría mostrar una lista de alimentos frecuentes
            // Por ahora, mostramos un mensaje para que use la búsqueda
            showNotification('Usa la búsqueda de alimentos para agregar a ' + comida, 'info');
        }

        function cambiarFecha(direccion) {
            const fechaInput = document.getElementById('fecha-actual');
            const fechaActual = new Date(fechaInput.value);
            fechaActual.setDate(fechaActual.getDate() + direccion);
            
            const nuevaFecha = fechaActual.toISOString().split('T')[0];
            fechaInput.value = nuevaFecha;
            
            // Recargar página con nueva fecha
            window.location.href = '?' + 'fecha=' + nuevaFecha;
        }

        function cambiarFechaManual() {
            const nuevaFecha = document.getElementById('fecha-actual').value;
            window.location.href = '?' + 'fecha=' + nuevaFecha;
        }

        function eliminarAlimento(idRegistro) {
            if (!confirm('¿Estás seguro de que deseas eliminar este alimento?')) {
                return;
            }

            showLoading();

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=eliminar_registro&id_registro=${idRegistro}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Alimento eliminado', 'success');
                    location.reload();
                } else {
                    showNotification('Error al eliminar alimento', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error al procesar la solicitud', 'error');
            });
        }

        function actualizarObjetivos() {
            const calorias = document.getElementById('obj-calorias').value;
            const proteinas = document.getElementById('obj-proteinas').value;
            const carbohidratos = document.getElementById('obj-carbohidratos').value;
            const grasas = document.getElementById('obj-grasas').value;

            showLoading();

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=actualizar_objetivos&calorias=${calorias}&proteinas=${proteinas}&carbohidratos=${carbohidratos}&grasas=${grasas}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Objetivos actualizados', 'success');
                    location.reload();
                } else {
                    showNotification('Error al actualizar objetivos', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Error al procesar la solicitud', 'error');
            });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Cerrar modal al hacer clic fuera
            document.getElementById('modal-seleccion').addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalSeleccion();
                }
            });

            // Cerrar modal con Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('modal-seleccion').style.display === 'block') {
                    cerrarModalSeleccion();
                }
            });

            // Auto-buscar al escribir con debounce
            let timeoutId;
            document.getElementById('buscar-alimento').addEventListener('input', function() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(buscarAlimentos, 300);
            });
        });
    </script>
</body>
</html>