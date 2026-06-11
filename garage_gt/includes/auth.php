<?php
// ============================================================
// includes/auth.php — Autenticación y control de acceso RBAC
// ============================================================

require_once __DIR__ . '/../config/config.php';

/**
 * Verifica que el usuario tenga sesión activa.
 * Si no, redirige al login.
 */
function requireLogin(): void {
    if (empty($_SESSION['empleado_DNI'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    // Verificar timeout de inactividad
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Verifica que el usuario tenga uno de los roles permitidos.
 * @param array|string $rolesPermitidos
 */
function requireRol($rolesPermitidos): void {
    requireLogin();
    $rolesPermitidos = (array) $rolesPermitidos;
    if (!in_array($_SESSION['empleado_roll'], $rolesPermitidos, true)) {
        header('Location: ' . BASE_URL . '/acceso_denegado.php');
        exit;
    }
}

/**
 * Devuelve el rol actual del usuario autenticado.
 */
function getRolActual(): string {
    return $_SESSION['empleado_roll'] ?? '';
}

/**
 * Devuelve el DNI del empleado autenticado.
 */
function getDNIActual(): string {
    return $_SESSION['empleado_DNI'] ?? '';
}

/**
 * Devuelve el nombre del empleado autenticado.
 */
function getNombreActual(): string {
    return $_SESSION['empleado_nombre'] ?? '';
}
