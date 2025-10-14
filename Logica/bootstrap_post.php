<?php
// Arranque común para handlers POST (economía y consistencia).
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
ini_set('session.use_strict_mode','1');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
session_regenerate_id(true);

// Permite que el caller defina adónde redirigir en error:
$__POST_REDIRECT = $GLOBALS['__POST_REDIRECT'] ?? '../login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('HTTP/1.1 405 Method Not Allowed'); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  $_SESSION['flash_error'] = 'CSRF inválido. Intenta nuevamente.';
  header("Location: {$__POST_REDIRECT}"); exit;
}

require_once __DIR__.'/sql.php';
$mysqli = Conectarse();
if (!$mysqli) {
  $_SESSION['flash_error'] = 'Sin conexión a la base de datos.';
  header("Location: {$__POST_REDIRECT}"); exit;
}
$mysqli->set_charset('utf8mb4');
