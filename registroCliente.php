<?php
// ---- Sesión segura + CSRF ----
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
session_start();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
session_regenerate_id(true);

// ---- CSP con NONCE para permitir <script> inline seguro ----
$CSP_NONCE = base64_encode(random_bytes(16));
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: interest-cohort=()");
header(
  "Content-Security-Policy: ".
  "default-src 'self'; ".
  "img-src 'self' data:; ".
  "style-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net 'unsafe-inline'; ".
  "font-src https://fonts.gstatic.com; ".
  "script-src 'self' https://cdn.jsdelivr.net 'nonce-{$CSP_NONCE}';"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crear Cuenta de Cliente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--brand:#4b41d9}
    *{font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif}
    .card-lite{border:1px solid #eee;border-radius:16px}
    .pw-meter{height:6px;border-radius:6px;background:#eee;overflow:hidden}
    .pw-meter>span{display:block;height:100%;width:0;background:var(--brand);transition:width .2s}
    .req-list li{margin:.15rem 0}.req-bad{color:#b42318}.req-ok{color:#16794f}
  </style>
</head>
<body class="bg-light">

<header class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
      <img src="images/Logo.png" height="28" class="me-2" alt="Logo">InmobiliariaModerna
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Menú">
      <span class="navbar-toggler-icon"></span>
    </button>
    <nav id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="inmuebles.php">Propiedades</a></li>
        <li class="nav-item"><a class="btn btn-outline-primary" href="login.php">Iniciar Sesión</a></li>
      </ul>
    </nav>
  </div>
</header>

<main class="container my-5" style="max-width:880px">
  <div class="card card-lite p-4 p-md-5 mx-auto">
    <h1 class="h3 fw-bold text-center mb-2">Crear Cuenta de Cliente</h1>
    <p class="text-center text-muted mb-4">Regístrate para explorar propiedades y gestionar tus citas.</p>

    <!-- NUEVO SISTEMA DE MENSAJES (sin ?success=1 ni scripts extra) -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger"><strong>Ups:</strong> <?= htmlspecialchars($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php elseif (!empty($_SESSION['flash_success'])): ?>
      <div class="alert alert-success" id="msg-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- IMPORTANTE: action actualizado a procesarRegistroCliente.php -->
    <form method="post" action="Logica/procesarRegistroCliente.php" novalidate autocomplete="off">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre</label>
          <input name="nombre" maxlength="100" class="form-control" placeholder="Tu nombre" required autocomplete="given-name">
        </div>
        <div class="col-md-6">
          <label class="form-label">Apellido</label>
          <input name="apellido" maxlength="100" class="form-control" placeholder="Tu apellido" required autocomplete="family-name">
        </div>

        <div class="col-12">
          <label class="form-label">Usuario Sugerido</label>
          <input name="usuario" id="usuario" maxlength="100" class="form-control" placeholder="usuario.unico (editable)" autocapitalize="none" autocomplete="username">
          <small class="text-muted">Generado automáticamente (puedes editarlo si deseas).</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">Correo Electrónico</label>
          <input name="correo" type="email" maxlength="100" class="form-control" placeholder="tucorreo@ejemplo.com" required autocomplete="email" inputmode="email">
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono (8 dígitos)</label>
          <input name="telefono" pattern="\d{8}" maxlength="8" class="form-control" placeholder="12345678" required inputmode="numeric" autocomplete="tel">
        </div>

        <div class="col-12">
          <label class="form-label">Dirección</label>
          <input name="direccion" maxlength="150" class="form-control" placeholder="Calle, número, ciudad" required autocomplete="street-address">
        </div>

        <div class="col-12">
          <label class="form-label">Contraseña</label>
          <input name="password" id="password" type="password" class="form-control" placeholder="Crea una contraseña segura" required autocomplete="new-password">
          <div class="d-flex align-items-center mt-2 gap-2">
            <div class="pw-meter flex-grow-1"><span id="pwBar"></span></div>
            <small id="pwLabel" class="text-muted">Débil</small>
          </div>
          <ul class="req-list small mt-2 text-muted" aria-live="polite">
            <li id="req_len" class="req-bad">Al menos 12 caracteres</li>
            <li id="req_may" class="req-bad">Una letra mayúscula</li>
            <li id="req_min" class="req-bad">Una letra minúscula</li>
            <li id="req_num" class="req-bad">Un número</li>
            <li id="req_sym" class="req-bad">Un símbolo</li>
          </ul>
        </div>

        <div class="col-12">
          <label class="form-label">Confirmar Contraseña</label>
          <input name="password2" id="password2" type="password" class="form-control" placeholder="Confirma tu contraseña" required autocomplete="new-password">
        </div>

        <div class="col-12">
          <button class="btn btn-primary w-100 py-2">Registrarse</button>
        </div>
      </div>
    </form>

    <p class="text-center mt-3 mb-0">¿Ya tienes una cuenta? <a href="login.php">Iniciar Sesión</a></p>
  </div>
</main>

<footer class="border-top py-4">
  <div class="container d-flex justify-content-between">
    <span class="text-muted">© <?= date('Y') ?> InmobiliariaModerna</span>
    <span class="text-muted">Recursos · Compañía</span>
  </div>
</footer>

<script nonce="<?= $CSP_NONCE ?>">
// --- Generación automática y dinámica del usuario ---
const nombre = document.querySelector('input[name="nombre"]');
const apellido = document.querySelector('input[name="apellido"]');
const usuario = document.getElementById('usuario');
let usuarioModificado = false;

function toASCII(s){ return s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/ñ/gi,'n'); }
function norm(s){ return toASCII(s).toLowerCase().replace(/[^a-z0-9 ]/g,' ').trim().replace(/\s+/g,' '); }

function sugerirUsuario(){
  if(usuarioModificado) return; // no sobrescribas si el usuario edita
  const n = (norm(nombre.value).split(' ')[0] || '').replace(/[^a-z]/g, '');
  const a = (norm(apellido.value).split(' ').slice(-1)[0] || '').replace(/[^a-z]/g, '');
  usuario.value = n && a ? `${n}.${a}` : (n || a || '');
}
nombre.addEventListener('input', sugerirUsuario);
apellido.addEventListener('input', sugerirUsuario);
usuario.addEventListener('input', ()=>usuarioModificado = true);

// --- Medidor de contraseña ---
const pwd=document.getElementById('password'), pwBar=document.getElementById('pwBar'), pwLabel=document.getElementById('pwLabel');
const reqs={len:rq('req_len'),may:rq('req_may'),min:rq('req_min'),num:rq('req_num'),sym:rq('req_sym')};
function rq(id){return document.getElementById(id)}
function evalPwd(v){
  const r={len:v.length>=12,may:/[A-Z]/.test(v),min:/[a-z]/.test(v),num:/\d/.test(v),sym:/[^A-Za-z0-9]/.test(v)};
  let s=Object.values(r).filter(Boolean).length;
  pwBar.style.width=(s*20)+'%';
  pwLabel.textContent=['Muy débil','Débil','Media','Buena','Fuerte','Excelente'][s];
  for(const k in r){reqs[k].className=r[k]?'req-ok':'req-bad';}
}
pwd.addEventListener('input', e=>evalPwd(e.target.value));
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" nonce="<?= $CSP_NONCE ?>"></script>
</body>
</html>
