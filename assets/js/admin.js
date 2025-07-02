 // Funciones mejoradas del modal
        function openCreateUserModal() {
            const modal = document.getElementById('createUserModal');
            modal.style.display = 'block';
            // Agregamos la clase 'active' después de un pequeño delay para la animación
            setTimeout(() => {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Bloquear scroll del fondo
            }, 10);
        }

        function closeCreateUserModal() {
            const modal = document.getElementById('createUserModal');
            modal.classList.remove('active');
            // Esperamos a que termine la animación para ocultar
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Restaurar scroll del fondo
                document.getElementById('createUserForm').reset();
            }, 300); // Debe coincidir con la duración de la transición CSS
        }

        // Cerrar modal al hacer clic fuera (versión mejorada)
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('createUserModal');
            if (event.target === modal) {
                closeCreateUserModal();
            }
        });

        // Cerrar modal con Escape key
        document.addEventListener('keydown', function(event) {
            const modal = document.getElementById('createUserModal');
            if (event.key === 'Escape' && modal.style.display === 'block') {
                closeCreateUserModal();
            }
        });

        // Crear usuario
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_user');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Usuario creado exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear usuario');
            });
        });

        // Cambiar estado de usuario
        function toggleUserStatus(userId) {
            if (confirm('¿Estás seguro de cambiar el estado de este usuario?')) {
                const formData = new FormData();
                formData.append('action', 'toggle_user_status');
                formData.append('user_id', userId);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar estado');
                });
            }
        }

        // Eliminar usuario
        function deleteUser(userId) {
            if (confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar usuario');
                });
            }
        }

        // Filtrar usuarios
        function filterUsers(tipo) {
            window.location.href = `?tipo=${tipo}&page=1`;
        }

        // Auto-refresh de métricas cada 30 segundos
        setInterval(function() {
            // Aquí podrías hacer una llamada AJAX para actualizar solo las métricas
            // sin recargar toda la página
        }, 30000);
        // Cambiar estado de usuario
function toggleUserStatus(userId) {
    if (confirm('¿Estás seguro de cambiar el estado de este usuario?\n\nAl desactivarlo, no podrá acceder al sistema.')) {
        const formData = new FormData();
        formData.append('action', 'toggle_user_status');
        formData.append('user_id', userId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cambiar estado');
        });
    }
}

    // Eliminar usuario
    function deleteUser(userId) {
        // Mostrar modal de confirmación más elegante
        const confirmation = confirm(
            '🚨 ELIMINACIÓN PERMANENTE 🚨\n\n' +
            'Estás a punto de eliminar este usuario y TODOS sus datos:\n' +
            '- Historial de pagos\n' +
            '- Medidas corporales\n' +
            '- Registros de asistencia\n' +
            '- Dietas y objetivos\n\n' +
            '¿Continuar con la eliminación?'
        );
        
        if (!confirmation) return;

        // Mostrar feedback visual
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        if (row) row.style.opacity = '0.5';
        
        const spinner = document.createElement('div');
        spinner.className = 'delete-spinner';
        if (row) row.querySelector('td:last-child').appendChild(spinner);

        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_user&user_id=${userId}`
        })
        .then(response => {
            if (!response.ok) throw new Error('Error en la red');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Mostrar notificación elegante
                showAlert('success', 'Usuario eliminado', 'El usuario y todos sus datos han sido eliminados');
                // Recargar después de 1.5 segundos
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            if (row) row.style.opacity = '1';
            spinner.remove();
            showAlert('error', 'Error', error.message);
            console.error('Error al eliminar:', error);
        });
    }

    // Función auxiliar para mostrar alertas (debes implementarla)
    function showAlert(type, title, message) {
        // Implementa tu propio sistema de alertas o usa un toast library
        alert(`${title}: ${message}`);

    }