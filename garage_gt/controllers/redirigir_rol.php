<?php
// controllers/redirigir_rol.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

switch ($_SESSION['empleado_roll']) {
    case 'gerente':
        header('Location: ' . BASE_URL . '/views/gerente/dashboard.php');
        break;
    case 'mecanico':
        header('Location: ' . BASE_URL . '/views/mecanico/dashboard.php');
        break;
    case 'recepcionista':
    default:
        header('Location: ' . BASE_URL . '/views/recepcionista/dashboard.php');
        break;
}
exit;
