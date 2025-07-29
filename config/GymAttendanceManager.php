<?php
/**
 * GymAttendanceManager - Controlador Central para el Sistema de Asistencias
 * 
 * Este archivo maneja toda la lógica de:
 * - Generación y validación de códigos de asistencia
 * - Registro de asistencias (manual y por código)
 * - Gestión de rachas con tolerancia
 * - Estadísticas y reportes
 */

require_once __DIR__ . '/config.php';

class GymAttendanceManager {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Generar un nuevo código de asistencia
     */
    public function generarCodigo($nombre, $descripcion, $tipo_duracion = 'dia', $hora_inicio = '06:00:00', $hora_fin = '23:59:59', $limite_usos = null, $creado_por) {
        try {
            $this->db->query("CALL GenerarCodigoAsistencia(:nombre, :descripcion, :tipo_duracion, :hora_inicio, :hora_fin, :limite_usos, :creado_por)");
            $this->db->bind(':nombre', $nombre);
            $this->db->bind(':descripcion', $descripcion);
            $this->db->bind(':tipo_duracion', $tipo_duracion);
            $this->db->bind(':hora_inicio', $hora_inicio);
            $this->db->bind(':hora_fin', $hora_fin);
            $this->db->bind(':limite_usos', $limite_usos);
            $this->db->bind(':creado_por', $creado_por);
            
            $resultado = $this->db->single();
            
            if ($resultado) {
                return [
                    'success' => true,
                    'codigo' => $resultado['codigo_generado'],
                    'expira_en' => $resultado['expira_en'],
                    'message' => 'Código generado exitosamente'
                ];
            }
            
            return ['success' => false, 'message' => 'Error al generar el código'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validar si un código es válido para usar
     */
    public function validarCodigo($codigo) {
        try {
            $this->db->query("
                SELECT 
                    id, codigo, nombre_codigo, tipo_duracion, 
                    fecha_inicio, fecha_expiracion, hora_inicio, hora_fin,
                    usos_totales, limite_usos, activo,
                    CASE 
                        WHEN CURDATE() > fecha_expiracion THEN 'expirado'
                        WHEN CURDATE() < fecha_inicio THEN 'pendiente'
                        WHEN CURTIME() NOT BETWEEN hora_inicio AND hora_fin THEN 'fuera_horario'
                        WHEN limite_usos IS NOT NULL AND usos_totales >= limite_usos THEN 'sin_usos'
                        WHEN activo = FALSE THEN 'inactivo'
                        ELSE 'valido'
                    END as estado
                FROM codigos_asistencia 
                WHERE codigo = :codigo
            ");
            $this->db->bind(':codigo', $codigo);
            $resultado = $this->db->single();
            
            if (!$resultado) {
                return ['valid' => false, 'message' => 'Código no encontrado'];
            }
            
            $estado = $resultado['estado'];
            $mensajes = [
                'expirado' => 'Código expirado',
                'pendiente' => 'Código aún no está activo',
                'fuera_horario' => 'Código fuera del horario permitido',
                'sin_usos' => 'Código sin usos disponibles',
                'inactivo' => 'Código desactivado',
                'valido' => 'Código válido'
            ];
            
            return [
                'valid' => $estado === 'valido',
                'message' => $mensajes[$estado],
                'data' => $resultado
            ];
            
        } catch (Exception $e) {
            return ['valid' => false, 'message' => 'Error al validar código: ' . $e->getMessage()];
        }
    }
    
    /**
     * Registrar asistencia por código
     */
    public function registrarAsistenciaPorCodigo($id_usuario, $codigo, $ip_usuario = null, $user_agent = null) {
        try {
            // Primero validar el código
            $validacion = $this->validarCodigo($codigo);
            if (!$validacion['valid']) {
                return $validacion;
            }
            
            $this->db->query("CALL RegistrarAsistenciaConCodigo(:id_usuario, :codigo, 'codigo_temporal', NULL, :ip_usuario, :user_agent)");
            $this->db->bind(':id_usuario', $id_usuario);
            $this->db->bind(':codigo', $codigo);
            $this->db->bind(':ip_usuario', $ip_usuario);
            $this->db->bind(':user_agent', $user_agent);
            
            $resultado = $this->db->single();
            
            if ($resultado && strpos($resultado['resultado'], 'SUCCESS') === 0) {
                return [
                    'success' => true,
                    'message' => 'Asistencia registrada correctamente',
                    'nueva_racha' => $resultado['nueva_racha'] ?? 0,
                    'tolerancia_aplicada' => $resultado['tolerancia_aplicada'] ?? false
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $resultado['resultado'] ?? 'Error desconocido'
                ];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al registrar asistencia: ' . $e->getMessage()];
        }
    }
    
    /**
 * Registrar asistencia manual (por admin) - Versión sin procedimiento almacenado
 */
public function registrarAsistenciaManual($id_usuario, $id_admin, $fecha = null, $metodo = 'admin_manual') {
    try {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }
        
        $this->db->beginTransaction();
        
        // Verificar que no exista asistencia para esa fecha
        $this->db->query("SELECT id FROM asistencias WHERE id_usuario = :id_usuario AND DATE(fecha) = :fecha");
        $this->db->bind(':id_usuario', $id_usuario);
        $this->db->bind(':fecha', $fecha);
        $existe = $this->db->single();
        
        if ($existe) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Ya existe asistencia para esa fecha'];
        }
        
        // Obtener datos actuales del usuario
        $this->db->query("
            SELECT racha_actual, racha_maxima, fecha_ultima_asistencia, tolerancia_usada, fecha_tolerancia 
            FROM usuarios 
            WHERE id = :id_usuario AND activo = TRUE
        ");
        $this->db->bind(':id_usuario', $id_usuario);
        $usuario = $this->db->single();
        
        if (!$usuario) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Usuario no encontrado o inactivo'];
        }
        
        // Obtener configuración de tolerancia
        $configuracion = $this->obtenerConfiguracionTolerancia();
        if (!$configuracion) {
            // Valores por defecto si no hay configuración
            $configuracion = [
                'dia_tolerancia' => 'sunday',
                'tolerancia_activa' => true
            ];
        }
        
        $dia_tolerancia = $configuracion['dia_tolerancia'] ?? 'sunday';
        $tolerancia_activa = $configuracion['tolerancia_activa'] ?? true;
        
        // Calcular nueva racha
        $racha_actual = $usuario['racha_actual'];
        $racha_maxima = $usuario['racha_maxima'];
        $fecha_ultima = $usuario['fecha_ultima_asistencia'];
        $tolerancia_usada = $usuario['tolerancia_usada'];
        $fecha_tolerancia = $usuario['fecha_tolerancia'];
        $tolerancia_aplicada = false;
        
        if ($fecha_ultima === null) {
            // Primera asistencia
            $racha_actual = 1;
            $tolerancia_usada = false;
            $fecha_tolerancia = null;
        } else {
            $fecha_actual_obj = new DateTime($fecha);
            $fecha_ultima_obj = new DateTime($fecha_ultima);
            $dias_diferencia = $fecha_ultima_obj->diff($fecha_actual_obj)->days;
            
            // Convertir día de tolerancia al formato de PHP
            $dias_php = [
                'monday' => 'monday',
                'tuesday' => 'tuesday', 
                'wednesday' => 'wednesday',
                'thursday' => 'thursday',
                'friday' => 'friday',
                'saturday' => 'saturday',
                'sunday' => 'sunday',
                'lunes' => 'monday',
                'martes' => 'tuesday',
                'miercoles' => 'wednesday',
                'jueves' => 'thursday',
                'viernes' => 'friday',
                'sabado' => 'saturday',
                'domingo' => 'sunday'
            ];
            
            $dia_tolerancia_php = $dias_php[strtolower($dia_tolerancia)] ?? 'sunday';
            $es_dia_tolerancia = strtolower($fecha_actual_obj->format('l')) === $dia_tolerancia_php;
            
            if ($dias_diferencia === 1) {
                // Día consecutivo
                $racha_actual++;
            } elseif ($dias_diferencia === 2 && $tolerancia_activa && $es_dia_tolerancia && !$tolerancia_usada) {
                // Aplicar tolerancia
                $racha_actual++;
                $tolerancia_usada = true;
                $tolerancia_aplicada = true;
                $fecha_tolerancia = $fecha_ultima_obj->add(new DateInterval('P1D'))->format('Y-m-d');
            } else {
                // Se rompió la racha
                $racha_actual = 1;
                $tolerancia_usada = false;
                $fecha_tolerancia = null;
            }
        }
        
        // Actualizar racha máxima si es necesario
        if ($racha_actual > $racha_maxima) {
            $racha_maxima = $racha_actual;
        }
        
        // Insertar asistencia
        $this->db->query("
            INSERT INTO asistencias (
                id_usuario, 
                fecha, 
                hora_entrada, 
                metodo_registro, 
                registrado_por, 
                tolerancia_aplicada, 
                validacion_admin, 
                notas
            ) VALUES (
                :id_usuario,
                :fecha,
                NOW(),
                :metodo,
                :registrado_por,
                :tolerancia_aplicada,
                TRUE,
                :notas
            )
        ");
        $this->db->bind(':id_usuario', $id_usuario);
        $this->db->bind(':fecha', $fecha);
        $this->db->bind(':metodo', $metodo);
        $this->db->bind(':registrado_por', $id_admin);
        $this->db->bind(':tolerancia_aplicada', $tolerancia_aplicada);
        $this->db->bind(':notas', 'Registrado manualmente por administrador');
        
        if (!$this->db->execute()) {
            throw new Exception('Error al insertar asistencia');
        }
        
        // Actualizar usuario con nueva racha
        $this->db->query("
            UPDATE usuarios 
            SET racha_actual = :racha_actual,
                racha_maxima = :racha_maxima,
                fecha_ultima_asistencia = :fecha_ultima,
                tolerancia_usada = :tolerancia_usada,
                fecha_tolerancia = :fecha_tolerancia
            WHERE id = :id_usuario
        ");
        $this->db->bind(':racha_actual', $racha_actual);
        $this->db->bind(':racha_maxima', $racha_maxima);
        $this->db->bind(':fecha_ultima', $fecha);
        $this->db->bind(':tolerancia_usada', $tolerancia_usada);
        $this->db->bind(':fecha_tolerancia', $fecha_tolerancia);
        $this->db->bind(':id_usuario', $id_usuario);
        $this->db->execute();
        
        // Registrar en historial de acciones administrativas
        $this->registrarAccionAdmin($id_usuario, $id_admin, 'registro_asistencia', "Asistencia registrada manualmente para la fecha: $fecha");
        
        $this->db->endTransaction();
        
        return [
            'success' => true,
            'message' => 'Asistencia registrada correctamente por admin',
            'nueva_racha' => $racha_actual,
            'tolerancia_aplicada' => $tolerancia_aplicada
        ];
        
    } catch (Exception $e) {
        $this->db->cancelTransaction();
        return ['success' => false, 'message' => 'Error al registrar asistencia manual: ' . $e->getMessage()];
    }
}
    /**
     * Obtener códigos activos
     */
    public function obtenerCodigosActivos($limit = 10) {
        try {
            $this->db->query("SELECT * FROM vista_codigos_activos LIMIT :limit");
            $this->db->bind(':limit', $limit);
            return $this->db->resultset();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de un usuario
     */
    public function obtenerEstadisticasUsuario($id_usuario) {
        try {
            $this->db->query("SELECT * FROM vista_estadisticas_usuario WHERE id = :id_usuario");
            $this->db->bind(':id_usuario', $id_usuario);
            $estadisticas = $this->db->single();
            
            if ($estadisticas) {
                // Obtener últimas asistencias
                $this->db->query("
                    SELECT a.*, c.nombre_codigo, c.codigo
                    FROM asistencias a
                    LEFT JOIN codigos_asistencia c ON a.id_codigo_usado = c.id
                    WHERE a.id_usuario = :id_usuario
                    ORDER BY a.fecha DESC, a.hora_entrada DESC
                    LIMIT 10
                ");
                $this->db->bind(':id_usuario', $id_usuario);
                $estadisticas['ultimas_asistencias'] = $this->db->resultset();
                
                // Calcular días desde última asistencia
                if ($estadisticas['fecha_ultima_asistencia']) {
                    $estadisticas['dias_desde_ultima'] = (new DateTime())->diff(new DateTime($estadisticas['fecha_ultima_asistencia']))->days;
                } else {
                    $estadisticas['dias_desde_ultima'] = null;
                }
            }
            
            return $estadisticas;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Obtener historial de uso de códigos
     */
    public function obtenerHistorialCodigos($id_codigo = null, $id_usuario = null, $fecha_inicio = null, $fecha_fin = null, $limit = 50) {
        try {
            $where_conditions = [];
            $params = [];
            
            if ($id_codigo) {
                $where_conditions[] = "uca.id_codigo = :id_codigo";
                $params[':id_codigo'] = $id_codigo;
            }
            
            if ($id_usuario) {
                $where_conditions[] = "uca.id_usuario = :id_usuario";
                $params[':id_usuario'] = $id_usuario;
            }
            
            if ($fecha_inicio) {
                $where_conditions[] = "uca.fecha_uso >= :fecha_inicio";
                $params[':fecha_inicio'] = $fecha_inicio;
            }
            
            if ($fecha_fin) {
                $where_conditions[] = "uca.fecha_uso <= :fecha_fin";
                $params[':fecha_fin'] = $fecha_fin;
            }
            
            $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
            
            $this->db->query("
                SELECT 
                    uca.*,
                    c.codigo,
                    c.nombre_codigo,
                    u.nombre,
                    u.apellido,
                    u.email,
                    admin.nombre as validado_por_nombre
                FROM uso_codigos_asistencia uca
                JOIN codigos_asistencia c ON uca.id_codigo = c.id
                JOIN usuarios u ON uca.id_usuario = u.id
                LEFT JOIN usuarios admin ON uca.validado_por = admin.id
                $where_clause
                ORDER BY uca.fecha_registro DESC
                LIMIT :limit
            ");
            
            foreach ($params as $param => $value) {
                $this->db->bind($param, $value);
            }
            $this->db->bind(':limit', $limit);
            
            return $this->db->resultset();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Desactivar código
     */
    public function desactivarCodigo($id_codigo, $id_admin) {
        try {
            $this->db->query("UPDATE codigos_asistencia SET activo = FALSE WHERE id = :id_codigo");
            $this->db->bind(':id_codigo', $id_codigo);
            $this->db->execute();
            
            if ($this->db->rowCount() > 0) {
                // Registrar acción
                $this->registrarAccionAdmin(null, $id_admin, 'desactivar', "Código de asistencia ID: $id_codigo desactivado");
                return ['success' => true, 'message' => 'Código desactivado correctamente'];
            }
            
            return ['success' => false, 'message' => 'No se pudo desactivar el código'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener ranking de asistencias del mes
     */
    public function obtenerRankingMensual($mes = null, $año = null, $limit = 10) {
        try {
            if (!$mes) $mes = date('n');
            if (!$año) $año = date('Y');
            
            $this->db->query("
                SELECT 
                    u.id,
                    u.nombre,
                    u.apellido,
                    u.racha_actual,
                    u.racha_maxima,
                    COUNT(a.id) as asistencias_mes,
                    RANK() OVER (ORDER BY COUNT(a.id) DESC, u.racha_actual DESC) as posicion
                FROM usuarios u
                LEFT JOIN asistencias a ON u.id = a.id_usuario 
                    AND MONTH(a.fecha) = :mes 
                    AND YEAR(a.fecha) = :año
                WHERE u.tipo = 'cliente' AND u.activo = TRUE
                GROUP BY u.id
                ORDER BY asistencias_mes DESC, u.racha_actual DESC
                LIMIT :limit
            ");
            $this->db->bind(':mes', $mes);
            $this->db->bind(':año', $año);
            $this->db->bind(':limit', $limit);
            
            return $this->db->resultset();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener estadísticas generales del gym
     */
    public function obtenerEstadisticasGenerales($fecha_inicio = null, $fecha_fin = null) {
        try {
            if (!$fecha_inicio) $fecha_inicio = date('Y-m-01'); // Primer día del mes
            if (!$fecha_fin) $fecha_fin = date('Y-m-d'); // Hoy
            
            // Estadísticas básicas
            $this->db->query("
                SELECT 
                    COUNT(DISTINCT a.id_usuario) as usuarios_activos,
                    COUNT(a.id) as total_asistencias,
                    COUNT(CASE WHEN a.metodo_registro = 'codigo_temporal' THEN 1 END) as asistencias_codigo,
                    COUNT(CASE WHEN a.metodo_registro = 'admin_manual' THEN 1 END) as asistencias_admin,
                    COUNT(CASE WHEN a.tolerancia_aplicada = TRUE THEN 1 END) as tolerancias_aplicadas,
                    AVG(u.racha_actual) as racha_promedio,
                    MAX(u.racha_maxima) as racha_maxima_general
                FROM asistencias a
                JOIN usuarios u ON a.id_usuario = u.id
                WHERE a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                    AND u.tipo = 'cliente'
            ");
            $this->db->bind(':fecha_inicio', $fecha_inicio);
            $this->db->bind(':fecha_fin', $fecha_fin);
            $estadisticas = $this->db->single();
            
            // Códigos activos
            $this->db->query("
                SELECT COUNT(*) as total_codigos_activos
                FROM codigos_asistencia 
                WHERE activo = TRUE AND fecha_expiracion >= CURDATE()
            ");
            $codigos = $this->db->single();
            $estadisticas['codigos_activos'] = $codigos['total_codigos_activos'];
            
            // Asistencias por día de la semana
            $this->db->query("
                SELECT 
                    DAYNAME(a.fecha) as dia_semana,
                    COUNT(*) as total_asistencias
                FROM asistencias a
                WHERE a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                GROUP BY DAYOFWEEK(a.fecha), DAYNAME(a.fecha)
                ORDER BY DAYOFWEEK(a.fecha)
            ");
            $this->db->bind(':fecha_inicio', $fecha_inicio);
            $this->db->bind(':fecha_fin', $fecha_fin);
            $estadisticas['asistencias_por_dia'] = $this->db->resultset();
            
            return $estadisticas;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Configurar tolerancia de racha
     */
    public function configurarTolerancia($dia_tolerancia, $tolerancia_activa = true, $racha_minima_premio = 7) {
        try {
            $this->db->query("
                UPDATE configuracion_racha 
                SET dia_tolerancia = :dia_tolerancia,
                    tolerancia_activa = :tolerancia_activa,
                    racha_minima_premio = :racha_minima_premio
            ");
            $this->db->bind(':dia_tolerancia', $dia_tolerancia);
            $this->db->bind(':tolerancia_activa', $tolerancia_activa);
            $this->db->bind(':racha_minima_premio', $racha_minima_premio);
            $this->db->execute();
            
            return ['success' => true, 'message' => 'Configuración de tolerancia actualizada'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener configuración actual de tolerancia
     */
    public function obtenerConfiguracionTolerancia() {
        try {
            $this->db->query("SELECT * FROM configuracion_racha LIMIT 1");
            return $this->db->single();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Recalcular rachas de todos los usuarios (función de mantenimiento)
     */
    public function recalcularRachas() {
        try {
            $this->db->beginTransaction();
            
            // Obtener todos los usuarios activos
            $this->db->query("SELECT id FROM usuarios WHERE tipo = 'cliente' AND activo = TRUE");
            $usuarios = $this->db->resultset();
            
            foreach ($usuarios as $usuario) {
                $this->recalcularRachaUsuario($usuario['id']);
            }
            
            $this->db->endTransaction();
            return ['success' => true, 'message' => 'Rachas recalculadas correctamente'];
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Recalcular racha de un usuario específico
     */
    private function recalcularRachaUsuario($id_usuario) {
        try {
            // Obtener todas las asistencias del usuario ordenadas por fecha
            $this->db->query("
                SELECT fecha 
                FROM asistencias 
                WHERE id_usuario = :id_usuario 
                ORDER BY fecha ASC
            ");
            $this->db->bind(':id_usuario', $id_usuario);
            $asistencias = $this->db->resultset();
            
            if (empty($asistencias)) {
                return; // Sin asistencias
            }
            
            $configuracion = $this->obtenerConfiguracionTolerancia();
            $dia_tolerancia = $configuracion['dia_tolerancia'] ?? 'domingo';
            $tolerancia_activa = $configuracion['tolerancia_activa'] ?? true;
            
            $racha_actual = 0;
            $racha_maxima = 0;
            $fecha_anterior = null;
            $tolerancia_usada = false;
            $fecha_tolerancia = null;
            
            foreach ($asistencias as $asistencia) {
                $fecha_actual = new DateTime($asistencia['fecha']);
                
                if ($fecha_anterior === null) {
                    // Primera asistencia
                    $racha_actual = 1;
                } else {
                    $dias_diferencia = $fecha_anterior->diff($fecha_actual)->days;
                    $es_dia_tolerancia = strtolower($fecha_actual->format('l')) === $dia_tolerancia;
                    
                    if ($dias_diferencia === 1) {
                        // Día consecutivo
                        $racha_actual++;
                    } elseif ($dias_diferencia === 2 && $tolerancia_activa && $es_dia_tolerancia && !$tolerancia_usada) {
                        // Aplicar tolerancia
                        $racha_actual++;
                        $tolerancia_usada = true;
                        $fecha_tolerancia = $fecha_anterior->add(new DateInterval('P1D'))->format('Y-m-d');
                    } else {
                        // Se rompió la racha
                        $racha_actual = 1;
                        $tolerancia_usada = false;
                        $fecha_tolerancia = null;
                    }
                }
                
                $racha_maxima = max($racha_maxima, $racha_actual);
                $fecha_anterior = $fecha_actual;
            }
            
            // Verificar si la racha actual está vigente (última asistencia no muy antigua)
            $ultima_fecha = end($asistencias)['fecha'];
            $dias_desde_ultima = (new DateTime())->diff(new DateTime($ultima_fecha))->days;
            
            if ($dias_desde_ultima > 2) { // Más de 2 días sin asistir
                $racha_actual = 0;
                $tolerancia_usada = false;
                $fecha_tolerancia = null;
            }
            
            // Actualizar en la base de datos
            $this->db->query("
                UPDATE usuarios 
                SET racha_actual = :racha_actual,
                    racha_maxima = :racha_maxima,
                    fecha_ultima_asistencia = :fecha_ultima_asistencia,
                    tolerancia_usada = :tolerancia_usada,
                    fecha_tolerancia = :fecha_tolerancia
                WHERE id = :id_usuario
            ");
            $this->db->bind(':racha_actual', $racha_actual);
            $this->db->bind(':racha_maxima', $racha_maxima);
            $this->db->bind(':fecha_ultima_asistencia', $ultima_fecha);
            $this->db->bind(':tolerancia_usada', $tolerancia_usada);
            $this->db->bind(':fecha_tolerancia', $fecha_tolerancia);
            $this->db->bind(':id_usuario', $id_usuario);
            $this->db->execute();
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Limpiar códigos expirados
     */
    public function limpiarCodigosExpirados() {
        try {
            $this->db->query("CALL LimpiarCodigosExpirados()");
            $resultado = $this->db->single();
            
            return [
                'success' => true,
                'message' => 'Códigos limpiados',
                'codigos_desactivados' => $resultado['codigos_desactivados'] ?? 0
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Registrar acción administrativa
     */
    private function registrarAccionAdmin($id_usuario, $id_admin, $accion, $detalles = null) {
        try {
            $this->db->query("
                INSERT INTO historial_acceso 
                (id_usuario, id_administrador, accion, detalles) 
                VALUES (:id_usuario, :id_admin, :accion, :detalles)
            ");
            $this->db->bind(':id_usuario', $id_usuario);
            $this->db->bind(':id_admin', $id_admin);
            $this->db->bind(':accion', $accion);
            $this->db->bind(':detalles', $detalles);
            $this->db->execute();
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Error registrando acción admin: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener asistencias de un usuario en un rango de fechas
     */
    public function obtenerAsistenciasUsuario($id_usuario, $fecha_inicio = null, $fecha_fin = null) {
        try {
            if (!$fecha_inicio) $fecha_inicio = date('Y-m-01');
            if (!$fecha_fin) $fecha_fin = date('Y-m-d');
            
            $this->db->query("
                SELECT 
                    a.*,
                    c.codigo,
                    c.nombre_codigo,
                    admin.nombre as registrado_por_nombre
                FROM asistencias a
                LEFT JOIN codigos_asistencia c ON a.id_codigo_usado = c.id
                LEFT JOIN usuarios admin ON a.registrado_por = admin.id
                WHERE a.id_usuario = :id_usuario 
                    AND a.fecha BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY a.fecha DESC, a.hora_entrada DESC
            ");
            $this->db->bind(':id_usuario', $id_usuario);
            $this->db->bind(':fecha_inicio', $fecha_inicio);
            $this->db->bind(':fecha_fin', $fecha_fin);
            
            return $this->db->resultset();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Verificar si un usuario puede usar tolerancia
     */
    public function puedeUsarTolerancia($id_usuario) {
        try {
            $this->db->query("
                SELECT 
                    u.tolerancia_usada,
                    u.fecha_tolerancia,
                    u.fecha_ultima_asistencia,
                    c.dia_tolerancia,
                    c.tolerancia_activa
                FROM usuarios u
                CROSS JOIN configuracion_racha c
                WHERE u.id = :id_usuario
            ");
            $this->db->bind(':id_usuario', $id_usuario);
            $resultado = $this->db->single();
            
            if (!$resultado || !$resultado['tolerancia_activa']) {
                return false;
            }
            
            // Si ya usó tolerancia esta semana, no puede usar otra
            if ($resultado['tolerancia_usada'] && $resultado['fecha_tolerancia']) {
                $fecha_tolerancia = new DateTime($resultado['fecha_tolerancia']);
                $inicio_semana = new DateTime();
                $inicio_semana->modify('monday this week');
                
                if ($fecha_tolerancia >= $inicio_semana) {
                    return false; // Ya usó tolerancia esta semana
                }
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Funciones de utilidad para usar en otras partes de la aplicación

/**
 * Función helper para inicializar el manager
 */
function gym_attendance_manager() {
    static $manager = null;
    if ($manager === null) {
        $manager = new GymAttendanceManager();
    }
    return $manager;
}

/**
 * Funciones de conveniencia
 */
function gym_generar_codigo($nombre, $descripcion, $tipo = 'dia', $creado_por = null) {
    if (!$creado_por && gym_is_logged_in()) {
        $creado_por = $_SESSION['user_id'];
    }
    return gym_attendance_manager()->generarCodigo($nombre, $descripcion, $tipo, '06:00:00', '23:59:59', null, $creado_por);
}

function gym_registrar_asistencia_codigo($id_usuario, $codigo) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    return gym_attendance_manager()->registrarAsistenciaPorCodigo($id_usuario, $codigo, $ip, $user_agent);
}

function gym_registrar_asistencia_admin($id_usuario, $id_admin, $fecha = null) {
    return gym_attendance_manager()->registrarAsistenciaManual($id_usuario, $id_admin, $fecha);
}

function gym_obtener_estadisticas_usuario($id_usuario) {
    return gym_attendance_manager()->obtenerEstadisticasUsuario($id_usuario);
}

function gym_obtener_ranking_mensual($limit = 10) {
    return gym_attendance_manager()->obtenerRankingMensual(null, null, $limit);
}

function gym_validar_codigo($codigo) {
    return gym_attendance_manager()->validarCodigo($codigo);
}

function gym_obtener_codigos_activos($limit = 10) {
    return gym_attendance_manager()->obtenerCodigosActivos($limit);
}

?>