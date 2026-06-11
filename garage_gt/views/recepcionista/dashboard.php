<?php
// views/recepcionista/dashboard.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['recepcionista', 'gerente']);
$pageTitle = 'Dashboard — Recepción';

$pdo = getDB();

// Estadísticas rápidas
$totalClientes  = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$totalVehiculos = $pdo->query("SELECT COUNT(*) FROM vehiculos")->fetchColumn();
$turnosPendientes = $pdo->query("SELECT COUNT(*) FROM turnos WHERE turno_estado = 'pendiente'")->fetchColumn();
$turnosHoy = $pdo->query("SELECT COUNT(*) FROM turnos WHERE turno_fecha = CURDATE()")->fetchColumn();

// Últimos turnos
$ultimosTurnos = $pdo->query("
    SELECT t.turno_id, t.turno_fecha, t.turno_hora, t.turno_estado,
           c.cliente_nombre, v.vehiculo_marca, v.vehiculo_modelo, v.vehiculo_patente,
           e.empleado_nombre AS mecanico_nombre
    FROM turnos t
    JOIN clientes  c ON t.cliente_DNI      = c.cliente_DNI
    JOIN vehiculos v ON t.vehiculo_patente = v.vehiculo_patente
    JOIN empleados e ON t.mecanico_dni     = e.empleado_DNI
    ORDER BY t.turno_fecha DESC, t.turno_hora DESC
    LIMIT 10
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?= $totalClientes ?></div>
                <div class="stat-label">Clientes registrados</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card green">
            <div class="stat-icon"><i class="bi bi-car-front-fill"></i></div>
            <div>
                <div class="stat-value"><?= $totalVehiculos ?></div>
                <div class="stat-label">Vehículos registrados</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card orange">
            <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
            <div>
                <div class="stat-value"><?= $turnosPendientes ?></div>
                <div class="stat-label">Turnos pendientes</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-calendar-day-fill"></i></div>
            <div>
                <div class="stat-value"><?= $turnosHoy ?></div>
                <div class="stat-label">Turnos hoy</div>
            </div>
        </div>
    </div>
</div>

<!-- Acciones rápidas -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning-fill"></i> Acciones rápidas
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>/views/recepcionista/clientes.php?action=nuevo"
                   class="btn btn-primary">
                    <i class="bi bi-person-plus-fill me-1"></i> Nuevo cliente
                </a>
                <a href="<?= BASE_URL ?>/views/recepcionista/vehiculos.php?action=nuevo"
                   class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Nuevo vehículo
                </a>
                <a href="<?= BASE_URL ?>/views/recepcionista/turnos.php?action=nuevo"
                   class="btn btn-primary">
                    <i class="bi bi-calendar-plus me-1"></i> Nuevo turno
                </a>
                <a href="<?= BASE_URL ?>/views/recepcionista/facturacion.php"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-receipt me-1"></i> Facturación
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Últimos turnos -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history"></i> Últimos turnos registrados
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Patente</th>
                        <th>Mecánico</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ultimosTurnos as $t): ?>
                    <tr>
                        <td><?= $t['turno_id'] ?></td>
                        <td><?= date('d/m/Y', strtotime($t['turno_fecha'])) ?></td>
                        <td><?= substr($t['turno_hora'], 0, 5) ?></td>
                        <td><?= htmlspecialchars($t['cliente_nombre']) ?></td>
                        <td><?= htmlspecialchars($t['vehiculo_marca'] . ' ' . $t['vehiculo_modelo']) ?></td>
                        <td><code><?= htmlspecialchars($t['vehiculo_patente']) ?></code></td>
                        <td><?= htmlspecialchars($t['mecanico_nombre']) ?></td>
                        <td>
                            <span class="badge badge-<?= $t['turno_estado'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $t['turno_estado'])) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($ultimosTurnos)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay turnos registrados aún.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
