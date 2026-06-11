<!-- includes/header.php — Layout principal con sidebar -->
<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$rol = getRolActual();
$nombre = getNombreActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> — <?= $pageTitle ?? 'Panel' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="wrapper d-flex">
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-wrench-adjustable-circle-fill"></i>
            <span><?= APP_NAME ?></span>
        </div>

        <ul class="sidebar-nav list-unstyled">

            <?php if ($rol === 'recepcionista' || $rol === 'gerente'): ?>
            <li class="nav-section">Recepción</li>
            <li>
                <a href="<?= BASE_URL ?>/views/recepcionista/clientes.php">
                    <i class="bi bi-people-fill"></i> Clientes
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/views/recepcionista/vehiculos.php">
                    <i class="bi bi-car-front-fill"></i> Vehículos
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/views/recepcionista/turnos.php">
                    <i class="bi bi-calendar-check-fill"></i> Turnos / Órdenes
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/views/recepcionista/facturacion.php">
                    <i class="bi bi-receipt-cutoff"></i> Facturación
                </a>
            </li>
            <?php endif; ?>

            <?php if ($rol === 'mecanico' || $rol === 'gerente'): ?>
            <li class="nav-section">Taller</li>
            <li>
                <a href="<?= BASE_URL ?>/views/mecanico/ordenes_pendientes.php">
                    <i class="bi bi-tools"></i> Órdenes Pendientes
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/views/mecanico/historial_tecnico.php">
                    <i class="bi bi-clock-history"></i> Historial Técnico
                </a>
            </li>
            <?php endif; ?>

            <?php if ($rol === 'gerente'): ?>
            <li class="nav-section">Administración</li>
            <li>
                <a href="<?= BASE_URL ?>/views/gerente/empleados.php">
                    <i class="bi bi-person-badge-fill"></i> Empleados
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/views/gerente/servicios.php">
                    <i class="bi bi-gear-fill"></i> Servicios
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/views/gerente/productos.php">
                    <i class="bi bi-box-seam-fill"></i> Productos / Stock
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/views/gerente/reportes.php">
                    <i class="bi bi-bar-chart-fill"></i> Reportes
                </a>
            </li>
            <?php endif; ?>

        </ul>

        <div class="sidebar-footer">
            <div class="user-info">
                <i class="bi bi-person-circle"></i>
                <div>
                    <span class="user-name"><?= htmlspecialchars($nombre) ?></span>
                    <span class="user-rol badge"><?= ucfirst($rol) ?></span>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/controllers/logout.php" class="btn-logout" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>

    <!-- CONTENIDO PRINCIPAL -->
    <div id="main-content" class="main-content flex-grow-1">
        <!-- Topbar -->
        <div class="topbar d-flex align-items-center px-4">
            <button id="sidebarToggle" class="btn btn-icon me-3">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="mb-0"><?= $pageTitle ?? 'Panel' ?></h5>
        </div>

        <!-- Área de trabajo -->
        <div class="content-area p-4">
