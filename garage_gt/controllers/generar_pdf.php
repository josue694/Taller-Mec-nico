<?php
// controllers/generar_pdf.php — RF-07: Exportar comprobante en PDF
// Usa mPDF si está disponible, sino genera HTML imprimible como fallback

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$facturaId = (int)($_GET['factura_id'] ?? 0);
if (!$facturaId) {
    die('ID de factura inválido.');
}

$pdo = getDB();

// Cargar datos completos de la factura
$stmt = $pdo->prepare("
    SELECT f.*,
           c.cliente_nombre, c.cliente_DNI, c.cliente_direccion,
           c.cliente_localidad, c.cliente_telefono, c.cliente_email,
           o.orden_fecha, o.vehiculo_patente,
           v.vehiculo_marca, v.vehiculo_modelo, v.vehiculo_anio, v.vehiculo_color,
           e.empleado_nombre AS emisor_nombre
    FROM facturas f
    JOIN clientes  c ON f.cliente_dni     = c.cliente_DNI
    JOIN ordenes   o ON f.orden_numero    = o.orden_numero
    JOIN vehiculos v ON o.vehiculo_patente= v.vehiculo_patente
    JOIN empleados e ON f.empleado_emisor = e.empleado_DNI
    WHERE f.factura_id = ?
");
$stmt->execute([$facturaId]);
$factura = $stmt->fetch();

if (!$factura) {
    die('Factura no encontrada.');
}

// Cargar servicios de la orden
$servicios = $pdo->prepare("
    SELECT ot.costo_ajustado, ot.complejidad, ot.orden_kilometros,
           s.servicio_nombre, s.servicio_descripcion,
           e.empleado_nombre AS mecanico_nombre
    FROM orden_trabajo ot
    JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
    JOIN empleados e ON ot.mecanico_DNI    = e.empleado_DNI
    WHERE ot.orden_numero = ?
");
$servicios->execute([$factura['orden_numero']]);
$serviciosData = $servicios->fetchAll();

// Cargar repuestos de la orden
$repuestos = $pdo->prepare("
    SELECT op.prod_codigo, op.prod_descripcion, op.cantidad, op.precio_unitario,
           (op.cantidad * op.precio_unitario) AS subtotal
    FROM orden_productos op
    WHERE op.orden_numero = ?
");
$repuestos->execute([$factura['orden_numero']]);
$repuestosData = $repuestos->fetchAll();

// Calcular totales
$subtotalMO  = array_sum(array_column($serviciosData, 'costo_ajustado'));
$subtotalRep = array_sum(array_column($repuestosData, 'subtotal'));
$total       = $subtotalMO + $subtotalRep;

// Número formateado
$nroFormato = $factura['tipo'] . '-' . str_pad($factura['nro_comprobante'], 8, '0', STR_PAD_LEFT);

// Guardar nombre del PDF en BD si aún no tiene
if (empty($factura['pdf_nombre'])) {
    $pdfNombre = 'factura_' . $nroFormato . '_' . date('Ymd') . '.pdf';
    $pdo->prepare("UPDATE facturas SET pdf_nombre=? WHERE factura_id=?")
        ->execute([$pdfNombre, $facturaId]);
}

// ── Intentar usar mPDF si está instalado ──────────────────────
$mPDFAvailable = class_exists('\Mpdf\Mpdf') ||
                 file_exists(__DIR__ . '/../vendor/autoload.php');

if ($mPDFAvailable && file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    ob_start();
}

// ── HTML del comprobante ──────────────────────────────────────
$html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura ' . $nroFormato . '</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #222; background: #fff; }
  .page { max-width: 800px; margin: 0 auto; padding: 30px; }

  /* Header */
  .header { display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 3px solid #1a1a2e; padding-bottom: 16px; margin-bottom: 20px; }
  .brand h1 { font-size: 22px; color: #1a1a2e; font-weight: 900; }
  .brand p  { font-size: 11px; color: #666; margin-top: 2px; }
  .comprobante { text-align: right; }
  .comprobante .tipo-badge {
    display: inline-block; background: #1a1a2e; color: #fff;
    padding: 4px 14px; border-radius: 4px; font-size: 14px; font-weight: 700;
    letter-spacing: 1px; margin-bottom: 6px;
  }
  .comprobante .nro { font-size: 18px; font-weight: 800; color: #e94560; }
  .comprobante .fecha { font-size: 11px; color: #666; }

  /* Info boxes */
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
  .info-box { border: 1px solid #ddd; border-radius: 6px; padding: 12px; }
  .info-box h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
                 color: #999; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
  .info-box p { margin-bottom: 3px; line-height: 1.5; }
  .info-box strong { color: #1a1a2e; }

  /* Tabla detalle */
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  thead th { background: #1a1a2e; color: #fff; padding: 8px 10px;
             font-size: 11px; text-align: left; }
  thead th.right { text-align: right; }
  tbody td { padding: 7px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr:nth-child(even) { background: #f9f9f9; }
  td.right { text-align: right; }

  /* Sección label */
  .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase;
                   letter-spacing: 1px; color: #666; margin: 16px 0 6px;
                   padding: 4px 10px; background: #f4f4f4; border-left: 3px solid #e94560; }

  /* Totales */
  .totales { display: flex; justify-content: flex-end; margin-top: 10px; }
  .totales-box { width: 280px; }
  .totales-row { display: flex; justify-content: space-between;
                 padding: 5px 0; border-bottom: 1px solid #eee; font-size: 12px; }
  .totales-row.grand { font-size: 15px; font-weight: 800; color: #e94560;
                       border-top: 2px solid #1a1a2e; border-bottom: none; padding-top: 8px; }

  /* Footer */
  .footer { margin-top: 30px; padding-top: 14px; border-top: 1px solid #ddd;
            display: flex; justify-content: space-between; font-size: 10px; color: #999; }
  .footer .emisor strong { color: #444; }

  /* Botones (solo pantalla) */
  .no-print { text-align: center; margin: 20px 0; }
  .btn-print { background: #1a1a2e; color: #fff; border: none; padding: 10px 28px;
               border-radius: 6px; font-size: 14px; cursor: pointer; margin: 0 6px; }
  .btn-back  { background: #e94560; color: #fff; border: none; padding: 10px 28px;
               border-radius: 6px; font-size: 14px; cursor: pointer; margin: 0 6px; }

  @media print {
    .no-print { display: none !important; }
    body { background: #fff; }
    .page { padding: 10px; }
  }
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
  <button class="btn-back"  onclick="history.back()">← Volver</button>
</div>

<div class="page">

  <!-- ENCABEZADO -->
  <div class="header">
    <div class="brand">
      <h1>⚙ Taller Mecánico</h1>
      <p>Sistema de Gestión de Taller Mecánico</p>
      <p>Guatemala</p>
    </div>
    <div class="comprobante">
      <div class="tipo-badge">COMPROBANTE TIPO ' . htmlspecialchars($factura['tipo']) . '</div>
      <div class="nro">' . htmlspecialchars($nroFormato) . '</div>
      <div class="fecha">Emitido: ' . date('d/m/Y H:i', strtotime($factura['fecha_emision'])) . '</div>
    </div>
  </div>

  <!-- INFO CLIENTE / VEHÍCULO -->
  <div class="info-grid">
    <div class="info-box">
      <h4>Datos del cliente</h4>
      <p><strong>' . htmlspecialchars($factura['cliente_nombre']) . '</strong></p>
      <p>DNI: ' . htmlspecialchars($factura['cliente_DNI']) . '</p>
      ' . ($factura['cliente_direccion'] ? '<p>' . htmlspecialchars($factura['cliente_direccion']) . ', ' . htmlspecialchars($factura['cliente_localidad'] ?? '') . '</p>' : '') . '
      ' . ($factura['cliente_telefono'] ? '<p>Tel: ' . htmlspecialchars($factura['cliente_telefono']) . '</p>' : '') . '
      ' . ($factura['cliente_email'] ? '<p>Email: ' . htmlspecialchars($factura['cliente_email']) . '</p>' : '') . '
    </div>
    <div class="info-box">
      <h4>Datos del vehículo</h4>
      <p><strong>Patente: ' . htmlspecialchars($factura['vehiculo_patente']) . '</strong></p>
      <p>' . htmlspecialchars($factura['vehiculo_marca'] . ' ' . $factura['vehiculo_modelo']) . '</p>
      ' . ($factura['vehiculo_anio'] ? '<p>Año: ' . htmlspecialchars($factura['vehiculo_anio']) . '</p>' : '') . '
      ' . ($factura['vehiculo_color'] ? '<p>Color: ' . htmlspecialchars($factura['vehiculo_color']) . '</p>' : '') . '
      <p>Orden de trabajo: #' . $factura['orden_numero'] . '</p>
    </div>
  </div>';

// SERVICIOS / MANO DE OBRA
if (!empty($serviciosData)) {
    $html .= '<div class="section-label">Mano de obra / Servicios</div>
  <table>
    <thead>
      <tr>
        <th>Servicio</th>
        <th>Mecánico</th>
        <th>Complejidad</th>
        <th class="right">Costo</th>
      </tr>
    </thead>
    <tbody>';
    foreach ($serviciosData as $s) {
        $html .= '<tr>
          <td>
            <strong>' . htmlspecialchars($s['servicio_nombre']) . '</strong>
            ' . ($s['orden_kilometros'] ? '<br><small>Km: ' . number_format($s['orden_kilometros']) . '</small>' : '') . '
          </td>
          <td>' . htmlspecialchars($s['mecanico_nombre']) . '</td>
          <td>' . $s['complejidad'] . '</td>
          <td class="right">Q ' . number_format($s['costo_ajustado'], 2) . '</td>
        </tr>';
    }
    $html .= '</tbody></table>';
}

// REPUESTOS
if (!empty($repuestosData)) {
    $html .= '<div class="section-label">Repuestos y materiales</div>
  <table>
    <thead>
      <tr>
        <th>Código</th>
        <th>Descripción</th>
        <th class="right">Cant.</th>
        <th class="right">P. Unit.</th>
        <th class="right">Subtotal</th>
      </tr>
    </thead>
    <tbody>';
    foreach ($repuestosData as $r) {
        $html .= '<tr>
          <td><code>' . htmlspecialchars($r['prod_codigo'] ?? '—') . '</code></td>
          <td>' . htmlspecialchars($r['prod_descripcion']) . '</td>
          <td class="right">' . number_format($r['cantidad'], 2) . '</td>
          <td class="right">Q ' . number_format($r['precio_unitario'], 2) . '</td>
          <td class="right">Q ' . number_format($r['subtotal'], 2) . '</td>
        </tr>';
    }
    $html .= '</tbody></table>';
}

// TOTALES
$html .= '
  <div class="totales">
    <div class="totales-box">
      <div class="totales-row">
        <span>Subtotal mano de obra</span>
        <span>Q ' . number_format($subtotalMO, 2) . '</span>
      </div>
      <div class="totales-row">
        <span>Subtotal repuestos</span>
        <span>Q ' . number_format($subtotalRep, 2) . '</span>
      </div>
      <div class="totales-row grand">
        <span>TOTAL</span>
        <span>Q ' . number_format($total, 2) . '</span>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <div class="footer">
    <div class="emisor">
      Emitido por: <strong>' . htmlspecialchars($factura['emisor_nombre']) . '</strong>
    </div>
    <div>
      Comprobante generado el ' . date('d/m/Y H:i') . '
    </div>
    <div>
      Taller Mecánico — Sistema de Gestión
    </div>
  </div>

</div><!-- /page -->
</body>
</html>';

// ── Intentar generar PDF con mPDF ─────────────────────────────
if ($mPDFAvailable && file_exists(__DIR__ . '/../vendor/autoload.php')) {
    ob_end_clean();
    try {
        $mpdf = new \Mpdf\Mpdf([
            'margin_top'    => 10,
            'margin_bottom' => 10,
            'margin_left'   => 10,
            'margin_right'  => 10,
        ]);
        $mpdf->WriteHTML($html);
        $pdfNombre = 'factura_' . $nroFormato . '_' . date('Ymd') . '.pdf';
        $ruta = UPLOAD_PATH . $pdfNombre;
        $mpdf->Output($ruta, 'F');
        $pdo->prepare("UPDATE facturas SET pdf_nombre=? WHERE factura_id=?")
            ->execute([$pdfNombre, $facturaId]);
        $mpdf->Output($pdfNombre, 'D'); // Descargar
        exit;
    } catch (\Exception $e) {
        // Fallback a HTML
    }
}

// ── Fallback: HTML imprimible en navegador ─────────────────────
echo $html;
