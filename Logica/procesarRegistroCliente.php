<?php
// Procesa ALTA de CLIENTE
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
session_start();
session_regenerate_id(true);

if (hit_rate_limit('rl_reg_cliente', 10)) {
  $_SESSION['flash_error']='Estás enviando muy rápido. Intenta en unos segundos.';
  header("Location: ../registroCliente.php"); exit;
}

require_once __DIR__ . '/bootstrap_post.php';      // carga $mysqli y valida CSRF/POST
require_once __DIR__ . '/auth_helpers.php';        // helpers

// ---------- Inputs ----------
$nombre   = cap($_POST['nombre']   ?? '', 100);
$apellido = cap($_POST['apellido'] ?? '', 100);
$usuarioI = cap($_POST['usuario']  ?? '', 100);
$correo   = mb_strtolower(cap($_POST['correo'] ?? '', 100), 'UTF-8');
$tel      = cap($_POST['telefono'] ?? '', 8);
$dir      = cap($_POST['direccion']?? '', 150);
$pwd      = (string)($_POST['password']  ?? '');
$pwd2     = (string)($_POST['password2'] ?? '');

// ---------- Usuario estandarizado ----------
$usuario = sanitize_username_input($usuarioI, $nombre, $apellido);
if (!preg_match('/^[a-z]+(\.[a-z0-9]+)*$/', $usuario)) {
  $usuario = build_username_base($nombre, $apellido) ?: 'usuario';
}
$usuario = username_unico($mysqli, $usuario, 'cliente');

// ---------- Validaciones ----------
$err = [];
if ($nombre==='' || $apellido==='')                $err[]='1';
if (!filter_var($correo, FILTER_VALIDATE_EMAIL))   $err[]='2';
if (!preg_match('/^\d{8}$/', $tel))                $err[]='3';
if ($dir==='')                                     $err[]='4';
if ($pwd !== $pwd2)                                $err[]='5';
[$pwd_ok] = validar_password($pwd); if (!$pwd_ok)  $err[]='6';
if (correo_existe($mysqli, $correo, 'cliente'))    $err[]='7';

if ($err){
  $_SESSION['flash_error']='No se pudo completar el registro. Revisa los datos.';
  header("Location: ../registroCliente.php"); exit;
}

// ---------- Bloqueos lógicos para evitar colisión sin índices ----------
$lockU = "cli:usr:".$usuario;
$lockC = "cli:mail:".$correo;

if (!get_named_lock($mysqli, $lockU, 5)) { $_SESSION['flash_error']='Sistema ocupado (usuario). Intenta de nuevo.'; header("Location: ../registroCliente.php"); exit; }
if (!get_named_lock($mysqli, $lockC, 5)) { release_named_lock($mysqli,$lockU); $_SESSION['flash_error']='Sistema ocupado (correo). Intenta de nuevo.'; header("Location: ../registroCliente.php"); exit; }

// ---------- Transacción ----------
$mysqli->begin_transaction();
try {
  // Verificación final dentro de la transacción (doble seguro)
  $s=$mysqli->prepare("SELECT 1 FROM cliente WHERE Usuario=? OR Correo=? LIMIT 1");
  $s->bind_param('ss',$usuario,$correo); $s->execute(); $s->store_result();
  if ($s->num_rows>0){ $s->close(); throw new RuntimeException('Duplicado'); }
  $s->close();

  $exp = (new DateTime('+60 days'))->format('Y-m-d H:i:s');

  $sql = "INSERT INTO cliente
          (Nombre, Apellido, Usuario, Correo, Telefono, Direccion,
           EstadoCuenta, IntentosFallidos, password_expires_at, is_deleted)
          VALUES (?, ?, ?, ?, ?, ?, 'Activo', 0, ?, 0)";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param('sssssss', $nombre, $apellido, $usuario, $correo, $tel, $dir, $exp);
  $stmt->execute();
  $idCliente = $stmt->insert_id;
  $stmt->close();

  $hash = password_hash($pwd, PASSWORD_DEFAULT);
  $stmt = $mysqli->prepare("INSERT INTO password_history (user_type, user_id, PasswordHash)
                            VALUES ('cliente', ?, ?)");
  $stmt->bind_param('is', $idCliente, $hash);
  $stmt->execute();
  $stmt->close();

  $mysqli->commit();
  $_SESSION['flash_success']='Registro exitoso. Ya puedes iniciar sesión.';
  header("Location: ../registroCliente.php"); exit;

} catch (Throwable $e) {
  $mysqli->rollback();
  $_SESSION['flash_error']='No se pudo completar el registro.';
  header("Location: ../registroCliente.php"); exit;
} finally {
  release_named_lock($mysqli,$lockC);
  release_named_lock($mysqli,$lockU);
}
