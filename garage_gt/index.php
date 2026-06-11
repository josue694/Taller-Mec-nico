<?php
// index.php — Punto de entrada principal
require_once __DIR__ . '/config/config.php';
header('Location: ' . BASE_URL . '/login.php');
exit;
