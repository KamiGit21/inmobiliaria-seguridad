-- Crear base (si no existe) y usarla
CREATE DATABASE IF NOT EXISTS droca
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE droca;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS password_history;
DROP TABLE IF EXISTS cita;
DROP TABLE IF EXISTS trabajador;
DROP TABLE IF EXISTS cliente;
DROP TABLE IF EXISTS vivienda;
DROP TABLE IF EXISTS tipooferta;
DROP TABLE IF EXISTS tipovivienda;
DROP TABLE IF EXISTS zonas;
DROP TABLE IF EXISTS estado;
DROP TABLE IF EXISTS rol_permiso;
DROP TABLE IF EXISTS permiso;
DROP TABLE IF EXISTS rol;
DROP TABLE IF EXISTS cargo;
DROP TABLE IF EXISTS area;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================
-- Núcleo de seguridad
-- =========================
CREATE TABLE area (
  idArea INT AUTO_INCREMENT PRIMARY KEY,
  NombreArea VARCHAR(100) NOT NULL,
  Descripcion TEXT,
  UNIQUE KEY uq_area_nombre (NombreArea)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cargo (
  idCargo INT AUTO_INCREMENT PRIMARY KEY,
  NombreCargo VARCHAR(100) NOT NULL,
  Descripcion TEXT,
  idArea INT NULL,
  UNIQUE KEY uq_cargo_nombre (NombreCargo),
  CONSTRAINT fk_cargo_area
    FOREIGN KEY (idArea) REFERENCES area(idArea)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rol (
  idRol INT AUTO_INCREMENT PRIMARY KEY,
  NombreRol VARCHAR(50) NOT NULL,
  Descripcion TEXT,
  UNIQUE KEY uq_rol_nombre (NombreRol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permiso (
  idPermiso INT AUTO_INCREMENT PRIMARY KEY,
  Modulo VARCHAR(100) NOT NULL,
  Accion ENUM('ver','crear','editar','eliminar','bloquear') NOT NULL,
  UNIQUE KEY uq_permiso (Modulo, Accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rol_permiso (
  idRol INT NOT NULL,
  idPermiso INT NOT NULL,
  PRIMARY KEY (idRol, idPermiso),
  CONSTRAINT fk_rp_rol
    FOREIGN KEY (idRol) REFERENCES rol(idRol)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_rp_permiso
    FOREIGN KEY (idPermiso) REFERENCES permiso(idPermiso)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Tablas maestras del negocio
-- =========================
CREATE TABLE estado (
  idEstado INT(11) NOT NULL AUTO_INCREMENT,
  Estado VARCHAR(100) NOT NULL,
  PRIMARY KEY (idEstado),
  UNIQUE KEY uq_estado (Estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE zonas (
  idZona INT(11) NOT NULL AUTO_INCREMENT,
  Zona VARCHAR(100) NOT NULL,
  PRIMARY KEY (idZona),
  UNIQUE KEY uq_zona (Zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tipovivienda (
  idTipoV INT(11) NOT NULL AUTO_INCREMENT,
  Vivienda VARCHAR(100) NOT NULL,
  PRIMARY KEY (idTipoV),
  UNIQUE KEY uq_tipovivienda (Vivienda)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tipooferta (
  idTipoO INT(11) NOT NULL AUTO_INCREMENT,
  Oferta VARCHAR(100) NOT NULL,
  PRIMARY KEY (idTipoO),
  UNIQUE KEY uq_tipooferta (Oferta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE vivienda (
  idVivienda INT(11) NOT NULL AUTO_INCREMENT,
  Direccion VARCHAR(150) NOT NULL,
  MontoPedido INT(11) NOT NULL,
  Vendido TINYINT(1) NOT NULL DEFAULT 0,
  Zonas_idZona INT(11) NOT NULL,
  TipoVivienda_idTipoV INT(11) NOT NULL,
  TipoOferta_idTipoO INT(11) NOT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idVivienda),
  KEY idx_vivienda_zona (Zonas_idZona),
  KEY idx_vivienda_tipov (TipoVivienda_idTipoV),
  KEY idx_vivienda_tipoo (TipoOferta_idTipoO),
  CONSTRAINT fk_vivienda_zona
    FOREIGN KEY (Zonas_idZona) REFERENCES zonas(idZona)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_vivienda_tipovivienda
    FOREIGN KEY (TipoVivienda_idTipoV) REFERENCES tipovivienda(idTipoV)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_vivienda_tipooferta
    FOREIGN KEY (TipoOferta_idTipoO) REFERENCES tipooferta(idTipoO)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Identidades (sin contraseñas en claro)
-- =========================
CREATE TABLE trabajador (
  idTrabajador INT(11) NOT NULL AUTO_INCREMENT,
  Nombre VARCHAR(100) NOT NULL,
  Apellido VARCHAR(100) NOT NULL,
  Usuario VARCHAR(100) NOT NULL,
  Telefono CHAR(8) NOT NULL,
  Correo VARCHAR(100) NOT NULL,
  idCargo INT NULL,
  idRol INT NULL,
  EstadoCuenta ENUM('Activo','Bloqueado') NOT NULL DEFAULT 'Activo',
  IntentosFallidos INT NOT NULL DEFAULT 0,
  locked_at DATETIME NULL,
  last_login_at DATETIME NULL,
  password_expires_at DATETIME NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idTrabajador),
  UNIQUE KEY uq_trabajador_usuario (Usuario),
  UNIQUE KEY uq_trabajador_correo (Correo),
  KEY idx_trabajador_cargo (idCargo),
  KEY idx_trabajador_rol (idRol),
  CONSTRAINT fk_trabajador_cargo
    FOREIGN KEY (idCargo) REFERENCES cargo(idCargo)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_trabajador_rol
    FOREIGN KEY (idRol) REFERENCES rol(idRol)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cliente (
  idCliente INT(11) NOT NULL AUTO_INCREMENT,
  Nombre VARCHAR(100) NOT NULL,
  Apellido VARCHAR(100) NOT NULL,
  Usuario VARCHAR(100) NOT NULL,
  Correo VARCHAR(100) NOT NULL,
  Telefono CHAR(8) NOT NULL,
  Direccion VARCHAR(150) NOT NULL,
  EstadoCuenta ENUM('Activo','Bloqueado') NOT NULL DEFAULT 'Activo',
  IntentosFallidos INT NOT NULL DEFAULT 0,
  locked_at DATETIME NULL,
  last_login_at DATETIME NULL,
  password_expires_at DATETIME NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idCliente),
  UNIQUE KEY uq_cliente_usuario (Usuario),
  UNIQUE KEY uq_cliente_correo (Correo),
  KEY idx_cliente_estado (EstadoCuenta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Operación (citas)
-- =========================
CREATE TABLE cita (
  idCita INT(11) NOT NULL AUTO_INCREMENT,
  FechaVisita DATE NOT NULL,
  FechaTrato DATE DEFAULT NULL,
  HoraInicio TIME NOT NULL,
  HoraFin TIME NOT NULL,
  esTrato TINYINT(1) DEFAULT NULL,
  MontoOfrecido DECIMAL(50,5) DEFAULT NULL,
  Trabajador_idTrabajador INT(11) DEFAULT NULL,
  Estado_idEstado INT(11) NOT NULL,
  Vivienda_idVivienda INT(11) NOT NULL,
  Cliente_idCliente INT(11) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idCita),
  KEY idx_cita_cliente (Cliente_idCliente),
  KEY idx_cita_estado (Estado_idEstado),
  KEY idx_cita_trab (Trabajador_idTrabajador),
  KEY idx_cita_viv (Vivienda_idVivienda),
  CONSTRAINT fk_cita_cliente
    FOREIGN KEY (Cliente_idCliente) REFERENCES cliente(idCliente)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_cita_estado
    FOREIGN KEY (Estado_idEstado) REFERENCES estado(idEstado)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_cita_trabajador
    FOREIGN KEY (Trabajador_idTrabajador) REFERENCES trabajador(idTrabajador)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_cita_vivienda
    FOREIGN KEY (Vivienda_idVivienda) REFERENCES vivienda(idVivienda)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- Histórico de contraseñas (solo HASH)
-- =========================
CREATE TABLE password_history (
  idHistorial INT AUTO_INCREMENT PRIMARY KEY,
  user_type ENUM('trabajador','cliente') NOT NULL,
  user_id INT NOT NULL,
  PasswordHash VARCHAR(255) NOT NULL,
  FechaCambio DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pwdhist_user (user_type, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
