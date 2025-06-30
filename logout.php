<?php
require_once 'config/config.php';

// Cerrar sesión
gym_logout();

// Mostrar mensaje y redirigir
gym_show_alert('Sesión cerrada exitosamente. ¡Hasta pronto!', 'success');
gym_redirect('login.php');
?>
