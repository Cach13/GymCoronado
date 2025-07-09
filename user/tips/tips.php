<?php
require_once('../../config/config.php');
require_once('../../config/User.php');

// Verificar permisos
gym_check_permission('cliente');

// Inicializar objeto User
$user = new User();