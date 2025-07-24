<?php
class AlimentosManager {
    private $pdo;
    
    public function __construct($database_connection) {
        $this->pdo = $database_connection;
    }
    
    /**
     * Buscar alimentos por término y/o etiqueta
     */
    public function buscarAlimentos($termino = '', $etiqueta_id = null, $limite = 20) {
        try {
            $stmt = $this->pdo->prepare("CALL BuscarAlimentos(?, ?, ?)");
            $stmt->execute([
                !empty($termino) ? $termino : null,
                $etiqueta_id,
                $limite
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Error al buscar alimentos: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener alimentos favoritos del usuario
     */
    public function obtenerFavoritos($id_usuario) {
        try {
            $sql = "
                SELECT 
                    ab.id,
                    ab.nombre,
                    ab.marca,
                    ab.calorias_100g,
                    ab.proteinas_100g,
                    ab.carbohidratos_100g,
                    ab.grasas_100g,
                    ab.fibra_100g,
                    ab.tipo_medida,
                    ab.peso_unidad,
                    GROUP_CONCAT(ea.nombre SEPARATOR ', ') as etiquetas
                FROM alimentos_favoritos af
                JOIN alimentos_base ab ON af.id_alimento = ab.id
                LEFT JOIN alimento_etiquetas ae ON ab.id = ae.id_alimento
                LEFT JOIN etiquetas_alimentos ea ON ae.id_etiqueta = ea.id
                WHERE af.id_usuario = ? AND ab.activo = TRUE
                GROUP BY ab.id
                ORDER BY af.fecha_agregado DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_usuario]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Error al obtener favoritos: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener alimentos más usados por el usuario
     */
    public function obtenerMasUsados($id_usuario, $limite = 10) {
        try {
            $sql = "
                SELECT 
                    ab.id,
                    ab.nombre,
                    ab.marca,
                    ab.calorias_100g,
                    ab.proteinas_100g,
                    ab.carbohidratos_100g,
                    ab.grasas_100g,
                    ab.fibra_100g,
                    ab.tipo_medida,
                    ab.peso_unidad,
                    COUNT(rc.id) as veces_usado_usuario,
                    MAX(rc.fecha_registro) as ultimo_uso
                FROM registro_comidas rc
                JOIN alimentos_base ab ON rc.id_alimento = ab.id
                WHERE rc.id_usuario = ? AND ab.activo = TRUE
                GROUP BY ab.id
                ORDER BY veces_usado_usuario DESC, ultimo_uso DESC
                LIMIT ?
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_usuario, $limite]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Error al obtener alimentos más usados: ' . $e->getMessage()];
        }
    }
    
    /**
     * Calcular macros basado en cantidad
     */
    public function calcularMacros($id_alimento, $cantidad, $tipo_medida = null) {
        try {
            // Obtener datos del alimento
            $stmt = $this->pdo->prepare("
                SELECT * FROM alimentos_base 
                WHERE id = ? AND activo = TRUE
            ");
            $stmt->execute([$id_alimento]);
            $alimento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$alimento) {
                return ['error' => 'Alimento no encontrado'];
            }
            
            // Usar tipo_medida del alimento si no se especifica
            if (!$tipo_medida) {
                $tipo_medida = $alimento['tipo_medida'];
            }
            
            // Calcular cantidad en gramos para el cálculo
            $cantidad_gramos = $cantidad;
            
            if ($tipo_medida === 'unidades' && $alimento['peso_unidad']) {
                $cantidad_gramos = $cantidad * $alimento['peso_unidad'];
            } elseif ($tipo_medida === 'mililitros') {
                // Para líquidos, 1ml ≈ 1g (aproximación)
                $cantidad_gramos = $cantidad;
            }
            
            // Calcular macros
            $factor = $cantidad_gramos / 100;
            
            $macros = [
                'alimento_id' => $id_alimento,
                'nombre' => $alimento['nombre'],
                'marca' => $alimento['marca'],
                'cantidad' => $cantidad,
                'tipo_medida' => $tipo_medida,
                'cantidad_gramos' => $cantidad_gramos,
                'calorias' => round($alimento['calorias_100g'] * $factor, 2),
                'proteinas' => round($alimento['proteinas_100g'] * $factor, 2),
                'carbohidratos' => round($alimento['carbohidratos_100g'] * $factor, 2),
                'grasas' => round($alimento['grasas_100g'] * $factor, 2),
                'fibra' => round($alimento['fibra_100g'] * $factor, 2),
                'sodio' => round($alimento['sodio_100g'] * $factor, 2),
                'azucares' => round($alimento['azucares_100g'] * $factor, 2),
                // Datos base por 100g para referencia
                'info_100g' => [
                    'calorias' => $alimento['calorias_100g'],
                    'proteinas' => $alimento['proteinas_100g'],
                    'carbohidratos' => $alimento['carbohidratos_100g'],
                    'grasas' => $alimento['grasas_100g'],
                    'fibra' => $alimento['fibra_100g']
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
            
            // Insertar registro de comida
            $stmt = $this->pdo->prepare("
                INSERT INTO registro_comidas (
                    id_usuario, id_alimento, fecha, comida, cantidad, tipo_medida,
                    calorias_totales, proteinas_totales, carbohidratos_totales,
                    grasas_totales, fibra_totales
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $id_usuario,
                $id_alimento,
                $fecha,
                $comida,
                $cantidad,
                $tipo_medida ?: $macros['tipo_medida'],
                $macros['calorias'],
                $macros['proteinas'],
                $macros['carbohidratos'],
                $macros['grasas'],
                $macros['fibra']
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
     * Obtener resumen del día
     */
    public function obtenerResumenDia($id_usuario, $fecha) {
        try {
            $sql = "
                SELECT 
                    rc.comida,
                    COUNT(*) as total_alimentos,
                    SUM(rc.calorias_totales) as calorias,
                    SUM(rc.proteinas_totales) as proteinas,
                    SUM(rc.carbohidratos_totales) as carbohidratos,
                    SUM(rc.grasas_totales) as grasas,
                    SUM(rc.fibra_totales) as fibra
                FROM registro_comidas rc
                WHERE rc.id_usuario = ? AND rc.fecha = ?
                GROUP BY rc.comida
                ORDER BY FIELD(rc.comida, 'desayuno', 'snack1', 'almuerzo', 'snack2', 'cena')
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_usuario, $fecha]);
            $por_comida = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular totales del día
            $sql_total = "
                SELECT 
                    SUM(calorias_totales) as calorias_dia,
                    SUM(proteinas_totales) as proteinas_dia,
                    SUM(carbohidratos_totales) as carbohidratos_dia,
                    SUM(grasas_totales) as grasas_dia,
                    SUM(fibra_totales) as fibra_dia,
                    COUNT(*) as total_registros
                FROM registro_comidas 
                WHERE id_usuario = ? AND fecha = ?
            ";
            
            $stmt_total = $this->pdo->prepare($sql_total);
            $stmt_total->execute([$id_usuario, $fecha]);
            $totales = $stmt_total->fetch(PDO::FETCH_ASSOC);
            
            // Obtener objetivos del usuario
            $objetivos = $this->obtenerObjetivos($id_usuario);
            
            return [
                'fecha' => $fecha,
                'por_comida' => $por_comida,
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
                // Crear objetivos por defecto basados en el perfil del usuario
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
        // Objetivos genéricos por defecto
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
        
        $macros = ['calorias', 'proteinas', 'carbohidratos', 'grasas', 'fibra'];
        
        foreach ($macros as $macro) {
            $consumido = floatval($totales[$macro . '_dia'] ?? 0);
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
     * Obtener detalles de comidas del día
     */
    public function obtenerDetallesDia($id_usuario, $fecha) {
        try {
            $sql = "
                SELECT 
                    rc.id,
                    rc.comida,
                    rc.cantidad,
                    rc.tipo_medida,
                    rc.calorias_totales,
                    rc.proteinas_totales,
                    rc.carbohidratos_totales,
                    rc.grasas_totales,
                    rc.fibra_totales,
                    rc.fecha_registro,
                    ab.nombre as alimento_nombre,
                    ab.marca as alimento_marca,
                    ab.tipo_medida as alimento_tipo_medida
                FROM registro_comidas rc
                JOIN alimentos_base ab ON rc.id_alimento = ab.id
                WHERE rc.id_usuario = ? AND rc.fecha = ?
                ORDER BY FIELD(rc.comida, 'desayuno', 'snack1', 'almuerzo', 'snack2', 'cena'), rc.fecha_registro
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_usuario, $fecha]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Error al obtener detalles del día: ' . $e->getMessage()];
        }
    }
    
    /**
     * Agregar/quitar de favoritos
     */
    public function toggleFavorito($id_usuario, $id_alimento) {
        try {
            // Verificar si ya está en favoritos
            $stmt = $this->pdo->prepare("
                SELECT id FROM alimentos_favoritos 
                WHERE id_usuario = ? AND id_alimento = ?
            ");
            $stmt->execute([$id_usuario, $id_alimento]);
            $existe = $stmt->fetch();
            
            if ($existe) {
                // Quitar de favoritos
                $stmt = $this->pdo->prepare("
                    DELETE FROM alimentos_favoritos 
                    WHERE id_usuario = ? AND id_alimento = ?
                ");
                $stmt->execute([$id_usuario, $id_alimento]);
                
                return ['success' => true, 'accion' => 'eliminado', 'es_favorito' => false];
            } else {
                // Agregar a favoritos
                $stmt = $this->pdo->prepare("
                    INSERT INTO alimentos_favoritos (id_usuario, id_alimento) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$id_usuario, $id_alimento]);
                
                return ['success' => true, 'accion' => 'agregado', 'es_favorito' => true];
            }
        } catch (Exception $e) {
            return ['error' => 'Error al actualizar favoritos: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener todas las etiquetas activas
     */
    public function obtenerEtiquetas() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM etiquetas_alimentos 
                WHERE activa = TRUE 
                ORDER BY nombre
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Error al obtener etiquetas: ' . $e->getMessage()];
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
                    SUM(rc.calorias_totales) as calorias,
                    SUM(rc.proteinas_totales) as proteinas,
                    SUM(rc.carbohidratos_totales) as carbohidratos,
                    SUM(rc.grasas_totales) as grasas,
                    COUNT(*) as registros
                FROM registro_comidas rc
                WHERE rc.id_usuario = ? 
                AND rc.fecha BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)
                GROUP BY rc.fecha
                ORDER BY rc.fecha
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_usuario, $fecha_inicio, $fecha_inicio]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Error al obtener estadísticas semanales: ' . $e->getMessage()];
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
        
        // Verificar autenticación (implementar según tu sistema)
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
            
            case 'detalles':
                return $this->obtenerDetalles();
            
            case 'favoritos':
                return $this->manejarFavoritos();
            
            case 'etiquetas':
                return $this->obtenerEtiquetas();
            
            case 'eliminar':
                return $this->eliminarRegistro();
            
            case 'estadisticas':
                return $this->obtenerEstadisticas();
            
            default:
                return $this->jsonResponse(['error' => 'Acción no válida'], 400);
        }
    }
    
    private function buscarAlimentos() {
        $termino = $_GET['q'] ?? '';
        $etiqueta = $_GET['etiqueta'] ?? null;
        $limite = intval($_GET['limite'] ?? 20);
        
        $resultados = $this->alimentosManager->buscarAlimentos($termino, $etiqueta, $limite);
        
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
    
    private function obtenerDetalles() {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $detalles = $this->alimentosManager->obtenerDetallesDia($this->getUserId(), $fecha);
        
        return $this->jsonResponse($detalles);
    }
    
    private function manejarFavoritos() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['alimento_id'])) {
                return $this->jsonResponse(['error' => 'ID de alimento requerido'], 400);
            }
            
            $resultado = $this->alimentosManager->toggleFavorito(
                $this->getUserId(),
                $data['alimento_id']
            );
            
            return $this->jsonResponse($resultado);
        } else {
            $favoritos = $this->alimentosManager->obtenerFavoritos($this->getUserId());
            return $this->jsonResponse($favoritos);
        }
    }
    
    private function obtenerEtiquetas() {
        $etiquetas = $this->alimentosManager->obtenerEtiquetas();
        return $this->jsonResponse($etiquetas);
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
    
    private function getUserId() {
        // Implementar según tu sistema de autenticación
        // Por ejemplo, desde session o JWT
        return $_SESSION['user_id'] ?? null;
    }
    
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Ejemplo de uso de la API
/*
// En tu endpoint principal (ej: api/alimentos.php)
require_once 'database_connection.php';
require_once 'AlimentosManager.php';

$api = new AlimentosAPI($pdo);
$api->handleRequest();
*/
?>