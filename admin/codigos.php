<?php

require_once '../config/GymAttendanceManager.php';

$manager = gym_attendance_manager();
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'generar_codigo':
            $resultado = $manager->generarCodigo(
                $_POST['nombre_codigo'],
                $_POST['descripcion'],
                $_POST['tipo_duracion'],
                $_POST['hora_inicio'],
                $_POST['hora_fin'],
                !empty($_POST['limite_usos']) ? intval($_POST['limite_usos']) : null,
                $_SESSION['user_id']
            );
            $mensaje = $resultado['message'];
            $tipo_mensaje = $resultado['success'] ? 'success' : 'error';
            if ($resultado['success']) {
                $mensaje .= " - Código: " . $resultado['codigo'];
            }
            break;
            
        case 'desactivar_codigo':
            $resultado = $manager->desactivarCodigo($_POST['id_codigo'], $_SESSION['user_id']);
            $mensaje = $resultado['message'];
            $tipo_mensaje = $resultado['success'] ? 'success' : 'error';
            break;
            
        case 'configurar_tolerancia':
            $resultado = $manager->configurarTolerancia(
                $_POST['dia_tolerancia'],
                isset($_POST['tolerancia_activa']),
                intval($_POST['racha_minima_premio'])
            );
            $mensaje = $resultado['message'];
            $tipo_mensaje = $resultado['success'] ? 'success' : 'error';
            break;
    }
}

// Obtener datos para mostrar
$codigos_activos = $manager->obtenerCodigosActivos(20);
$configuracion_tolerancia = $manager->obtenerConfiguracionTolerancia();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Códigos - Gym</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #4f46e5;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4338ca;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .main-content {
            margin: 2rem 0;
        }
        
        .tabs {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .tab-buttons {
            display: flex;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .tab-button {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.2s;
        }
        
        .tab-button.active {
            background: white;
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
        }
        
        .tab-content {
            display: none;
            padding: 2rem;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .grid-2 {
            grid-template-columns: 1fr 1fr;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin-bottom: 1rem;
            color: #374151;
            font-size: 1.2rem;
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
            font-size: 0.9rem;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th, .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .table tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .codigo-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .codigo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .codigo-number {
            font-size: 1.1rem;
            font-weight: bold;
            font-family: monospace;
            color: #4f46e5;
            background: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        
        .codigo-info {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .codigo-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .tab-buttons {
                flex-wrap: wrap;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1>🏋️ Gestión de Códigos</h1>
                <div class="user-info">
                    <span>👤 Admin: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?></span>
                    <a href="logout.php" class="btn btn-secondary">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="main-content">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <div class="tabs">
                <div class="tab-buttons">
                    <button class="tab-button active" onclick="showTab('codigos')">📱 Códigos</button>
                    <button class="tab-button" onclick="showTab('configuracion')">⚙️ Configuración</button>
                </div>

                <!-- Tab: Códigos -->
                <div id="codigos" class="tab-content active">
                    <div class="grid grid-2">
                        <div class="card">
                            <h3>Generar Nuevo Código</h3>
                            <form method="POST">
                                <input type="hidden" name="accion" value="generar_codigo">
                                
                                <div class="form-group">
                                    <label class="form-label">Nombre del Código</label>
                                    <input type="text" name="nombre_codigo" class="form-input" required 
                                           placeholder="Ej: Código Diario Enero">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Descripción</label>
                                    <textarea name="descripcion" class="form-textarea" rows="3" required
                                              placeholder="Descripción del propósito del código"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Tipo de Duración</label>
                                    <select name="tipo_duracion" class="form-select">
                                        <option value="dia">Diario (expira al final del día)</option>
                                        <option value="semana">Semanal</option>
                                        <option value="mes">Mensual</option>
                                        <option value="personalizado">Personalizado</option>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Hora Inicio</label>
                                        <input type="time" name="hora_inicio" class="form-input" value="06:00">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Hora Fin</label>
                                        <input type="time" name="hora_fin" class="form-input" value="23:59">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Límite de Usos (opcional)</label>
                                    <input type="number" name="limite_usos" class="form-input" min="1"
                                           placeholder="Dejar vacío para usos ilimitados">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">🎯 Generar Código</button>
                            </form>
                        </div>

                        <div class="card">
                            <h3>Códigos Activos (<?= count($codigos_activos) ?>)</h3>
                            <div style="max-height: 500px; overflow-y: auto;">
                                <?php if (empty($codigos_activos)): ?>
                                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                                        📝 No hay códigos activos
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($codigos_activos as $codigo): ?>
                                    <div class="codigo-card">
                                        <div class="codigo-header">
                                            <div>
                                                <div class="codigo-number"><?= htmlspecialchars($codigo['codigo']) ?></div>
                                                <div style="font-weight: 500; color: #374151; margin-top: 0.25rem;">
                                                    <?= htmlspecialchars($codigo['nombre_codigo']) ?>
                                                </div>
                                            </div>
                                            <div>
                                                <?php
                                                $ahora = new DateTime();
                                                $expira = new DateTime($codigo['fecha_expiracion']);
                                                if ($expira < $ahora): ?>
                                                    <span class="badge badge-danger">Expirado</span>
                                                <?php elseif ($codigo['limite_usos'] && $codigo['usos_totales'] >= $codigo['limite_usos']): ?>
                                                    <span class="badge badge-warning">Sin usos</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="codigo-info">
                                            <?= htmlspecialchars($codigo['descripcion']) ?>
                                        </div>
                                        
                                        <div class="codigo-meta">
                                            <span>
                                                Usos: <?= $codigo['usos_totales'] ?>
                                                <?php if ($codigo['limite_usos']): ?>
                                                    / <?= $codigo['limite_usos'] ?>
                                                <?php endif; ?>
                                            </span>
                                            <span>
                                                Expira: <?= date('d/m/Y H:i', strtotime($codigo['fecha_expiracion'])) ?>
                                            </span>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="accion" value="desactivar_codigo">
                                                <input type="hidden" name="id_codigo" value="<?= $codigo['id'] ?>">
                                                <button type="submit" class="btn btn-danger" 
                                                        onclick="return confirm('¿Desactivar este código?')"
                                                        style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">
                                                    🚫 Desactivar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Configuración -->
                <div id="configuracion" class="tab-content">
                    <div class="card">
                        <h3>⚙️ Configuración de Tolerancia</h3>
                        <form method="POST">
                            <input type="hidden" name="accion" value="configurar_tolerancia">
                            
                            <div class="form-group">
                                <label class="form-label">Día de Tolerancia</label>
                                <select name="dia_tolerancia" class="form-select">
                                    <option value="lunes" <?= ($configuracion_tolerancia['dia_tolerancia'] ?? '') === 'lunes' ? 'selected' : '' ?>>Lunes</option>
                                    <option value="martes" <?= ($configuracion_tolerancia['dia_tolerancia'] ?? '') === 'martes' ? 'selected' : '' ?>>Martes</option>
                                    <option value="miercoles" <?= ($configuracion_tolerancia['dia_tolerancia'] ?? '') === 'miercoles' ? 'selected' : '' ?>>Miércoles</option>
                                    <option value="jueves" <?= ($configuracion_tolerancia['dia_tolerancia'] ?? '') === 'jueves' ? 'selected' : '' ?>>Jueves</option>
                                    <option value="viernes" <?= ($configuracion_tolerancia['dia_tolerancia'] ?? '') === 'viernes' ? 'selected' : '' ?>>Viernes</option>
                                    <option value="sabado" <?= ($configuracion_tolerancia['dia_tolerancia'] ?? '') === 'sabado' ? 'selected' : '' ?>>Sábado</option>
                                    <option value="domingo" <?= ($configuracion_tolerancia['dia_tolerancia'] ?? '') === 'domingo' ? 'selected' : '' ?>>Domingo</option>
                                </select>
                                <small style="color: #6b7280;">Día en el que se puede aplicar tolerancia para mantener la racha</small>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="tolerancia_activa" 
                                           <?= ($configuracion_tolerancia['tolerancia_activa'] ?? false) ? 'checked' : '' ?>>
                                    <span class="form-label" style="margin: 0;">Tolerancia Activa</span>
                                </label>
                                <small style="color: #6b7280;">Permite que los usuarios mantengan su racha saltándose un día</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Racha Mínima para Premio</label>
                                <input type="number" name="racha_minima_premio" class="form-input" min="1" 
                                       value="<?= $configuracion_tolerancia['racha_minima_premio'] ?? 7 ?>">
                                <small style="color: #6b7280;">Días consecutivos necesarios para obtener premios</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">💾 Guardar Configuración</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para cambiar entre tabs
        function showTab(tabName) {
            // Ocultar todos los contenidos
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Desactivar todos los botones
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));
            
            // Mostrar el contenido seleccionado
            document.getElementById(tabName).classList.add('active');
            
            // Activar el botón seleccionado
            event.target.classList.add('active');
        }

        // Validación en tiempo real para formularios
        document.addEventListener('DOMContentLoaded', function() {
            // Validar que las horas de inicio sean menores que las de fin
            const horaInicio = document.querySelector('input[name="hora_inicio"]');
            const horaFin = document.querySelector('input[name="hora_fin"]');
            
            if (horaInicio && horaFin) {
                function validarHoras() {
                    if (horaInicio.value && horaFin.value && horaInicio.value >= horaFin.value) {
                        horaFin.setCustomValidity('La hora de fin debe ser posterior a la hora de inicio');
                    } else {
                        horaFin.setCustomValidity('');
                    }
                }
                
                horaInicio.addEventListener('change', validarHoras);
                horaFin.addEventListener('change', validarHoras);
            }
        });

        // Función para copiar código al portapapeles
        function copiarCodigo(codigo) {
            navigator.clipboard.writeText(codigo).then(() => {
                mostrarNotificacion('Código copiado: ' + codigo, 'success');
            }).catch(() => {
                // Fallback para navegadores que no soportan clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = codigo;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                mostrarNotificacion('Código copiado: ' + codigo, 'success');
            });
        }

        // Función para mostrar notificaciones
        function mostrarNotificacion(mensaje, tipo = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${tipo === 'success' ? '#059669' : tipo === 'error' ? '#dc2626' : '#4f46e5'};
                color: white;
                border-radius: 6px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 1000;
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = mensaje;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Añadir event listeners para copiar códigos al hacer clic
        document.addEventListener('DOMContentLoaded', function() {
            const codigosNumeros = document.querySelectorAll('.codigo-number');
            codigosNumeros.forEach(codigoEl => {
                codigoEl.style.cursor = 'pointer';
                codigoEl.title = 'Clic para copiar';
                codigoEl.addEventListener('click', () => {
                    copiarCodigo(codigoEl.textContent);
                });
            });
        });

        // Añadir estilos de animación
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>