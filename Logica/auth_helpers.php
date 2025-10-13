<?php
// Helpers comunes para registro de identidades

function cap($s,$n){ return mb_substr(trim((string)$s),0,$n,'UTF-8'); }

function strip_accents($s){
  if (function_exists('transliterator_transliterate')){
    $t = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s);
    return str_replace(['ñ','Ñ'],'n', $t);
  }
  if (function_exists('iconv')){
    $t = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
    return str_replace(['ñ','Ñ'],'n', strtolower($t));
  }
  $map=['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','ñ'=>'n','Ñ'=>'n'];
  return strtr(mb_strtolower($s,'UTF-8'), $map);
}
function norm_words($s){
  $s = strip_accents($s);
  $s = preg_replace('/[^a-z0-9 ]/',' ', $s);
  return trim(preg_replace('/\s+/',' ', $s));
}

function build_username_base($nombre,$apellido){
  $n = explode(' ', norm_words($nombre));
  $a = explode(' ', norm_words($apellido));
  $first = preg_replace('/[^a-z]/','', $n[0] ?? '');
  $last  = preg_replace('/[^a-z]/','', (end($a) ?: ''));
  $u = ($first && $last) ? ($first.'.'.$last) : ($first ?: $last);
  $u = preg_replace('/\.+/','.', $u);
  return trim($u, '.');
}

function sanitize_username_input($user,$nombre,$apellido){
  $u = strip_accents(mb_strtolower($user,'UTF-8'));
  $u = preg_replace(['/[^a-z0-9\.]/','/\.+/'],['','.' ],$u);
  $u = trim($u,'.');
  if ($u==='' || !preg_match('/^[a-z]+(\.[a-z0-9]+)*$/',$u)) {
    $u = build_username_base($nombre,$apellido) ?: 'usuario';
  }
  return $u;
}

// Busca un username disponible agregando sufijo -i si hace falta (sin tocar esquema)
function username_unico($db,$user,$table){
  $base=$user; $i=0;
  $stmt = $db->prepare("SELECT 1 FROM {$table} WHERE Usuario=? LIMIT 1");
  while(true){
    $try = $i ? ($base.'-'.$i) : $base;
    $stmt->bind_param('s',$try); $stmt->execute(); $stmt->store_result();
    if ($stmt->num_rows===0){ $stmt->close(); return $try; }
    $stmt->free_result();
    $i++;
  }
}

// Política de contraseñas
function validar_password($pwd){
  $r=['len'=>strlen($pwd)>=12,'may'=>preg_match('/[A-Z]/',$pwd),'min'=>preg_match('/[a-z]/',$pwd),'num'=>preg_match('/\d/',$pwd),'sym'=>preg_match('/[^A-Za-z0-9]/',$pwd)];
  return [array_product(array_map(fn($v)=>$v?1:0,$r))===1,$r];
}

// ¿Correo existe? (consulta simple, sin índices únicos)
function correo_existe($db,$correo,$table){
  $s=$db->prepare("SELECT 1 FROM {$table} WHERE Correo=? LIMIT 1");
  $s->bind_param('s',$correo); $s->execute(); $s->store_result();
  $x=$s->num_rows>0; $s->close(); return $x;
}

// Correo corporativo empleado
function generar_correo_empleado($usuario, $dominio='droca.local'){
  return $usuario.'@'.$dominio;
}

// Rate limit por sesión (clave reutilizable)
function hit_rate_limit($key, $seconds=10){
  $now = time();
  if (!empty($_SESSION[$key]) && $now - $_SESSION[$key] < $seconds) return true;
  $_SESSION[$key] = $now; return false;
}

/**
 * Bloqueo lógico con GET_LOCK para evitar colisiones de Usuario/Correo
 * No requiere cambios de esquema.
 * Devuelve true si tomó el lock, false si no.
 */
function get_named_lock($db, $name, $timeout=5){
  $sql="SELECT GET_LOCK(?, ?)";
  $s=$db->prepare($sql); $s->bind_param('si',$name,$timeout); $s->execute(); $s->bind_result($ok);
  $s->fetch(); $s->close(); return (int)$ok===1;
}
function release_named_lock($db, $name){
  $sql="SELECT RELEASE_LOCK(?)";
  $s=$db->prepare($sql); $s->bind_param('s',$name); $s->execute(); $s->close();
}
