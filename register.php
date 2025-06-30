<?php
require_once 'config/config.php';
require_once 'user/User.php';

// Si ya está logueado, redirigir al dashboard
if (gym_is_logged_in()) {
    gym_redirect('dashboard.php');
}

$errors = [];
$success = '';
$form_data = [];

// Procesar el formulario de registro
if ($_POST) {
    $form_data = [
        'email' => gym_sanitize($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'nombre' => gym_sanitize($_POST['nombre']),
        'apellido' => gym_sanitize($_POST['apellido']),
        'telefono' => gym_sanitize($_POST['telefono']),
        'fecha_nacimiento' => $_POST['fecha_nacimiento'],
        'genero' => $_POST['genero'],
        'objetivo' => $_POST['objetivo']
    ];

    $user = new User();
    
    // Validar datos
    $validation_errors = $user->validate_registration($form_data);
    if ($validation_errors) {
        $errors = array_merge($errors, $validation_errors);
    }

    // Validar confirmación de contraseña
    if ($form_data['password'] !== $form_data['confirm_password']) {
        $errors[] = 'Las contraseñas no coinciden';
    }

    // Si no hay errores, registrar usuario
    if (empty($errors)) {
        $result = $user->register($form_data);
        
        if ($result['success']) {
            $success = $result['message'];
            $form_data = []; // Limpiar formulario
            gym_show_alert('¡Registro exitoso! Ya puedes iniciar sesión.', 'success');
            header("refresh:2;url=login.php");
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .register-form {
            padding: 3rem;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h2 {
            color: #333;
            font-weight: 600;
        }
        .register-header p {
            color: #666;
            margin-bottom: 0;
        }
        .form-floating label {
            color: #666;
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .gym-icon {
            font-size: 3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row .form-floating {
            flex: 1;
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-container">
                    <div class="register-form">
                        <div class="register-header">
                            <i class="fas fa-dumbbell gym-icon"></i>
                            <h2>Crear Cuenta</h2>
                            <p>Únete a <?php echo SITE_NAME; ?> y comienza tu transformación</p>
                        </div>

                        <?php if ($errors): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Por favor corrige los siguientes errores:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <div class="mt-2">
                                    <small>Redirigiendo al login...</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <!-- Email -->
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required value="<?php echo $form_data['email'] ?? ''; ?>">
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>Email *
                                </label>
                            </div>

                            <!-- Nombre y Apellido -->
                            <div class="form-row">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" required value="<?php echo $form_data['nombre'] ?? ''; ?>">
                                    <label for="nombre">
                                        <i class="fas fa-user me-2"></i>Nombre *
                                    </label>
                                </div>
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Apellido" required value="<?php echo $form_data['apellido'] ?? ''; ?>">
                                    <label for="apellido">
                                        <i class="fas fa-user me-2"></i>Apellido *
                                    </label>
                                </div>
                            </div>

                            <!-- Contraseñas -->
                            <div class="form-row">
                                <div class="form-floating position-relative">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                                    <label for="password">
                                        <i class="fas fa-lock me-2"></i>Contraseña *
                                    </label>
                                    <span class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                        <i class="fas fa-eye" id="toggleIcon1"></i>
                                    </span>
                                </div>
                                <div class="form-floating position-relative">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña" required>
                                    <label for="confirm_password">
                                        <i class="fas fa-lock me-2"></i>Confirmar *
                                    </label>
                                    <span class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                        <i class="fas fa-eye" id="toggleIcon2"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Teléfono -->
                            <div class="form-floating">
                                <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="Teléfono" value="<?php echo $form_data['telefono'] ?? ''; ?>">
                                <label for="telefono">
                                    <i class="fas fa-phone me-2"></i>Teléfono
                                </label>
                            </div>

                            <!-- Fecha de nacimiento y género -->
                            <div class="form-row">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo $form_data['fecha_nacimiento'] ?? ''; ?>">
                                    <label for="fecha_nacimiento">
                                        <i class="fas fa-calendar me-2"></i>Fecha de Nacimiento
                                    </label>
                                </div>
                                <div class="form-floating">
                                    <select class="form-select" id="genero" name="genero">
                                        <option value="">Seleccionar...</option>
                                        <option value="masculino" <?php echo ($form_data['genero'] ?? '') == 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="femenino" <?php echo ($form_data['genero'] ?? '') == 'femenino' ? 'selected' : ''; ?>>Femenino</option>
                                        <option value="otro" <?php echo ($form_data['genero'] ?? '') == 'otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                    <label for="genero">
                                        <i class="fas fa-venus-mars me-2"></i>Género
                                    </label>
                                </div>
                            </div>

                            <!-- Objetivo -->
                            <div class="form-floating">
                                <select class="form-select" id="objetivo" name="objetivo">
                                    <option value="mantener" <?php echo ($form_data['objetivo'] ?? 'mantener') == 'mantener' ? 'selected' : ''; ?>>Mantener peso</option>
                                    <option value="bajar_peso" <?php echo ($form_data['objetivo'] ?? '') == 'bajar_peso' ? 'selected' : ''; ?>>Bajar de peso</option>
                                    <option value="subir_masa" <?php echo ($form_data['objetivo'] ?? '') == 'subir_masa' ? 'selected' : ''; ?>>Subir masa muscular</option>
                                    <option value="definir" <?php echo ($form_data['objetivo'] ?? '') == 'definir' ? 'selected' : ''; ?>>Definir/Tonificar</option>
                                </select>
                                <label for="objetivo">
                                    <i class="fas fa-target me-2"></i>Objetivo Principal
                                </label>
                            </div>

                            <!-- Términos y condiciones -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    Acepto los <a href="#" class="text-decoration-none">términos y condiciones</a> y la <a href="#" class="text-decoration-none">política de privacidad</a>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>
                                Crear Cuenta
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-0">¿Ya tienes cuenta? 
                                <a href="login.php" class="text-decoration-none fw-bold">Iniciar Sesión</a>
                            </p>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Los campos marcados con * son obligatorios
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
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

        // Validación en tiempo real
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword && confirmPassword.length > 0) {
                this.setCustomValidity('Las contraseñas no coinciden');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });

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