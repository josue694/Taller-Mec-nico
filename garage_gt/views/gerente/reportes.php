<?php
// views/gerente/reportes.php — Reportes y exportación de planillas
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['gerente']);
$pageTitle = 'Reportes';
$pdo = getDB();

$tipo   = $_GET['tipo']   ?? 'ordenes';
$desde  = $_GET['desde']  ?? date('Y-m-01');
$hasta  = $_GET['hasta']  ?? date('Y-m-d');
$export = isset($_GET['export']);

// ── Datos según tipo ──────────────────────────────────────────
$datos = [];
$columnas = [];

if ($tipo === 'ordenes') {
    $columnas = ['#Orden','Fecha','Vehículo','Cliente','Servicio','Mecánico','Km','Costo MO','Costo Rep.','Total','Estado'];
    $stmt = $pdo->prepare("
        SELECT ot.orden_numero, o.orden_fecha, o.vehiculo_patente,
               c.cliente_nombre, s.servicio_nombre,
               e.empleado_nombre AS mecanico_nombre,
               ot.orden_kilometros, ot.costo_ajustado,
               COALESCE((SELECT SUM(op.cantidad*op.precio_unitario) FROM orden_productos op WHERE op.orden_numero=ot.orden_numero),0) AS costo_rep,
               ot.orden_estado
        FROM orden_trabajo ot
        JOIN ordenes   o ON ot.orden_numero    = o.orden_numero
        JOIN vehiculos v ON o.vehiculo_patente = v.vehiculo_patente
        JOIN clientes  c ON v.cliente_DNI      = c.cliente_DNI
        JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
        JOIN empleados e ON ot.mecanico_DNI    = e.empleado_DNI
        WHERE o.orden_fecha BETWEEN ? AND ?
        ORDER BY ot.orden_numero DESC
    ");
    $stmt->execute([$desde, $hasta]);
    $datos = $stmt->fetchAll();

} elseif ($tipo === 'facturacion') {
    $columnas = ['#Factura','Comprobante','Tipo','Fecha','Cliente','Patente','Total','Emisor'];
    $stmt = $pdo->prepare("
        SELECT f.factura_id, CONCAT(f.tipo,'-',LPAD(f.nro_comprobante,8,'0')) AS comprobante,
               f.tipo, f.fecha_emision, c.cliente_nombre,
               f.vehiculo_patente, f.total, e.empleado_nombre AS emisor
        FROM facturas f
        JOIN clientes  c ON f.cliente_dni     = c.cliente_DNI
        JOIN empleados e ON f.empleado_emisor = e.empleado_DNI
        WHERE DATE(f.fecha_emision) BETWEEN ? AND ?
        ORDER BY f.factura_id DESC
    ");
    $stmt->execute([$desde, $hasta]);
    $datos = $stmt->fetchAll();

} elseif ($tipo === 'mecanicos') {
    $columnas = ['Mecánico','DNI','Total órdenes','Finalizadas','Pendientes','Ingresos generados'];
    $stmt = $pdo->query("
        SELECT e.empleado_nombre, e.empleado_DNI,
               COUNT(ot.orden_numero) AS total,
               SUM(ot.orden_estado) AS finalizadas,
               SUM(1-ot.orden_estado) AS pendientes,
               COALESCE(SUM(ot.costo_ajustado),0) AS ingresos
        FROM empleados e
        LEFT JOIN orden_trabajo ot ON ot.mecanico_DNI = e.empleado_DNI
        WHERE e.empleado_roll = 'mecanico'
        GROUP BY e.empleado_DNI, e.empleado_nombre
        ORDER BY finalizadas DESC
    ");
    $datos = $stmt->fetchAll();
}

// ── Exportar CSV ──────────────────────────────────────────────
if ($export && !empty($datos)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, $columnas, ';');
    foreach ($datos as $row) { fputcsv($out, array_values($row), ';'); }
    fclose($out);
    exit;
}

// Totales para facturación
$totalFacturado = ($tipo === 'facturacion') ? array_sum(array_column($datos, 'total')) : 0;

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-funnel-fill"></i> Filtros de reporte</div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tipo de reporte</label>
                <select name="tipo" class="form-select">
                    <option value="ordenes"     <?= $tipo==='ordenes'     ?'selected':'' ?>>Órdenes de trabajo</option>
                    <option value="facturacion" <?= $tipo==='facturacion' ?'selected':'' ?>>Facturación</option>
                    <option value="mecanicos"   <?= $tipo==='mecanicos'   ?'selected':'' ?>>Rendimiento mecánicos</option>
                </select>
            </div>
            <?php if ($tipo !== 'mecanicos'): ?>
            <div class="col-md-2">
                <label class="form-label">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= $desde ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= $hasta ?>">
            </div>
            <?php endif; ?>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Generar
                </button>
                <a href="?tipo=<?= $tipo ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>&export=1"
                   class="btn btn-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV
                </a>
            </div>
        </form>
    </div>
</div>

<?php if ($tipo === 'facturacion' && $totalFacturado > 0): ?>
<div class="alert alert-success">
    <i class="bi bi-cash-stack me-2"></i>
    <strong>Total facturado en el período: Q<?= number_format($totalFacturado, 2) ?></strong>
    — <?= count($datos) ?> comprobantes
</div>
<?php endif; ?>

<!-- Tabla de resultados -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-table"></i>
            <?php
            $titulos = ['ordenes'=>'Órdenes de trabajo','facturacion'=>'Facturación','mecanicos'=>'Rendimiento por mecánico'];
            echo $titulos[$tipo] ?? 'Reporte';
            ?>
        </span>
        <span class="badge bg-secondary"><?= count($datos) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($datos)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox" style="font-size:2.5rem"></i>
            <p class="mt-2">No hay datos para los filtros seleccionados.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover datatable mb-0">
                <thead>
                    <tr><?php foreach ($columnas as $col): ?><th><?= $col ?></th><?php endforeach; ?></tr>
                </thead>
                <tbody>
                <?php foreach ($datos as $row): ?>
                <tr>
                    <?php if ($tipo === 'ordenes'): ?>
                        <td><?= $row['orden_numero'] ?></td>
                        <td><?= $row['orden_fecha'] ?></td>
                        <td><code><?= htmlspecialchars($row['vehiculo_patente']) ?></code></td>
                        <td><?= htmlspecialchars($row['cliente_nombre']) ?></td>
                        <td><?= htmlspecialchars($row['servicio_nombre']) ?></td>
                        <td><?= htmlspecialchars($row['mecanico_nombre']) ?></td>
                        <td><?= $row['orden_kilometros'] ? number_format($row['orden_kilometros']).' km' : '—' ?></td>
                        <td>Q<?= number_format($row['costo_ajustado'],2) ?></td>
                        <td>Q<?= number_format($row['costo_rep'],2) ?></td>
                        <td><strong>Q<?= number_format($row['costo_ajustado']+$row['costo_rep'],2) ?></strong></td>
                        <td>
                            <span class="badge <?= $row['orden_estado'] ? 'badge-finalizado':'badge-pendiente' ?>">
                                <?= $row['orden_estado'] ? 'Finalizado':'Pendiente' ?>
                            </span>
                        </td>
                    <?php elseif ($tipo === 'facturacion'): ?>
                        <td><?= $row['factura_id'] ?></td>
                        <td><code><?= $row['comprobante'] ?></code></td>
                        <td><?= $row['tipo'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['fecha_emision'])) ?></td>
                        <td><?= htmlspecialchars($row['cliente_nombre']) ?></td>
                        <td><code><?= htmlspecialchars($row['vehiculo_patente']) ?></code></td>
                        <td><strong>Q<?= number_format($row['total'],2) ?></strong></td>
                        <td><?= htmlspecialchars($row['emisor']) ?></td>
                    <?php elseif ($tipo === 'mecanicos'): ?>
                        <td><strong><?= htmlspecialchars($row['empleado_nombre']) ?></strong></td>
                        <td><code><?= $row['empleado_DNI'] ?></code></td>
                        <td><?= $row['total'] ?></td>
                        <td><span class="badge badge-finalizado"><?= $row['finalizadas'] ?></span></td>
                        <td><span class="badge badge-pendiente"><?= $row['pendientes'] ?></span></td>
                        <td><strong>Q<?= number_format($row['ingresos'],2) ?></strong></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
