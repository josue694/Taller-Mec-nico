<?php
// views/mecanico/ordenes_pendientes.php — RF-06: Gestión de estados
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['mecanico', 'gerente']);
$pageTitle = 'Órdenes Pendientes';
$pdo = getDB();
$msg = '';
$msgType = 'success';
$mecDNI  = getDNIActual();
$esGerente = getRolActual() === 'gerente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion    = $_POST['accion'] ?? '';
    $ordenNum  = (int)($_POST['orden_numero'] ?? 0);
    $srvCodigo = trim($_POST['servicio_codigo'] ?? '');

    if ($accion === 'finalizar') {
        // RF-06: Pendiente → Finalizado
        $pdo->prepare("UPDATE orden_trabajo SET orden_estado=1
                       WHERE orden_numero=? AND servicio_codigo=?")
            ->execute([$ordenNum, $srvCodigo]);
        // Actualizar turno si todos los servicios están finalizados
        $pendientes = $pdo->prepare("SELECT COUNT(*) FROM orden_trabajo
                                     WHERE orden_numero=? AND orden_estado=0");
        $pendientes->execute([$ordenNum]);
        if ($pendientes->fetchColumn() == 0) {
            $pdo->prepare("UPDATE turnos t
                           JOIN orden_trabajo ot ON ot.turno_id = t.turno_id
                           SET t.turno_estado='finalizado'
                           WHERE ot.orden_numero=?")
                ->execute([$ordenNum]);
        }
        $msg = 'Orden actualizada a Finalizado.';

    } elseif ($accion === 'agregar_producto') {
        // Agregar repuesto a la orden
        $prodId  = (int)$_POST['prod_id'];
        $cant    = (float)$_POST['cantidad'];
        $precio  = (float)$_POST['precio_unitario'];
        $prodDesc= trim($_POST['prod_descripcion'] ?? '');
        $prodCod = trim($_POST['prod_codigo'] ?? '');

        if ($prodId && $cant > 0) {
            // Verificar stock
            $stock = $pdo->prepare("SELECT prod_stock, prod_descripcion, prod_codigo, prod_precio_venta
                                    FROM productos WHERE prod_id=?");
            $stock->execute([$prodId]);
            $prod = $stock->fetch();
            if ($prod && $prod['prod_stock'] >= $cant) {
                $pdo->prepare("INSERT INTO orden_productos
                    (orden_numero, prod_id, prod_codigo, prod_descripcion, cantidad, precio_unitario, mecanico_DNI)
                    VALUES (?,?,?,?,?,?,?)")
                    ->execute([$ordenNum, $prodId, $prod['prod_codigo'],
                               $prod['prod_descripcion'], $cant, $prod['prod_precio_venta'], $mecDNI]);
                // Descontar stock
                $pdo->prepare("UPDATE productos SET prod_stock = prod_stock - ? WHERE prod_id=?")
                    ->execute([$cant, $prodId]);
                // Recalcular costo orden
                $pdo->prepare("UPDATE ordenes o
                    SET o.orden_costo = (
                        SELECT COALESCE(SUM(ot.costo_ajustado),0) FROM orden_trabajo ot WHERE ot.orden_numero=o.orden_numero
                    ) + (
                        SELECT COALESCE(SUM(op.cantidad * op.precio_unitario),0) FROM orden_productos op WHERE op.orden_numero=o.orden_numero
                    ) WHERE o.orden_numero=?")
                    ->execute([$ordenNum]);
                $msg = 'Repuesto agregado a la orden.';
            } else {
                $msg = 'Stock insuficiente para este producto.';
                $msgType = 'danger';
            }
        }
    }
}

// Cargar órdenes pendientes del mecánico actual (o todas si es gerente)
$whereRol = $esGerente ? '' : "AND ot.mecanico_DNI = '$mecDNI'";
$ordenes = $pdo->query("
    SELECT ot.orden_numero, ot.servicio_codigo, ot.costo_ajustado,
           ot.orden_kilometros, ot.orden_comentario, ot.orden_estado,
           ot.mecanico_DNI, ot.turno_id,
           o.orden_fecha, o.vehiculo_patente,
           v.vehiculo_marca, v.vehiculo_modelo, v.vehiculo_color,
           c.cliente_nombre, c.cliente_telefono,
           s.servicio_nombre,
           e.empleado_nombre AS mecanico_nombre,
           t.turno_comentario
    FROM orden_trabajo ot
    JOIN ordenes  o  ON ot.orden_numero    = o.orden_numero
    JOIN vehiculos v ON o.vehiculo_patente = v.vehiculo_patente
    JOIN clientes  c ON v.cliente_DNI      = c.cliente_DNI
    JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
    JOIN empleados e ON ot.mecanico_DNI    = e.empleado_DNI
    LEFT JOIN turnos t ON ot.turno_id = t.turno_id
    WHERE ot.orden_estado = 0 $whereRol
    ORDER BY ot.orden_numero ASC
")->fetchAll();

$productos = $pdo->query("SELECT prod_id, prod_codigo, prod_descripcion, prod_stock, prod_precio_venta
                           FROM productos WHERE prod_disponible=1 AND prod_stock>0
                           ORDER BY prod_descripcion")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (empty($ordenes)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-check-circle-fill" style="font-size:3rem;color:#00b894"></i>
        <h5 class="mt-3">¡Sin órdenes pendientes!</h5>
        <p>No tienes órdenes de trabajo asignadas por el momento.</p>
    </div>
</div>
<?php else: ?>

<?php foreach ($ordenes as $o): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <i class="bi bi-tools"></i>
            <strong>Orden #<?= $o['orden_numero'] ?></strong>
            — <?= htmlspecialchars($o['servicio_nombre']) ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge badge-pendiente">Pendiente</span>
            <span class="badge bg-secondary">Q<?= number_format($o['costo_ajustado'], 2) ?></span>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <p class="mb-1"><strong><i class="bi bi-car-front me-1"></i>Vehículo:</strong><br>
                   <?= htmlspecialchars($o['vehiculo_marca'] . ' ' . $o['vehiculo_modelo']) ?>
                   <code class="ms-1"><?= htmlspecialchars($o['vehiculo_patente']) ?></code></p>
                <p class="mb-1"><strong><i class="bi bi-person me-1"></i>Cliente:</strong><br>
                   <?= htmlspecialchars($o['cliente_nombre']) ?>
                   <?php if ($o['cliente_telefono']): ?>
                   <br><small class="text-muted"><?= htmlspecialchars($o['cliente_telefono']) ?></small>
                   <?php endif; ?>
                </p>
                <?php if ($o['orden_kilometros']): ?>
                <p class="mb-1"><strong>Kilometraje:</strong> <?= number_format($o['orden_kilometros']) ?> km</p>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <strong>Falla reportada:</strong>
                <p class="text-muted"><?= nl2br(htmlspecialchars($o['turno_comentario'] ?? $o['orden_comentario'] ?? 'Sin descripción')) ?></p>
                <strong>Mecánico:</strong> <?= htmlspecialchars($o['mecanico_nombre']) ?>
            </div>
            <div class="col-md-4">
                <!-- Agregar repuesto -->
                <div class="mb-3">
                    <strong class="d-block mb-2"><i class="bi bi-box me-1"></i>Agregar repuesto</strong>
                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="agregar_producto">
                        <input type="hidden" name="orden_numero" value="<?= $o['orden_numero'] ?>">
                        <input type="hidden" name="servicio_codigo" value="<?= $o['servicio_codigo'] ?>">
                        <select name="prod_id" class="form-select form-select-sm mb-2" required>
                            <option value="">— Seleccionar producto —</option>
                            <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['prod_id'] ?>">
                                <?= htmlspecialchars($p['prod_descripcion']) ?>
                                (Stock: <?= $p['prod_stock'] ?>) — Q<?= number_format($p['prod_precio_venta'],2) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-group input-group-sm">
                            <input type="number" name="cantidad" class="form-control"
                                   placeholder="Cantidad" min="1" step="1" required>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Finalizar -->
                <form method="POST" action=""
                      onsubmit="return confirm('¿Marcar esta orden como FINALIZADA?')">
                    <input type="hidden" name="accion" value="finalizar">
                    <input type="hidden" name="orden_numero" value="<?= $o['orden_numero'] ?>">
                    <input type="hidden" name="servicio_codigo" value="<?= $o['servicio_codigo'] ?>">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle me-1"></i> Marcar como Finalizado
                    </button>
                </form>
            </div>
        </div>

        <?php
        // Repuestos ya agregados
        $repuestos = $pdo->prepare("SELECT * FROM orden_productos WHERE orden_numero=?");
        $repuestos->execute([$o['orden_numero']]);
        $reps = $repuestos->fetchAll();
        if ($reps):
        ?>
        <hr>
        <strong class="d-block mb-2">Repuestos usados:</strong>
        <table class="table table-sm table-bordered mb-0">
            <thead><tr><th>Código</th><th>Descripción</th><th>Cantidad</th><th>Precio unit.</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($reps as $r): ?>
            <tr>
                <td><code><?= htmlspecialchars($r['prod_codigo']) ?></code></td>
                <td><?= htmlspecialchars($r['prod_descripcion']) ?></td>
                <td><?= $r['cantidad'] ?></td>
                <td>Q<?= number_format($r['precio_unitario'], 2) ?></td>
                <td>Q<?= number_format($r['cantidad'] * $r['precio_unitario'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
