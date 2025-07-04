<?php
require_once('../../config/config.php');
require_once('../../config/User.php');

// Verificar permisos
gym_check_permission('cliente');

// Inicializar objeto User
$user = new User();

// Obtener todas las rutinas preestablecidas
$rutinas = $user->obtener_rutinas_preestablecidas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutinas Disponibles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-rutina {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .card-rutina:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .badge-categoria {
            background-color: #6c757d;
            color: white;
        }
        .badge-objetivo {
            background-color: #0d6efd;
            color: white;
        }
        .info-card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .ejercicio-item {
            border-left: 4px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .ejercicio-imagen {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-5">Rutinas Disponibles</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>

        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-md-4">
                <select id="filtroCategoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    <option value="fuerza">Fuerza</option>
                    <option value="cardio">Cardio</option>
                    <option value="hiit">HIIT</option>
                    <option value="funcional">Funcional</option>
                    <option value="flexibilidad">Flexibilidad</option>
                </select>
            </div>
            <div class="col-md-4">
                <select id="filtroObjetivo" class="form-select">
                    <option value="">Todos los objetivos</option>
                    <option value="perdida_peso">Pérdida de peso</option>
                    <option value="ganancia_muscular">Ganancia muscular</option>
                    <option value="definicion">Definición</option>
                    <option value="resistencia">Resistencia</option>
                    <option value="fuerza">Fuerza</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" id="buscarRutina" class="form-control" placeholder="Buscar rutina...">
            </div>
        </div>

        <!-- Lista de rutinas -->
        <div class="row" id="listaRutinas">
            <?php if (empty($rutinas)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay rutinas disponibles en este momento.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($rutinas as $rutina): ?>
                <div class="col-md-6 col-lg-4 mb-4 rutina-card" 
                     data-categoria="<?php echo strtolower($rutina['categoria']); ?>" 
                     data-objetivo="<?php echo strtolower($rutina['objetivo']); ?>"
                     data-nombre="<?php echo strtolower($rutina['titulo']); ?>">
                    <div class="card h-100 card-rutina" onclick="verDetalleRutina(<?php echo $rutina['id']; ?>)">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($rutina['titulo']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars(substr($rutina['descripcion'], 0, 100) . (strlen($rutina['descripcion']) > 100 ? '...' : '')); ?></p>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge badge-categoria">
                                    <i class="fas fa-tag"></i> <?php echo ucfirst($rutina['categoria']); ?>
                                </span>
                                <span class="badge badge-objetivo">
                                    <i class="fas fa-bullseye"></i> <?php echo ucfirst(str_replace('_', ' ', $rutina['objetivo'])); ?>
                                </span>
                            </div>
                            
                            <div class="d-flex justify-content-between small text-muted">
                                <span><i class="fas fa-clock"></i> <?php echo $rutina['duracion_minutos']; ?> min</span>
                                <span><i class="fas fa-dumbbell"></i> <?php echo isset($rutina['total_ejercicios']) ? $rutina['total_ejercicios'] : 0; ?> ejercicios</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para ver detalles de rutina -->
    <div class="modal fade" id="modalVerRutina" tabindex="-1" aria-labelledby="modalVerRutinaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerRutinaLabel">Detalles de la Rutina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="contenidoVerRutina">
                    <!-- Contenido dinámico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="iniciarRutina()">Iniciar Rutina</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let rutinaActual = null;

        // Configurar filtros
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('filtroCategoria').addEventListener('change', filtrarRutinas);
            document.getElementById('filtroObjetivo').addEventListener('change', filtrarRutinas);
            document.getElementById('buscarRutina').addEventListener('input', filtrarRutinas);
        });

        function filtrarRutinas() {
            const categoria = document.getElementById('filtroCategoria').value;
            const objetivo = document.getElementById('filtroObjetivo').value;
            const busqueda = document.getElementById('buscarRutina').value.toLowerCase();

            document.querySelectorAll('.rutina-card').forEach(rutina => {
                const cumpleCategoria = !categoria || rutina.dataset.categoria === categoria;
                const cumpleObjetivo = !objetivo || rutina.dataset.objetivo === objetivo;
                const cumpleBusqueda = !busqueda || rutina.dataset.nombre.includes(busqueda);

                rutina.style.display = (cumpleCategoria && cumpleObjetivo && cumpleBusqueda) ? 'block' : 'none';
            });
        }

        function verDetalleRutina(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalVerRutina'));
            const contenido = document.getElementById('contenidoVerRutina');
            
            // Mostrar spinner de carga
            contenido.innerHTML = `
                <div class="text-center my-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles de la rutina...</p>
                </div>
            `;
            modal.show();

            // Cargar datos de la rutina
            fetch(`../../config/ver_rutina_con_ejersicios.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data); // Para debugging
                    
                    if (data.success) {
                        rutinaActual = data;
                        mostrarDetallesRutina(data);
                    } else {
                        contenido.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                ${data.message || 'No se pudieron cargar los detalles de la rutina.'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    contenido.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Error al cargar los detalles de la rutina: ${error.message}
                            <br><small class="text-muted">Verifica que el archivo ver_rutina_con_ejercicios.php existe y está configurado correctamente.</small>
                        </div>
                    `;
                });
        }

        function mostrarDetallesRutina(data) {
            const contenido = document.getElementById('contenidoVerRutina');
            
            // Formatear los ejercicios
            let ejerciciosHTML = '';
            if (data.ejercicios && data.ejercicios.length > 0) {
                ejerciciosHTML = '<div class="row">';
                data.ejercicios.forEach((ejercicio, index) => {
                    ejerciciosHTML += `
                        <div class="col-12 mb-3">
                            <div class="ejercicio-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        <span class="badge bg-primary fs-6">${index + 1}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1">${ejercicio.nombre}</h6>
                                        <p class="text-muted mb-2">${ejercicio.descripcion || 'Sin descripción'}</p>
                                        <div class="small">
                                            <span class="badge bg-secondary me-1">${ejercicio.series} series</span>
                                            <span class="badge bg-secondary me-1">${ejercicio.repeticiones} reps</span>
                                            <span class="badge bg-secondary">${ejercicio.descanso}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        ${ejercicio.imagen_url ? 
                                            `<img src="${ejercicio.imagen_url}" class="ejercicio-imagen" alt="${ejercicio.nombre}">` : 
                                            `<div class="ejercicio-imagen bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-dumbbell text-muted"></i>
                                            </div>`
                                        }
                                    </div>
                                </div>
                                ${ejercicio.instrucciones ? 
                                    `<div class="mt-2 p-2 bg-light rounded">
                                        <small><strong>Instrucciones:</strong> ${ejercicio.instrucciones}</small>
                                    </div>` : ''
                                }
                            </div>
                        </div>
                    `;
                });
                ejerciciosHTML += '</div>';
            } else {
                ejerciciosHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> 
                        No hay ejercicios definidos para esta rutina.
                    </div>
                `;
            }

            // Mostrar toda la información
            contenido.innerHTML = `
                <div class="info-card">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-tag"></i> Categoría:</strong> ${data.categoria}</p>
                            <p><strong><i class="fas fa-bullseye"></i> Objetivo:</strong> ${data.objetivo.replace('_', ' ')}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-clock"></i> Duración:</strong> ${data.duracion_minutos} minutos</p>
                            <p><strong><i class="fas fa-dumbbell"></i> Ejercicios:</strong> ${data.ejercicios ? data.ejercicios.length : 0}</p>
                        </div>
                    </div>
                </div>

                ${data.descripcion ? 
                    `<div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle"></i> ${data.descripcion}
                    </div>` : ''
                }

                <h6 class="mb-3"><i class="fas fa-list"></i> Ejercicios de la Rutina:</h6>
                ${ejerciciosHTML}
            `;
        }

        function iniciarRutina() {
            if (rutinaActual) {
                // Aquí puedes agregar lógica para iniciar la rutina
                alert(`¡Iniciando rutina: ${rutinaActual.titulo}!`);
                // Redirigir a página de entrenamiento o realizar alguna acción
            }
        }
    </script>
</body>
</html>