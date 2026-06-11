<?php
// views/recepcionista/turnos.php — RF-05: Crear orden de trabajo
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['recepcionista', 'gerente']);
$pageTitle = 'Turnos y Órdenes de Servicio';
$pdo = getDB();
$msg = '';
$msgType = 'success';

// ── Controlador: asignar_turno.php equivalente ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion       = $_POST['accion'] ?? '';
    $dni          = trim($_POST['cliente_DNI'] ?? '');
    $patente      = strtoupper(trim($_POST['vehiculo_patente'] ?? ''));
    $mecanico     = trim($_POST['mecanico_dni'] ?? '');
    $fecha        = $_POST['turno_fecha'] ?? '';
    $hora         = $_POST['turno_hora']  ?? '';
    $comentario   = trim($_POST['turno_comentario'] ?? '');
    $servicioCode = trim($_POST['servicio_codigo'] ?? '');
    $km           = (int)($_POST['orden_kilometros'] ?? 0);

    if ($accion === 'crear_turno') {
        if (!$dni || !$patente || !$mecanico || !$fecha || !$hora) {
            $msg = 'Completa todos los campos obligatorios.';
            $msgType = 'danger';
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Crear turno
                $stmtT = $pdo->prepare("INSERT INTO turnos
                    (turno_fecha, turno_hora, cliente_DNI, vehiculo_patente, mecanico_dni, turno_estado, turno_comentario)
                    VALUES (?,?,?,?,?,'pendiente',?)");
                $stmtT->execute([$fecha, $hora, $dni, $patente, $mecanico, $comentario]);
                $turnoId = $pdo->lastInsertId();

                // 2. Crear orden
                $stmtO = $pdo->prepare("INSERT INTO ordenes
                    (orden_fecha, vehiculo_patente, orden_costo)
                    VALUES (?,?,0)");
                $stmtO->execute([date('Y-m-d'), $patente]);
                $ordenNum = $pdo->lastInsertId();

                // 3. Crear orden_trabajo si se seleccionó servicio
                if ($servicioCode) {
                    $srv = $pdo->prepare("SELECT servicio_costo FROM servicios WHERE servicio_codigo=?");
                    $srv->execute([$servicioCode]);
                    $costo = $srv->fetchColumn() ?: 0;

                    $pdo->prepare("INSERT INTO orden_trabajo
                        (orden_numero, servicio_codigo, costo_ajustado, orden_kilometros,
                         orden_comentario, orden_estado, mecanico_DNI, turno_id)
                        VALUES (?,?,?,?,?,0,?,?)")
                        ->execute([$ordenNum, $servicioCode, $costo, $km, $comentario, $mecanico, $turnoId]);

                    // Actualizar costo en orden
                    $pdo->prepare("UPDATE ordenes SET orden_costo=? WHERE orden_numero=?")
                        ->execute([$costo, $ordenNum]);
                }

                $pdo->commit();
                $msg = "Turno #$turnoId creado exitosamente. Orden de trabajo #$ordenNum generada.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $msg = 'Error al crear el turno: ' . $e->getMessage();
                $msgType = 'danger';
            }
        }
    } elseif ($accion === 'cancelar_turno') {
        $turnoId = (int)$_POST['turno_id'];
        $pdo->prepare("UPDATE turnos SET turno_estado='cancelado' WHERE turno_id=?")
            ->execute([$turnoId]);
        $msg = 'Turno cancelado.';
        $msgType = 'warning';
    }
}

// ── Datos para formulario ─────────────────────────────────────
$clientes  = $pdo->query("SELECT cliente_DNI, cliente_nombre FROM clientes ORDER BY cliente_nombre")->fetchAll();
$mecanicos = $pdo->query("SELECT empleado_DNI, empleado_nombre FROM empleados
                          WHERE empleado_roll='mecanico' AND empleado_habilitado=1
                          AND empleado_estado='disponible' ORDER BY empleado_nombre")->fetchAll();
$servicios = $pdo->query("SELECT servicio_codigo, servicio_nombre, servicio_costo FROM servicios
                          WHERE servicio_disponible=1 ORDER BY servicio_nombre")->fetchAll();

// Vehículos del cliente seleccionado (para búsqueda AJAX)
$vehiculos = [];
if (!empty($_GET['dni_cliente'])) {
    $stmt = $pdo->prepare("SELECT vehiculo_patente, vehiculo_marca, vehiculo_modelo
                           FROM vehiculos WHERE cliente_DNI=?");
    $stmt->execute([$_GET['dni_cliente']]);
    $vehiculos = $stmt->fetchAll();
}

// Lista de turnos
$turnos = $pdo->query("
    SELECT t.*, c.cliente_nombre,
           CONCAT(v.vehiculo_marca,' ',v.vehiculo_modelo) AS vehiculo,
           e.empleado_nombre AS mecanico_nombre
    FROM turnos t
    JOIN clientes  c ON t.cliente_DNI      = c.cliente_DNI
    JOIN vehiculos v ON t.vehiculo_patente = v.vehiculo_patente
    JOIN empleados e ON t.mecanico_dni     = e.empleado_DNI
    ORDER BY t.turno_fecha DESC, t.turno_hora DESC
    LIMIT 50
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Formulario nuevo turno -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-plus"></i> Nueva orden de servicio
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formTurno">
                    <input type="hidden" name="accion" value="crear_turno">

                    <div class="mb-3">
                        <label class="form-label">Cliente *</label>
                        <select name="cliente_DNI" id="selectCliente" class="form-select" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['cliente_DNI'] ?>">
                                <?= htmlspecialchars($c['cliente_nombre']) ?> (<?= $c['cliente_DNI'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Patente del vehículo *</label>
                        <div class="input-group">
                            <input type="text" name="vehiculo_patente" id="inputPatente"
                                   class="form-control" style="text-transform:uppercase"
                                   placeholder="Ej: ABC123" required maxlength="10">
                            <button type="button" class="btn btn-outline-secondary" id="btnBuscarVehiculo"
                                    data-bs-toggle="tooltip" title="Buscar vehículos del cliente">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div id="vehiculosCliente" class="mt-1"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mecánico disponible *</label>
                        <select name="mecanico_dni" class="form-select" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($mecanicos as $m): ?>
                            <option value="<?= $m['empleado_DNI'] ?>">
                                <?= htmlspecialchars($m['empleado_nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($mecanicos)): ?>
                        <small class="text-danger">No hay mecánicos disponibles actualmente.</small>
                        <?php endif; ?>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label">Fecha *</label>
                            <input type="date" name="turno_fecha" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Hora *</label>
                            <input type="time" name="turno_hora" class="form-control"
                                   value="<?= date('H:i') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Servicio inicial</label>
                        <select name="servicio_codigo" class="form-select">
                            <option value="">— Sin servicio asignado —</option>
                            <?php foreach ($servicios as $s): ?>
                            <option value="<?= $s['servicio_codigo'] ?>">
                                <?= htmlspecialchars($s['servicio_nombre']) ?>
                                — Q<?= number_format($s['servicio_costo'], 2) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kilometraje</label>
                        <input type="number" name="orden_kilometros" class="form-control" min="0" placeholder="Km">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Detalle de falla / comentario</label>
                        <textarea name="turno_comentario" class="form-control" rows="3"
                                  placeholder="Describe el problema reportado por el cliente..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-1"></i> Crear orden de servicio
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista de turnos -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-check"></i> Turnos registrados
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha / Hora</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Mecánico</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($turnos as $t): ?>
                            <tr>
                                <td><?= $t['turno_id'] ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($t['turno_fecha'])) ?><br>
                                    <small class="text-muted"><?= substr($t['turno_hora'],0,5) ?></small>
                                </td>
                                <td><?= htmlspecialchars($t['cliente_nombre']) ?></td>
                                <td>
                                    <?= htmlspecialchars($t['vehiculo']) ?><br>
                                    <small><code><?= htmlspecialchars($t['vehiculo_patente']) ?></code></small>
                                </td>
                                <td><?= htmlspecialchars($t['mecanico_nombre']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $t['turno_estado'] ?>">
                                        <?= ucfirst(str_replace('_',' ',$t['turno_estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($t['turno_estado'] === 'pendiente'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="accion" value="cancelar_turno">
                                        <input type="hidden" name="turno_id" value="<?= $t['turno_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-confirm"
                                                data-confirm="¿Cancelar este turno?">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($turnos)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No hay turnos registrados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Buscar vehículos del cliente seleccionado
$('#btnBuscarVehiculo').on('click', function() {
    const dni = $('#selectCliente').val();
    if (!dni) { alert('Selecciona un cliente primero.'); return; }
    $.getJSON('<?= BASE_URL ?>/controllers/ajax_vehiculos.php', { dni: dni }, function(data) {
        let html = '';
        if (data.length) {
            html = '<div class="list-group mt-1">';
            data.forEach(v => {
                html += `<a href="#" class="list-group-item list-group-item-action py-1 px-2 select-patente"
                            data-patente="${v.vehiculo_patente}">
                           <code>${v.vehiculo_patente}</code> — ${v.vehiculo_marca} ${v.vehiculo_modelo}
                         </a>`;
            });
            html += '</div>';
        } else {
            html = '<small class="text-muted">Este cliente no tiene vehículos registrados.</small>';
        }
        $('#vehiculosCliente').html(html);
    });
});

$(document).on('click', '.select-patente', function(e) {
    e.preventDefault();
    $('#inputPatente').val($(this).data('patente'));
    $('#vehiculosCliente').html('');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
