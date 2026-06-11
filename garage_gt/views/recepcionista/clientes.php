<?php
// views/recepcionista/clientes.php — RF-01: Gestión de Clientes
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['recepcionista', 'gerente']);
$pageTitle = 'Gestión de Clientes';
$pdo = getDB();
$msg = '';
$msgType = 'success';

// ── Procesar POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $dni    = trim($_POST['cliente_DNI']   ?? '');
    $nombre = trim($_POST['cliente_nombre'] ?? '');
    $tel    = trim($_POST['cliente_telefono'] ?? '');
    $email  = trim($_POST['cliente_email']   ?? '');
    $dir    = trim($_POST['cliente_direccion'] ?? '');
    $loc    = trim($_POST['cliente_localidad'] ?? '');

    if ($accion === 'crear') {
        $pass = trim($_POST['cliente_contrasena'] ?? '');
        if ($dni === '' || $nombre === '' || $pass === '') {
            $msg = 'DNI, nombre y contraseña son obligatorios.';
            $msgType = 'danger';
        } else {
            try {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO clientes
                    (cliente_DNI, cliente_contrasena, cliente_nombre, cliente_telefono,
                     cliente_email, cliente_direccion, cliente_localidad)
                    VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$dni, $hash, $nombre, $tel, $email, $dir, $loc]);
                $msg = 'Cliente registrado correctamente.';
            } catch (PDOException $e) {
                $msg = 'Error: El DNI ya existe o datos inválidos.';
                $msgType = 'danger';
            }
        }
    } elseif ($accion === 'editar') {
        if ($dni === '' || $nombre === '') {
            $msg = 'DNI y nombre son obligatorios.';
            $msgType = 'danger';
        } else {
            $stmt = $pdo->prepare("UPDATE clientes SET
                cliente_nombre=?, cliente_telefono=?, cliente_email=?,
                cliente_direccion=?, cliente_localidad=?
                WHERE cliente_DNI=?");
            $stmt->execute([$nombre, $tel, $email, $dir, $loc, $dni]);
            $msg = 'Cliente actualizado correctamente.';
        }
    } elseif ($accion === 'eliminar') {
        try {
            $pdo->prepare("DELETE FROM clientes WHERE cliente_DNI=?")->execute([$dni]);
            $msg = 'Cliente eliminado.';
        } catch (PDOException $e) {
            $msg = 'No se puede eliminar: el cliente tiene vehículos o registros asociados.';
            $msgType = 'danger';
        }
    }
}

// ── Cargar lista ──────────────────────────────────────────────
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY cliente_nombre")->fetchAll();

// Cargar cliente a editar (si viene por GET)
$editCliente = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE cliente_DNI = ?");
    $stmt->execute([$_GET['editar']]);
    $editCliente = $stmt->fetch();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Formulario -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus-fill"></i>
                <?= $editCliente ? 'Editar cliente' : 'Nuevo cliente' ?>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="<?= $editCliente ? 'editar' : 'crear' ?>">

                    <div class="mb-3">
                        <label class="form-label">DNI *</label>
                        <input type="text" name="cliente_DNI" class="form-control" maxlength="10"
                               value="<?= htmlspecialchars($editCliente['cliente_DNI'] ?? '') ?>"
                               <?= $editCliente ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" name="cliente_nombre" class="form-control" maxlength="50"
                               value="<?= htmlspecialchars($editCliente['cliente_nombre'] ?? '') ?>" required>
                    </div>
                    <?php if (!$editCliente): ?>
                    <div class="mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="cliente_contrasena" class="form-control" required>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="cliente_telefono" class="form-control" maxlength="15"
                               value="<?= htmlspecialchars($editCliente['cliente_telefono'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="cliente_email" class="form-control" maxlength="255"
                               value="<?= htmlspecialchars($editCliente['cliente_email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="cliente_direccion" class="form-control" maxlength="50"
                               value="<?= htmlspecialchars($editCliente['cliente_direccion'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Localidad</label>
                        <input type="text" name="cliente_localidad" class="form-control" maxlength="15"
                               value="<?= htmlspecialchars($editCliente['cliente_localidad'] ?? '') ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-save me-1"></i>
                            <?= $editCliente ? 'Guardar cambios' : 'Registrar cliente' ?>
                        </button>
                        <?php if ($editCliente): ?>
                        <a href="clientes.php" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de clientes -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-people-fill"></i> Listado de clientes
                <span class="badge bg-secondary ms-auto"><?= count($clientes) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($c['cliente_DNI']) ?></code></td>
                                <td><?= htmlspecialchars($c['cliente_nombre']) ?></td>
                                <td><?= htmlspecialchars($c['cliente_telefono'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($c['cliente_email'] ?? '—') ?></td>
                                <td>
                                    <a href="?editar=<?= urlencode($c['cliente_DNI']) ?>"
                                       class="btn btn-sm btn-outline-primary me-1"
                                       data-bs-toggle="tooltip" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar cliente <?= htmlspecialchars($c['cliente_nombre']) ?>?')">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="cliente_DNI" value="<?= htmlspecialchars($c['cliente_DNI']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="tooltip" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clientes)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay clientes registrados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
