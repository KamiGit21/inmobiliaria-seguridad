<?php
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
session_start();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
session_regenerate_id(true);

$CSP_NONCE = base64_encode(random_bytes(16));
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: interest-cohort=()");
header("Content-Security-Policy: ".
  "default-src 'self'; img-src 'self' data:; ".
  "style-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net 'unsafe-inline'; ".
  "font-src https://fonts.gstatic.com; ".
  "script-src 'self' https://cdn.jsdelivr.net 'nonce-{$CSP_NONCE}';"
);

// Cargar catálogos (cargos/roles) para selects
require_once __DIR__.'/Logica/sql.php';
$mysqli = Conectarse(); 
$mysqli->set_charset('utf8mb4');
$cargos = $mysqli->query("SELECT idCargo, NombreCargo FROM cargo ORDER BY NombreCargo")->fetch_all(MYSQLI_ASSOC);
$roles  = $mysqli->query("SELECT idRol, NombreRol FROM rol ORDER BY NombreRol")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Alta de Trabajador</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--brand:#4b41d9} 
*{font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif}
.card-lite{border:1px solid #eee;border-radius:16px}
</style>
</head>
<body class="bg-light">
<header class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="panelControl.php">Panel</a>
  </div>
</header>

<main class="container my-5" style="max-width:880px">
  <div class="card card-lite p-4 p-md-5 mx-auto">
    <h1 class="h3 fw-bold text-center mb-3">Alta de Trabajador</h1>

    <!-- NUEVO SISTEMA DE MENSAJES (sin ?success/error) -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php elseif (!empty($_SESSION['flash_success'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <form method="post" action="Logica/procesarRegistroEmpleado.php" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre</label>
          <input name="nombre" maxlength="100" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Apellido</label>
          <input name="apellido" maxlength="100" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Usuario (estándar)</label>
          <input id="usuario" name="usuario" class="form-control" readonly>
          <small class="text-muted">Se genera como nombre.apellido (único).</small>
        </div>
        <div class="col-md-6">
          <label class="form-label">Correo corporativo</label>
          <!-- Eliminado name="correo" -->
          <input id="correo" class="form-control" readonly>
          <small class="text-muted">@droca.local</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">Teléfono (8 dígitos)</label>
          <input name="telefono" pattern="\d{8}" maxlength="8" class="form-control" required>
        </div>
        <div class="col-12">
          <label class="form-label">Dirección</label>
          <input name="direccion" maxlength="150" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Cargo</label>
          <select name="idCargo" class="form-select">
            <option value="">— Opcional —</option>
            <?php foreach($cargos as $c): ?>
              <option value="<?= (int)$c['idCargo'] ?>"><?= htmlspecialchars($c['NombreCargo']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Rol</label>
          <select name="idRol" class="form-select">
            <option value="">— Opcional —</option>
            <?php foreach($roles as $r): ?>
              <option value="<?= (int)$r['idRol'] ?>"><?= htmlspecialchars($r['NombreRol']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Contraseña temporal</label>
          <input name="password" id="password" type="password" class="form-control" required>
          <small class="text-muted">Debe cumplir la política. Se forzará cambio al primer ingreso.</small>
        </div>
        <div class="col-12">
          <label class="form-label">Confirmar contraseña</label>
          <input name="password2" id="password2" type="password" class="form-control" required>
        </div>

        <div class="col-12">
          <button class="btn btn-primary w-100">Crear Trabajador</button>
        </div>
      </div>
    </form>
  </div>
</main>

<script nonce="<?= $CSP_NONCE ?>">
function toASCII(s){return s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/ñ/gi,'n')}
function norm(s){return toASCII(s).toLowerCase().replace(/[^a-z0-9 ]/g,' ').trim().replace(/\s+/g,' ')}
const nombre=document.querySelector('input[name="nombre"]');
const apellido=document.querySelector('input[name="apellido"]');
const usuario=document.getElementById('usuario');
const correo=document.getElementById('correo');
function sugerir(){
  const n=(norm(nombre.value).split(' ')[0]||'').replace(/[^a-z]/g,'');
  const a=(norm(apellido.value).split(' ').slice(-1)[0]||'').replace(/[^a-z]/g,'');
  const u = (n&&a) ? `${n}.${a}` : (n||a||'usuario');
  usuario.value = u;
  correo.value  = `${u}@droca.local`;
}
nombre.addEventListener('input',sugerir);
apellido.addEventListener('input',sugerir);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" nonce="<?= $CSP_NONCE ?>"></script>
</body>
</html>
