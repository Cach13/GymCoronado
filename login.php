<?php
require_once 'config/config.php';
require_once 'user/User.php';

// Si ya está logueado, redirigir al dashboard
if (gym_is_logged_in()) {
    gym_redirect('dashboard.php');
}

$error = '';
$success = '';

// Procesar el formulario de login
if ($_POST) {
    $email = gym_sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } else {
        $user = new User();
        $result = $user->login($email, $password);
        
        if ($result['success']) {
            $success = $result['message'];
            // Redirigir después de un login exitoso
            header("refresh:1;url= dashboard.php");
        } else {
            $error = $result['message'];
        }
    }
}

// Obtener alertas si las hay
$alert = gym_get_alert();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg,rgb(111, 125, 184) 0%,rgb(61, 78, 153) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background: rgb(198, 201, 205);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-form {
            padding: 3rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h2 {
            color: #333;
            font-weight: 600;
        }
        .login-header p {
            color: #666;
            margin-bottom: 0;
        }
        .form-floating label {
            color: #666;
        }
        .btn-login {
            background: linear-gradient(135deg,rgb(15, 46, 185) 0%,rgb(33, 60, 179) 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .gym-icon {
            font-size: 3rem;
            background: linear-gradient(135deg,rgb(15, 46, 185) 0%,rgb(33, 60, 179) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
        }
        .divider span {
            background: white;
            padding: 0 1rem;
            color: #666;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <div class="login-form">
                        <div class="login-header">
                            <i class="fas fa-dumbbell gym-icon"></i>
                            <h2><?php echo SITE_NAME; ?></h2>
                            <p>Inicia sesión en tu cuenta</p>
                        </div>

                        <!-- Mostrar alertas -->
                        <?php if ($alert): ?>
                            <div class="alert alert-<?php echo $alert['type'] == 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show" role="alert">
                                <?php echo $alert['message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <div class="mt-2">
                                    <small>Redirigiendo al dashboard...</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                            </div>

                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Contraseña
                                </label>
                                <span class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        Recordarme
                                    </label>
                                </div>
                                <a href="forgot_password.php" class="text-decoration-none">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </div>

                            <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar Sesión
                            </button>
                        </form>

                        <div class="divider">
                            <span>¿No tienes cuenta?</span>
                        </div>

                        <div class="text-center">
                            <a href="register.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user-plus me-2"></i>
                                Crear Cuenta
                            </a>
                        </div>

                        <!-- Demo credentials -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <small class="text-muted">
                                <strong>Credenciales de prueba:</strong><br>
                                <strong>Admin:</strong> admin@gym.com / admin123<br>
                                <strong>Cliente:</strong> cliente@gym.com / cliente123<br>
                                <strong>Entrenador:</strong> entrenador@gym.com / entrenador123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-success)');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>