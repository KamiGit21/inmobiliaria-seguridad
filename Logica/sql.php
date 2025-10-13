<?php
/* ========= Config ========= */
define('DB_HOST',    '127.0.0.1');   // evita resolución DNS
define('DB_USER',    'root');
define('DB_PASS',    '');            // ajusta si usas contraseña
define('DB_NAME',    'droca');
define('DB_PORT',    3307);          // <-- puerto solicitado
define('DB_CHARSET', 'utf8mb4');

/* ========= Núcleo de conexión ========= */
function Conectarse()
{
    $mysqli = mysqli_init();

    // Opcional: timeouts más amables
    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

    if (!$mysqli->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT)) {
        // Retorna 0 para mantener compat. con tu código existente
        return 0;
    }

    // Charset seguro
    if (!$mysqli->set_charset(DB_CHARSET)) {
        // Si falla el charset, cerramos y devolvemos 0 como haces tú
        $mysqli->close();
        return 0;
    }
    return $mysqli;
}

/* ========= Helpers internos (no cambian lógica externa) ========= */
function _fetchAllAssoc(mysqli $cx, string $sql, ?array $params = null, ?string $types = null): array
{
    if ($params && $types) {
        $stmt = $cx->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) { $stmt->close(); return []; }
        $res = $stmt->get_result();
        $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    } else {
        $res = $cx->query($sql);
        if (!$res) return [];
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $res->free();
        return $data;
    }
}

function _execStmt(mysqli $cx, string $sql, string $types, array $params): array
{
    $stmt = $cx->prepare($sql);
    if (!$stmt) return [false, "Error al preparar: {$cx->error}", null];

    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    $msg = $ok ? null : $stmt->error;
    $insertId = $ok ? $stmt->insert_id : null;
    $stmt->close();
    return [$ok, $msg, $insertId];
}

/* ========= Zonas / Tipos ========= */
function obtenerZonas()
{
    $cx = Conectarse();
    if (!$cx) return [];
    // Mantengo el nombre de tabla tal como lo tenías aquí
    $zonas = _fetchAllAssoc($cx, "SELECT * FROM zonas;");
    $cx->close();
    return $zonas;
}

function obtenerTiposVivienda()
{
    $cx = Conectarse();
    if (!$cx) return [];
    $tipos = _fetchAllAssoc($cx, "SELECT * FROM tipovivienda;");
    $cx->close();
    return $tipos;
}

function obtenerTiposOferta()
{
    $cx = Conectarse();
    if (!$cx) return [];
    $tipos = _fetchAllAssoc($cx, "SELECT * FROM tipooferta;");
    $cx->close();
    return $tipos;
}

/* ========= Vivienda ========= */
function insertarVivienda($direccion, $montoPedido, $zona, $tipoVivienda, $tipoOferta)
{
    $cx = Conectarse();
    if (!$cx) { die("Error de conexión a la base de datos"); }

    $sql = "INSERT INTO Vivienda (
                Direccion, MontoPedido, Vendido, Zonas_idZona, TipoVivienda_idTipoV, TipoOferta_idTipoO
            ) VALUES (?, ?, FALSE, ?, ?, ?)";

    // Mantengo tipos como los tenías (siiii). Si MontoPedido fuera decimal, cambia a 'sdiii'.
    [$ok, $err] = _execStmt($cx, $sql, 'siiii', [
        $direccion,
        (int)$montoPedido,
        (int)$zona,
        (int)$tipoVivienda,
        (int)$tipoOferta
    ]);

    echo $ok ? "Registro insertado correctamente" : "Error al insertar el registro: $err";
    $cx->close();
}

function obtenerViviendasDisponibles()
{
    $cx = Conectarse();
    if (!$cx) return [];

    $sql = "
        SELECT 
            V.idVivienda,
            V.Direccion,
            V.MontoPedido,
            V.Vendido,
            Z.Zona,
            TV.Vivienda AS TipoVivienda,
            TOF.Oferta AS TipoOferta
        FROM Vivienda V
        JOIN Zonas Z       ON V.Zonas_idZona        = Z.idZona
        JOIN TipoVivienda TV ON V.TipoVivienda_idTipoV = TV.idTipoV
        JOIN TipoOferta TOF  ON V.TipoOferta_idTipoO  = TOF.idTipoO
        WHERE V.Vendido = FALSE;
    ";

    $rows = _fetchAllAssoc($cx, $sql);
    $cx->close();
    return $rows;
}

/* ========= Trabajador ========= */
// Nombre original mal escrito conservado para compatibilidad:
function obtenerTabajadores()
{
    return _obtenerTrabajadores();
}

function _obtenerTrabajadores()
{
    $cx = Conectarse();
    if (!$cx) return [];
    $rows = _fetchAllAssoc($cx, "SELECT * FROM trabajador;");
    $cx->close();
    return $rows;
}

function insertarTrabajador($nombre, $telefono, $correo)
{
    $cx = Conectarse();
    if (!$cx) { die("Error de conexión a la base de datos"); }

    $sql = "INSERT INTO Trabajador (nombre, telefono, correo) VALUES (?, ?, ?)";
    [$ok, $err] = _execStmt($cx, $sql, 'sss', [$nombre, $telefono, $correo]);

    echo $ok ? "Registro insertado correctamente" : "Error al insertar el registro: $err";
    $cx->close();
}

function obtenerTabajadoresPorId($idTrabajador) // Se conserva el nombre original
{
    $cx = Conectarse();
    if (!$cx) return null;

    $sql = "
        SELECT T.idTrabajador, T.nombre, T.telefono, T.correo
        FROM Trabajador T
        WHERE T.idTrabajador = ?";

    $rows = _fetchAllAssoc($cx, $sql, [(int)$idTrabajador], 'i');
    $cx->close();
    return $rows ? $rows[0] : null;
}

/* ========= Cliente / Cita ========= */
function insertarCliente($nombre, $telefono, $correo)
{
    $cx = Conectarse();
    if (!$cx) { die("Error de conexión a la base de datos"); }

    $sql = "INSERT INTO Cliente (Nombre, Telefono, Correo) VALUES (?, ?, ?)";
    [$ok, $err, $insertId] = _execStmt($cx, $sql, 'sss', [$nombre, $telefono, $correo]);

    if ($ok) {
        $cx->close();
        return $insertId;
    } else {
        echo "Error al insertar el cliente: $err";
        $cx->close();
        return null;
    }
}

function insertarCita($fechaVisita, $horaInicio, $horaFin, $idVivienda, $idCliente, $estado)
{
    $cx = Conectarse();
    if (!$cx) { die("Error de conexión a la base de datos"); }

    $sql = "INSERT INTO Cita (
                FechaVisita, HoraInicio, HoraFin, esTrato, Vivienda_idVivienda, 
                Cliente_idCliente, Estado_idEstado, Trabajador_idTrabajador, MontoOfrecido, FechaTrato
            ) VALUES (?, ?, ?, FALSE, ?, ?, ?, 1, -1, NULL)";

    [$ok, $err] = _execStmt($cx, $sql, 'sssiii', [
        $fechaVisita, $horaInicio, $horaFin,
        (int)$idVivienda, (int)$idCliente, (int)$estado
    ]);

    echo $ok ? "Cita registrada correctamente." : "Error al registrar la cita: $err";
    $cx->close();
}

function obtenerCitasPorTelefono($telefono)
{
    $cx = Conectarse();
    if (!$cx) { die("Error de conexión a la base de datos"); }

    $sql = "
        SELECT 
            C.FechaVisita, C.HoraInicio, C.HoraFin, E.Estado,
            TV.Vivienda AS TipoVivienda, Z.Zona,
            V.MontoPedido AS Monto, TOF.Oferta AS TipoOferta, C.idCita
        FROM Cita C
        JOIN Cliente CL     ON C.Cliente_idCliente      = CL.idCliente
        JOIN Estado E       ON C.Estado_idEstado        = E.idEstado
        JOIN Vivienda V     ON C.Vivienda_idVivienda    = V.idVivienda
        JOIN TipoVivienda TV ON V.TipoVivienda_idTipoV   = TV.idTipoV
        JOIN Zonas Z        ON V.Zonas_idZona           = Z.idZona
        JOIN TipoOferta TOF ON V.TipoOferta_idTipoO     = TOF.idTipoO
        WHERE CL.Telefono = ?
          AND C.Estado_idEstado != 2;
    ";

    $rows = _fetchAllAssoc($cx, $sql, [$telefono], 's');
    $cx->close();
    return $rows;
}

function cancelarCita($idCita)
{
    $cx = Conectarse();
    if (!$cx) { die("Error de conexión a la base de datos"); }

    $sql = "UPDATE Cita SET Estado_idEstado = 2 WHERE idCita = ?";
    [$ok, $err] = _execStmt($cx, $sql, 'i', [(int)$idCita]);

    echo $ok ? "Cita cancelada correctamente." : "Error al cancelar la cita: $err";
    $cx->close();
}

/* ========= Consultas por ID ========= */
function obtenerInmueblePorId($idInmueble)
{
    $cx = Conectarse();
    if (!$cx) return null;

    $sql = "
        SELECT V.idVivienda, V.Direccion, V.MontoPedido, 
               V.Zonas_idZona, V.TipoVivienda_idTipoV, V.TipoOferta_idTipoO
        FROM Vivienda V
        WHERE V.idVivienda = ?";

    $rows = _fetchAllAssoc($cx, $sql, [(int)$idInmueble], 'i');
    $cx->close();
    return $rows ? $rows[0] : null;
}

function obtenerCitasVigentes()
{
    $cx = Conectarse();
    if (!$cx) return [];

    $sql = "
        SELECT 
            C.idCita, C.FechaVisita, C.HoraInicio, C.HoraFin,
            CL.Nombre AS NombreCliente, CL.Telefono AS TelefonoCliente, CL.Correo AS CorreoCliente,
            V.Direccion AS DireccionVivienda, E.Estado
        FROM Cita C
        JOIN Cliente CL ON C.Cliente_idCliente = CL.idCliente
        JOIN Vivienda V ON C.Vivienda_idVivienda = V.idVivienda
        JOIN Estado E   ON C.Estado_idEstado    = E.idEstado
    ";

    $rows = _fetchAllAssoc($cx, $sql);
    $cx->close();
    return $rows;
}
?>
