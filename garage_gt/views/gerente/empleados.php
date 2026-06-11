<?php
// views/gerente/empleados.php — RF-04: Control de Personal
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['gerente']);
$pageTitle = 'Gestión de Empleados';
$pdo = getDB();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion  = $_POST['accion'] ?? '';
    $dni     = trim($_POST['empleado_DNI'] ?? '');
    $nombre  = trim($_POST['empleado_nombre'] ?? '');
    $rol     = trim($_POST['empleado_roll'] ?? '');
    $email   = trim($_POST['empleado_email'] ?? '');
    $tel     = trim($_POST['empleado_telefono'] ?? '');
    $dir     = trim($_POST['empleado_direccion'] ?? '');
    $loc     = trim($_POST['empleado_localidad'] ?? '');
    $estado  = trim($_POST['empleado_estado'] ?? 'disponible');
    $habilitado = isset($_POST['empleado_habilitado']) ? 1 : 0;

    if ($accion === 'crear') {
        $pass = trim($_POST['empleado_contrasena'] ?? '');
        if (!$dni || !$nombre || !$rol || !$pass) {
            $msg = 'DNI, nombre, rol y contraseña son obligatorios.';
            $msgType = 'danger';
        } elseif (!in_array($rol, ROLES)) {
            $msg = 'Rol inválido.';
            $msgType = 'danger';
        } else {
            try {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO empleados
                    (empleado_DNI, empleado_contrasena, empleado_nombre, empleado_roll,
                     empleado_email, empleado_telefono, empleado_direccion, empleado_localidad,
                     empleado_habilitado, empleado_estado)
                    VALUES (?,?,?,?,?,?,?,?,1,'disponible')")
                    ->execute([$dni, $hash, $nombre, $rol, $email, $tel, $dir, $loc]);
                $msg = 'Empleado registrado correctamente.';
            } catch (PDOException $e) {
                $msg = 'Error: El DNI ya existe.';
                $msgType = 'danger';
            }
        }
    } elseif ($accion === 'editar') {
        $pdo->prepare("UPDATE empleados SET
            empleado_nombre=?, empleado_roll=?, empleado_email=?,
            empleado_telefono=?, empleado_direccion=?, empleado_localidad=?,
            empleado_estado=?, empleado_habilitado=?
            WHERE empleado_DNI=?")
            ->execute([$nombre, $rol, $email, $tel, $dir, $loc, $estado, $habilitado, $dni]);
        $msg = 'Empleado actualizado.';
    } elseif ($accion === 'cambiar_estado') {
        $nuevoEstado  = trim($_POST['nuevo_estado'] ?? '');
        $habilitadoV  = ($nuevoEstado === 'disponible') ? 1 : 1;
        $pdo->prepare("UPDATE empleados SET empleado_estado=?, empleado_habilitado=?
                       WHERE empleado_DNI=?")
            ->execute([$nuevoEstado, $habilitadoV, $dni]);
        $msg = 'Estado actualizado.';
    } elseif ($accion === 'reset_pass') {
        $newPass = trim($_POST['nueva_contrasena'] ?? '');
        if ($newPass) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE empleados SET empleado_contrasena=? WHERE empleado_DNI=?")
                ->execute([$hash, $dni]);
            $msg = 'Contraseña actualizada.';
        }
    }
}

$empleados = $pdo->query("SELECT * FROM empleados ORDER BY empleado_roll, empleado_nombre")->fetchAll();

$editEmp = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM empleados WHERE empleado_DNI=?");
    $stmt->execute([$_GET['editar']]);
    $editEmp = $stmt->fetch();
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
                <i class="bi bi-person-badge-fill"></i>
                <?= $editEmp ? 'Editar empleado' : 'Nuevo empleado' ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="<?= $editEmp ? 'editar' : 'crear' ?>">
                    <div class="mb-3">
                        <label class="form-label">DNI *</label>
                        <input type="text" name="empleado_DNI" class="form-control" maxlength="10"
                               value="<?= htmlspecialchars($editEmp['empleado_DNI'] ?? '') ?>"
                               <?= $editEmp ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" name="empleado_nombre" class="form-control" maxlength="50"
                               value="<?= htmlspecialchars($editEmp['empleado_nombre'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select name="empleado_roll" class="form-select" required>
                            <option value="">— Seleccionar —</option>
                            <option value="recepcionista" <?= ($editEmp['empleado_roll']??'') === 'recepcionista' ? 'selected':'' ?>>Recepcionista</option>
                            <option value="mecanico"      <?= ($editEmp['empleado_roll']??'') === 'mecanico'      ? 'selected':'' ?>>Mecánico</option>
                            <option value="gerente"       <?= ($editEmp['empleado_roll']??'') === 'gerente'       ? 'selected':'' ?>>Gerente</option>
                        </select>
                    </div>
                    <?php if (!$editEmp): ?>
                    <div class="mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="empleado_contrasena" class="form-control" required>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="empleado_email" class="form-control"
                               value="<?= htmlspecialchars($editEmp['empleado_email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="empleado_telefono" class="form-control" maxlength="15"
                               value="<?= htmlspecialchars($editEmp['empleado_telefono'] ?? '') ?>">
                    </div>
                    <?php if ($editEmp): ?>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="empleado_estado" class="form-select">
                            <option value="disponible"     <?= ($editEmp['empleado_estado']??'') === 'disponible'     ? 'selected':'' ?>>Disponible</option>
                            <option value="no_disponible"  <?= ($editEmp['empleado_estado']??'') === 'no_disponible'  ? 'selected':'' ?>>No disponible</option>
                            <option value="licencia"       <?= ($editEmp['empleado_estado']??'') === 'licencia'       ? 'selected':'' ?>>Licencia</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="empleado_habilitado"
                               id="habilitado" <?= $editEmp['empleado_habilitado'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="habilitado">Habilitado para ingresar</label>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-save me-1"></i>
                            <?= $editEmp ? 'Guardar cambios' : 'Registrar empleado' ?>
                        </button>
                        <?php if ($editEmp): ?>
                        <a href="empleados.php" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($editEmp): ?>
                <hr>
                <form method="POST">
                    <input type="hidden" name="accion" value="reset_pass">
                    <input type="hidden" name="empleado_DNI" value="<?= $editEmp['empleado_DNI'] ?>">
                    <label class="form-label fw-bold">Resetear contraseña</label>
                    <div class="input-group">
                        <input type="password" name="nueva_contrasena" class="form-control"
                               placeholder="Nueva contraseña" required>
                        <button type="submit" class="btn btn-warning btn-confirm"
                                data-confirm="¿Cambiar la contraseña de este empleado?">
                            <i class="bi bi-key"></i>
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabla empleados -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-people-fill"></i> Empleados registrados
                <span class="badge bg-secondary ms-auto"><?= count($empleados) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Habilitado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($empleados as $e): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($e['empleado_DNI']) ?></code></td>
                            <td><?= htmlspecialchars($e['empleado_nombre']) ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst($e['empleado_roll']) ?></span></td>
                            <td>
                                <span class="badge <?= $e['empleado_estado']==='disponible' ? 'badge-finalizado' : 'badge-pendiente' ?>">
                                    <?= ucfirst(str_replace('_',' ',$e['empleado_estado'])) ?>
                                </span>
                            </td>
                            <td>
                                <?= $e['empleado_habilitado']
                                    ? '<i class="bi bi-check-circle-fill text-success"></i>'
                                    : '<i class="bi bi-x-circle-fill text-danger"></i>' ?>
                            </td>
                            <td>
                                <a href="?editar=<?= urlencode($e['empleado_DNI']) ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
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
