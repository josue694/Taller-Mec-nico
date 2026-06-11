<?php
// views/gerente/servicios.php — Gestión de servicios
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['gerente']);
$pageTitle = 'Gestión de Servicios';
$pdo = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion  = $_POST['accion'] ?? '';
    $codigo  = strtoupper(trim($_POST['servicio_codigo'] ?? ''));
    $nombre  = trim($_POST['servicio_nombre'] ?? '');
    $desc    = trim($_POST['servicio_descripcion'] ?? '');
    $costo   = (float)str_replace(',', '.', $_POST['servicio_costo'] ?? 0);
    $disp    = isset($_POST['servicio_disponible']) ? 1 : 0;

    if ($accion === 'crear') {
        if (!$codigo || !$nombre) { $msg = 'Código y nombre son obligatorios.'; $msgType = 'danger'; }
        else {
            try {
                $pdo->prepare("INSERT INTO servicios (servicio_codigo, servicio_nombre, servicio_descripcion, servicio_costo, servicio_disponible) VALUES (?,?,?,?,?)")
                    ->execute([$codigo, $nombre, $desc, $costo, 1]);
                $msg = 'Servicio creado.';
            } catch (PDOException $e) { $msg = 'El código ya existe.'; $msgType = 'danger'; }
        }
    } elseif ($accion === 'editar') {
        $pdo->prepare("UPDATE servicios SET servicio_nombre=?, servicio_descripcion=?, servicio_costo=?, servicio_disponible=? WHERE servicio_codigo=?")
            ->execute([$nombre, $desc, $costo, $disp, $codigo]);
        $msg = 'Servicio actualizado.';
    } elseif ($accion === 'eliminar') {
        try {
            $pdo->prepare("DELETE FROM servicios WHERE servicio_codigo=?")->execute([$codigo]);
            $msg = 'Servicio eliminado.';
        } catch (PDOException $e) { $msg = 'No se puede eliminar: está en uso.'; $msgType = 'danger'; }
    }
}

$servicios = $pdo->query("SELECT * FROM servicios ORDER BY servicio_nombre")->fetchAll();

$editSrv = null;
if (isset($_GET['editar'])) {
    $s = $pdo->prepare("SELECT * FROM servicios WHERE servicio_codigo=?");
    $s->execute([$_GET['editar']]);
    $editSrv = $s->fetch();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
    <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-gear-fill"></i> <?= $editSrv ? 'Editar servicio' : 'Nuevo servicio' ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="<?= $editSrv ? 'editar' : 'crear' ?>">
                    <div class="mb-3">
                        <label class="form-label">Código *</label>
                        <input type="text" name="servicio_codigo" class="form-control" maxlength="5"
                               style="text-transform:uppercase"
                               value="<?= htmlspecialchars($editSrv['servicio_codigo'] ?? '') ?>"
                               <?= $editSrv ? 'readonly' : 'required' ?>>
                        <small class="text-muted">Máx. 5 caracteres, ej: SV006</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="servicio_nombre" class="form-control" maxlength="35"
                               value="<?= htmlspecialchars($editSrv['servicio_nombre'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="servicio_descripcion" class="form-control" rows="2" maxlength="100"><?= htmlspecialchars($editSrv['servicio_descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Costo base (Q)</label>
                        <div class="input-group">
                            <span class="input-group-text">Q</span>
                            <input type="number" name="servicio_costo" class="form-control" min="0" step="0.01"
                                   value="<?= $editSrv['servicio_costo'] ?? '0.00' ?>">
                        </div>
                    </div>
                    <?php if ($editSrv): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="servicio_disponible" id="dispSrv"
                               <?= $editSrv['servicio_disponible'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="dispSrv">Disponible para asignar</label>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-save me-1"></i> <?= $editSrv ? 'Guardar' : 'Crear servicio' ?>
                        </button>
                        <?php if ($editSrv): ?><a href="servicios.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ul"></i> Catálogo de servicios
                <span class="badge bg-secondary ms-auto"><?= count($servicios) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0">
                        <thead><tr><th>Código</th><th>Nombre</th><th>Descripción</th><th>Costo</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($servicios as $s): ?>
                        <tr>
                            <td><code><?= $s['servicio_codigo'] ?></code></td>
                            <td><?= htmlspecialchars($s['servicio_nombre']) ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($s['servicio_descripcion'] ?? '—') ?></small></td>
                            <td>Q<?= number_format($s['servicio_costo'], 2) ?></td>
                            <td>
                                <span class="badge <?= $s['servicio_disponible'] ? 'badge-finalizado' : 'badge-cancelado' ?>">
                                    <?= $s['servicio_disponible'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td>
                                <a href="?editar=<?= $s['servicio_codigo'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar servicio?')">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="servicio_codigo" value="<?= $s['servicio_codigo'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
