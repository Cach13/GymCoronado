<?php
class AlimentosManager {
    private $pdo;
    
    public function __construct($database_connection) {
        $this->pdo = $database_connection;
    }
    
    /**
     * Buscar alimentos por término y/o grupo
     */
    public function buscarAlimentos($termino = '', $grupo_id = null, $limite = 20) {
        try {
            $sql = "
                SELECT 
                    a.id,
                    a.nombre,
                    a.marca,
                    a.calorias_100,
                    a.proteinas_100,
                    a.carbohidratos_100,
                    a.grasas_100,
                    a.fibra_100,
                    a.tipo_medida,
                    g.nombre as grupo_nombre,
                    g.color as grupo_color
                FROM alimentos a
                JOIN grupos_alimentos g ON a.id_grupo = g.id
                WHERE a.activo = TRUE
            ";
            
            $params = [];
            
            if (!empty($termino)) {
                $sql .= " AND (a.nombre LIKE ? OR a.marca LIKE ?)";
                $params[] = "%$termino%";
                $params[] = "%$termino%";
            }
            
            if ($grupo_id) {
                $sql .= " AND a.id_grupo = ?";
                $params[] = $grupo_id;
            }
            
            $sql .= " ORDER BY a.nombre ASC LIMIT ?";
            $params[] = $limite;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Error al buscar alimentos: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener alimentos más consumidos por el usuario
     */
    public function obtenerMasConsumidos($id_usuario, $limite = 10) {
        try {
            $sql = "
                SELECT 
                    a.id,
                    a.nombre,
                    a.marca,
                    a.calorias_100,
                    a.proteinas_100,
                    a.carbohidratos_100,
                    a.grasas_100,
                    a.fibra_100,
                    a.tipo_medida,
                    g.nombre as grupo_nombre,
                    g.color as grupo_color,
                    uaf.veces_consumido,
                    uaf.ultima_vez
                FROM usuario_alimentos_frecuentes uaf
                JOIN alimentos a ON uaf.id_alimento = a.id
                JOIN grupos_alimentos g ON a.id_grupo = g.id
                WHERE uaf.id_usuario = ? AND a.activo = TRUE
                ORDER BY uaf.veces_consumido DESC, uaf.ultima_vez DESC
                LIMIT ?
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_usuario, $limite]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Error al obtener alimentos más consumidos: ' . $e->getMessage()];
        }
    }
    
    /**
     * Calcular macros basado en cantidad - AQUÍ ES DONDE SE HACE LA MAGIA
     */
    public function calcularMacros($id_alimento, $cantidad, $tipo_medida_usuario = null) {
        try {
            // Obtener datos del alimento
            $stmt = $this->pdo->prepare("
                SELECT a.*, g.nombre as grupo_nombre, g.color as grupo_color
                FROM alimentos a
                JOIN grupos_alimentos g ON a.id_grupo = g.id
                WHERE a.id = ? AND a.activo = TRUE
            ");
            $stmt->execute([$id_alimento]);
            $alimento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$alimento) {
                return ['error' => 'Alimento no encontrado'];
            }
            
            // Usar tipo_medida del alimento si no se especifica otro
            $tipo_medida = $tipo_medida_usuario ?: $alimento['tipo_medida'];
            
            // Validar que el tipo de medida sea compatible
            if ($tipo_medida_usuario && $tipo_medida_usuario != $alimento['tipo_medida']) {
                // Permitir conversión básica entre gramos y mililitros (densidad ≈ 1)
                if (($alimento['tipo_medida'] == 'gramos' && $tipo_medida_usuario == 'mililitros') ||
                    ($alimento['tipo_medida'] == 'mililitros' && $tipo_medida_usuario == 'gramos')) {
                    $tipo_medida = $tipo_medida_usuario;
                } else {
                    return ['error' => 'Tipo de medida incompatible con el alimento'];
                }
            }
            
            // Calcular factor (siempre basado en 100g/ml)
            $factor = $cantidad / 100;
            
            // Calcular macros
            $macros = [
                'alimento_id' => $id_alimento,
                'nombre' => $alimento['nombre'],
                'marca' => $alimento['marca'],
                'grupo' => $alimento['grupo_nombre'],
                'grupo_color' => $alimento['grupo_color'],
                'cantidad' => $cantidad,
                'tipo_medida' => $tipo_medida,
                'calorias' => round($alimento['calorias_100'] * $factor, 2),
                'proteinas' => round($alimento['proteinas_100'] * $factor, 2),
                'carbohidratos' => round($alimento['carbohidratos_100'] * $factor, 2),
                'grasas' => round($alimento['grasas_100'] * $factor, 2),
                'fibra' => round($alimento['fibra_100'] * $factor, 2),
                // Datos base por 100g/ml para referencia
                'info_100' => [
                    'calorias' => floatval($alimento['calorias_100']),
                    'proteinas' => floatval($alimento['proteinas_100']),
                    'carbohidratos' => floatval($alimento['carbohidratos_100']),
                    'grasas' => floatval($alimento['grasas_100']),
                    'fibra' => floatval($alimento['fibra_100']),
                    'tipo_medida' => $alimento['tipo_medida']
                ]
            ];
            
            return $macros;
        } catch (Exception $e) {
            return ['error' => 'Error al calcular macros: ' . $e->getMessage()];
        }
    }
    
    /**
     * Registrar comida
     */
    public function registrarComida($id_usuario, $id_alimento, $fecha, $comida, $cantidad, $tipo_medida = null) {
        try {
            $this->pdo->beginTransaction();
            
            // Calcular macros
            $macros = $this->calcularMacros($id_alimento, $cantidad, $tipo_medida);
            
            if (isset($macros['error'])) {
                throw new Exception($macros['error']);
            }
            
            // Insertar registro de comida (sin campos calculados en BD)
            $stmt = $this->pdo->prepare("
                INSERT INTO registro_comidas (
                    id_usuario, id_alimento, fecha, comida, cantidad
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $id_usuario,
                $id_alimento,
                $fecha,
                $comida,
                $cantidad
            ]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'id_registro' => $this->pdo->lastInsertId(),
                'macros' => $macros,
                'mensaje' => 'Comida registrada exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => 'Error al registrar comida: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener resumen del día - CON CÁLCULOS EN TIEMPO REAL
     */
    public function obtenerResumenDia($id_usuario, $fecha) {
        try {
            // Obtener todos los registros del día
            $sql = "
                SELECT 
                    rc.id,
                    rc.comida,
                    rc.cantidad,
                    a.id as alimento_id,
                    a.nombre as alimento_nombre,
                    a.marca as alimento_marca,
                    a.calorias_100,
                    a.proteinas_100,
                    a.carbohidratos_100,
                    a.grasas_100,
                    a.fibra_100,
                    a.tipo_medida,
                    g.nombre as grupo_nombre,
                    g.color as grupo_color
                FROM registro_comidas rc
                JOIN alimentos a ON rc.id_alimento = a.id
                JOIN grupos_alimentos g ON a.id_grupo = g.id
                WHERE rc.id_usuario = ? AND rc.fecha = ?
                ORDER BY FIELD(rc.comida, 'desayuno', 'snack1', 'almuerzo', 'snack2', 'cena'), rc.fecha_registro
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_usuario, $fecha]);
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular macros por comida y totales
            $por_comida = [];
            $totales = [
                'calorias' => 0,
                'proteinas' => 0,
                'carbohidratos' => 0,
                'grasas' => 0,
                'fibra' => 0,
                'total_alimentos' => 0
            ];
            
            foreach ($registros as $registro) {
                $comida = $registro['comida'];
                $factor = $registro['cantidad'] / 100;
                
                $calorias = $registro['calorias_100'] * $factor;
                $proteinas = $registro['proteinas_100'] * $factor;
                $carbohidratos = $registro['carbohidratos_100'] * $factor;
                $grasas = $registro['grasas_100'] * $factor;
                $fibra = $registro['fibra_100'] * $factor;
                
                // Inicializar comida si no existe
                if (!isset($por_comida[$comida])) {
                    $por_comida[$comida] = [
                        'comida' => $comida,
                        'alimentos' => [],
                        'calorias' => 0,
                        'proteinas' => 0,
                        'carbohidratos' => 0,
                        'grasas' => 0,
                        'fibra' => 0,
                        'total_alimentos' => 0
                    ];
                }
                
                // Agregar alimento a la comida
                $por_comida[$comida]['alimentos'][] = [
                    'id' => $registro['id'],
                    'alimento_id' => $registro['alimento_id'],
                    'nombre' => $registro['alimento_nombre'],
                    'marca' => $registro['alimento_marca'],
                    'grupo' => $registro['grupo_nombre'],
                    'grupo_color' => $registro['grupo_color'],
                    'cantidad' => floatval($registro['cantidad']),
                    'tipo_medida' => $registro['tipo_medida'],
                    'calorias' => round($calorias, 2),
                    'proteinas' => round($proteinas, 2),
                    'carbohidratos' => round($carbohidratos, 2),
                    'grasas' => round($grasas, 2),
                    'fibra' => round($fibra, 2)
                ];
                
                // Sumar a totales de la comida
                $por_comida[$comida]['calorias'] += $calorias;
                $por_comida[$comida]['proteinas'] += $proteinas;
                $por_comida[$comida]['carbohidratos'] += $carbohidratos;
                $por_comida[$comida]['grasas'] += $grasas;
                $por_comida[$comida]['fibra'] += $fibra;
                $por_comida[$comida]['total_alimentos']++;
                
                // Sumar a totales del día
                $totales['calorias'] += $calorias;
                $totales['proteinas'] += $proteinas;
                $totales['carbohidratos'] += $carbohidratos;
                $totales['grasas'] += $grasas;
                $totales['fibra'] += $fibra;
                $totales['total_alimentos']++;
            }
            
            // Redondear totales
            foreach ($totales as $key => $valor) {
                if ($key !== 'total_alimentos') {
                    $totales[$key] = round($valor, 2);
                }
            }
            
            // Redondear totales por comida
            foreach ($por_comida as $comida => $datos) {
                $por_comida[$comida]['calorias'] = round($datos['calorias'], 2);
                $por_comida[$comida]['proteinas'] = round($datos['proteinas'], 2);
                $por_comida[$comida]['carbohidratos'] = round($datos['carbohidratos'], 2);
                $por_comida[$comida]['grasas'] = round($datos['grasas'], 2);
                $por_comida[$comida]['fibra'] = round($datos['fibra'], 2);
            }
            
            // Obtener objetivos del usuario
            $objetivos = $this->obtenerObjetivos($id_usuario);
            
            return [
                'fecha' => $fecha,
                'por_comida' => array_values($por_comida), // Convertir a array indexado
                'totales' => $totales,
                'objetivos' => $objetivos,
                'progreso' => $this->calcularProgreso($totales, $objetivos)
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Error al obtener resumen del día: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener objetivos nutricionales del usuario
     */
    public function obtenerObjetivos($id_usuario) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM objetivos_nutricionales 
                WHERE id_usuario = ? AND activo = TRUE 
                ORDER BY fecha_inicio DESC 
                LIMIT 1
            ");
            $stmt->execute([$id_usuario]);
            $objetivo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$objetivo) {
                // Crear objetivos por defecto
                return $this->crearObjetivosPorDefecto($id_usuario);
            }
            
            return $objetivo;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Crear objetivos por defecto
     */
    private function crearObjetivosPorDefecto($id_usuario) {
        $objetivos_defecto = [
            'calorias_objetivo' => 2000,
            'proteinas_objetivo' => 150,
            'carbohidratos_objetivo' => 250,
            'grasas_objetivo' => 67,
            'agua_objetivo' => 2.5
        ];
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO objetivos_nutricionales 
                (id_usuario, calorias_objetivo, proteinas_objetivo, carbohidratos_objetivo, grasas_objetivo, agua_objetivo, fecha_inicio)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE())
            ");
            
            $stmt->execute([
                $id_usuario,
                $objetivos_defecto['calorias_objetivo'],
                $objetivos_defecto['proteinas_objetivo'],
                $objetivos_defecto['carbohidratos_objetivo'],
                $objetivos_defecto['grasas_objetivo'],
                $objetivos_defecto['agua_objetivo']
            ]);
            
            return array_merge($objetivos_defecto, ['id' => $this->pdo->lastInsertId()]);
        } catch (Exception $e) {
            return $objetivos_defecto;
        }
    }
    
    /**
     * Calcular progreso vs objetivos
     */
    private function calcularProgreso($totales, $objetivos) {
        if (!$objetivos) return null;
        
        $progreso = [];
        $macros = ['calorias', 'proteinas', 'carbohidratos', 'grasas'];
        
        foreach ($macros as $macro) {
            $consumido = floatval($totales[$macro] ?? 0);
            $objetivo_key = $macro . '_objetivo';
            $objetivo = floatval($objetivos[$objetivo_key] ?? 1);
            
            $porcentaje = $objetivo > 0 ? ($consumido / $objetivo) * 100 : 0;
            
            $progreso[$macro] = [
                'consumido' => $consumido,
                'objetivo' => $objetivo,
                'restante' => max(0, $objetivo - $consumido),
                'porcentaje' => round($porcentaje, 1),
                'estado' => $this->determinarEstado($porcentaje, $macro)
            ];
        }
        
        return $progreso;
    }
    
    /**
     * Determinar estado del progreso
     */
    private function determinarEstado($porcentaje, $macro) {
        if ($macro === 'calorias') {
            if ($porcentaje < 80) return 'bajo';
            if ($porcentaje <= 110) return 'optimo';
            return 'alto';
        }
        
        if ($porcentaje < 70) return 'bajo';
        if ($porcentaje <= 120) return 'optimo';
        return 'alto';
    }
    
    /**
     * Obtener todos los grupos de alimentos
     */
    public function obtenerGrupos() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM grupos_alimentos 
                WHERE activo = TRUE 
                ORDER BY nombre
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Error al obtener grupos: ' . $e->getMessage()];
        }
    }
    
    /**
     * Eliminar registro de comida
     */
    public function eliminarRegistro($id_registro, $id_usuario) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM registro_comidas 
                WHERE id = ? AND id_usuario = ?
            ");
            $stmt->execute([$id_registro, $id_usuario]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'mensaje' => 'Registro eliminado exitosamente'];
            } else {
                return ['error' => 'No se pudo eliminar el registro'];
            }
        } catch (Exception $e) {
            return ['error' => 'Error al eliminar registro: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener estadísticas semanales
     */
    public function obtenerEstadisticasSemanales($id_usuario, $fecha_inicio = null) {
        try {
            if (!$fecha_inicio) {
                $fecha_inicio = date('Y-m-d', strtotime('monday this week'));
            }
            
            $sql = "
                SELECT 
                    rc.fecha,
                    COUNT(*) as total_registros,
                    COUNT(DISTINCT rc.id_alimento) as alimentos_diferentes
                FROM registro_comidas rc
                WHERE rc.id_usuario = ? 
                AND rc.fecha BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)
                GROUP BY rc.fecha
                ORDER BY rc.fecha
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_usuario, $fecha_inicio, $fecha_inicio]);
            $datos_basicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular macros para cada día
            $estadisticas = [];
            foreach ($datos_basicos as $dia) {
                $resumen_dia = $this->obtenerResumenDia($id_usuario, $dia['fecha']);
                $estadisticas[] = [
                    'fecha' => $dia['fecha'],
                    'registros' => $dia['total_registros'],
                    'alimentos_diferentes' => $dia['alimentos_diferentes'],
                    'macros' => $resumen_dia['totales'] ?? []
                ];
            }
            
            return $estadisticas;
        } catch (Exception $e) {
            return ['error' => 'Error al obtener estadísticas semanales: ' . $e->getMessage()];
        }
    }
    
    /**
     * Agregar nuevo alimento (para admins)
     */
    public function agregarAlimento($datos, $creado_por = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO alimentos (
                    nombre, marca, id_grupo, calorias_100, proteinas_100, 
                    carbohidratos_100, grasas_100, fibra_100, tipo_medida, creado_por
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $datos['nombre'],
                $datos['marca'] ?? null,
                $datos['id_grupo'],
                $datos['calorias_100'],
                $datos['proteinas_100'] ?? 0,
                $datos['carbohidratos_100'] ?? 0,
                $datos['grasas_100'] ?? 0,
                $datos['fibra_100'] ?? 0,
                $datos['tipo_medida'] ?? 'gramos',
                $creado_por
            ]);
            
            return [
                'success' => true,
                'id' => $this->pdo->lastInsertId(),
                'mensaje' => 'Alimento agregado exitosamente'
            ];
        } catch (Exception $e) {
            return ['error' => 'Error al agregar alimento: ' . $e->getMessage()];
        }
    }
}

// Clase para endpoints API
class AlimentosAPI {
    private $alimentosManager;
    
    public function __construct($database_connection) {
        $this->alimentosManager = new AlimentosManager($database_connection);
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        // Verificar autenticación
        $id_usuario = $this->getUserId();
        if (!$id_usuario) {
            return $this->jsonResponse(['error' => 'Usuario no autenticado'], 401);
        }
        
        switch ($action) {
            case 'buscar':
                return $this->buscarAlimentos();
            
            case 'calcular':
                return $this->calcularMacros();
            
            case 'registrar':
                return $this->registrarComida();
            
            case 'resumen':
                return $this->obtenerResumen();
            
            case 'mas-consumidos':
                return $this->obtenerMasConsumidos();
            
            case 'grupos':
                return $this->obtenerGrupos();
            
            case 'eliminar':
                return $this->eliminarRegistro();
            
            case 'estadisticas':
                return $this->obtenerEstadisticas();
            
            case 'agregar-alimento':
                return $this->agregarAlimento();
            
            default:
                return $this->jsonResponse(['error' => 'Acción no válida'], 400);
        }
    }
    
    private function buscarAlimentos() {
        $termino = $_GET['q'] ?? '';
        $grupo = $_GET['grupo'] ?? null;
        $limite = intval($_GET['limite'] ?? 20);
        
        $resultados = $this->alimentosManager->buscarAlimentos($termino, $grupo, $limite);
        return $this->jsonResponse($resultados);
    }
    
    private function calcularMacros() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['alimento_id']) || !isset($data['cantidad'])) {
            return $this->jsonResponse(['error' => 'Faltan parámetros requeridos'], 400);
        }
        
        $macros = $this->alimentosManager->calcularMacros(
            $data['alimento_id'],
            $data['cantidad'],
            $data['tipo_medida'] ?? null
        );
        
        return $this->jsonResponse($macros);
    }
    
    private function registrarComida() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $campos_requeridos = ['alimento_id', 'fecha', 'comida', 'cantidad'];
        foreach ($campos_requeridos as $campo) {
            if (!isset($data[$campo])) {
                return $this->jsonResponse(['error' => "Campo requerido: $campo"], 400);
            }
        }
        
        $resultado = $this->alimentosManager->registrarComida(
            $this->getUserId(),
            $data['alimento_id'],
            $data['fecha'],
            $data['comida'],
            $data['cantidad'],
            $data['tipo_medida'] ?? null
        );
        
        return $this->jsonResponse($resultado);
    }
    
    private function obtenerResumen() {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $resumen = $this->alimentosManager->obtenerResumenDia($this->getUserId(), $fecha);
        return $this->jsonResponse($resumen);
    }
    
    private function obtenerMasConsumidos() {
        $limite = intval($_GET['limite'] ?? 10);
        $alimentos = $this->alimentosManager->obtenerMasConsumidos($this->getUserId(), $limite);
        return $this->jsonResponse($alimentos);
    }
    
    private function obtenerGrupos() {
        $grupos = $this->alimentosManager->obtenerGrupos();
        return $this->jsonResponse($grupos);
    }
    
    private function eliminarRegistro() {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return $this->jsonResponse(['error' => 'Método no permitido'], 405);
        }
        
        $id_registro = $_GET['id'] ?? null;
        if (!$id_registro) {
            return $this->jsonResponse(['error' => 'ID de registro requerido'], 400);
        }
        
        $resultado = $this->alimentosManager->eliminarRegistro($id_registro, $this->getUserId());
        return $this->jsonResponse($resultado);
    }
    
    private function obtenerEstadisticas() {
        $tipo = $_GET['tipo'] ?? 'semanal';
        
        if ($tipo === 'semanal') {
            $fecha_inicio = $_GET['fecha'] ?? null;
            $stats = $this->alimentosManager->obtenerEstadisticasSemanales($this->getUserId(), $fecha_inicio);
        } else {
            $stats = ['error' => 'Tipo de estadística no válido'];
        }
        
        return $this->jsonResponse($stats);
    }
    
    private function agregarAlimento() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Método no permitido'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $campos_requeridos = ['nombre', 'id_grupo', 'calorias_100'];
        foreach ($campos_requeridos as $campo) {
            if (!isset($data[$campo])) {
                return $this->jsonResponse(['error' => "Campo requerido: $campo"], 400);
            }
        }
        
        $resultado = $this->alimentosManager->agregarAlimento($data, $this->getUserId());
        return $this->jsonResponse($resultado);
    }
    
    private function getUserId() {
        // Implementar según tu sistema de autenticación
        return $_SESSION['user_id'] ?? null;
    }
    
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        echo json_encode($data);
        exit;
    }
}

// Ejemplo de uso
/*
// En api/alimentos.php
require_once 'config/database.php';
require_once 'classes/AlimentosManager.php';

$api = new AlimentosAPI($pdo);
$api->handleRequest();
*/
?>