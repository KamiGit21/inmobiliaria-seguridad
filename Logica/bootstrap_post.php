<?php
// Arranque común para handlers POST (economía y consistencia).
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
ini_set('session.use_strict_mode','1');
session_start();
session_regenerate_id(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('HTTP/1.1 405 Method Not Allowed'); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  $_SESSION['flash_error'] = 'CSRF inválido. Intenta nuevamente.'; header('Location: ../login.php'); exit;
}

require_once __DIR__.'/sql.php';
$mysqli = Conectarse();
if (!$mysqli) { $_SESSION['flash_error'] = 'Sin conexión a la base de datos.'; header('Location: ../login.php'); exit; }
$mysqli->set_charset('utf8mb4');
