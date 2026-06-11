<?php
// views/gerente/dashboard.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRol(['gerente']);
$pageTitle = 'Panel de Gerencia';
$pdo = getDB();

// Estadísticas generales
$totalClientes   = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$totalVehiculos  = $pdo->query("SELECT COUNT(*) FROM vehiculos")->fetchColumn();
$totalEmpleados  = $pdo->query("SELECT COUNT(*) FROM empleados WHERE empleado_habilitado=1")->fetchColumn();
$mecanicosDisp   = $pdo->query("SELECT COUNT(*) FROM empleados WHERE empleado_roll='mecanico' AND empleado_estado='disponible' AND empleado_habilitado=1")->fetchColumn();
$ordenesPend     = $pdo->query("SELECT COUNT(*) FROM orden_trabajo WHERE orden_estado=0")->fetchColumn();
$ordenesFin      = $pdo->query("SELECT COUNT(*) FROM orden_trabajo WHERE orden_estado=1")->fetchColumn();
$facturacionMes  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE MONTH(fecha_emision)=MONTH(CURDATE()) AND YEAR(fecha_emision)=YEAR(CURDATE())")->fetchColumn();
$stockCritico    = $pdo->query("SELECT COUNT(*) FROM productos WHERE prod_stock <= 5 AND prod_disponible=1")->fetchColumn();

// Últimas órdenes
$ultimasOrdenes = $pdo->query("
    SELECT ot.orden_numero, ot.orden_estado, ot.costo_ajustado,
           o.orden_fecha, o.vehiculo_patente,
           v.vehiculo_marca, v.vehiculo_modelo,
           c.cliente_nombre,
           s.servicio_nombre,
           e.empleado_nombre AS mecanico_nombre
    FROM orden_trabajo ot
    JOIN ordenes   o  ON ot.orden_numero    = o.orden_numero
    JOIN vehiculos v  ON o.vehiculo_patente = v.vehiculo_patente
    JOIN clientes  c  ON v.cliente_DNI      = c.cliente_DNI
    JOIN servicios s  ON ot.servicio_codigo = s.servicio_codigo
    JOIN empleados e  ON ot.mecanico_DNI    = e.empleado_DNI
    ORDER BY ot.orden_numero DESC LIMIT 8
")->fetchAll();

// Facturación últimos 6 meses
$facturacionMeses = $pdo->query("
    SELECT DATE_FORMAT(fecha_emision,'%b %Y') AS mes,
           YEAR(fecha_emision) AS anio,
           MONTH(fecha_emision) AS nmes,
           COALESCE(SUM(total),0) AS total
    FROM facturas
    WHERE fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(fecha_emision), MONTH(fecha_emision)
    ORDER BY anio, nmes
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div><div class="stat-value"><?= $totalClientes ?></div><div class="stat-label">Clientes</div></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card green">
            <div class="stat-icon"><i class="bi bi-car-front-fill"></i></div>
            <div><div class="stat-value"><?= $totalVehiculos ?></div><div class="stat-label">Vehículos</div></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card orange">
            <div class="stat-icon"><i class="bi bi-tools"></i></div>
            <div><div class="stat-value"><?= $ordenesPend ?></div><div class="stat-label">Órdenes pendientes</div></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <div><div class="stat-value">Q<?= number_format($facturacionMes, 0) ?></div><div class="stat-label">Facturado este mes</div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <i class="bi bi-person-badge-fill" style="font-size:2rem;color:#0984e3"></i>
            <div class="fw-bold fs-4"><?= $totalEmpleados ?></div>
            <small class="text-muted">Empleados activos</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <i class="bi bi-wrench" style="font-size:2rem;color:#00b894"></i>
            <div class="fw-bold fs-4"><?= $mecanicosDisp ?></div>
            <small class="text-muted">Mecánicos disponibles</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <i class="bi bi-check-circle-fill" style="font-size:2rem;color:#00b894"></i>
            <div class="fw-bold fs-4"><?= $ordenesFin ?></div>
            <small class="text-muted">Órdenes finalizadas</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <i class="bi bi-exclamation-triangle-fill" style="font-size:2rem;color:<?= $stockCritico > 0 ? '#e17055' : '#00b894' ?>"></i>
            <div class="fw-bold fs-4 <?= $stockCritico > 0 ? 'text-danger' : '' ?>"><?= $stockCritico ?></div>
            <small class="text-muted">Productos stock crítico</small>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Gráfica facturación -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart-fill"></i> Facturación últimos 6 meses</div>
            <div class="card-body">
                <canvas id="chartFacturacion" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- Últimas órdenes -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-check"></i> Últimas órdenes</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>#</th><th>Cliente</th><th>Servicio</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($ultimasOrdenes as $o): ?>
                        <tr>
                            <td><?= $o['orden_numero'] ?></td>
                            <td><?= htmlspecialchars(substr($o['cliente_nombre'], 0, 14)) ?></td>
                            <td><?= htmlspecialchars(substr($o['servicio_nombre'], 0, 16)) ?></td>
                            <td>
                                <span class="badge <?= $o['orden_estado'] ? 'badge-finalizado' : 'badge-pendiente' ?>">
                                    <?= $o['orden_estado'] ? 'Listo' : 'Pend.' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ultimasOrdenes)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Sin órdenes aún.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_column($facturacionMeses, 'mes')) ?>;
const datos  = <?= json_encode(array_map('floatval', array_column($facturacionMeses, 'total'))) ?>;

new Chart(document.getElementById('chartFacturacion'), {
    type: 'bar',
    data: {
        labels: labels.length ? labels : ['Sin datos'],
        datasets: [{
            label: 'Facturación (Q)',
            data: datos.length ? datos : [0],
            backgroundColor: 'rgba(233,69,96,.7)',
            borderColor: '#e94560',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => 'Q' + v.toLocaleString() } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
