<?php
// views/gerente/productos.php — Gestión de productos / inventario
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['gerente']);
$pageTitle = 'Productos y Stock';
$pdo = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion     = $_POST['accion'] ?? '';
    $id         = (int)($_POST['prod_id'] ?? 0);
    $codigo     = trim($_POST['prod_codigo'] ?? '');
    $categoria  = trim($_POST['prod_categoria'] ?? '');
    $descripcion= trim($_POST['prod_descripcion'] ?? '');
    $stock      = (int)($_POST['prod_stock'] ?? 0);
    $pProveedor = (float)str_replace(',','.',$_POST['prod_precio_proveedor'] ?? 0);
    $pVenta     = (float)str_replace(',','.',$_POST['prod_precio_venta'] ?? 0);
    $disp       = isset($_POST['prod_disponible']) ? 1 : 0;

    if ($accion === 'crear') {
        if (!$descripcion) { $msg = 'La descripción es obligatoria.'; $msgType = 'danger'; }
        else {
            $pdo->prepare("INSERT INTO productos (prod_codigo, prod_categoria, prod_descripcion, prod_stock, prod_precio_proveedor, prod_precio_venta, prod_disponible) VALUES (?,?,?,?,?,?,1)")
                ->execute([$codigo, $categoria, $descripcion, $stock, $pProveedor, $pVenta]);
            $msg = 'Producto creado.';
        }
    } elseif ($accion === 'editar') {
        $pdo->prepare("UPDATE productos SET prod_codigo=?, prod_categoria=?, prod_descripcion=?, prod_stock=?, prod_precio_proveedor=?, prod_precio_venta=?, prod_disponible=? WHERE prod_id=?")
            ->execute([$codigo, $categoria, $descripcion, $stock, $pProveedor, $pVenta, $disp, $id]);
        $msg = 'Producto actualizado.';
    } elseif ($accion === 'ajustar_stock') {
        $ajuste = (int)$_POST['ajuste_cantidad'];
        $tipo   = $_POST['tipo_ajuste']; // entrada | salida
        $delta  = ($tipo === 'entrada') ? abs($ajuste) : -abs($ajuste);
        $pdo->prepare("UPDATE productos SET prod_stock = prod_stock + ? WHERE prod_id=?")
            ->execute([$delta, $id]);
        $msg = 'Stock ajustado correctamente.';
    } elseif ($accion === 'eliminar') {
        try {
            $pdo->prepare("DELETE FROM productos WHERE prod_id=?")->execute([$id]);
            $msg = 'Producto eliminado.';
        } catch (PDOException $e) { $msg = 'No se puede eliminar: está en uso en órdenes.'; $msgType = 'danger'; }
    }
}

$productos = $pdo->query("SELECT * FROM productos ORDER BY prod_categoria, prod_descripcion")->fetchAll();

$editProd = null;
if (isset($_GET['editar'])) {
    $s = $pdo->prepare("SELECT * FROM productos WHERE prod_id=?");
    $s->execute([$_GET['editar']]);
    $editProd = $s->fetch();
}

// Agrupar por categoría para la tabla
$categorias = array_unique(array_filter(array_column($productos, 'prod_categoria')));

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
    <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Alerta stock crítico -->
<?php
$criticos = array_filter($productos, fn($p) => $p['prod_stock'] <= 5 && $p['prod_disponible']);
if ($criticos):
?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <strong><?= count($criticos) ?> producto(s) con stock crítico (≤5 unidades).</strong>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Formulario -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-box-seam-fill"></i> <?= $editProd ? 'Editar producto' : 'Nuevo producto' ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="<?= $editProd ? 'editar' : 'crear' ?>">
                    <?php if ($editProd): ?>
                    <input type="hidden" name="prod_id" value="<?= $editProd['prod_id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Código</label>
                        <input type="text" name="prod_codigo" class="form-control" maxlength="20"
                               value="<?= htmlspecialchars($editProd['prod_codigo'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <input type="text" name="prod_categoria" class="form-control" maxlength="100"
                               list="listCategorias"
                               value="<?= htmlspecialchars($editProd['prod_categoria'] ?? '') ?>">
                        <datalist id="listCategorias">
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción *</label>
                        <input type="text" name="prod_descripcion" class="form-control" maxlength="255"
                               value="<?= htmlspecialchars($editProd['prod_descripcion'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock inicial</label>
                        <input type="number" name="prod_stock" class="form-control" min="0"
                               value="<?= $editProd['prod_stock'] ?? 0 ?>">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Precio proveedor</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Q</span>
                                <input type="number" name="prod_precio_proveedor" class="form-control"
                                       min="0" step="0.01" value="<?= $editProd['prod_precio_proveedor'] ?? '0.00' ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Precio venta</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Q</span>
                                <input type="number" name="prod_precio_venta" class="form-control"
                                       min="0" step="0.01" value="<?= $editProd['prod_precio_venta'] ?? '0.00' ?>">
                            </div>
                        </div>
                    </div>
                    <?php if ($editProd): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="prod_disponible" id="dispProd"
                               <?= $editProd['prod_disponible'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="dispProd">Disponible</label>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-save me-1"></i> <?= $editProd ? 'Guardar' : 'Crear' ?>
                        </button>
                        <?php if ($editProd): ?><a href="productos.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
                    </div>
                </form>

                <?php if ($editProd): ?>
                <hr>
                <form method="POST">
                    <input type="hidden" name="accion" value="ajustar_stock">
                    <input type="hidden" name="prod_id" value="<?= $editProd['prod_id'] ?>">
                    <label class="form-label fw-bold">Ajuste de stock manual</label>
                    <div class="input-group mb-2">
                        <select name="tipo_ajuste" class="form-select form-select-sm">
                            <option value="entrada">➕ Entrada</option>
                            <option value="salida">➖ Salida</option>
                        </select>
                        <input type="number" name="ajuste_cantidad" class="form-control form-control-sm"
                               min="1" placeholder="Cantidad" required>
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm w-100">
                        <i class="bi bi-arrow-repeat me-1"></i> Ajustar stock
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-boxes"></i> Inventario
                <span class="badge bg-secondary ms-auto"><?= count($productos) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0">
                        <thead>
                            <tr><th>Código</th><th>Descripción</th><th>Categoría</th><th>Stock</th><th>P. Venta</th><th>Estado</th><th>Acc.</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($productos as $p): ?>
                        <tr class="<?= $p['prod_stock'] <= 5 && $p['prod_disponible'] ? 'table-warning' : '' ?>">
                            <td><code><?= htmlspecialchars($p['prod_codigo'] ?? '—') ?></code></td>
                            <td><?= htmlspecialchars($p['prod_descripcion']) ?></td>
                            <td><small><?= htmlspecialchars($p['prod_categoria'] ?? '—') ?></small></td>
                            <td>
                                <span class="fw-bold <?= $p['prod_stock'] <= 5 ? 'text-danger' : '' ?>">
                                    <?= $p['prod_stock'] ?>
                                    <?= $p['prod_stock'] <= 5 ? ' ⚠️' : '' ?>
                                </span>
                            </td>
                            <td>Q<?= number_format($p['prod_precio_venta'], 2) ?></td>
                            <td>
                                <span class="badge <?= $p['prod_disponible'] ? 'badge-finalizado' : 'badge-cancelado' ?>">
                                    <?= $p['prod_disponible'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td>
                                <a href="?editar=<?= $p['prod_id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar producto?')">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="prod_id" value="<?= $p['prod_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($productos)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay productos registrados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
