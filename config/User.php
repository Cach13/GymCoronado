<?php
require_once __DIR__ . '/config.php';

class User {
    private $db;
    private $error;

    public function __construct() {
        $this->db = new Database();
    }

    // Registrar nuevo usuario
    public function register($data) {
        $this->db->query('SELECT id FROM usuarios WHERE email = :email');
        $this->db->bind(':email', $data['email']);
        
        if ($this->db->single()) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }

        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

        $this->db->query('INSERT INTO usuarios (email, password, nombre, apellido, telefono, fecha_nacimiento, genero, objetivo, tipo) 
                         VALUES (:email, :password, :nombre, :apellido, :telefono, :fecha_nacimiento, :genero, :objetivo, :tipo)');
        
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $hashed_password);
        $this->db->bind(':nombre', $data['nombre']);
        $this->db->bind(':apellido', $data['apellido']);
        $this->db->bind(':telefono', $data['telefono'] ?? null);
        $this->db->bind(':fecha_nacimiento', $data['fecha_nacimiento'] ?? null);
        $this->db->bind(':genero', $data['genero'] ?? null);
        $this->db->bind(':objetivo', $data['objetivo'] ?? 'mantener');
        $this->db->bind(':tipo', $data['tipo'] ?? 'cliente');

        if ($this->db->execute()) {
            $user_id = $this->db->lastInsertId();
            $this->create_default_nutrition_goals($user_id);
            return ['success' => true, 'message' => 'Usuario registrado exitosamente', 'user_id' => $user_id];
        } else {
            return ['success' => false, 'message' => 'Error al registrar usuario'];
        }
    }
    

    // Iniciar sesión
    public function login($email, $password) {
        $this->db->query("SELECT * FROM usuarios WHERE email = :email AND activo = 1 AND puede_acceder = 1");
        $this->db->bind(':email', $email);
        $user = $this->db->single();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_apellido'] = $user['apellido'];
                $_SESSION['user_tipo'] = $user['tipo'];
                $_SESSION['user_objetivo'] = $user['objetivo'];
                $_SESSION['user_puede_acceder'] = $user['puede_acceder'];
                
                if (isset($user['tipo_suscripcion'])) {
                    $_SESSION['user_tipo_suscripcion'] = $user['tipo_suscripcion'];
                    $_SESSION['user_fecha_fin_suscripcion'] = $user['fecha_fin_suscripcion'];
                    $_SESSION['user_estado_suscripcion'] = $user['estado_suscripcion'];
                    $_SESSION['user_modalidad_pago'] = $user['modalidad_pago'];
                }

                return ['success' => true, 'message' => 'Inicio de sesión exitoso'];
            } else {
                return ['success' => false, 'message' => 'Contraseña incorrecta'];
            }
        } else {
            $this->db->query("SELECT * FROM usuarios WHERE email = :email");
            $this->db->bind(':email', $email);
            $userExists = $this->db->single();
            
            if ($userExists) {
                if ($userExists['activo'] == 0) {
                    return ['success' => false, 'message' => 'Tu cuenta está desactivada'];
                } elseif ($userExists['puede_acceder'] == 0) {
                    return ['success' => false, 'message' => 'Acceso temporalmente suspendido'];
                }
            }
            
            return ['success' => false, 'message' => 'Credenciales incorrectas o usuario inactivo'];
        }
    }

    // Obtener usuario por ID
    public function get_user_by_id($id) {
        $this->db->query('SELECT * FROM usuarios WHERE id = :id AND activo = 1');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Obtener usuario por email
    public function get_user_by_email($email) {
        $this->db->query('SELECT * FROM usuarios WHERE email = :email AND activo = 1');
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    // Actualizar perfil de usuario
    public function update_profile($user_id, $data) {
        $this->db->query('UPDATE usuarios SET 
                         nombre = :nombre, 
                         apellido = :apellido, 
                         telefono = :telefono, 
                         fecha_nacimiento = :fecha_nacimiento, 
                         genero = :genero, 
                         objetivo = :objetivo 
                         WHERE id = :id');
        
        $this->db->bind(':id', $user_id);
        $this->db->bind(':nombre', $data['nombre']);
        $this->db->bind(':apellido', $data['apellido']);
        $this->db->bind(':telefono', $data['telefono'] ?? null);
        $this->db->bind(':fecha_nacimiento', $data['fecha_nacimiento'] ?? null);
        $this->db->bind(':genero', $data['genero'] ?? null);
        $this->db->bind(':objetivo', $data['objetivo'] ?? 'mantener');

        if ($this->db->execute()) {
            $_SESSION['user_nombre'] = $data['nombre'];
            $_SESSION['user_apellido'] = $data['apellido'];
            $_SESSION['user_objetivo'] = $data['objetivo'] ?? 'mantener';
            
            return ['success' => true, 'message' => 'Perfil actualizado exitosamente'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar perfil'];
        }
    }

    // Cambiar contraseña
    public function change_password($user_id, $current_password, $new_password) {
        $this->db->query('SELECT password FROM usuarios WHERE id = :id');
        $this->db->bind(':id', $user_id);
        $user = $this->db->single();

        if (!password_verify($current_password, $user['password'])) {
            return ['success' => false, 'message' => 'La contraseña actual es incorrecta'];
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $this->db->query('UPDATE usuarios SET password = :password WHERE id = :id');
        $this->db->bind(':password', $hashed_password);
        $this->db->bind(':id', $user_id);

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Contraseña cambiada exitosamente'];
        } else {
            return ['success' => false, 'message' => 'Error al cambiar contraseña'];
        }
    }

    // Obtener todos los usuarios (para admin)
    public function get_all_users($tipo = null, $limit = null, $offset = 0) {
        $query = 'SELECT id, email, nombre, apellido, telefono, tipo, activo, fecha_registro FROM usuarios';
        
        if ($tipo) {
            $query .= ' WHERE tipo = :tipo';
        }
        
        $query .= ' ORDER BY fecha_registro DESC';
        
        if ($limit) {
            $query .= ' LIMIT :limit OFFSET :offset';
        }

        $this->db->query($query);
        
        if ($tipo) {
            $this->db->bind(':tipo', $tipo);
        }
        
        if ($limit) {
            $this->db->bind(':limit', $limit);
            $this->db->bind(':offset', $offset);
        }

        return $this->db->resultset();
    }

    // Activar/desactivar usuario
    public function toggle_user_status($user_id) {
        $this->db->query('UPDATE usuarios SET activo = NOT activo WHERE id = :id');
        $this->db->bind(':id', $user_id);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Estado del usuario actualizado'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar estado'];
        }
    }

    // Crear objetivos nutricionales por defecto
    private function create_default_nutrition_goals($user_id) {
        $this->db->query('INSERT INTO objetivos_nutricionales 
                         (id_usuario, calorias_objetivo, proteinas_objetivo, carbohidratos_objetivo, grasas_objetivo, fecha_inicio) 
                         VALUES (:user_id, 2000, 120, 250, 67, CURDATE())');
        
        $this->db->bind(':user_id', $user_id);
        $this->db->execute();
    }

    // Actualizar último login
    private function update_last_login($user_id) {
        // Opcional: Implementar si se necesita tracking de logins
    }

    // Obtener estadísticas del usuario
    public function get_user_stats($user_id) {
        $this->db->query('SELECT COUNT(DISTINCT fecha) as dias_asistencia FROM rachas WHERE id_usuario = :user_id AND activa = 1');
        $this->db->bind(':user_id', $user_id);
        $asistencia = $this->db->single();

        $this->db->query('SELECT MAX(fecha) as ultima_asistencia FROM rachas WHERE id_usuario = :user_id');
        $this->db->bind(':user_id', $user_id);
        $ultima = $this->db->single();

        $rutinas_completadas = 0;

        return [
            'dias_asistencia' => $asistencia['dias_asistencia'] ?? 0,
            'ultima_asistencia' => $ultima['ultima_asistencia'],
            'rutinas_completadas' => $rutinas_completadas
        ];
    }

    // Validar datos de registro
    public function validate_registration($data) {
        $errors = [];

        if (empty($data['email']) || !gym_validate_email($data['email'])) {
            $errors[] = 'Email inválido';
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        }

        if (empty($data['nombre']) || strlen($data['nombre']) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        }

        if (empty($data['apellido']) || strlen($data['apellido']) < 2) {
            $errors[] = 'El apellido debe tener al menos 2 caracteres';
        }

        return $errors;
    }

    // Ejercicios preestablecidos
    public function agregar_ejercicio_preestablecido($data) {
        if (!isset($_SESSION['user_tipo']) || ($_SESSION['user_tipo'] !== 'admin' && $_SESSION['user_tipo'] !== 'entrenador')) {
            return ['success' => false, 'message' => 'No tienes permisos para realizar esta acción'];
        }

        $required = ['nombre', 'grupo_muscular', 'series', 'repeticiones', 'tiempo_descanso'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "El campo $field es requerido"];
            }
        }

        $this->db->query('INSERT INTO ejercicios_preestablecidos 
                         (nombre, grupo_muscular, series, repeticiones, tiempo_descanso, instrucciones, imagen_url, dificultad, equipamiento_necesario, creado_por) 
                         VALUES (:nombre, :grupo_muscular, :series, :repeticiones, :tiempo_descanso, :instrucciones, :imagen_url, :dificultad, :equipamiento, :creado_por)');
        
        $this->db->bind(':nombre', $data['nombre']);
        $this->db->bind(':grupo_muscular', $data['grupo_muscular']);
        $this->db->bind(':series', $data['series']);
        $this->db->bind(':repeticiones', $data['repeticiones']);
        $this->db->bind(':tiempo_descanso', $data['tiempo_descanso']);
        $this->db->bind(':instrucciones', $data['instrucciones'] ?? null);
        $this->db->bind(':imagen_url', $data['imagen_url'] ?? null);
        $this->db->bind(':dificultad', $data['dificultad'] ?? 'intermedio');
        $this->db->bind(':equipamiento', $data['equipamiento_necesario'] ?? null);
        $this->db->bind(':creado_por', $_SESSION['user_id']);

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Ejercicio agregado correctamente', 'ejercicio_id' => $this->db->lastInsertId()];
        } else {
            return ['success' => false, 'message' => 'Error al agregar el ejercicio'];
        }
    }

    public function obtener_ejercicios_preestablecidos($grupo_muscular = null) {
        $query = 'SELECT * FROM ejercicios_preestablecidos WHERE 1=1';
        
        if ($grupo_muscular) {
            $query .= ' AND grupo_muscular = :grupo_muscular';
        }
        
        $query .= ' ORDER BY nombre ASC';
        
        $this->db->query($query);
        
        if ($grupo_muscular) {
            $this->db->bind(':grupo_muscular', $grupo_muscular);
        }
        
        return $this->db->resultset();
    }

    public function editar_ejercicio_preestablecido($id, $data) {
        if (!isset($_SESSION['user_tipo']) || ($_SESSION['user_tipo'] !== 'admin' && $_SESSION['user_tipo'] !== 'entrenador')) {
            return ['success' => false, 'message' => 'No tienes permisos para realizar esta acción'];
        }

        $this->db->query('UPDATE ejercicios_preestablecidos SET 
                         nombre = :nombre, 
                         grupo_muscular = :grupo_muscular, 
                         series = :series, 
                         repeticiones = :repeticiones, 
                         tiempo_descanso = :tiempo_descanso, 
                         instrucciones = :instrucciones, 
                         imagen_url = :imagen_url, 
                         dificultad = :dificultad, 
                         equipamiento_necesario = :equipamiento
                         WHERE id = :id');
        
        $this->db->bind(':id', $id);
        $this->db->bind(':nombre', $data['nombre']);
        $this->db->bind(':grupo_muscular', $data['grupo_muscular']);
        $this->db->bind(':series', $data['series']);
        $this->db->bind(':repeticiones', $data['repeticiones']);
        $this->db->bind(':tiempo_descanso', $data['tiempo_descanso']);
        $this->db->bind(':instrucciones', $data['instrucciones']);
        $this->db->bind(':imagen_url', $data['imagen_url']);
        $this->db->bind(':dificultad', $data['dificultad']);
        $this->db->bind(':equipamiento', $data['equipamiento_necesario']);

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Ejercicio actualizado correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar el ejercicio'];
        }
    }

    public function eliminar_ejercicio_preestablecido($id) {
        if (!isset($_SESSION['user_tipo']) || ($_SESSION['user_tipo'] !== 'admin' && $_SESSION['user_tipo'] !== 'entrenador')) {
            return ['success' => false, 'message' => 'No tienes permisos para realizar esta acción'];
        }

        $this->db->query('DELETE FROM ejercicios_preestablecidos WHERE id = :id');
        $this->db->bind(':id', $id);

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Ejercicio eliminado correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al eliminar el ejercicio'];
        }
    }

    // Rutinas preestablecidas
    public function crear_rutina_preestablecida($data, $ejercicios_ids) {
        if (!isset($_SESSION['user_tipo']) || ($_SESSION['user_tipo'] !== 'admin' && $_SESSION['user_tipo'] !== 'entrenador')) {
            return ['success' => false, 'message' => 'No tienes permisos para realizar esta acción'];
        }

        $required = ['titulo', 'categoria', 'objetivo'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "El campo $field es requerido"];
            }
        }

        if (empty($ejercicios_ids)) {
            return ['success' => false, 'message' => 'Debes seleccionar al menos un ejercicio'];
        }

        $placeholders = implode(',', array_fill(0, count($ejercicios_ids), '?'));
        $this->db->query("SELECT * FROM ejercicios_preestablecidos WHERE id IN ($placeholders)");
        
        for ($i = 0; $i < count($ejercicios_ids); $i++) {
            $this->db->bind($i + 1, $ejercicios_ids[$i]);
        }
        
        $ejercicios = $this->db->resultset();
        
        $ejercicios_array = [];
        foreach ($ejercicios as $ejercicio) {
            $ejercicios_array[] = [
                'id' => $ejercicio['id'],
                'nombre' => $ejercicio['nombre'],
                'series' => $ejercicio['series'],
                'repeticiones' => $ejercicio['repeticiones'],
                'descanso' => $ejercicio['tiempo_descanso'],
                'instrucciones' => $ejercicio['instrucciones']
            ];
        }

        $this->db->query('INSERT INTO rutinas 
                        (id_entrenador, titulo, descripcion, categoria, objetivo, duracion_minutos, ejercicios) 
                        VALUES (:entrenador_id, :titulo, :descripcion, :categoria, :objetivo, :duracion, :ejercicios)');
        
        $this->db->bind(':entrenador_id', $_SESSION['user_id']);
        $this->db->bind(':titulo', $data['titulo']);
        $this->db->bind(':descripcion', $data['descripcion'] ?? '');
        $this->db->bind(':categoria', $data['categoria']);
        $this->db->bind(':objetivo', $data['objetivo']);
        $this->db->bind(':duracion', $data['duracion_minutos'] ?? 60);
        $this->db->bind(':ejercicios', json_encode($ejercicios_array));

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Rutina creada correctamente', 'rutina_id' => $this->db->lastInsertId()];
        } else {
            return ['success' => false, 'message' => 'Error al crear la rutina'];
        }
    }

    public function obtener_rutinas_preestablecidas() {
        $this->db->query('SELECT * FROM rutinas ORDER BY titulo ASC');
        return $this->db->resultset();
    }

    public function editar_rutina_preestablecida($id, $data, $ejercicios_ids) {
        if (!isset($_SESSION['user_tipo']) || ($_SESSION['user_tipo'] !== 'admin' && $_SESSION['user_tipo'] !== 'entrenador')) {
            return ['success' => false, 'message' => 'No tienes permisos para realizar esta acción'];
        }

        $placeholders = implode(',', array_fill(0, count($ejercicios_ids), '?'));
        $this->db->query("SELECT * FROM ejercicios_preestablecidos WHERE id IN ($placeholders)");
        
        for ($i = 0; $i < count($ejercicios_ids); $i++) {
            $this->db->bind($i + 1, $ejercicios_ids[$i]);
        }
        
        $ejercicios = $this->db->resultset();
        
        $ejercicios_array = [];
        foreach ($ejercicios as $ejercicio) {
            $ejercicios_array[] = [
                'id' => $ejercicio['id'],
                'nombre' => $ejercicio['nombre'],
                'series' => $ejercicio['series'],
                'repeticiones' => $ejercicio['repeticiones'],
                'descanso' => $ejercicio['tiempo_descanso'],
                'instrucciones' => $ejercicio['instrucciones']
            ];
        }

        $this->db->query('UPDATE rutinas SET 
                        titulo = :titulo, 
                        descripcion = :descripcion, 
                        categoria = :categoria, 
                        objetivo = :objetivo, 
                        duracion_minutos = :duracion, 
                        ejercicios = :ejercicios 
                        WHERE id = :id');
        
        $this->db->bind(':id', $id);
        $this->db->bind(':titulo', $data['titulo']);
        $this->db->bind(':descripcion', $data['descripcion']);
        $this->db->bind(':categoria', $data['categoria']);
        $this->db->bind(':objetivo', $data['objetivo']);
        $this->db->bind(':duracion', $data['duracion_minutos']);
        $this->db->bind(':ejercicios', json_encode($ejercicios_array));

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Rutina actualizada correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar la rutina'];
        }
    }

    public function eliminar_rutina_preestablecida($id) {
        if (!isset($_SESSION['user_tipo']) || ($_SESSION['user_tipo'] !== 'admin' && $_SESSION['user_tipo'] !== 'entrenador')) {
            return ['success' => false, 'message' => 'No tienes permisos para realizar esta acción'];
        }

        $this->db->query('DELETE FROM rutinas WHERE id = :id');
        $this->db->bind(':id', $id);

        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Rutina eliminada correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al eliminar la rutina'];
        }
    }

    
}
?>