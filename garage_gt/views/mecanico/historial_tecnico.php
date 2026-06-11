<?php
// views/mecanico/historial_tecnico.php — RF-03: Historial por patente
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['mecanico', 'recepcionista', 'gerente']);
$pageTitle = 'Historial Técnico';
$pdo = getDB();
$patente  = strtoupper(trim($_GET['patente'] ?? $_POST['patente'] ?? ''));
$historial = [];
$vehiculo  = null;

if ($patente) {
    $stmt = $pdo->prepare("SELECT v.*, c.cliente_nombre, c.cliente_telefono
                           FROM vehiculos v JOIN clientes c ON v.cliente_DNI=c.cliente_DNI
                           WHERE v.vehiculo_patente=?");
    $stmt->execute([$patente]);
    $vehiculo = $stmt->fetch();

    if ($vehiculo) {
        $historial = $pdo->prepare("
            SELECT ot.orden_numero, ot.orden_estado, ot.orden_comentario,
                   ot.costo_ajustado, ot.orden_kilometros,
                   o.orden_fecha,
                   s.servicio_nombre, s.servicio_descripcion,
                   e.empleado_nombre AS mecanico_nombre,
                   t.turno_fecha, t.turno_comentario,
                   (SELECT COALESCE(SUM(op.cantidad * op.precio_unitario),0)
                    FROM orden_productos op WHERE op.orden_numero=ot.orden_numero) AS costo_repuestos
            FROM orden_trabajo ot
            JOIN ordenes  o  ON ot.orden_numero    = o.orden_numero
            JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
            JOIN empleados e ON ot.mecanico_DNI    = e.empleado_DNI
            LEFT JOIN turnos t ON ot.turno_id = t.turno_id
            WHERE o.vehiculo_patente = ?
            ORDER BY ot.orden_numero DESC
        ");
        $historial->execute([$patente]);
        $historial = $historial->fetchAll();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Buscador -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-search"></i> Buscar historial por patente
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="patente" class="form-control"
                       placeholder="Ej: ABC123" maxlength="10"
                       style="text-transform:uppercase"
                       value="<?= htmlspecialchars($patente) ?>" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($patente && !$vehiculo): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No se encontró ningún vehículo con la patente <strong><?= htmlspecialchars($patente) ?></strong>.
</div>
<?php endif; ?>

<?php if ($vehiculo): ?>
<!-- Info del vehículo -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-car-front-fill"></i> Información del vehículo
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6 col-md-3">
                <small class="text-muted d-block">Patente</small>
                <strong><code><?= htmlspecialchars($vehiculo['vehiculo_patente']) ?></code></strong>
            </div>
            <div class="col-sm-6 col-md-3">
                <small class="text-muted d-block">Marca / Modelo</small>
                <strong><?= htmlspecialchars($vehiculo['vehiculo_marca'] . ' ' . $vehiculo['vehiculo_modelo']) ?></strong>
            </div>
            <div class="col-sm-6 col-md-2">
                <small class="text-muted d-block">Año</small>
                <strong><?= htmlspecialchars($vehiculo['vehiculo_anio'] ?? '—') ?></strong>
            </div>
            <div class="col-sm-6 col-md-2">
                <small class="text-muted d-block">Color</small>
                <strong><?= htmlspecialchars($vehiculo['vehiculo_color'] ?? '—') ?></strong>
            </div>
            <div class="col-sm-6 col-md-2">
                <small class="text-muted d-block">Propietario</small>
                <strong><?= htmlspecialchars($vehiculo['cliente_nombre']) ?></strong><br>
                <small class="text-muted"><?= htmlspecialchars($vehiculo['cliente_telefono'] ?? '') ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Historial -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history"></i> Historial de mantenimientos</span>
        <span class="badge bg-secondary"><?= count($historial) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($historial)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox" style="font-size:2.5rem"></i>
            <p class="mt-2">Este vehículo no tiene historial de mantenimientos.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover datatable mb-0">
                <thead>
                    <tr>
                        <th>#Orden</th>
                        <th>Fecha</th>
                        <th>Servicio</th>
                        <th>Mecánico</th>
                        <th>Km</th>
                        <th>Costo MO</th>
                        <th>Costo Repuestos</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historial as $h): ?>
                <tr>
                    <td><?= $h['orden_numero'] ?></td>
                    <td><?= $h['turno_fecha'] ? date('d/m/Y', strtotime($h['turno_fecha'])) : ($h['orden_fecha'] ?? '—') ?></td>
                    <td>
                        <strong><?= htmlspecialchars($h['servicio_nombre']) ?></strong>
                        <?php if ($h['orden_comentario']): ?>
                        <br><small class="text-muted"><?= htmlspecialchars(substr($h['orden_comentario'], 0, 60)) ?>...</small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($h['mecanico_nombre']) ?></td>
                    <td><?= $h['orden_kilometros'] ? number_format($h['orden_kilometros']) . ' km' : '—' ?></td>
                    <td>Q<?= number_format($h['costo_ajustado'], 2) ?></td>
                    <td>Q<?= number_format($h['costo_repuestos'], 2) ?></td>
                    <td><strong>Q<?= number_format($h['costo_ajustado'] + $h['costo_repuestos'], 2) ?></strong></td>
                    <td>
                        <span class="badge <?= $h['orden_estado'] ? 'badge-finalizado' : 'badge-pendiente' ?>">
                            <?= $h['orden_estado'] ? 'Finalizado' : 'Pendiente' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
