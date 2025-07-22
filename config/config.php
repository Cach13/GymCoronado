<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Cambia por tu usuario de MySQL
define('DB_PASS', '');     // Cambia por tu contraseña de MySQL
define('DB_NAME', 'gym'); // Base de datos actualizada

// Configuración de la aplicación
define('SITE_URL', 'http://localhost/gym/');
define('SITE_NAME', 'Coronado Gym');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS

// Crear conexión PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clase para manejar la conexión a la base de datos
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $dbh;
    private $error;
    private $stmt;

    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8';
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            die('Error de conexión: ' . $this->error);
        }
    }

    public function query($query) {
        $this->stmt = $this->dbh->prepare($query);
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):  $type = PDO::PARAM_INT; break;
                case is_bool($value): $type = PDO::PARAM_BOOL; break;
                case is_null($value): $type = PDO::PARAM_NULL; break;
                default:              $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        return $this->stmt->execute();
    }

    public function resultset() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    public function endTransaction() {
        return $this->dbh->commit();
    }

    public function cancelTransaction() {
        return $this->dbh->rollBack();
    }
}

// Función para sanitizar datos
function gym_sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Función para validar email
function gym_validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para verificar si el usuario está logueado
function gym_is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para obtener datos del usuario actual (actualizada con nueva estructura)
function gym_get_logged_in_user() {
    if (gym_is_logged_in()) {
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'nombre' => $_SESSION['user_nombre'],
            'apellido' => $_SESSION['user_apellido'],
            'telefono' => $_SESSION['user_telefono'] ?? null,
            'tipo' => $_SESSION['user_tipo'],
            'objetivo' => $_SESSION['user_objetivo'] ?? 'mantener',
            'activo' => $_SESSION['user_activo'] ?? true,
            'puede_acceder' => $_SESSION['user_puede_acceder'] ?? true,
            'fecha_nacimiento' => $_SESSION['user_fecha_nacimiento'] ?? null,
            'genero' => $_SESSION['user_genero'] ?? null
        ];
    }
    return null;
}


// Función para cerrar sesión
function gym_logout() {
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
}

// Función para redireccionar
function gym_redirect($url) {
    header("Location: " . $url);
    exit();
}

// Función para mostrar alertas
function gym_show_alert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Función para obtener y limpiar alertas
function gym_get_alert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Función para verificar permisos (actualizada con jerarquía de roles)
function gym_check_permission($required_role) {
    if (!gym_is_logged_in()) {
        gym_redirect('login.php');
    }

    $user_role = $_SESSION['user_tipo'];
    $roles_hierarchy = ['cliente' => 1, 'entrenador' => 2, 'admin' => 3];

    if (!isset($roles_hierarchy[$user_role])) {
        gym_show_alert('Tu rol de usuario no está configurado correctamente', 'error');
        gym_logout();
        gym_redirect('login.php');
    }

    if ($roles_hierarchy[$user_role] < $roles_hierarchy[$required_role]) {
        gym_show_alert('No tienes permisos para acceder a esta página', 'error');
        gym_redirect('dashboard.php');
    }
}
