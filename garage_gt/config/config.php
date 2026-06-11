<?php
// ============================================================
// config/config.php — Configuración general del sistema
// ============================================================

define('APP_NAME',    'Taller Mecanico');
define('APP_VERSION', '1.0.0');
define('BASE_URL',    'http://localhost/garage_gt');

// Tiempo de inactividad de sesión en segundos (30 minutos)
define('SESSION_TIMEOUT', 1800);

// Roles válidos del sistema
define('ROLES', ['recepcionista', 'mecanico', 'gerente']);

// Ruta base de uploads
define('UPLOAD_PATH', __DIR__ . '/../uploads/facturas/');

// Zona horaria
date_default_timezone_set('America/Guatemala');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
