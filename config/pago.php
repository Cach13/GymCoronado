<?php
require_once __DIR__ . '/config.php';

class GymPagos {
    private $conn;
    
    public function __construct($conexion) {
        $this->conn = $conexion;
    }
    
    /**
     * Registrar un nuevo pago
     */
    public function registrarPago($datos) {
        try {
            $validacion = $this->validarPago($datos);
            if (!$validacion['valido']) {
                return [
                    'success' => false,
                    'error' => 'Datos inválidos: ' . implode(', ', $validacion['errores'])
                ];
            }
            
            $sql = "INSERT INTO pagos (
                id_usuario, monto, modalidad_pago, fecha_pago, 
                referencia_pago, notas, registrado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $datos['id_usuario'],
                $datos['monto'],
                $datos['modalidad_pago'],
                $datos['fecha_pago'],
                $datos['referencia_pago'] ?? null,
                $datos['notas'] ?? null,
                $datos['registrado_por']
            ]);
            
            return [
                'success' => true,
                'id_pago' => $this->conn->lastInsertId(),
                'mensaje' => 'Pago registrado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al registrar pago: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Renovar suscripción con pago
     */
    public function renovarSuscripcion($datos) {
        try {
            $validacion = $this->validarPago($datos);
            if (!$validacion['valido']) {
                return [
                    'success' => false,
                    'error' => 'Datos inválidos: ' . implode(', ', $validacion['errores'])
                ];
            }
            
            if (empty($datos['tipo_suscripcion'])) {
                return [
                    'success' => false,
                    'error' => 'Tipo de suscripción es requerido'
                ];
            }
            
            $this->conn->beginTransaction();
            
            // 1. Finalizar suscripción activa actual si existe
            $sql_finalizar = "UPDATE suscripciones 
                             SET estado = 'vencida' 
                             WHERE id_usuario = ? AND estado = 'activa'";
            $stmt = $this->conn->prepare($sql_finalizar);
            $stmt->execute([$datos['id_usuario']]);
            
            // 2. Registrar el pago
            $sql_pago = "INSERT INTO pagos (
                id_usuario, monto, modalidad_pago, fecha_pago, 
                referencia_pago, notas, registrado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql_pago);
            $stmt->execute([
                $datos['id_usuario'],
                $datos['monto'],
                $datos['modalidad_pago'],
                $datos['fecha_pago'],
                $datos['referencia_pago'] ?? null,
                $datos['notas'] ?? null,
                $datos['registrado_por']
            ]);
            
            $id_pago = $this->conn->lastInsertId();
            
            // 3. Calcular fechas de la nueva suscripción
            $fecha_inicio = $datos['fecha_pago'];
            $fecha_fin = $this->calcularFechaFin($fecha_inicio, $datos['tipo_suscripcion']);
            
            // 4. Crear nueva suscripción
            $sql_suscripcion = "INSERT INTO suscripciones (
                id_usuario, id_pago, tipo_suscripcion, 
                fecha_inicio, fecha_fin, estado
            ) VALUES (?, ?, ?, ?, ?, 'activa')";
            
            $stmt = $this->conn->prepare($sql_suscripcion);
            $stmt->execute([
                $datos['id_usuario'],
                $id_pago,
                $datos['tipo_suscripcion'],
                $fecha_inicio,
                $fecha_fin
            ]);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'id_pago' => $id_pago,
                'id_suscripcion' => $this->conn->lastInsertId(),
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'mensaje' => 'Suscripción renovada exitosamente'
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'error' => 'Error al renovar suscripción: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calcular fecha de fin según tipo de suscripción
     */
    private function calcularFechaFin($fecha_inicio, $tipo_suscripcion) {
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
                throw new Exception('Tipo de suscripción no válido');
        }
        
        return $fecha->format('Y-m-d');
    }
    
    /**
     * Obtener historial de pagos por usuario
     */
    public function obtenerHistorialPagos($id_usuario, $limite = 10, $offset = 0) {
        try {
            $sql = "SELECT 
                p.id,
                p.monto,
                p.modalidad_pago,
                p.fecha_pago,
                p.referencia_pago,
                p.notas,
                p.fecha_registro,
                u.nombre as registrado_por_nombre,
                s.tipo_suscripcion,
                s.fecha_inicio as suscripcion_inicio,
                s.fecha_fin as suscripcion_fin,
                s.estado as estado_suscripcion
            FROM pagos p
            LEFT JOIN usuarios u ON p.registrado_por = u.id
            LEFT JOIN suscripciones s ON p.id = s.id_pago
            WHERE p.id_usuario = ?
            ORDER BY p.fecha_pago DESC, p.fecha_registro DESC
            LIMIT ? OFFSET ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id_usuario, $limite, $offset]);
            
            return [
                'success' => true,
                'pagos' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener historial: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener todos los pagos con filtros
     */
    public function obtenerPagos($filtros = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            // Construir condiciones WHERE dinámicamente
            if (!empty($filtros['fecha_desde'])) {
                $where_conditions[] = "p.fecha_pago >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $where_conditions[] = "p.fecha_pago <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
            
            if (!empty($filtros['modalidad_pago'])) {
                $where_conditions[] = "p.modalidad_pago = ?";
                $params[] = $filtros['modalidad_pago'];
            }
            
            if (!empty($filtros['monto_min'])) {
                $where_conditions[] = "p.monto >= ?";
                $params[] = $filtros['monto_min'];
            }
            
            if (!empty($filtros['monto_max'])) {
                $where_conditions[] = "p.monto <= ?";
                $params[] = $filtros['monto_max'];
            }
            
            if (!empty($filtros['usuario_nombre'])) {
                $where_conditions[] = "(u.nombre LIKE ? OR u.apellido LIKE ?)";
                $params[] = '%' . $filtros['usuario_nombre'] . '%';
                $params[] = '%' . $filtros['usuario_nombre'] . '%';
            }
            
            if (!empty($filtros['tipo_suscripcion'])) {
                $where_conditions[] = "s.tipo_suscripcion = ?";
                $params[] = $filtros['tipo_suscripcion'];
            }
            
            if (!empty($filtros['estado_suscripcion'])) {
                $where_conditions[] = "s.estado = ?";
                $params[] = $filtros['estado_suscripcion'];
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $sql = "SELECT 
                p.id,
                p.monto,
                p.modalidad_pago,
                p.fecha_pago,
                p.referencia_pago,
                p.notas,
                p.fecha_registro,
                u.nombre,
                u.apellido,
                u.email,
                reg.nombre as registrado_por_nombre,
                s.tipo_suscripcion,
                s.fecha_inicio,
                s.fecha_fin,
                s.estado as estado_suscripcion
            FROM pagos p
            JOIN usuarios u ON p.id_usuario = u.id
            LEFT JOIN usuarios reg ON p.registrado_por = reg.id
            LEFT JOIN suscripciones s ON p.id = s.id_pago
            $where_clause
            ORDER BY p.fecha_pago DESC, p.fecha_registro DESC";
            
            $limite = $filtros['limite'] ?? 50;
            $offset = $filtros['offset'] ?? 0;
            
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limite;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'pagos' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener pagos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas de pagos
     */
    public function obtenerEstadisticasPagos($fecha_inicio = null, $fecha_fin = null) {
        try {
            $where_clause = "";
            $params = [];
            
            if ($fecha_inicio && $fecha_fin) {
                $where_clause = "WHERE p.fecha_pago BETWEEN ? AND ?";
                $params = [$fecha_inicio, $fecha_fin];
            } elseif ($fecha_inicio) {
                $where_clause = "WHERE p.fecha_pago >= ?";
                $params = [$fecha_inicio];
            } elseif ($fecha_fin) {
                $where_clause = "WHERE p.fecha_pago <= ?";
                $params = [$fecha_fin];
            }
            
            // Estadísticas generales
            $sql_general = "SELECT 
                COUNT(*) as total_pagos,
                SUM(p.monto) as ingresos_totales,
                AVG(p.monto) as promedio_pago,
                MIN(p.monto) as pago_minimo,
                MAX(p.monto) as pago_maximo
            FROM pagos p
            $where_clause";
            
            $stmt = $this->conn->prepare($sql_general);
            $stmt->execute($params);
            $estadisticas_generales = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Estadísticas por modalidad de pago
            $sql_modalidad = "SELECT 
                p.modalidad_pago,
                COUNT(*) as cantidad,
                SUM(p.monto) as total_monto,
                AVG(p.monto) as promedio_monto
            FROM pagos p
            $where_clause
            GROUP BY p.modalidad_pago
            ORDER BY total_monto DESC";
            
            $stmt = $this->conn->prepare($sql_modalidad);
            $stmt->execute($params);
            $por_modalidad = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estadísticas por tipo de suscripción
            $sql_suscripcion = "SELECT 
                s.tipo_suscripcion,
                COUNT(*) as cantidad,
                SUM(p.monto) as total_monto,
                AVG(p.monto) as promedio_monto
            FROM pagos p
            JOIN suscripciones s ON p.id = s.id_pago
            $where_clause
            GROUP BY s.tipo_suscripcion
            ORDER BY total_monto DESC";
            
            $stmt = $this->conn->prepare($sql_suscripcion);
            $stmt->execute($params);
            $por_tipo_suscripcion = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ingresos por mes
            $sql_mensual = "SELECT 
                DATE_FORMAT(p.fecha_pago, '%Y-%m') as mes,
                COUNT(*) as cantidad_pagos,
                SUM(p.monto) as ingresos_mes
            FROM pagos p
            $where_clause
            GROUP BY DATE_FORMAT(p.fecha_pago, '%Y-%m')
            ORDER BY mes DESC
            LIMIT 12";
            
            $stmt = $this->conn->prepare($sql_mensual);
            $stmt->execute($params);
            $ingresos_mensuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estadísticas por estado de suscripción
            $sql_estado = "SELECT 
                s.estado,
                COUNT(*) as cantidad,
                SUM(p.monto) as total_monto
            FROM pagos p
            JOIN suscripciones s ON p.id = s.id_pago
            $where_clause
            GROUP BY s.estado
            ORDER BY total_monto DESC";
            
            $stmt = $this->conn->prepare($sql_estado);
            $stmt->execute($params);
            $por_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'estadisticas' => [
                    'generales' => $estadisticas_generales,
                    'por_modalidad' => $por_modalidad,
                    'por_tipo_suscripcion' => $por_tipo_suscripcion,
                    'por_estado' => $por_estado,
                    'ingresos_mensuales' => $ingresos_mensuales
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Buscar pagos por referencia
     */
    public function buscarPorReferencia($referencia) {
        try {
            $sql = "SELECT 
                p.id,
                p.monto,
                p.modalidad_pago,
                p.fecha_pago,
                p.referencia_pago,
                p.notas,
                u.nombre,
                u.apellido,
                u.email,
                s.tipo_suscripcion,
                s.fecha_inicio,
                s.fecha_fin,
                s.estado as estado_suscripcion
            FROM pagos p
            JOIN usuarios u ON p.id_usuario = u.id
            LEFT JOIN suscripciones s ON p.id = s.id_pago
            WHERE p.referencia_pago LIKE ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['%' . $referencia . '%']);
            
            return [
                'success' => true,
                'pagos' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error en la búsqueda: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener pagos pendientes de procesar (sin suscripción asociada)
     */
    public function obtenerPagosPendientes() {
        try {
            $sql = "SELECT 
                p.id,
                p.monto,
                p.modalidad_pago,
                p.fecha_pago,
                p.referencia_pago,
                p.notas,
                u.nombre,
                u.apellido,
                u.email,
                u.telefono
            FROM pagos p
            JOIN usuarios u ON p.id_usuario = u.id
            LEFT JOIN suscripciones s ON p.id = s.id_pago
            WHERE s.id IS NULL
            ORDER BY p.fecha_pago DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return [
                'success' => true,
                'pagos_pendientes' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener pagos pendientes: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar reporte de ingresos
     */
    public function generarReporteIngresos($fecha_inicio, $fecha_fin) {
        try {
            $sql = "SELECT 
                DATE(p.fecha_pago) as fecha,
                COUNT(*) as total_pagos,
                SUM(p.monto) as ingresos_dia,
                GROUP_CONCAT(DISTINCT p.modalidad_pago) as modalidades_usadas,
                AVG(p.monto) as promedio_pago
            FROM pagos p
            WHERE p.fecha_pago BETWEEN ? AND ?
            GROUP BY DATE(p.fecha_pago)
            ORDER BY fecha DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$fecha_inicio, $fecha_fin]);
            
            return [
                'success' => true,
                'reporte' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al generar reporte: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener suscripción activa de un usuario
     */
    public function obtenerSuscripcionActiva($id_usuario) {
        try {
            $sql = "SELECT 
                s.id,
                s.tipo_suscripcion,
                s.fecha_inicio,
                s.fecha_fin,
                s.estado,
                p.monto,
                p.modalidad_pago,
                p.fecha_pago
            FROM suscripciones s
            LEFT JOIN pagos p ON s.id_pago = p.id
            WHERE s.id_usuario = ? AND s.estado = 'activa'
            ORDER BY s.fecha_inicio DESC
            LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id_usuario]);
            
            return [
                'success' => true,
                'suscripcion' => $stmt->fetch(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener suscripción: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar si un usuario tiene suscripción activa
     */
    public function tienesSuscripcionActiva($id_usuario) {
        try {
            $sql = "SELECT COUNT(*) as tiene_activa 
                    FROM suscripciones 
                    WHERE id_usuario = ? 
                    AND estado = 'activa' 
                    AND fecha_fin >= CURDATE()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id_usuario]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'tiene_activa' => $resultado['tiene_activa'] > 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al verificar suscripción: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar estado de suscripción
     */
    public function actualizarEstadoSuscripcion($id_suscripcion, $nuevo_estado) {
        try {
            $estados_validos = ['activa', 'vencida', 'cancelada', 'suspendida'];
            
            if (!in_array($nuevo_estado, $estados_validos)) {
                return [
                    'success' => false,
                    'error' => 'Estado no válido'
                ];
            }
            
            $sql = "UPDATE suscripciones SET estado = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$nuevo_estado, $id_suscripcion]);
            
            return [
                'success' => true,
                'mensaje' => 'Estado actualizado exitosamente'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al actualizar estado: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar datos de pago
     */
    private function validarDatosPago($datos) {
        $errores = [];
        
        if (empty($datos['id_usuario'])) {
            $errores[] = 'ID de usuario es requerido';
        }
        
        if (empty($datos['monto']) || $datos['monto'] <= 0) {
            $errores[] = 'Monto debe ser mayor a 0';
        }
        
        if (empty($datos['modalidad_pago'])) {
            $errores[] = 'Modalidad de pago es requerida';
        }
        
        if (!in_array($datos['modalidad_pago'], ['efectivo', 'transferencia', 'tarjeta', 'otro'])) {
            $errores[] = 'Modalidad de pago no válida';
        }
        
        if (empty($datos['fecha_pago'])) {
            $errores[] = 'Fecha de pago es requerida';
        }
        
        if (empty($datos['registrado_por'])) {
            $errores[] = 'Usuario que registra es requerido';
        }
        
        return $errores;
    }
    
    /**
     * Método público para validar datos antes de procesar
     */
    public function validarPago($datos) {
        $errores = $this->validarDatosPago($datos);
        
        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
}

// Ejemplo de uso
/*
// Conectar a la base de datos
try {
    $pdo = new PDO("mysql:host=localhost;dbname=gym_app", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear instancia de la clase
    $gymPagos = new GymPagos($pdo);
    
    // Ejemplo: Renovar suscripción
    $datos_renovacion = [
        'id_usuario' => 1,
        'tipo_suscripcion' => 'mensual',
        'modalidad_pago' => 'efectivo',
        'monto' => 500.00,
        'fecha_pago' => '2025-01-15',
        'referencia_pago' => 'REC-001',
        'notas' => 'Renovación mensual',
        'registrado_por' => 1
    ];
    
    $resultado = $gymPagos->renovarSuscripcion($datos_renovacion);
    
    if ($resultado['success']) {
        echo "Suscripción renovada exitosamente";
        echo "Válida desde: " . $resultado['fecha_inicio'];
        echo "Válida hasta: " . $resultado['fecha_fin'];
    } else {
        echo "Error: " . $resultado['error'];
    }
    
    // Ejemplo: Verificar si tiene suscripción activa
    $verificacion = $gymPagos->tienesSuscripcionActiva(1);
    if ($verificacion['success']) {
        echo $verificacion['tiene_activa'] ? "Tiene suscripción activa" : "No tiene suscripción activa";
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
*/
?>