<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Dietas - Gym App</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
      
        
        /* Header personalizado */
.header-custom {
    background: linear-gradient(135deg, #0b2d9cff 0%, #25408cff 100%);
    color: white;
    padding: 2rem 0;
}

/* Cards de dietas */
.diet-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.diet-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

/* Headers de cards según género */
.card-header-male {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
}

.card-header-female {
    background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
    color: white;
}

/* Badges de objetivos */
.badge-bajar {
    background: #3a7bd5;
}

.badge-subir {
    background: #00d2ff;
}

/* Estadísticas nutricionales */
.nutrition-stats {
    background: #f8f9fa;
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
}

.stat-label {
    font-size: 0.85rem;
    color: #3a7bd5;
}

/* Filtros */
.filter-section {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

/* Responsivo */
@media (max-width: 768px) {
    .header-custom h1 {
        font-size: 2rem;
    }
}
    </style>
</head>

<body class="bg-light">
    <!-- ===========================================
         HEADER PRINCIPAL
    =========================================== -->
    <header class="header-custom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 mb-0">
                        <i class="bi bi-heart-pulse"></i>
                        Sistema de Dietas
                    </h1>
                    <p class="lead mb-0">Gestión completa de dietas alimentarias personalizadas</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50">
                        <i class="bi bi-clipboard-data"></i> 
                        <span id="total-dietas">8</span> Dietas Disponibles
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
            <h5 class="mb-3">
                <i class="bi bi-funnel"></i> Filtrar Dietas
            </h5>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Género</label>
                    <select class="form-select" id="filtro-genero">
                        <option value="">Todos</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Objetivo</label>
                    <select class="form-select" id="filtro-objetivo">
                        <option value="">Todos</option>
                        <option value="bajar_peso">Bajar Peso</option>
                        <option value="subir_peso">Subir Peso</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rango Calórico</label>
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
                <h2 class="mb-4">
                    <i class="bi bi-card-list"></i> Dietas Disponibles
                </h2>
            </div>
        </div>
        
        <div class="row" id="dietas-container">
            <!-- Las dietas se cargarán aquí dinámicamente -->
        </div>
    </div>

    <!-- ===========================================
         FOOTER
    =========================================== -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Sistema de Dietas</h5>
                    <p class="mb-0">Gestión profesional de dietas alimentarias</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">© 2024 GECK Codex. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

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
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle"></i>
                            No se encontraron dietas que coincidan con los filtros seleccionados.
                        </div>
                    </div>
                `;
                return;
            }
            
            const dietasHTML = dietas.map(dieta => `
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card diet-card h-100">
                        <div class="card-header ${dieta.genero === 'masculino' ? 'card-header-male' : 'card-header-female'}">
                            <h5 class="mb-0">
                                <i class="bi bi-${dieta.genero === 'masculino' ? 'person' : 'person-dress'}"></i>
                                ${dieta.nombre}
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted mb-3">${dieta.descripcion}</p>
                            
                            <div class="mb-3">
                                <span class="badge ${dieta.objetivo === 'bajar_peso' ? 'badge-bajar' : 'badge-subir'} me-2">
                                    ${dieta.objetivo === 'bajar_peso' ? '📉 Bajar Peso' : '📈 Subir Peso'}
                                </span>
                                <span class="badge bg-secondary">
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
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> ${dieta.duracion_sugerida} | 
                                    <i class="bi bi-egg-fried"></i> ${dieta.comidas} comidas/día
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-success">Beneficios:</h6>
                                <ul class="list-unstyled">
                                    ${dieta.beneficios.map(beneficio => `
                                        <li><i class="bi bi-check-circle text-success me-1"></i> ${beneficio}</li>
                                    `).join('')}
                                </ul>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
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
        }

        // ===========================================
        // FUNCIONES DE FILTRADO
        // ===========================================
        
        function aplicarFiltros() {
            const genero = document.getElementById('filtro-genero').value;
            const objetivo = document.getElementById('filtro-objetivo').value;
            const calorias = document.getElementById('filtro-calorias').value;
            
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