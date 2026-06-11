<?php
// controllers/ajax_vehiculos.php — Devuelve vehículos de un cliente en JSON
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');
$dni = trim($_GET['dni'] ?? '');
if ($dni === '') { echo '[]'; exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT vehiculo_patente, vehiculo_marca, vehiculo_modelo
                        FROM vehiculos WHERE cliente_DNI = ? ORDER BY vehiculo_patente");
$stmt->execute([$dni]);
echo json_encode($stmt->fetchAll());
