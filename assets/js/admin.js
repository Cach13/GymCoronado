// Función genérica para cerrar modales
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            // Resetear formularios si existen
            const form = modal.querySelector('form');
            if (form) form.reset();
        }, 300);
    }
}

// Abrir modal para crear usuario
function openCreateUserModal() {
    const modal = document.getElementById('createUserModal');
    modal.style.display = 'block';
    setTimeout(() => {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }, 10);
}

// Event listeners para cerrar modales
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modal al hacer clic fuera
    document.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal && modal.style.display === 'block') {
                const modalId = modal.getAttribute('id');
                closeModal(modalId);
            }
        });
    });

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    const modalId = modal.getAttribute('id');
                    closeModal(modalId);
                }
            });
        }
    });

    // Manejar formulario de crear usuario
    const createUserForm = document.getElementById('createUserForm');
    if (createUserForm) {
        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.classList.add('btn-loading');
            submitBtn.innerHTML = 'Creando...';

            const formData = new FormData(this);
            formData.append('action', 'create_user');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Usuario creado exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(() => {
                alert('Error al crear usuario');
            })
            .finally(() => {
                submitBtn.classList.remove('btn-loading');
                submitBtn.innerHTML = originalText;
            });
        });
    }
});

// Registro rápido de asistencia
function addQuickAttendance(userId, button) {
    if (!confirm('¿Registrar asistencia para hoy?')) return;

    const originalHTML = button.innerHTML;
    button.classList.add('btn-loading');
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;

    const formData = new FormData();
    formData.append('action', 'add_quick_attendance');
    formData.append('user_id', userId);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
            button.classList.remove('btn-loading');
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
    })
    .catch(() => {
        alert('Error al registrar asistencia');
        button.classList.remove('btn-loading');
        button.innerHTML = originalHTML;
        button.disabled = false;
    });
}

// Cambiar estado activo del usuario
function toggleUserStatus(userId) {
    if (!confirm('¿Estás seguro de cambiar el estado de este usuario?\n\nAl desactivarlo, no podrá acceder al sistema.')) return;

    const formData = new FormData();
    formData.append('action', 'toggle_user_status');
    formData.append('user_id', userId);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Error al cambiar estado'));
}

// Cambiar permiso de acceso del usuario
function toggleAccessStatus(userId) {
    if (!confirm('¿Cambiar permisos de acceso de este usuario?')) return;

    const formData = new FormData();
    formData.append('action', 'toggle_access_status');
    formData.append('user_id', userId);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Error al cambiar acceso'));
}

// Eliminar usuario con confirmación y feedback
function deleteUser(userId) {
    const confirmation = confirm(
        '🚨 ELIMINACIÓN PERMANENTE 🚨\n\n' +
        'Estás a punto de eliminar este usuario y TODOS sus datos asociados.\n\n' +
        '¿Continuar con la eliminación?'
    );
    if (!confirmation) return;

    // Buscar el botón de eliminación y mostrar loading
    const deleteButton = document.querySelector(`button[onclick="deleteUser(${userId})"]`);
    if (deleteButton) {
        deleteButton.classList.add('btn-loading');
        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        deleteButton.disabled = true;
    }

    const formData = new FormData();
    formData.append('action', 'delete_user');
    formData.append('user_id', userId);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => {
        if (!res.ok) throw new Error('Error en la red');
        return res.json();
    })
    .then(data => {
        if (data.success) {
            alert('Usuario eliminado: ' + data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        if (deleteButton) {
            deleteButton.classList.remove('btn-loading');
            deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
            deleteButton.disabled = false;
        }
        alert('Error al eliminar usuario: ' + error.message);
    });
}

// Filtrar usuarios por tipo
function filterUsers(tipo) {
    window.location.href = `?tipo=${tipo}&page=1`;
}