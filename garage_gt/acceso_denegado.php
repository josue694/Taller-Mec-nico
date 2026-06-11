<?php
// acceso_denegado.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body style="background:#f0f2f5;display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div style="text-align:center;max-width:400px;padding:40px;">
    <i class="bi bi-shield-x-fill" style="font-size:5rem;color:#e94560;"></i>
    <h2 style="margin:20px 0 10px;font-weight:800;color:#1a1a2e;">Acceso denegado</h2>
    <p style="color:#636e72;margin-bottom:24px;">
        No tienes permiso para acceder a esta sección.<br>
        Tu rol actual es: <strong><?= htmlspecialchars($_SESSION['empleado_roll'] ?? '—') ?></strong>
    </p>
    <a href="<?= BASE_URL ?>/controllers/redirigir_rol.php"
       style="background:#e94560;color:#fff;padding:10px 28px;border-radius:8px;
              text-decoration:none;font-weight:600;">
        <i class="bi bi-house-fill me-2"></i>Ir a mi panel
    </a>
</div>
</body>
</html>
