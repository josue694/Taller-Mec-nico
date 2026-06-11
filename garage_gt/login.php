<?php
// ============================================================
// login.php — Punto de entrada del sistema
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Si ya hay sesión activa, redirigir al dashboard correspondiente
if (!empty($_SESSION['empleado_roll'])) {
    header('Location: ' . BASE_URL . '/controllers/redirigir_rol.php');
    exit;
}

$error   = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni  = trim($_POST['dni']  ?? '');
    $pass = trim($_POST['pass'] ?? '');

    if ($dni === '' || $pass === '') {
        $error = 'Ingresa tu DNI y contraseña.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM empleados WHERE empleado_DNI = ? AND empleado_habilitado = 1 LIMIT 1");
        $stmt->execute([$dni]);
        $emp = $stmt->fetch();

        if ($emp && password_verify($pass, $emp['empleado_contrasena'])) {
            session_regenerate_id(true);
            $_SESSION['empleado_DNI']    = $emp['empleado_DNI'];
            $_SESSION['empleado_nombre'] = $emp['empleado_nombre'];
            $_SESSION['empleado_roll']   = $emp['empleado_roll'];
            $_SESSION['last_activity']   = time();
            header('Location: ' . BASE_URL . '/controllers/redirigir_rol.php');
            exit;
        } else {
            $error = 'DNI o contraseña incorrectos, o usuario deshabilitado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= APP_NAME ?> — Iniciar sesión</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <i class="bi bi-wrench-adjustable-circle-fill"></i>
            <h2><?= APP_NAME ?></h2>
            <p>Sistema de Gestión de Taller Mecánico</p>
        </div>

        <?php if ($timeout): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-clock me-2"></i>Tu sesión expiró por inactividad.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="dni" class="form-label">DNI / Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" id="dni" name="dni" class="form-control"
                           placeholder="Ingresa tu DNI" required maxlength="10"
                           value="<?= htmlspecialchars($_POST['dni'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-4">
                <label for="pass" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" id="pass" name="pass" class="form-control"
                           placeholder="Ingresa tu contraseña" required>
                    <button type="button" class="btn btn-outline-secondary" id="togglePass">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
            </button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePass').addEventListener('click', function(){
    const inp = document.getElementById('pass');
    const icon = this.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>
</body>
</html>
