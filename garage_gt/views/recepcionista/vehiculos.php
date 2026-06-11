<?php
// views/recepcionista/vehiculos.php — RF-02: Gestión de Vehículos
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['recepcionista', 'gerente']);
$pageTitle = 'Gestión de Vehículos';
$pdo = getDB();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion  = $_POST['accion'] ?? '';
    $patente = strtoupper(trim($_POST['vehiculo_patente'] ?? ''));
    $dni     = trim($_POST['cliente_DNI'] ?? '');
    $marca   = trim($_POST['vehiculo_marca'] ?? '');
    $modelo  = trim($_POST['vehiculo_modelo'] ?? '');
    $anio    = trim($_POST['vehiculo_anio'] ?? '');
    $color   = trim($_POST['vehiculo_color'] ?? '');
    $motor   = trim($_POST['vehiculo_motor'] ?? '');

    if ($accion === 'crear') {
        if ($patente === '' || $dni === '') {
            $msg = 'Patente y DNI del cliente son obligatorios.';
            $msgType = 'danger';
        } else {
            // Verificar que el cliente existe
            $chk = $pdo->prepare("SELECT cliente_DNI FROM clientes WHERE cliente_DNI = ?");
            $chk->execute([$dni]);
            if (!$chk->fetch()) {
                $msg = 'El DNI del cliente no existe en el sistema.';
                $msgType = 'danger';
            } else {
                try {
                    $pdo->prepare("INSERT INTO vehiculos
                        (vehiculo_patente, cliente_DNI, vehiculo_marca, vehiculo_modelo,
                         vehiculo_anio, vehiculo_color, vehiculo_motor)
                        VALUES (?,?,?,?,?,?,?)")
                        ->execute([$patente, $dni, $marca, $modelo, $anio, $color, $motor]);
                    $msg = 'Vehículo registrado correctamente.';
                } catch (PDOException $e) {
                    $msg = 'Error: La patente ya existe.';
                    $msgType = 'danger';
                }
            }
        }
    } elseif ($accion === 'editar') {
        $pdo->prepare("UPDATE vehiculos SET
            cliente_DNI=?, vehiculo_marca=?, vehiculo_modelo=?,
            vehiculo_anio=?, vehiculo_color=?, vehiculo_motor=?
            WHERE vehiculo_patente=?")
            ->execute([$dni, $marca, $modelo, $anio, $color, $motor, $patente]);
        $msg = 'Vehículo actualizado.';
    } elseif ($accion === 'eliminar') {
        try {
            $pdo->prepare("DELETE FROM vehiculos WHERE vehiculo_patente=?")->execute([$patente]);
            $msg = 'Vehículo eliminado.';
        } catch (PDOException $e) {
            $msg = 'No se puede eliminar: tiene órdenes o turnos asociados.';
            $msgType = 'danger';
        }
    }
}

$vehiculos = $pdo->query("
    SELECT v.*, c.cliente_nombre
    FROM vehiculos v
    JOIN clientes c ON v.cliente_DNI = c.cliente_DNI
    ORDER BY v.vehiculo_patente
")->fetchAll();

$clientes = $pdo->query("SELECT cliente_DNI, cliente_nombre FROM clientes ORDER BY cliente_nombre")->fetchAll();

$editVehiculo = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE vehiculo_patente = ?");
    $stmt->execute([$_GET['editar']]);
    $editVehiculo = $stmt->fetch();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Formulario -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-car-front-fill"></i>
                <?= $editVehiculo ? 'Editar vehículo' : 'Registrar vehículo' ?>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="<?= $editVehiculo ? 'editar' : 'crear' ?>">

                    <div class="mb-3">
                        <label class="form-label">Patente *</label>
                        <input type="text" name="vehiculo_patente" class="form-control"
                               maxlength="10" style="text-transform:uppercase"
                               value="<?= htmlspecialchars($editVehiculo['vehiculo_patente'] ?? '') ?>"
                               <?= $editVehiculo ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cliente (DNI) *</label>
                        <select name="cliente_DNI" class="form-select" required>
                            <option value="">— Seleccionar cliente —</option>
                            <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['cliente_DNI'] ?>"
                                <?= ($editVehiculo['cliente_DNI'] ?? '') === $c['cliente_DNI'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['cliente_nombre']) ?> (<?= $c['cliente_DNI'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" name="vehiculo_marca" class="form-control" maxlength="10"
                                   value="<?= htmlspecialchars($editVehiculo['vehiculo_marca'] ?? '') ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="vehiculo_modelo" class="form-control" maxlength="10"
                                   value="<?= htmlspecialchars($editVehiculo['vehiculo_modelo'] ?? '') ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Año</label>
                            <input type="text" name="vehiculo_anio" class="form-control" maxlength="4"
                                   value="<?= htmlspecialchars($editVehiculo['vehiculo_anio'] ?? '') ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Color</label>
                            <input type="text" name="vehiculo_color" class="form-control" maxlength="10"
                                   value="<?= htmlspecialchars($editVehiculo['vehiculo_color'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motor</label>
                        <input type="text" name="vehiculo_motor" class="form-control" maxlength="10"
                               value="<?= htmlspecialchars($editVehiculo['vehiculo_motor'] ?? '') ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-save me-1"></i>
                            <?= $editVehiculo ? 'Guardar cambios' : 'Registrar' ?>
                        </button>
                        <?php if ($editVehiculo): ?>
                        <a href="vehiculos.php" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ul"></i> Vehículos registrados
                <span class="badge bg-secondary ms-auto"><?= count($vehiculos) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0">
                        <thead>
                            <tr>
                                <th>Patente</th>
                                <th>Marca / Modelo</th>
                                <th>Año</th>
                                <th>Color</th>
                                <th>Propietario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($vehiculos as $v): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($v['vehiculo_patente']) ?></code></td>
                                <td><?= htmlspecialchars($v['vehiculo_marca'] . ' ' . $v['vehiculo_modelo']) ?></td>
                                <td><?= htmlspecialchars($v['vehiculo_anio'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($v['vehiculo_color'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($v['cliente_nombre']) ?></td>
                                <td>
                                    <a href="?editar=<?= urlencode($v['vehiculo_patente']) ?>"
                                       class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/views/mecanico/historial_tecnico.php?patente=<?= urlencode($v['vehiculo_patente']) ?>"
                                       class="btn btn-sm btn-outline-info me-1"
                                       data-bs-toggle="tooltip" title="Ver historial">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar vehículo <?= htmlspecialchars($v['vehiculo_patente']) ?>?')">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="vehiculo_patente" value="<?= htmlspecialchars($v['vehiculo_patente']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vehiculos)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No hay vehículos registrados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
