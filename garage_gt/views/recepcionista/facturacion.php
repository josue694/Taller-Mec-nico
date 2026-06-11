<?php
// views/recepcionista/facturacion.php — RF-07: Facturación y PDF
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['recepcionista', 'gerente']);
$pageTitle = 'Facturación';
$pdo = getDB();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion     = $_POST['accion'] ?? '';
    $ordenNum   = (int)($_POST['orden_numero'] ?? 0);
    $tipo       = trim($_POST['tipo'] ?? 'B');
    $clienteDni = trim($_POST['cliente_dni'] ?? '');
    $emailDst   = trim($_POST['email_destino'] ?? '');
    $emisorDni  = getDNIActual();

    if ($accion === 'generar_factura' && $ordenNum) {
        try {
            $pdo->beginTransaction();

            // Calcular total
            $totalMO = $pdo->prepare("SELECT COALESCE(SUM(costo_ajustado),0)
                                      FROM orden_trabajo WHERE orden_numero=?");
            $totalMO->execute([$ordenNum]);
            $montoMO = (float)$totalMO->fetchColumn();

            $totalRep = $pdo->prepare("SELECT COALESCE(SUM(cantidad*precio_unitario),0)
                                       FROM orden_productos WHERE orden_numero=?");
            $totalRep->execute([$ordenNum]);
            $montoRep = (float)$totalRep->fetchColumn();

            $total = $montoMO + $montoRep;

            // Obtener próximo número de comprobante
            $pdo->prepare("UPDATE factura_numeradores SET proximo=proximo+1 WHERE tipo=?")
                ->execute([$tipo]);
            $nro = $pdo->prepare("SELECT proximo-1 FROM factura_numeradores WHERE tipo=?");
            $nro->execute([$tipo]);
            $nroComp = (int)$nro->fetchColumn();

            // Obtener vehículo
            $veh = $pdo->prepare("SELECT vehiculo_patente FROM ordenes WHERE orden_numero=?");
            $veh->execute([$ordenNum]);
            $patente = $veh->fetchColumn();

            // Insertar factura
            $pdo->prepare("INSERT INTO facturas
                (tipo, nro_comprobante, fecha_emision, orden_numero, cliente_dni,
                 vehiculo_patente, total, email_destino, email_enviado, empleado_emisor)
                VALUES (?,?,NOW(),?,?,?,?,?,0,?)")
                ->execute([$tipo, $nroComp, $ordenNum, $clienteDni, $patente,
                           $total, $emailDst, $emisorDni]);
            $factId = $pdo->lastInsertId();

            // Actualizar orden
            $pdo->prepare("UPDATE ordenes SET orden_costo=? WHERE orden_numero=?")
                ->execute([$total, $ordenNum]);

            $pdo->commit();

            // Redirigir a generación de PDF
            header("Location: " . BASE_URL . "/controllers/generar_pdf.php?factura_id=$factId");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = 'Error al generar factura: ' . $e->getMessage();
            $msgType = 'danger';
        }
    }
}

// Órdenes finalizadas sin facturar
$ordenesFin = $pdo->query("
    SELECT DISTINCT o.orden_numero, o.orden_fecha, o.vehiculo_patente, o.orden_costo,
           v.vehiculo_marca, v.vehiculo_modelo,
           c.cliente_DNI, c.cliente_nombre, c.cliente_email
    FROM ordenes o
    JOIN vehiculos v ON o.vehiculo_patente = v.vehiculo_patente
    JOIN clientes  c ON v.cliente_DNI      = c.cliente_DNI
    WHERE NOT EXISTS (SELECT 1 FROM facturas f WHERE f.orden_numero = o.orden_numero)
      AND EXISTS (SELECT 1 FROM orden_trabajo ot WHERE ot.orden_numero = o.orden_numero AND ot.orden_estado=1)
    ORDER BY o.orden_numero DESC
")->fetchAll();

// Facturas emitidas
$facturas = $pdo->query("
    SELECT f.*, c.cliente_nombre, o.vehiculo_patente, e.empleado_nombre AS emisor_nombre
    FROM facturas f
    JOIN clientes  c ON f.cliente_dni     = c.cliente_DNI
    JOIN ordenes   o ON f.orden_numero    = o.orden_numero
    JOIN empleados e ON f.empleado_emisor = e.empleado_DNI
    ORDER BY f.factura_id DESC
    LIMIT 30
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Órdenes listas para facturar -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-check-circle-fill text-success"></i> Órdenes finalizadas — Pendientes de facturar
        <span class="badge bg-success ms-2"><?= count($ordenesFin) ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($ordenesFin)): ?>
        <div class="text-center text-muted py-4">No hay órdenes pendientes de facturar.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>#Orden</th><th>Fecha</th><th>Cliente</th><th>Vehículo</th><th>Total estimado</th><th>Acción</th></tr>
                </thead>
                <tbody>
                <?php foreach ($ordenesFin as $o): ?>
                <tr>
                    <td><?= $o['orden_numero'] ?></td>
                    <td><?= $o['orden_fecha'] ?></td>
                    <td><?= htmlspecialchars($o['cliente_nombre']) ?></td>
                    <td><code><?= htmlspecialchars($o['vehiculo_patente']) ?></code>
                        <?= htmlspecialchars($o['vehiculo_marca'] . ' ' . $o['vehiculo_modelo']) ?></td>
                    <td><strong>Q<?= number_format($o['orden_costo'], 2) ?></strong></td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalFacturar"
                                data-orden="<?= $o['orden_numero'] ?>"
                                data-cliente="<?= $o['cliente_DNI'] ?>"
                                data-email="<?= htmlspecialchars($o['cliente_email'] ?? '') ?>"
                                data-total="<?= $o['orden_costo'] ?>">
                            <i class="bi bi-receipt me-1"></i> Facturar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Historial de facturas -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-archive-fill"></i> Facturas emitidas
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover datatable mb-0">
                <thead>
                    <tr><th>#</th><th>Comprobante</th><th>Tipo</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Emisor</th><th>PDF</th></tr>
                </thead>
                <tbody>
                <?php foreach ($facturas as $f): ?>
                <tr>
                    <td><?= $f['factura_id'] ?></td>
                    <td><?= $f['tipo'] . '-' . str_pad($f['nro_comprobante'], 8, '0', STR_PAD_LEFT) ?></td>
                    <td><?= $f['tipo'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($f['fecha_emision'])) ?></td>
                    <td><?= htmlspecialchars($f['cliente_nombre']) ?></td>
                    <td><strong>Q<?= number_format($f['total'], 2) ?></strong></td>
                    <td><?= htmlspecialchars($f['emisor_nombre']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/controllers/generar_pdf.php?factura_id=<?= $f['factura_id'] ?>"
                           class="btn btn-sm btn-outline-danger" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($facturas)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No hay facturas emitidas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Facturar -->
<div class="modal fade" id="modalFacturar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Generar factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="generar_factura">
                <input type="hidden" name="orden_numero"  id="modalOrdenNum">
                <input type="hidden" name="cliente_dni"   id="modalClienteDni">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipo de comprobante</label>
                        <select name="tipo" class="form-select">
                            <option value="B">B — Consumidor Final</option>
                            <option value="A">A — Responsable Inscripto</option>
                            <option value="C">C — Monotributista</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email para envío (opcional)</label>
                        <input type="email" name="email_destino" id="modalEmail" class="form-control"
                               placeholder="cliente@email.com">
                    </div>
                    <div class="alert alert-info mb-0">
                        <strong>Total a facturar: Q<span id="modalTotal">0.00</span></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Generar factura PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('modalFacturar').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('modalOrdenNum').value   = btn.dataset.orden;
    document.getElementById('modalClienteDni').value = btn.dataset.cliente;
    document.getElementById('modalEmail').value      = btn.dataset.email;
    document.getElementById('modalTotal').textContent = parseFloat(btn.dataset.total).toFixed(2);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
