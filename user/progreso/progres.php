<?php
require_once('../../config/config.php');

// Función para obtener datos de medidas del usuario
function obtenerDatosMedidas($db, $id_usuario, $filtro = '6m') {
    $whereClause = '';
    switch($filtro) {
        case '1m':
            $whereClause = "AND fecha_medicion >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
        case '3m':
            $whereClause = "AND fecha_medicion >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
            break;
        case '6m':
            $whereClause = "AND fecha_medicion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
            break;
        case '1y':
            $whereClause = "AND fecha_medicion >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
        case 'all':
            $whereClause = "";
            break;
    }

    $sql = "SELECT 
                fecha_medicion,
                peso,
                altura,
                grasa_corporal,
                masa_muscular,
                cintura,
                cadera,
                pecho,
                brazo_derecho,
                brazo_izquierdo,
                pierna_derecha,
                pierna_izquierda,
                cuello
            FROM medidas 
            WHERE id_usuario = :id_usuario $whereClause
            ORDER BY fecha_medicion ASC";

    $db->query($sql);
    $db->bind(':id_usuario', $id_usuario);
    return $db->resultset();
}

// Función para obtener estadísticas generales
function obtenerEstadisticasGenerales($db, $id_usuario) {
    $sql = "SELECT 
                COUNT(*) as total_mediciones,
                (SELECT peso FROM medidas WHERE id_usuario = :id_usuario ORDER BY fecha_medicion DESC LIMIT 1) as peso_actual,
                (SELECT altura FROM medidas WHERE id_usuario = :id_usuario ORDER BY fecha_medicion DESC LIMIT 1) as altura_actual,
                (SELECT peso FROM medidas WHERE id_usuario = :id_usuario ORDER BY fecha_medicion ASC LIMIT 1) as peso_inicial,
                (SELECT fecha_medicion FROM medidas WHERE id_usuario = :id_usuario ORDER BY fecha_medicion ASC LIMIT 1) as fecha_inicial
            FROM medidas 
            WHERE id_usuario = :id_usuario";

    $db->query($sql);
    $db->bind(':id_usuario', $id_usuario);
    return $db->single();
}

// Función para calcular cambios en períodos específicos
function calcularCambiosTemporales($db, $id_usuario, $dias) {
    // Construimos la consulta con $dias inyectado directamente
    $sql = "SELECT 
                AVG(peso) as peso_promedio,
                AVG(grasa_corporal) as grasa_promedio,
                AVG(masa_muscular) as musculo_promedio
            FROM medidas 
            WHERE id_usuario = :id_usuario 
            AND fecha_medicion >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)";

    $db->query($sql);
    $db->bind(':id_usuario', $id_usuario);
    return $db->single();
}



// Obtener datos de sesión o por GET
$id_usuario = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : gym_get_logged_in_user()['id'];
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '6m';

// Instanciar la clase Database (ya conectada con PDO)
$db = new Database();

// Obtener datos
$datosMedidas = obtenerDatosMedidas($db, $id_usuario, $filtro);
$estadisticasGenerales = obtenerEstadisticasGenerales($db, $id_usuario);
$cambios30dias = calcularCambiosTemporales($db, $id_usuario, 30);
$cambios90dias = calcularCambiosTemporales($db, $id_usuario, 90);

// Preparar datos para JavaScript
$datosJS = [
    'fechas' => [],
    'peso' => [],
    'altura' => [],
    'grasaCorporal' => [],
    'masaMuscular' => [],
    'cintura' => [],
    'cadera' => [],
    'pecho' => [],
    'brazoDerecho' => [],
    'brazoIzquierdo' => [],
    'piernaDerecha' => [],
    'piernaIzquierda' => [],
    'cuello' => []
];

foreach($datosMedidas as $medida) {
    $datosJS['fechas'][] = $medida['fecha_medicion'];
    $datosJS['peso'][] = (float)$medida['peso'];
    $datosJS['altura'][] = (float)$medida['altura'];
    $datosJS['grasaCorporal'][] = (float)$medida['grasa_corporal'];
    $datosJS['masaMuscular'][] = (float)$medida['masa_muscular'];
    $datosJS['cintura'][] = (float)$medida['cintura'];
    $datosJS['cadera'][] = (float)$medida['cadera'];
    $datosJS['pecho'][] = (float)$medida['pecho'];
    $datosJS['brazoDerecho'][] = (float)$medida['brazo_derecho'];
    $datosJS['brazoIzquierdo'][] = (float)$medida['brazo_izquierdo'];
    $datosJS['piernaDerecha'][] = (float)$medida['pierna_derecha'];
    $datosJS['piernaIzquierda'][] = (float)$medida['pierna_izquierda'];
    $datosJS['cuello'][] = (float)$medida['cuello'];
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficas de Progreso - Métricas Corporales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>   body {
            padding-top: 120px; /* Espacio para el header sticky */
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            color: #495057;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            background: linear-gradient(135deg, #218838 0%, #1aa085 100%);
            transform: translateY(-2px);
            color: white;
        }
        
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .metric-summary {
            background: rgb(20, 66, 233);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .trend-up {
            color: #28a745;
        }
        
        .trend-down {
            color: #dc3545;
        }
        
        .trend-stable {
            color: #ffc107;
        }
        
        .chart-controls {
            margin-bottom: 20px;
        }
        
        .filter-btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .alert-no-data {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }

        /* Header con efecto glass mejorado */
        .glass-header {
            background: linear-gradient(135deg, rgb(20, 66, 233) 0%, rgba(52, 76, 161, 0.95) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 100px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Contenedor del header */
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Logo y título */
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-section img {
            height: 60px;
            width: auto;
        }

        .site-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            margin: 0;
        }

        /* Botones del header */
        .header-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            border-radius: 10px;
            padding: 12px 20px;
            text-decoration: none;
            color: white;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            backdrop-filter: blur(10px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Estadísticas mejoradas */
        .stats-section {
            margin-top: 30px;
            margin-bottom: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.95rem;
            color: #6c757d;
            font-weight: 500;
            margin: 0;
        }

        .stat-trend {
            font-size: 0.85rem;
            margin-top: 8px;
            padding: 4px 8px;
            border-radius: 20px;
            display: inline-block;
        }

        .trend-positive {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .trend-negative {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .trend-neutral {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 90px;
            }
            
            .glass-header {
                height: 80px;
            }
            
            .site-title {
                font-size: 1.4rem;
            }
            
            .btn-back span {
                display: none;
            }
            
            .btn-back {
                padding: 10px 12px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .stat-card {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .logo-section img {
                height: 45px;
            }
            
            .site-title {
                font-size: 1.2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header con efecto glass mejorado -->
    <header class="glass-header">
        <div class="header-container">
            <!-- Logo y título -->
            <div class="logo-section">
                <img src="/assets/images/gym (1).png" alt="Logo">
                <h1 class="site-title">Progreso de Métricas</h1>
            </div>
            
            <!-- Botones -->
            <div class="header-buttons">
                <a href="metricas.php" class="btn-back">
                    <i class="fas fa-plus me-2"></i>
                    <span>Agregar Métricas</span>
                </a>
                <a href="/dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i>
                    <span>Volver</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Contenido principal -->
    <div class="container-fluid px-4">
        <!-- Verificación de datos (esto iría en tu PHP) -->
        <div style="display: none;" id="no-data-alert">
            <div class="alert alert-warning alert-no-data">
                <i class="fas fa-chart-line fa-3x mb-3"></i>
                <h4>No hay datos disponibles</h4>
                <p>Aún no tienes medidas registradas. <a href="metricas.php">Agrega tu primera medición</a> para ver tus gráficas de progreso.</p>
            </div>
        </div>

        <?php if (empty($datosMedidas)): ?>
            <div class="alert alert-warning alert-no-data">
                <i class="fas fa-chart-line fa-3x mb-3"></i>
                <h4>No hay datos disponibles</h4>
                <p>Aún no tienes medidas registradas. <a href="metricas.php">Agrega tu primera medición</a> para ver tus gráficas de progreso.</p>
            </div>
        <?php else: ?>

         <!-- Estadísticas Generales Mejoradas -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-value">24</div>
                    <p class="stat-label">Mediciones Totales</p>
                    <div class="stat-trend trend-positive">
                        <i class="fas fa-arrow-up"></i> +3 este mes
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-weight"></i>
                    </div>
                    <div class="stat-value">72.5 kg</div>
                    <p class="stat-label">Peso Actual</p>
                    <div class="stat-trend trend-negative">
                        <i class="fas fa-arrow-down"></i> -2.3 kg
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stat-value">22.4</div>
                    <p class="stat-label">IMC Actual</p>
                    <div class="stat-trend trend-positive">
                        <i class="fas fa-check-circle"></i> Normal
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-trending-up"></i>
                    </div>
                    <div class="stat-value">-5.2%</div>
                    <p class="stat-label">Progreso General</p>
                    <div class="stat-trend trend-positive">
                        <i class="fas fa-trophy"></i> Excelente
                    </div>
                </div>
            </div>
        </div>

        <!-- Controles de Filtro -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="metric-card">
                    <h5 class="section-title">
                        <i class="fas fa-filter"></i> Filtros de Visualización
                    </h5>
                    <div class="chart-controls">
                        <a href="?id_usuario=<?php echo $id_usuario; ?>&filtro=1m" class="btn btn-outline-primary filter-btn <?php echo $filtro == '1m' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-day"></i> 1 Mes
                        </a>
                        <a href="?id_usuario=<?php echo $id_usuario; ?>&filtro=3m" class="btn btn-outline-primary filter-btn <?php echo $filtro == '3m' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-week"></i> 3 Meses
                        </a>
                        <a href="?id_usuario=<?php echo $id_usuario; ?>&filtro=6m" class="btn btn-outline-primary filter-btn <?php echo $filtro == '6m' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i> 6 Meses
                        </a>
                        <a href="?id_usuario=<?php echo $id_usuario; ?>&filtro=1y" class="btn btn-outline-primary filter-btn <?php echo $filtro == '1y' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar"></i> 1 Año
                        </a>
                        <a href="?id_usuario=<?php echo $id_usuario; ?>&filtro=all" class="btn btn-outline-primary filter-btn <?php echo $filtro == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-plus"></i> Todo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos Principales -->
        <div class="row">
            <!-- Evolución de Peso -->
            <div class="col-lg-6">
                <div class="metric-card">
                    <div class="chart-title">
                        <i class="fas fa-weight text-primary"></i> Evolución del Peso
                    </div>
                    <div class="chart-container">
                        <canvas id="pesoChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Composición Corporal -->
            <div class="col-lg-6">
                <div class="metric-card">
                    <div class="chart-title">
                        <i class="fas fa-chart-pie text-warning"></i> Composición Corporal
                    </div>
                    <div class="chart-container">
                        <canvas id="composicionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Medidas Corporales -->
            <div class="col-lg-8">
                <div class="metric-card">
                    <div class="chart-title">
                        <i class="fas fa-ruler text-info"></i> Evolución de Medidas Corporales
                    </div>
                    <div class="chart-container">
                        <canvas id="medidasChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Resumen de Progreso -->
            <div class="col-lg-4">
                <div class="metric-card">
                    <h5 class="section-title">
                        <i class="fas fa-trophy"></i> Resumen de Progreso
                    </h5>
                    <div id="progresoSummary">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- IMC Evolution -->
            <div class="col-lg-6">
                <div class="metric-card">
                    <div class="chart-title">
                        <i class="fas fa-chart-line text-success"></i> Evolución del IMC
                    </div>
                    <div class="chart-container">
                        <canvas id="imcChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Extremidades -->
            <div class="col-lg-6">
                <div class="metric-card">
                    <div class="chart-title">
                        <i class="fas fa-hand-paper text-danger"></i> Medidas de Extremidades
                    </div>
                    <div class="chart-container">
                        <canvas id="extremidadesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparación Temporal -->
        <div class="row">
            <div class="col-12">
                <div class="metric-card">
                    <h5 class="section-title">
                        <i class="fas fa-exchange-alt"></i> Comparación Temporal
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="metric-summary">
                                <h6><i class="fas fa-calendar-day"></i> Últimos 30 días</h6>
                                <div id="cambios30dias">
                                    <?php if ($cambios30dias): ?>
                                        <p><i class="fas fa-weight"></i> Peso promedio: <?php echo number_format($cambios30dias['peso_promedio'], 1); ?> kg</p>
                                        <p><i class="fas fa-chart-pie"></i> Grasa promedio: <?php echo number_format($cambios30dias['grasa_promedio'], 1); ?>%</p>
                                        <p><i class="fas fa-dumbbell"></i> Músculo promedio: <?php echo number_format($cambios30dias['musculo_promedio'], 1); ?> kg</p>
                                    <?php else: ?>
                                        <p>No hay datos suficientes</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="metric-summary">
                                <h6><i class="fas fa-calendar-week"></i> Últimos 90 días</h6>
                                <div id="cambios90dias">
                                    <?php if ($cambios90dias): ?>
                                        <p><i class="fas fa-weight"></i> Peso promedio: <?php echo number_format($cambios90dias['peso_promedio'], 1); ?> kg</p>
                                        <p><i class="fas fa-chart-pie"></i> Grasa promedio: <?php echo number_format($cambios90dias['grasa_promedio'], 1); ?>%</p>
                                        <p><i class="fas fa-dumbbell"></i> Músculo promedio: <?php echo number_format($cambios90dias['musculo_promedio'], 1); ?> kg</p>
                                    <?php else: ?>
                                        <p>No hay datos suficientes</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Datos desde PHP
        const datosReales = <?php echo json_encode($datosJS); ?>;
        
        // Verificar si hay datos
        if (!datosReales.fechas || datosReales.fechas.length === 0) {
            console.log('No hay datos para mostrar gráficos');
            // Salir si no hay datos
        } else {
            let charts = {};

            // Configuración común para todos los gráficos
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                }
            };

            // Funciones auxiliares
            function formatearFecha(fecha) {
                return new Date(fecha).toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit'
                });
            }

            function calcularIMC(pesos, alturas) {
                return pesos.map((peso, index) => {
                    if (peso && alturas[index]) {
                        const alturaM = alturas[index] / 100;
                        return parseFloat((peso / (alturaM * alturaM)).toFixed(1));
                    }
                    return 0;
                });
            }

            function filtrarDatos(datos) {
                // Filtrar datos nulos o vacíos
                const indices = [];
                for (let i = 0; i < datos.fechas.length; i++) {
                    if (datos.peso[i] !== null && datos.peso[i] !== undefined && datos.peso[i] > 0) {
                        indices.push(i);
                    }
                }
                
                const datosFiltrados = {};
                Object.keys(datos).forEach(key => {
                    datosFiltrados[key] = indices.map(i => datos[key][i]);
                });
                
                return datosFiltrados;
            }

            // Filtrar datos válidos
            const datosValidos = filtrarDatos(datosReales);

            // Inicializar gráficos
            function inicializarGraficos() {
                // Gráfico de Peso
                const pesoCtx = document.getElementById('pesoChart').getContext('2d');
                charts.peso = new Chart(pesoCtx, {
                    type: 'line',
                    data: {
                        labels: datosValidos.fechas.map(fecha => formatearFecha(fecha)),
                        datasets: [{
                            label: 'Peso (kg)',
                            data: datosValidos.peso,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        ...commonOptions,
                        plugins: {
                            ...commonOptions.plugins,
                            title: {
                                display: true,
                                text: 'Tendencia de Peso'
                            }
                        }
                    }
                });

                // Gráfico de Composición Corporal
                const composicionCtx = document.getElementById('composicionChart').getContext('2d');
                charts.composicion = new Chart(composicionCtx, {
                    type: 'line',
                    data: {
                        labels: datosValidos.fechas.map(fecha => formatearFecha(fecha)),
                        datasets: [{
                            label: 'Grasa Corporal (%)',
                            data: datosValidos.grasaCorporal,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4
                        }, {
                            label: 'Masa Muscular (kg)',
                            data: datosValidos.masaMuscular,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            ...commonOptions.scales,
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
                });

                // Gráfico de Medidas Corporales
                const medidasCtx = document.getElementById('medidasChart').getContext('2d');
                charts.medidas = new Chart(medidasCtx, {
                    type: 'line',
                    data: {
                        labels: datosValidos.fechas.map(fecha => formatearFecha(fecha)),
                        datasets: [{
                            label: 'Cintura (cm)',
                            data: datosValidos.cintura,
                            borderColor: '#ffc107',
                            backgroundColor: 'rgba(255, 193, 7, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4
                        }, {
                            label: 'Cadera (cm)',
                            data: datosValidos.cadera,
                            borderColor: '#6f42c1',
                            backgroundColor: 'rgba(111, 66, 193, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4
                        }, {
                            label: 'Pecho (cm)',
                            data: datosValidos.pecho,
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4
                        }]
                    },
                    options: commonOptions
                });

                // Gráfico de IMC
                const imcCtx = document.getElementById('imcChart').getContext('2d');
                const imcData = calcularIMC(datosValidos.peso, datosValidos.altura);
                charts.imc = new Chart(imcCtx, {
                    type: 'line',
                    data: {
                        labels: datosValidos.fechas.map(fecha => formatearFecha(fecha)),
                        datasets: [{
                            label: 'IMC',
                            data: imcData,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5
                        }]
                    },
                    options: commonOptions
                });

                // Gráfico de Extremidades (Radar)
                // Gráfico de Extremidades (Radar) - Continuación
                const extremidadesCtx = document.getElementById('extremidadesChart').getContext('2d');
                if (datosValidos.fechas.length > 0) {
                    const ultimoIndice = datosValidos.fechas.length - 1;
                    const primerIndice = 0;
                    
                    charts.extremidades = new Chart(extremidadesCtx, {
                        type: 'radar',
                        data: {
                            labels: ['Brazo Derecho', 'Brazo Izquierdo', 'Pierna Derecha', 'Pierna Izquierda', 'Cuello'],
                            datasets: [{
                                label: 'Última Medición',
                                data: [
                                    datosValidos.brazoDerecho[ultimoIndice] || 0,
                                    datosValidos.brazoIzquierdo[ultimoIndice] || 0,
                                    datosValidos.piernaDerecha[ultimoIndice] || 0,
                                    datosValidos.piernaIzquierda[ultimoIndice] || 0,
                                    datosValidos.cuello[ultimoIndice] || 0
                                ],
                                borderColor: '#dc3545',
                                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                                borderWidth: 2,
                                pointBackgroundColor: '#dc3545',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20
                                    }
                                }
                            },
                            scales: {
                                r: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0,0,0,0.1)'
                                    },
                                    pointLabels: {
                                        font: {
                                            size: 12
                                        }
                                    }
                                }
                            }
                        }
                    });

                    // Si hay más de una medición, agregar comparación
                    if (datosValidos.fechas.length > 1) {
                        charts.extremidades.data.datasets.push({
                            label: 'Primera Medición',
                            data: [
                                datosValidos.brazoDerecho[primerIndice] || 0,
                                datosValidos.brazoIzquierdo[primerIndice] || 0,
                                datosValidos.piernaDerecha[primerIndice] || 0,
                                datosValidos.piernaIzquierda[primerIndice] || 0,
                                datosValidos.cuello[primerIndice] || 0
                            ],
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 2,
                            pointBackgroundColor: '#28a745',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5
                        });
                        charts.extremidades.update();
                    }
                }

                // Generar resumen de progreso
                generarResumenProgreso();
            }

            // Función para generar resumen de progreso
            function generarResumenProgreso() {
                const contenedor = document.getElementById('progresoSummary');
                let html = '';

                if (datosValidos.fechas.length === 0) {
                    html = '<p class="text-muted">No hay datos suficientes para generar un resumen.</p>';
                } else if (datosValidos.fechas.length === 1) {
                    html = `
                        <div class="metric-summary">
                            <h6><i class="fas fa-info-circle"></i> Primera Medición</h6>
                            <p>Fecha: ${formatearFecha(datosValidos.fechas[0])}</p>
                            <p>Peso: ${datosValidos.peso[0]} kg</p>
                            <p>IMC: ${calcularIMC([datosValidos.peso[0]], [datosValidos.altura[0]])[0]}</p>
                        </div>
                    `;
                } else {
                    const primerPeso = datosValidos.peso[0];
                    const ultimoPeso = datosValidos.peso[datosValidos.peso.length - 1];
                    const cambiosPeso = ultimoPeso - primerPeso;
                    
                    const primeraGrasa = datosValidos.grasaCorporal[0];
                    const ultimaGrasa = datosValidos.grasaCorporal[datosValidos.grasaCorporal.length - 1];
                    const cambiosGrasa = ultimaGrasa - primeraGrasa;
                    
                    const primerMusculo = datosValidos.masaMuscular[0];
                    const ultimoMusculo = datosValidos.masaMuscular[datosValidos.masaMuscular.length - 1];
                    const cambiosMusculo = ultimoMusculo - primerMusculo;
                    
                    const primeraCintura = datosValidos.cintura[0];
                    const ultimaCintura = datosValidos.cintura[datosValidos.cintura.length - 1];
                    const cambiosCintura = ultimaCintura - primeraCintura;

                    html = `
                        <div class="metric-summary mb-3">
                            <h6><i class="fas fa-weight"></i> Cambio de Peso</h6>
                            <p class="${cambiosPeso > 0 ? 'trend-up' : cambiosPeso < 0 ? 'trend-down' : 'trend-stable'}">
                                ${cambiosPeso > 0 ? '+' : ''}${cambiosPeso.toFixed(1)} kg
                                <i class="fas fa-${cambiosPeso > 0 ? 'arrow-up' : cambiosPeso < 0 ? 'arrow-down' : 'minus'}"></i>
                            </p>
                        </div>
                        
                        <div class="metric-summary mb-3">
                            <h6><i class="fas fa-chart-pie"></i> Cambio de Grasa</h6>
                            <p class="${cambiosGrasa < 0 ? 'trend-up' : cambiosGrasa > 0 ? 'trend-down' : 'trend-stable'}">
                                ${cambiosGrasa > 0 ? '+' : ''}${cambiosGrasa.toFixed(1)}%
                                <i class="fas fa-${cambiosGrasa < 0 ? 'arrow-down' : cambiosGrasa > 0 ? 'arrow-up' : 'minus'}"></i>
                            </p>
                        </div>
                        
                        <div class="metric-summary mb-3">
                            <h6><i class="fas fa-dumbbell"></i> Cambio de Músculo</h6>
                            <p class="${cambiosMusculo > 0 ? 'trend-up' : cambiosMusculo < 0 ? 'trend-down' : 'trend-stable'}">
                                ${cambiosMusculo > 0 ? '+' : ''}${cambiosMusculo.toFixed(1)} kg
                                <i class="fas fa-${cambiosMusculo > 0 ? 'arrow-up' : cambiosMusculo < 0 ? 'arrow-down' : 'minus'}"></i>
                            </p>
                        </div>
                        
                        <div class="metric-summary mb-3">
                            <h6><i class="fas fa-ruler"></i> Cambio de Cintura</h6>
                            <p class="${cambiosCintura < 0 ? 'trend-up' : cambiosCintura > 0 ? 'trend-down' : 'trend-stable'}">
                                ${cambiosCintura > 0 ? '+' : ''}${cambiosCintura.toFixed(1)} cm
                                <i class="fas fa-${cambiosCintura < 0 ? 'arrow-down' : cambiosCintura > 0 ? 'arrow-up' : 'minus'}"></i>
                            </p>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <small>
                                <i class="fas fa-calendar"></i> Período: ${formatearFecha(datosValidos.fechas[0])} - ${formatearFecha(datosValidos.fechas[datosValidos.fechas.length - 1])}
                                <br>
                                <i class="fas fa-chart-line"></i> ${datosValidos.fechas.length} mediciones registradas
                            </small>
                        </div>
                    `;
                }

                contenedor.innerHTML = html;
            }

            // Función para obtener el color de tendencia
            function obtenerColorTendencia(valor) {
                if (valor > 0) return 'trend-up';
                if (valor < 0) return 'trend-down';
                return 'trend-stable';
            }

            // Función para calcular promedios móviles
            function calcularPromedioMovil(datos, ventana = 3) {
                const promedios = [];
                for (let i = 0; i < datos.length; i++) {
                    const inicio = Math.max(0, i - Math.floor(ventana / 2));
                    const fin = Math.min(datos.length, i + Math.ceil(ventana / 2));
                    const segmento = datos.slice(inicio, fin);
                    const promedio = segmento.reduce((sum, val) => sum + val, 0) / segmento.length;
                    promedios.push(promedio);
                }
                return promedios;
            }

            // Función para agregar líneas de tendencia
            function agregarLineaTendencia(chart, datos) {
                if (datos.length < 2) return;
                
                // Calcular regresión lineal simple
                const n = datos.length;
                const sumX = datos.reduce((sum, _, i) => sum + i, 0);
                const sumY = datos.reduce((sum, val) => sum + val, 0);
                const sumXY = datos.reduce((sum, val, i) => sum + i * val, 0);
                const sumX2 = datos.reduce((sum, _, i) => sum + i * i, 0);
                
                const slope = (n * sumXY - sumX * sumY) / (n * sumX2 - sumX * sumX);
                const intercept = (sumY - slope * sumX) / n;
                
                const tendencia = datos.map((_, i) => slope * i + intercept);
                
                chart.data.datasets.push({
                    label: 'Tendencia',
                    data: tendencia,
                    borderColor: 'rgba(108, 117, 125, 0.8)',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false,
                    tension: 0
                });
                
                chart.update();
            }

            // Función para actualizar gráficos con animaciones
            function actualizarGraficos() {
                Object.values(charts).forEach(chart => {
                    if (chart) {
                        chart.update('active');
                    }
                });
            }

            // Función para exportar datos
            function exportarDatos() {
                const datos = {
                    fechas: datosValidos.fechas,
                    metricas: {
                        peso: datosValidos.peso,
                        altura: datosValidos.altura,
                        grasaCorporal: datosValidos.grasaCorporal,
                        masaMuscular: datosValidos.masaMuscular,
                        cintura: datosValidos.cintura,
                        cadera: datosValidos.cadera,
                        pecho: datosValidos.pecho,
                        brazoDerecho: datosValidos.brazoDerecho,
                        brazoIzquierdo: datosValidos.brazoIzquierdo,
                        piernaDerecha: datosValidos.piernaDerecha,
                        piernaIzquierda: datosValidos.piernaIzquierda,
                        cuello: datosValidos.cuello
                    }
                };
                
                const dataStr = JSON.stringify(datos, null, 2);
                const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
                
                const exportFileDefaultName = 'metricas_corporales.json';
                
                const linkElement = document.createElement('a');
                linkElement.setAttribute('href', dataUri);
                linkElement.setAttribute('download', exportFileDefaultName);
                linkElement.click();
            }

            // Función para imprimir gráficos
            function imprimirGraficos() {
                window.print();
            }

            // Función para mostrar estadísticas detalladas
            function mostrarEstadisticasDetalladas() {
                if (datosValidos.fechas.length === 0) return;
                
                const estadisticas = {
                    peso: calcularEstadisticas(datosValidos.peso),
                    grasaCorporal: calcularEstadisticas(datosValidos.grasaCorporal),
                    masaMuscular: calcularEstadisticas(datosValidos.masaMuscular),
                    cintura: calcularEstadisticas(datosValidos.cintura)
                };
                
                console.log('Estadísticas detalladas:', estadisticas);
                
                // Mostrar en modal o alert
                const mensaje = `
                    ESTADÍSTICAS DETALLADAS
                    
                    Peso:
                    - Promedio: ${estadisticas.peso.promedio.toFixed(1)} kg
                    - Máximo: ${estadisticas.peso.maximo.toFixed(1)} kg
                    - Mínimo: ${estadisticas.peso.minimo.toFixed(1)} kg
                    - Desviación: ${estadisticas.peso.desviacion.toFixed(1)} kg
                    
                    Grasa Corporal:
                    - Promedio: ${estadisticas.grasaCorporal.promedio.toFixed(1)}%
                    - Máximo: ${estadisticas.grasaCorporal.maximo.toFixed(1)}%
                    - Mínimo: ${estadisticas.grasaCorporal.minimo.toFixed(1)}%
                    
                    Masa Muscular:
                    - Promedio: ${estadisticas.masaMuscular.promedio.toFixed(1)} kg
                    - Máximo: ${estadisticas.masaMuscular.maximo.toFixed(1)} kg
                    - Mínimo: ${estadisticas.masaMuscular.minimo.toFixed(1)} kg
                    
                    Cintura:
                    - Promedio: ${estadisticas.cintura.promedio.toFixed(1)} cm
                    - Máximo: ${estadisticas.cintura.maximo.toFixed(1)} cm
                    - Mínimo: ${estadisticas.cintura.minimo.toFixed(1)} cm
                `;
                
                alert(mensaje);
            }

            // Función auxiliar para calcular estadísticas
            function calcularEstadisticas(datos) {
                const datosValidos = datos.filter(d => d && d > 0);
                if (datosValidos.length === 0) return { promedio: 0, maximo: 0, minimo: 0, desviacion: 0 };
                
                const suma = datosValidos.reduce((sum, val) => sum + val, 0);
                const promedio = suma / datosValidos.length;
                const maximo = Math.max(...datosValidos);
                const minimo = Math.min(...datosValidos);
                
                const varianza = datosValidos.reduce((sum, val) => sum + Math.pow(val - promedio, 2), 0) / datosValidos.length;
                const desviacion = Math.sqrt(varianza);
                
                return { promedio, maximo, minimo, desviacion };
            }

            // Función para cambiar tipo de gráfico
            function cambiarTipoGrafico(chartName, newType) {
                if (charts[chartName]) {
                    charts[chartName].config.type = newType;
                    charts[chartName].update();
                }
            }

            // Función para mostrar/ocultar datasets
            function toggleDataset(chartName, datasetIndex) {
                if (charts[chartName]) {
                    const chart = charts[chartName];
                    const meta = chart.getDatasetMeta(datasetIndex);
                    meta.hidden = !meta.hidden;
                    chart.update();
                }
            }

            // Inicializar todos los gráficos
            inicializarGraficos();

            // Agregar líneas de tendencia opcionales
            setTimeout(() => {
                if (datosValidos.peso.length > 3) {
                    agregarLineaTendencia(charts.peso, datosValidos.peso);
                }
            }, 1000);

            // Event listeners para funcionalidades adicionales
            document.addEventListener('keydown', function(e) {
                // Ctrl + E para exportar datos
                if (e.ctrlKey && e.key === 'e') {
                    e.preventDefault();
                    exportarDatos();
                }
                
                // Ctrl + P para imprimir
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    imprimirGraficos();
                }
                
                // Ctrl + I para estadísticas
                if (e.ctrlKey && e.key === 'i') {
                    e.preventDefault();
                    mostrarEstadisticasDetalladas();
                }
            });

            // Actualizar gráficos cuando cambie el tamaño de ventana
            window.addEventListener('resize', function() {
                setTimeout(actualizarGraficos, 100);
            });

            console.log('Gráficos inicializados correctamente');
            console.log('Datos procesados:', datosValidos.fechas.length, 'mediciones');
        }
    </script>