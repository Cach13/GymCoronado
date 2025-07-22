// Abrir modal para crear usuario
function openCreateUserModal() {
    const modal = document.getElementById('createUserModal');
    modal.style.display = 'block';
    setTimeout(() => {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // bloquear scroll fondo
    }, 10);
}

// Cerrar modal crear usuario
function closeCreateUserModal() {
    const modal = document.getElementById('createUserModal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = ''; // restaurar scroll
        document.getElementById('createUserForm').reset();
    }, 300);
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    const modal = document.getElementById('createUserModal');
    if (event.target === modal) {
        closeCreateUserModal();
    }
});

// Cerrar modal con Escape
document.addEventListener('keydown', function(event) {
    const modal = document.getElementById('createUserModal');
    if (event.key === 'Escape' && modal.style.display === 'block') {
        closeCreateUserModal();
    }
});

// Crear usuario - envío formulario
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    e.preventDefault();

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
    .catch(() => alert('Error al crear usuario'));
});

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

    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (row) row.style.opacity = '0.5';

    const spinner = document.createElement('div');
    spinner.className = 'delete-spinner';
    if (row) row.querySelector('td:last-child').appendChild(spinner);

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete_user&user_id=${userId}`
    })
    .then(res => {
        if (!res.ok) throw new Error('Error en la red');
        return res.json();
    })
    .then(data => {
        if (data.success) {
            alert('Usuario eliminado: ' + data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        if (row) row.style.opacity = '1';
        spinner.remove();
        alert('Error al eliminar usuario: ' + error.message);
    });
}

// Filtrar usuarios por tipo
function filterUsers(tipo) {
    window.location.href = `?tipo=${tipo}&page=1`;
}

// Nota: Si quieres puedes implementar una función showAlert para mejores notificaciones.
