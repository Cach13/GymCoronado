<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Dietas - Glassmorphism</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Fondo con imagen y blur */
        body {
            background: linear-gradient(135deg, rgba(13, 109, 253, 0.27), rgba(15, 15, 15, 0.59)),
                        url('https://images.unsplash.com/photo-1490645935967-10de6ba17061?ixlib=rb-4.0.3&auto=format&fit=crop&w=2053&q=80');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            position: relative;
            min-height: 100vh;
        }

        /* Overlay con blur para mejor legibilidad */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            z-index: -1;
        }

        /* Header personalizado (sin efecto vidrio) */
        .header-custom {
            background: rgb(20, 66, 233);
            color: white;
            padding: 2rem 0;
            position: sticky; /* Cambia esto */
            top: 0;
            z-index: 10;
        }

        /* Efecto glassmorphism base */
        .glass-effect {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        /* Sección de filtros con efecto vidrio */
        .filter-section {
            background: rgba(255, 255, 255, 0.52);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Cards de dietas con efecto vidrio */
        .diet-card {
            background: rgba(255, 255, 255, 1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .diet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.2);
        }

        /* Headers de cards según género con efecto vidrio */
        .card-header-male {
            background: rgba(30, 60, 114, 0.8);
            backdrop-filter: blur(10px);
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-header-female {
            background: rgba(58, 123, 213, 0.8);
            backdrop-filter: blur(10px);
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Badges de objetivos con efecto vidrio */
        .badge-bajar {
            background: rgba(58, 123, 213, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .badge-subir {
            background: rgba(0, 210, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Estadísticas nutricionales con efecto vidrio */
        .nutrition-stats {
            background: rgba(248, 249, 250, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1a3a8f;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
        }

        .stat-label {
            font-size: 0.85rem;
            color: #3a7bd5;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
        }

        /* Footer con efecto vidrio */
        .footer-glass {
            background: rgba(33, 37, 41, 0.9);
            backdrop-filter: blur(15px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        /* Formularios con efecto vidrio */
        .form-select, .form-control {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #333;
        }

        .form-select:focus, .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(13, 110, 253, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Botones con efecto vidrio */
        .btn-primary {
            background: rgba(13, 110, 253, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: rgba(13, 110, 253, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }

        /* Texto con mejor contraste */
        .text-enhanced {
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
            color: #333;
        }

        .text-white-enhanced {
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* Animaciones suaves */
        * {
            transition: all 0.3s ease;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .header-custom h1 {
                font-size: 2rem;
            }
            
            body::before {
                backdrop-filter: blur(3px);
            }
        }
         /* Botón de retorno normal */
        .btn-back {
            background: rgba(255, 255, 255, 0.52);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }
    </style>
</head>

<body>
    <!-- ===========================================
         HEADER PRINCIPAL
    =========================================== -->
    <header class="header-custom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 mb-0 text-white-enhanced">
                        <img src="/assets/images/gym (1).png" alt="Logo" style="height: 65px;" class="me-2">
                        Nutrición
                    </h1>
                </div>
                <div class="col-md-4 text-end">
                     <div class="flex items-center space-x-4">
                        <a href="/dashboard.php" class="btn-back text-white hover:text-blue-100 transition-all duration-300 p-3 ">
                            <i class="fas fa-arrow-left text-lg me-2"></i>
                            Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ===========================================
         SECCIÓN DE FILTROS
    =========================================== -->
    <div class="container mt-4">
        <div class="filter-section">
            <h5 class="mb-3 text-enhanced">
                <i class="bi bi-funnel"></i> Filtrar Dietas
            </h5>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label text-enhanced">Género</label>
                    <select class="form-select" id="filtro-genero">
                        <option value="">Todos</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-enhanced">Objetivo</label>
                    <select class="form-select" id="filtro-objetivo">
                        <option value="">Todos</option>
                        <option value="bajar_peso">Bajar Peso</option>
                        <option value="subir_peso">Subir Peso</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-enhanced">Rango Calórico</label>
                    <select class="form-select" id="filtro-calorias">
                        <option value="">Todos</option>
                        <option value="1000-1500">1000-1500 cal</option>
                        <option value="1500-2000">1500-2000 cal</option>
                        <option value="2000-2500">2000-2500 cal</option>
                        <option value="2500+">2500+ cal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary d-block w-100" onclick="aplicarFiltros()">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===========================================
         SECCIÓN DE DIETAS
    =========================================== -->
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4 text-enhanced">
                    <i class="bi bi-card-list"></i> Dietas Disponibles
                </h2>
            </div>
        </div>
        
        <div class="row" id="dietas-container">
            <!-- Ejemplo de dieta masculina -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="diet-card">
                    <div class="card-header-male p-3">
                        <h5 class="mb-0">
                            <i class="bi bi-person-fill me-2"></i>
                            Dieta Masculina - Definición
                        </h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge badge-bajar px-3 py-2">
                                <i class="bi bi-arrow-down-circle me-1"></i>
                                Bajar Peso
                            </span>
                            <span class="text-muted text-enhanced">2000 cal</span>
                        </div>
                        
                        <div class="nutrition-stats mb-3">
                            <div class="row">
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number">150g</div>
                                        <div class="stat-label">Proteínas</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number">200g</div>
                                        <div class="stat-label">Carbohidratos</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number">67g</div>
                                        <div class="stat-label">Grasas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-muted text-enhanced">
                            <i class="bi bi-clock me-1"></i>
                            Dieta diseñada para definición muscular con déficit calórico controlado.
                        </p>
                        
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-eye me-1"></i>
                            Ver Detalles
                        </button>
                    </div>
                </div>
            </div>

            <!-- Ejemplo de dieta femenina -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="diet-card">
                    <div class="card-header-female p-3">
                        <h5 class="mb-0">
                            <i class="bi bi-person-fill me-2"></i>
                            Dieta Femenina - Tonificación
                        </h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge badge-subir px-3 py-2">
                                <i class="bi bi-arrow-up-circle me-1"></i>
                                Subir Peso
                            </span>
                            <span class="text-muted text-enhanced">1800 cal</span>
                        </div>
                        
                        <div class="nutrition-stats mb-3">
                            <div class="row">
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number">120g</div>
                                        <div class="stat-label">Proteínas</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number">180g</div>
                                        <div class="stat-label">Carbohidratos</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number">60g</div>
                                        <div class="stat-label">Grasas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-muted text-enhanced">
                            <i class="bi bi-clock me-1"></i>
                            Plan nutricional para tonificación y ganancia de masa muscular magra.
                        </p>
                        
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-eye me-1"></i>
                            Ver Detalles
                        </button>
                    </div>
                </div>
            </div>

            <!-- Ejemplo de dieta alta en calorías -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="diet-card">
                    <div class="card-header-male p-3">
                        <h5 class="mb-0">
                            <i class="bi bi-person-fill me-2"></i>
                            Dieta Volumen - Masculina
                        </h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge badge-subir px-3 py-2">
                                <i class="bi bi-arrow-up-circle me-1"></i>
                                Subir Peso
                            </span>
                            <span class="text-muted text-enhanced">2800 cal</span>
                        </div>
                        
                        <div class="nutrition-stats mb-3">
                            <div class="row">
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number">200g</div>
                                        <div class="stat-label">Proteínas</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number">350g</div>
                                        <div class="stat-label">Carbohidratos</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <div class="stat-number">93g</div>
                                        <div class="stat-label">Grasas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-muted text-enhanced">
                            <i class="bi bi-clock me-1"></i>
                            Dieta hipercalórica para ganancia de masa muscular y volumen.
                        </p>
                        
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-eye me-1"></i>
                            Ver Detalles
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===========================================
         FOOTER
    =========================================== -->
    <footer class="footer-glass mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-white-enhanced">Sistema de Dietas</h5>
                    <p class="mb-0 text-white-enhanced">Gestión profesional de dietas alimentarias</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-white-enhanced">© 2024 GECK Codex. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ===========================================
        // DATOS SIMULADOS DE DIETAS
        // ===========================================
        const dietasData = [
            // DIETAS PARA MUJERES - BAJAR PESO
            {
                id: 1,
                nombre: "Dieta Mediterránea Femenina",
                descripcion: "Dieta balanceada basada en alimentos frescos del mediterráneo, ideal para pérdida de peso gradual y sostenible.",
                genero: "femenino",
                objetivo: "bajar_peso",
                calorias: 1400,
                proteinas: 105,
                carbohidratos: 140,
                grasas: 47,
                comidas: 5,
                duracion_sugerida: "8-12 semanas",
                beneficios: ["Pérdida de peso gradual", "Mejora cardiovascular", "Rico en antioxidantes"],
                archivo: "mediterranea.html"
            },
            
            
            // DIETAS PARA MUJERES - SUBIR PESO
            {
                id: 3,
                nombre: "Dieta Ganancia Limpia Femenina",
                descripcion: "Dieta hipercalórica con alimentos nutritivos para ganar peso y masa muscular de forma saludable.",
                genero: "femenino",
                objetivo: "subir_peso",
                calorias: 2200,
                proteinas: 140,
                carbohidratos: 275,
                grasas: 73,
                comidas: 6,
                duracion_sugerida: "12-16 semanas",
                beneficios: ["Ganancia de masa muscular", "Aumento de energía", "Nutrición completa"],
                archivo: "ganancia.html"
            },
           
            // DIETAS PARA HOMBRES - BAJAR PESO
            {
                id: 5,
                nombre: "Dieta Cutting Masculina",
                descripcion: "Dieta de definición para hombres que buscan reducir grasa corporal manteniendo masa muscular.",
                genero: "masculino",
                objetivo: "bajar_peso",
                calorias: 1800,
                proteinas: 180,
                carbohidratos: 135,
                grasas: 60,
                comidas: 5,
                duracion_sugerida: "8-12 semanas",
                beneficios: ["Definición muscular", "Pérdida de grasa", "Mantiene músculo"],
                archivo: "bajar.html"
            },
           
            
            // DIETAS PARA HOMBRES - SUBIR PESO
            {
                id: 7,
                nombre: "Dieta Bulk Masculina",
                descripcion: "Dieta hipercalórica para hombres que buscan ganar masa muscular y peso de forma eficiente.",
                genero: "masculino",
                objetivo: "subir_peso",
                calorias: 3000,
                proteinas: 200,
                carbohidratos: 375,
                grasas: 100,
                comidas: 6,
                duracion_sugerida: "12-16 semanas",
                beneficios: ["Ganancia muscular rápida", "Fuerza aumentada", "Volumen corporal"],
                archivo: "subir.html"
            },
           
        ];

        // ===========================================
        // FUNCIONES DE RENDERIZADO
        // ===========================================
        
        function renderizarDietas(dietas = dietasData) {
            const container = document.getElementById('dietas-container');
            
            if (dietas.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info text-center glass-effect">
                            <i class="bi bi-info-circle"></i>
                            No se encontraron dietas que coincidan con los filtros seleccionados.
                        </div>
                    </div>
                `;
                return;
            }
            
            const dietasHTML = dietas.map(dieta => `
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="diet-card h-100">
                        <div class="card-header ${dieta.genero === 'masculino' ? 'card-header-male' : 'card-header-female'} p-3">
                            <h5 class="mb-0">
                                <i class="bi bi-${dieta.genero === 'masculino' ? 'person' : 'person-dress'}"></i>
                                ${dieta.nombre}
                            </h5>
                        </div>
                        <div class="card-body p-3">
                            <p class="card-text text-enhanced mb-3">${dieta.descripcion}</p>
                            
                            <div class="mb-3">
                                <span class="badge ${dieta.objetivo === 'bajar_peso' ? 'badge-bajar' : 'badge-subir'} me-2">
                                    ${dieta.objetivo === 'bajar_peso' ? '📉 Bajar Peso' : '📈 Subir Peso'}
                                </span>
                                <span class="badge bg-secondary" style="background: rgba(108, 117, 125, 0.8) !important; backdrop-filter: blur(10px);">
                                    ${dieta.genero === 'masculino' ? '👨 Masculino' : '👩 Femenino'}
                                </span>
                            </div>
                            
                            <div class="nutrition-stats mb-3">
                                <div class="row text-center">
                                    <div class="col-6 col-sm-3">
                                        <div class="stat-item">
                                            <div class="stat-number">${dieta.calorias}</div>
                                            <div class="stat-label">Calorías</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="stat-item">
                                            <div class="stat-number">${dieta.proteinas}g</div>
                                            <div class="stat-label">Proteínas</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="stat-item">
                                            <div class="stat-number">${dieta.carbohidratos}g</div>
                                            <div class="stat-label">Carbohidratos</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="stat-item">
                                            <div class="stat-number">${dieta.grasas}g</div>
                                            <div class="stat-label">Grasas</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-enhanced">
                                    <i class="bi bi-clock"></i> ${dieta.duracion_sugerida} | 
                                    <i class="bi bi-egg-fried"></i> ${dieta.comidas} comidas/día
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-success" style="color: #28a745 !important; text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);">Beneficios:</h6>
                                <ul class="list-unstyled">
                                    ${dieta.beneficios.map(beneficio => `
                                        <li class="text-enhanced"><i class="bi bi-check-circle text-success me-1"></i> ${beneficio}</li>
                                    `).join('')}
                                </ul>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent" style="border-top: 1px solid rgba(255, 255, 255, 0.2);">
                            <div class="d-grid">
                                <button class="btn btn-primary" onclick="verDetallesDieta('${dieta.archivo}')">
                                    <i class="bi bi-eye"></i> Ver Detalles
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = dietasHTML;
            
            // Agregar efectos de hover mejorados a las nuevas tarjetas
            const cards = document.querySelectorAll('.diet-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        }

        // ===========================================
        // FUNCIONES DE FILTRADO
        // ===========================================
        
        function aplicarFiltros() {
            const genero = document.getElementById('filtro-genero').value;
            const objetivo = document.getElementById('filtro-objetivo').value;
            const calorias = document.getElementById('filtro-calorias').value;
            
            // Agregar efecto de loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Filtrando...';
            btn.disabled = true;
            
            setTimeout(() => {
                let dietasFiltradas = dietasData;
                
                // Filtrar por género
                if (genero) {
                    dietasFiltradas = dietasFiltradas.filter(dieta => dieta.genero === genero);
                }
                
                // Filtrar por objetivo
                if (objetivo) {
                    dietasFiltradas = dietasFiltradas.filter(dieta => dieta.objetivo === objetivo);
                }
                
                // Filtrar por calorías
                if (calorias) {
                    dietasFiltradas = dietasFiltradas.filter(dieta => {
                        const cal = dieta.calorias;
                        switch (calorias) {
                            case '1000-1500':
                                return cal >= 1000 && cal <= 1500;
                            case '1500-2000':
                                return cal >= 1500 && cal <= 2000;
                            case '2000-2500':
                                return cal >= 2000 && cal <= 2500;
                            case '2500+':
                                return cal >= 2500;
                            default:
                                return true;
                        }
                    });
                }
                
                renderizarDietas(dietasFiltradas);
                
                // Actualizar contador
                document.getElementById('total-dietas').textContent = dietasFiltradas.length;
                
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 800);
        }

        // ===========================================
        // FUNCIONES DE INTERACCIÓN
        // ===========================================
        
        function verDetallesDieta(archivo) {
            // Redirigir al archivo específico de la dieta
            window.location.href = archivo;
        }

        // ===========================================
        // INICIALIZACIÓN
        // ===========================================
        
        document.addEventListener('DOMContentLoaded', function() {
            renderizarDietas();
            
            // Actualizar contador de dietas
            document.getElementById('total-dietas').textContent = dietasData.length;
        });
    </script>
</body>
</html>