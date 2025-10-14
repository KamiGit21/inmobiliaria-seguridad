<?php
include('sql.php'); 
session_start();

$conn = Conectarse(); 

if (!$conn) {
    $_SESSION['error'] = "Error interno: Fallo al conectar con la base de datos.";
    header("Location: login.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $conn->close(); // Cerrar conexión antes de salir
    header("Location: login.php");
    exit();
}

$usuario = $_POST['usuario'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($usuario) || empty($password)) {
    $_SESSION['error'] = "Debes ingresar tu usuario y contraseña.";
    $conn->close();
    header("Location: login.php");
    exit();
}

$user_found = null;
$user_type = null;

//Buscar en trabajador
$sql_trabajador = "SELECT idTrabajador AS id, Nombre, Apellido, Usuario, PasswordHash, idRol, EstadoCuenta FROM trabajador WHERE Usuario = ? AND is_deleted = 0";
$stmt_trabajador = $conn->prepare($sql_trabajador);

if ($stmt_trabajador) { // Siempre verifica si la preparación fue exitosa
    $stmt_trabajador->bind_param("s", $usuario);
    $stmt_trabajador->execute();
    $result_trabajador = $stmt_trabajador->get_result();

    if ($result_trabajador->num_rows > 0) {
        $user_found = $result_trabajador->fetch_assoc();
        $user_type = 'trabajador';
    }
    $stmt_trabajador->close();
}



//Buscar en cliente
if (!$user_found) {
    $sql_cliente = "SELECT idCliente AS id, Nombre, Apellido, Usuario, PasswordHash, EstadoCuenta FROM cliente WHERE Usuario = ? AND is_deleted = 0";
    $stmt_cliente = $conn->prepare($sql_cliente);

    if ($stmt_cliente) { // Siempre verifica si la preparación fue exitosa
        $stmt_cliente->bind_param("s", $usuario);
        $stmt_cliente->execute();
        $result_cliente = $stmt_cliente->get_result();

        if ($result_cliente->num_rows > 0) {
            $user_found = $result_cliente->fetch_assoc();
            $user_type = 'cliente';
        }
        $stmt_cliente->close();
    }
}

//Bloquear cuenta
if ($user_found) {
    if ($user_found['EstadoCuenta'] === 'Bloqueado') {
        $_SESSION['error'] = "Tu cuenta está bloqueada. Contacta al administrador.";
        $conn->close();
        header("Location: login.php");
        exit();
    }

    if (password_verify($password, $user_found['PasswordHash'])) {
        //Iniciar sesion
        $_SESSION['user_id'] = $user_found['id'];
        $_SESSION['username'] = $user_found['Usuario'];
        $_SESSION['nombre_completo'] = $user_found['Nombre'] . ' ' . $user_found['Apellido'];
        $_SESSION['user_type'] = $user_type;

        //Desbloquear usuario
        $update_login = "UPDATE {$user_type} SET last_login_at = NOW(), IntentosFallidos = 0 WHERE {$user_type}." . "id" . ucfirst($user_type) . " = ?";
        if ($stmt_update = $conn->prepare($update_login)) {
             $stmt_update->bind_param("i", $user_found['id']);
             $stmt_update->execute();
             $stmt_update->close();
        }

        $conn->close(); 
        header("Location: panelControl.php");
        exit();
    } else {
        //Aumentar intentos fallidos
        $update_intentos = "UPDATE {$user_type} SET IntentosFallidos = IntentosFallidos + 1 WHERE {$user_type}." . "id" . ucfirst($user_type) . " = ?";
        if ($stmt_update = $conn->prepare($update_intentos)) {
            $stmt_update->bind_param("i", $user_found['id']);
            $stmt_update->execute();
            $stmt_update->close();
        }

        $_SESSION['error'] = "Contraseña incorrecta.";
        $conn->close(); 
        header("Location: login.php");
        exit();
    }
} else {
    // Usuario no encontrado
    $_SESSION['error'] = "Usuario no encontrado.";
    $conn->close(); 
    header("Location: login.php");
    exit();
}
?>
