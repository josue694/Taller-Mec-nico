<?php
// controllers/logout.php
require_once __DIR__ . '/../config/config.php';
session_unset();
session_destroy();
$timeout = isset($_GET['timeout']) ? '?timeout=1' : '';
header('Location: ' . BASE_URL . '/login.php' . $timeout);
exit;
