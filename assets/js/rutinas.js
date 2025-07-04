    // Variables globales
        let ejerciciosSeleccionados = [];
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            inicializarEventos();
            configurarFiltros();
        });

        function inicializarEventos() {
            // Tabs
            document.querySelectorAll('[data-toggle="tab"]').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = this.getAttribute('href');
                    
                    // Remover active de todos los tabs
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('show', 'active');
                    });
                    document.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    
                    // Activar tab seleccionado
                    document.querySelector(target).classList.add('show', 'active');
                    this.classList.add('active');
                });
            });

            // Formulario ejercicio
            document.getElementById('formEjercicio').addEventListener('submit', function(e) {
                e.preventDefault();
                guardarEjercicio();
            });

            // Formulario rutina
            document.getElementById('formRutina').addEventListener('submit', function(e) {
                e.preventDefault();
                guardarRutina();
            });

            // Checkboxes de ejercicios
            document.querySelectorAll('#listaEjerciciosDisponibles input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        agregarEjercicioSeleccionado(this.value, this.nextElementSibling.querySelector('strong').textContent);
                    } else {
                        removerEjercicioSeleccionado(this.value);
                    }
                });
            });

            // Búsqueda en modal de rutina
            document.getElementById('buscarEjerciciosModal').addEventListener('input', function() {
                filtrarEjerciciosModal(this.value);
            });
        }

        function configurarFiltros() {
            // Filtros de ejercicios
            document.getElementById('filtroGrupo').addEventListener('change', aplicarFiltrosEjercicios);
            document.getElementById('filtroDificultad').addEventListener('change', aplicarFiltrosEjercicios);
            document.getElementById('buscarEjercicio').addEventListener('input', aplicarFiltrosEjercicios);

            // Filtros de rutinas
            document.getElementById('filtroCategoria').addEventListener('change', aplicarFiltrosRutinas);
            document.getElementById('filtroObjetivo').addEventListener('change', aplicarFiltrosRutinas);
            document.getElementById('buscarRutina').addEventListener('input', aplicarFiltrosRutinas);
        }

        function aplicarFiltrosEjercicios() {
            const grupo = document.getElementById('filtroGrupo').value;
            const dificultad = document.getElementById('filtroDificultad').value;
            const busqueda = document.getElementById('buscarEjercicio').value.toLowerCase();

            document.querySelectorAll('.grupo-ejercicios').forEach(grupoCard => {
                let mostrarGrupo = false;

                if (grupo && grupoCard.dataset.grupo !== grupo) {
                    grupoCard.style.display = 'none';
                    return;
                }

                grupoCard.querySelectorAll('.ejercicio-card').forEach(ejercicio => {
                    let mostrar = true;

                    if (dificultad && ejercicio.dataset.dificultad !== dificultad) {
                        mostrar = false;
                    }

                    if (busqueda) {
                        const nombre = ejercicio.querySelector('.card-title').textContent.toLowerCase();
                        if (!nombre.includes(busqueda)) {
                            mostrar = false;
                        }
                    }

                    ejercicio.style.display = mostrar ? 'block' : 'none';
                    if (mostrar) mostrarGrupo = true;
                });

                grupoCard.style.display = mostrarGrupo ? 'block' : 'none';
            });
        }

        function aplicarFiltrosRutinas() {
            const categoria = document.getElementById('filtroCategoria').value;
            const objetivo = document.getElementById('filtroObjetivo').value;
            const busqueda = document.getElementById('buscarRutina').value.toLowerCase();

            document.querySelectorAll('.rutina-card').forEach(rutina => {
                let mostrar = true;

                if (categoria && rutina.dataset.categoria !== categoria) {
                    mostrar = false;
                }

                if (objetivo && rutina.dataset.objetivo !== objetivo) {
                    mostrar = false;
                }

                if (busqueda) {
                    const titulo = rutina.querySelector('.card-title').textContent.toLowerCase();
                    const descripcion = rutina.querySelector('.card-text').textContent.toLowerCase();
                    if (!titulo.includes(busqueda) && !descripcion.includes(busqueda)) {
                        mostrar = false;
                    }
                }

                rutina.style.display = mostrar ? 'block' : 'none';
            });
        }

        function filtrarEjerciciosModal(busqueda) {
            const busquedaLower = busqueda.toLowerCase();
            document.querySelectorAll('.ejercicio-disponible').forEach(item => {
                const nombre = item.dataset.nombre;
                item.style.display = nombre.includes(busquedaLower) ? 'block' : 'none';
            });
        }

        function agregarEjercicioSeleccionado(id, nombre) {
            if (!ejerciciosSeleccionados.includes(id)) {
                ejerciciosSeleccionados.push(id);
                actualizarListaSeleccionados();
            }
        }

        function removerEjercicioSeleccionado(id) {
            ejerciciosSeleccionados = ejerciciosSeleccionados.filter(ejercicioId => ejercicioId !== id);
            actualizarListaSeleccionados();
        }

        function actualizarListaSeleccionados() {
            const lista = document.getElementById('listaEjerciciosSeleccionados');
            if (ejerciciosSeleccionados.length === 0) {
                lista.innerHTML = '<p class="text-muted">Selecciona ejercicios de la lista de la izquierda</p>';
                return;
            }

            let html = '';
            ejerciciosSeleccionados.forEach(id => {
                const checkbox = document.getElementById(`ejercicio_${id}`);
                const label = checkbox.nextElementSibling;
                const nombre = label.querySelector('strong').textContent;
                const detalles = label.querySelector('small').textContent;
                
                html += `
                    <div class="alert alert-light d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${nombre}</strong><br>
                            <small class="text-muted">${detalles}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerEjercicioDeRutina('${id}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            lista.innerHTML = html;
        }

        function removerEjercicioDeRutina(id) {
            document.getElementById(`ejercicio_${id}`).checked = false;
            removerEjercicioSeleccionado(id);
        }

        function guardarEjercicio() {
            const formData = new FormData(document.getElementById('formEjercicio'));
            const esEdicion = document.getElementById('ejercicioId').value !== '';
            
            formData.append('action', esEdicion ? 'editar_ejercicio' : 'agregar_ejercicio');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(esEdicion ? 'Ejercicio actualizado correctamente' : 'Ejercicio agregado correctamente');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar el ejercicio'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el ejercicio');
            });
        }

        function guardarRutina() {
            if (ejerciciosSeleccionados.length === 0) {
                alert('Debes seleccionar al menos un ejercicio para la rutina');
                return;
            }

            const formData = new FormData(document.getElementById('formRutina'));
            const esEdicion = document.getElementById('rutinaId').value !== '';
            
            formData.append('action', esEdicion ? 'editar_rutina' : 'crear_rutina');
            
            // Agregar ejercicios seleccionados
            ejerciciosSeleccionados.forEach(id => {
                formData.append('ejercicios[]', id);
            });

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(esEdicion ? 'Rutina actualizada correctamente' : 'Rutina creada correctamente');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar la rutina'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar la rutina');
            });
        }

        function editarEjercicio(id) {
            // Aquí deberías hacer una petición para obtener los datos del ejercicio
            // y llenar el formulario modal
            document.getElementById('modalEjercicioLabel').textContent = 'Editar Ejercicio';
            document.getElementById('ejercicioId').value = id;
            
            // Mostrar modal
            new bootstrap.Modal(document.getElementById('modalEjercicio')).show();
        }

        function eliminarEjercicio(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este ejercicio?')) {
                const formData = new FormData();
                formData.append('action', 'eliminar_ejercicio');
                formData.append('id', id);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Ejercicio eliminado correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo eliminar el ejercicio'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el ejercicio');
                });
            }
        }

        function editarRutina(id) {
            // Aquí deberías hacer una petición para obtener los datos de la rutina
            // y llenar el formulario modal
            document.getElementById('modalRutinaLabel').textContent = 'Editar Rutina';
            document.getElementById('rutinaId').value = id;
            
            // Mostrar modal
            new bootstrap.Modal(document.getElementById('modalRutina')).show();
        }

        function verRutina(id) {
    // Mostrar el modal con mensaje de carga
    const modal = new bootstrap.Modal(document.getElementById('modalVerRutina'));
    const contenido = document.getElementById('contenidoVerRutina');
    
    contenido.innerHTML = `
        <div class="text-center my-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles de la rutina...</p>
        </div>
    `;
    modal.show();

    // Realizar petición AJAX para obtener los detalles de la rutina
    fetch(`../config/ver_rutina_con_ejersicios.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al obtener los datos de la rutina');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Formatear los ejercicios como lista
                let ejerciciosHTML = '';
                if (data.ejercicios && data.ejercicios.length > 0) {
                    ejerciciosHTML = '<ol class="list-group list-group-numbered">';
                    data.ejercicios.forEach(ejercicio => {
                        ejerciciosHTML += `
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">${ejercicio.nombre}</div>
                                        ${ejercicio.series} series × ${ejercicio.repeticiones} repeticiones
                                        <div class="text-muted small mt-1">Descanso: ${ejercicio.descanso}</div>
                                        ${ejercicio.instrucciones ? `<div class="mt-2">${ejercicio.instrucciones}</div>` : ''}
                                    </div>
                                    ${ejercicio.imagen_url ? `<img src="${ejercicio.imagen_url}" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">` : ''}
                                </div>
                            </li>
                        `;
                    });
                    ejerciciosHTML += '</ol>';
                } else {
                    ejerciciosHTML = '<div class="alert alert-warning">No hay ejercicios definidos para esta rutina.</div>';
                }

                // Mostrar toda la información en el modal
                contenido.innerHTML = `
                    <div class="modal-header">
                        <h5 class="modal-title">${data.titulo}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <p class="mb-1"><strong>Creada por:</strong> ${data.entrenador_nombre} ${data.entrenador_apellido}</p>
                            <p class="mb-1"><strong>Categoría:</strong> ${data.categoria}</p>
                            <p class="mb-1"><strong>Objetivo:</strong> ${data.objetivo.replace('_', ' ')}</p>
                            <p class="mb-1"><strong>Duración:</strong> ${data.duracion_minutos} minutos</p>
                        </div>
                        
                        ${data.descripcion ? `<div class="alert alert-light mb-3">${data.descripcion}</div>` : ''}
                        
                        <h6 class="mb-3">Ejercicios:</h6>
                        ${ejerciciosHTML}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                `;
            } else {
                contenido.innerHTML = `
                    <div class="alert alert-danger">
                        ${data.message || 'Error al cargar los detalles de la rutina'}
                    </div>
                    <div class="text-center mb-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contenido.innerHTML = `
                <div class="alert alert-danger">
                    Error al cargar los detalles de la rutina: ${error.message}
                </div>
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            `;
        });
}

        function eliminarRutina(id) {
            if (confirm('¿Estás seguro de que quieres eliminar esta rutina?')) {
                const formData = new FormData();
                formData.append('action', 'eliminar_rutina');
                formData.append('id', id);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Rutina eliminada correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo eliminar la rutina'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la rutina');
                });
            }
        }

        // Limpiar formularios al cerrar modales
        document.getElementById('modalEjercicio').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formEjercicio').reset();
            document.getElementById('ejercicioId').value = '';
            document.getElementById('modalEjercicioLabel').textContent = 'Agregar Ejercicio';
        });

        document.getElementById('modalRutina').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formRutina').reset();
            document.getElementById('rutinaId').value = '';
            document.getElementById('modalRutinaLabel').textContent = 'Agregar Rutina';
            ejerciciosSeleccionados = [];
            document.querySelectorAll('#listaEjerciciosDisponibles input[type="checkbox"]').forEach(cb => cb.checked = false);
            actualizarListaSeleccionados();
        });