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

// Función para obtener datos de suscripción del usuario (nueva función)
function gym_get_user_subscription($user_id) {
    $db = new Database();
    $db->query("SELECT s.*, p.modalidad_pago 
                FROM suscripciones s 
                LEFT JOIN pagos p ON s.id_pago = p.id 
                WHERE s.id_usuario = :user_id 
                ORDER BY s.fecha_inicio DESC LIMIT 1");
    $db->bind(':user_id', $user_id);
    return $db->single();
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

// Función para verificar estado de suscripción (actualizada con nueva estructura)
function gym_check_subscription($user_id = null) {
    if (!gym_is_logged_in()) return false;
    
    if ($_SESSION['user_tipo'] !== 'cliente') return true;
    
    $user_id = $user_id ?? $_SESSION['user_id'];
    $subscription = gym_get_user_subscription($user_id);
    
    if (!$subscription) {
        return false;
    }
    
    $today = new DateTime();
    $end_date = new DateTime($subscription['fecha_fin']);
    
    return ($end_date >= $today && $subscription['estado'] === 'activa');
}

// Función para obtener días restantes de suscripción (actualizada)
function gym_get_remaining_days($user_id = null) {
    if (!gym_is_logged_in()) {
        return 0;
    }
    
    $user_id = $user_id ?? $_SESSION['user_id'];
    $subscription = gym_get_user_subscription($user_id);
    
    if (!$subscription || empty($subscription['fecha_fin'])) {
        return 0;
    }
    
    $today = new DateTime();
    $end_date = new DateTime($subscription['fecha_fin']);
    
    if ($end_date < $today) {
        return 0;
    }
    
    return $today->diff($end_date)->days;
}

// Función para formatear tipo de suscripción (actualizada)
function gym_format_subscription_type($type) {
    $types = [
        'semanal' => 'Semanal',
        'mensual' => 'Mensual',
        'trimestral' => 'Trimestral',
        'semestral' => 'Semestral',
        'anual' => 'Anual'
    ];
    
    return $types[$type] ?? $type;
}

// Función para obtener estado de suscripción formateado (nueva función)
function gym_format_subscription_status($status) {
    $statuses = [
        'activa' => 'Activa',
        'vencida' => 'Vencida',
        'cancelada' => 'Cancelada',
        'suspendida' => 'Suspendida'
    ];
    
    return $statuses[$status] ?? $status;
}

// Función para obtener precio de suscripción (nueva función)
function gym_get_subscription_price($type) {
    $db = new Database();
    $db->query("SELECT precio FROM precios_suscripciones 
                WHERE tipo_suscripcion = :type 
                AND activo = TRUE 
                AND (fecha_fin_vigencia IS NULL OR fecha_fin_vigencia >= CURDATE())
                ORDER BY fecha_inicio_vigencia DESC LIMIT 1");
    $db->bind(':type', $type);
    $result = $db->single();
    return $result ? $result['precio'] : 0;
}