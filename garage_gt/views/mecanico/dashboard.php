<?php
// views/mecanico/dashboard.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['mecanico', 'gerente']);
$pageTitle = 'Panel del Mecánico';
$pdo    = getDB();
$mecDNI = getDNIActual();

$pendientes  = $pdo->prepare("SELECT COUNT(*) FROM orden_trabajo WHERE mecanico_DNI=? AND orden_estado=0");
$pendientes->execute([$mecDNI]);
$totalPend = $pendientes->fetchColumn();

$finalizadas = $pdo->prepare("SELECT COUNT(*) FROM orden_trabajo WHERE mecanico_DNI=? AND orden_estado=1");
$finalizadas->execute([$mecDNI]);
$totalFin = $finalizadas->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <div class="stat-card orange">
            <div class="stat-icon"><i class="bi bi-tools"></i></div>
            <div>
                <div class="stat-value"><?= $totalPend ?></div>
                <div class="stat-label">Órdenes pendientes</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="stat-card green">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $totalFin ?></div>
                <div class="stat-label">Órdenes finalizadas</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-lightning-fill"></i> Acceso rápido</div>
    <div class="card-body d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/views/mecanico/ordenes_pendientes.php" class="btn btn-primary">
            <i class="bi bi-tools me-1"></i> Ver órdenes pendientes
        </a>
        <a href="<?= BASE_URL ?>/views/mecanico/historial_tecnico.php" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> Historial técnico
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
