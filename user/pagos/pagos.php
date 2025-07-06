<?php
require_once('../../config/config.php');
require_once('../../config/User.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$usuario_id = $_SESSION['user_id'];
$user = new User();

// Procesar solicitud de suscripción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $respuesta = ['success' => false, 'message' => ''];
    
    switch ($_POST['accion']) {
        case 'solicitar_suscripcion':
            // Validar datos requeridos
            if (empty($_POST['tipo_suscripcion']) || empty($_POST['modalidad_pago']) || empty($_POST['monto'])) {
                $respuesta['message'] = 'Todos los campos son requeridos';
                break;
            }
            
            // Calcular fecha de fin según el tipo de suscripción
            $fecha_inicio = date('Y-m-d');
            $fecha_fin = calcularFechaFin($_POST['tipo_suscripcion'], $fecha_inicio);
            
            $datos = [
                'tipo_suscripcion' => $_POST['tipo_suscripcion'],
                'modalidad_pago' => $_POST['modalidad_pago'],
                'monto' => $_POST['monto'],
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'referencia_pago' => $_POST['referencia_pago'] ?? null
            ];
            
            $resultado = $user->create_subscription($usuario_id, $datos);
            
            if ($resultado['success']) {
                $respuesta['success'] = true;
                $respuesta['message'] = 'Suscripción procesada exitosamente';
                $respuesta['datos'] = $resultado;
            } else {
                $respuesta['message'] = $resultado['message'];
            }
            break;
            
        case 'obtener_precios':
            $respuesta['success'] = true;
            $respuesta['precios'] = obtenerPreciosSuscripcion();
            break;
            
        case 'obtener_suscripcion_actual':
            $suscripcion = $user->get_active_subscription($usuario_id);
            $respuesta['success'] = true;
            $respuesta['suscripcion'] = $suscripcion;
            break;
            
        case 'obtener_historial':
            $historial = $user->get_user_subscriptions($usuario_id);
            $respuesta['success'] = true;
            $respuesta['historial'] = $historial;
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit();
}

// Obtener información del usuario y su suscripción actual
$suscripcion_actual = $user->get_active_subscription($usuario_id);
$historial_pagos = $user->get_user_subscriptions($usuario_id);

// Función para obtener precios de suscripción
function obtenerPreciosSuscripcion() {
    return [
        'semanal' => ['precio' => 150, 'descuento' => 0, 'duracion' => '1 semana'],
        'mensual' => ['precio' => 500, 'descuento' => 0, 'duracion' => '1 mes'],
        'trimestral' => ['precio' => 1350, 'descuento' => 10, 'duracion' => '3 meses'],
        'semestral' => ['precio' => 2400, 'descuento' => 20, 'duracion' => '6 meses'],
        'anual' => ['precio' => 4200, 'descuento' => 30, 'duracion' => '1 año']
    ];
}

// Función para calcular fecha de fin según tipo de suscripción
function calcularFechaFin($tipo_suscripcion, $fecha_inicio) {
    $fecha = new DateTime($fecha_inicio);
    
    switch ($tipo_suscripcion) {
        case 'semanal':
            $fecha->add(new DateInterval('P7D'));
            break;
        case 'mensual':
            $fecha->add(new DateInterval('P1M'));
            break;
        case 'trimestral':
            $fecha->add(new DateInterval('P3M'));
            break;
        case 'semestral':
            $fecha->add(new DateInterval('P6M'));
            break;
        case 'anual':
            $fecha->add(new DateInterval('P1Y'));
            break;
        default:
            $fecha->add(new DateInterval('P1M'));
    }
    
    return $fecha->format('Y-m-d');
}

$precios = obtenerPreciosSuscripcion();

// Información de la cuenta bancaria
$datos_cuenta = [
    'banco' => 'Banco Nacional',
    'titular' => 'Gimnasio FitLife',
    'numero_cuenta' => '1234-5678-9012-3456',
    'clabe' => '012345678901234567',
    'sucursal' => 'Centro'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripciones - Gimnasio FitLife</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .subscription-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .subscription-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            transition: transform 0.2s ease;
            border: 1px solid #e5e7eb;
        }

        .subscription-card:hover {
            transform: translateY(-2px);
        }

        .subscription-card.popular {
            border: 2px solid #3b82f6;
            position: relative;
        }

        .popular-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #3b82f6;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .subscription-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        .subscription-price {
            font-size: 2rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 0.25rem;
        }

        .subscription-duration {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .subscription-discount {
            background: #dcfce7;
            color: #166534;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .select-btn {
            width: 100%;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .select-btn:hover {
            background: #2563eb;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .subscription-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            margin: 0.25rem 0;
        }
        
        .subscription-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .subscription-expired {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .subscription-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .payment-method {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            background: #e0e7ff;
            color: #3730a3;
            display: inline-block;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            overflow-y: auto;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.active {
            opacity: 1;
        }

        .modal-content {
            background: white;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            position: relative;
            top: 5%;
        }

        .modal.active .modal-content {
            transform: translateY(0);
        }

        @media (min-height: 600px) {
            .modal-content {
                top: 50%;
                transform: translateY(calc(-50% - 20px));
            }
            
            .modal.active .modal-content {
                transform: translateY(-50%);
            }
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            color: #6b7280;
            transition: color 0.2s ease;
        }

        .close:hover {
            color: #374151;
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

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: #2563eb;
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

        .bank-info {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }

        .bank-info h4 {
            margin-bottom: 1rem;
            color: #1f2937;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bank-details {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .bank-details strong {
            color: #3b82f6;
            font-weight: 600;
        }

        .current-subscription-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-weight: 500;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .info-value {
            font-weight: 600;
            color: #1f2937;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .subscription-grid {
                grid-template-columns: 1fr;
            }
            
            .bank-details {
                grid-template-columns: 1fr;
            }
        }
        .header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-back {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-back:hover {
    background: rgba(255, 255, 255, 0.2);
}
    </style>
</head>
<body>
    <div class="header">
    <div class="header-content">
        <h1><i class="fas fa-credit-card"></i> Suscripciones</h1>
        <div class="header-actions">
            <a href="/dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Regresar al Dashboard
            </a>
        </div>
    </div>
</div>
    
    <div class="container">
        <!-- Suscripción Actual -->
        <?php if ($suscripcion_actual): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-user-check"></i> 
                    Tu Suscripción Actual
                </h3>
            </div>
            <div style="padding: 1.5rem;">
                <div class="current-subscription-info">
                    <div class="info-item">
                        <span class="info-label">Tipo de Suscripción</span>
                        <span class="info-value"><?php echo ucfirst($suscripcion_actual['tipo_suscripcion']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Estado</span>
                        <span class="subscription-badge subscription-<?php echo $suscripcion_actual['estado']; ?>">
                            <?php echo ucfirst($suscripcion_actual['estado']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fecha de Vencimiento</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($suscripcion_actual['fecha_fin'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Modalidad de Pago</span>
                        <span class="payment-method"><?php echo ucfirst($suscripcion_actual['modalidad_pago']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Planes de Suscripción -->
        <div class="subscription-grid">
            <?php foreach ($precios as $tipo => $info): ?>
            <div class="subscription-card <?php echo $tipo === 'mensual' ? 'popular' : ''; ?>">
                <?php if ($tipo === 'mensual'): ?>
                <div class="popular-badge">Más Popular</div>
                <?php endif; ?>
                
                <div class="subscription-title"><?php echo ucfirst($tipo); ?></div>
                <div class="subscription-price">$<?php echo number_format($info['precio']); ?></div>
                <div class="subscription-duration"><?php echo $info['duracion']; ?></div>
                
                <?php if ($info['descuento'] > 0): ?>
                <div class="subscription-discount">
                    <?php echo $info['descuento']; ?>% de descuento
                </div>
                <?php endif; ?>
                
                <button class="select-btn" onclick="abrirModalSuscripcion('<?php echo $tipo; ?>', <?php echo $info['precio']; ?>)">
                    <i class="fas fa-check"></i>
                    Seleccionar Plan
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Historial de Pagos -->
        <?php if (!empty($historial_pagos)): ?>
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-history"></i> 
                    Historial de Suscripciones
                </h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial_pagos as $pago): ?>
                        <tr>
                            <td><?php echo ucfirst($pago['tipo_suscripcion']); ?></td>
                            <td>$<?php echo number_format($pago['monto']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($pago['fecha_inicio'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($pago['fecha_fin'])); ?></td>
                            <td>
                                <span class="subscription-badge subscription-<?php echo $pago['estado']; ?>">
                                    <?php echo ucfirst($pago['estado']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal de Suscripción -->
    <div id="modalSuscripcion" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalSuscripcion()">&times;</span>
            <h2>Procesar Suscripción</h2>
            <div id="alertContainer"></div>
            
            <form id="formSuscripcion">
                <input type="hidden" id="tipoSuscripcion" name="tipo_suscripcion">
                <input type="hidden" id="montoSuscripcion" name="monto">
                <input type="hidden" name="accion" value="solicitar_suscripcion">
                
                <div class="form-group">
                    <label class="form-label">Plan Seleccionado:</label>
                    <input type="text" id="planSeleccionado" class="form-input" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Monto a Pagar:</label>
                    <input type="text" id="montoMostrar" class="form-input" readonly>
                </div>
                
                <div class="form-group">
                    <label for="modalidad_pago" class="form-label">Modalidad de Pago:</label>
                    <select id="modalidad_pago" name="modalidad_pago" class="form-input" required>
                        <option value="">Seleccionar...</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="referencia_pago" class="form-label">Referencia de Pago (opcional):</label>
                    <input type="text" id="referencia_pago" name="referencia_pago" class="form-input"
                           placeholder="Número de transacción, folio, etc.">
                </div>
                
                <div class="bank-info">
                    <h4><i class="fas fa-university"></i> Información Bancaria</h4>
                    <div class="bank-details">
                        <strong>Banco:</strong> <span><?php echo $datos_cuenta['banco']; ?></span>
                        <strong>Titular:</strong> <span><?php echo $datos_cuenta['titular']; ?></span>
                        <strong>Número de Cuenta:</strong> <span><?php echo $datos_cuenta['numero_cuenta']; ?></span>
                        <strong>CLABE:</strong> <span><?php echo $datos_cuenta['clabe']; ?></span>
                        <strong>Sucursal:</strong> <span><?php echo $datos_cuenta['sucursal']; ?></span>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 1.5rem;">
                    <i class="fas fa-check"></i> Procesar Suscripción
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function abrirModalSuscripcion(tipo, monto) {
            document.getElementById('tipoSuscripcion').value = tipo;
            document.getElementById('montoSuscripcion').value = monto;
            document.getElementById('planSeleccionado').value = tipo.charAt(0).toUpperCase() + tipo.slice(1);
            document.getElementById('montoMostrar').value = '$' + monto.toLocaleString();
            
            const modal = document.getElementById('modalSuscripcion');
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('active'), 10);
            
            document.getElementById('alertContainer').innerHTML = '';
        }
        
        function cerrarModalSuscripcion() {
            const modal = document.getElementById('modalSuscripcion');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('formSuscripcion').reset();
            }, 300);
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalSuscripcion');
            if (event.target === modal) {
                cerrarModalSuscripcion();
            }
        }
        
        // Manejar envío del formulario
        document.getElementById('formSuscripcion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const alertContainer = document.getElementById('alertContainer');
                
                if (data.success) {
                    alertContainer.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alertContainer.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> ' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('alertContainer').innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> Error al procesar la solicitud</div>';
            });
        });
    </script>
</body>
</html>