-- ============================================================
-- BASE DE DATOS: garage_gt
-- Sistema de Gestión de Taller Mecánico
-- ============================================================

CREATE DATABASE IF NOT EXISTS garage_gt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE garage_gt;

-- ------------------------------------------------------------
-- TABLA: clientes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes (
    cliente_DNI         VARCHAR(10)  NOT NULL,
    cliente_contrasena  VARCHAR(255) NOT NULL,
    cliente_nombre      VARCHAR(50)  NOT NULL,
    cliente_direccion   VARCHAR(50)  DEFAULT NULL,
    cliente_localidad   VARCHAR(15)  DEFAULT NULL,
    cliente_telefono    VARCHAR(15)  DEFAULT NULL,
    cliente_email       VARCHAR(255) DEFAULT NULL,
    token_recuperacion  VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (cliente_DNI)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: empleados
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS empleados (
    empleado_DNI        VARCHAR(10)  NOT NULL,
    empleado_contrasena VARCHAR(255) NOT NULL,
    empleado_nombre     VARCHAR(50)  NOT NULL,
    empleado_roll       VARCHAR(255) NOT NULL COMMENT 'recepcionista | mecanico | gerente',
    empleado_email      TEXT         DEFAULT NULL,
    token_recuperacion  VARCHAR(255) DEFAULT NULL,
    empleado_direccion  VARCHAR(50)  DEFAULT NULL,
    empleado_localidad  VARCHAR(15)  DEFAULT NULL,
    empleado_telefono   VARCHAR(15)  DEFAULT NULL,
    empleado_habilitado TINYINT(1)   NOT NULL DEFAULT 1,
    empleado_estado     VARCHAR(50)  DEFAULT 'disponible' COMMENT 'disponible | no_disponible | licencia',
    licencia_desde      DATE         DEFAULT NULL,
    licencia_hasta      DATE         DEFAULT NULL,
    PRIMARY KEY (empleado_DNI)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: vehiculos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vehiculos (
    vehiculo_patente VARCHAR(10) NOT NULL,
    cliente_DNI      VARCHAR(10) NOT NULL,
    vehiculo_marca   VARCHAR(10) DEFAULT NULL,
    vehiculo_modelo  VARCHAR(10) DEFAULT NULL,
    vehiculo_anio    VARCHAR(4)  DEFAULT NULL,
    vehiculo_color   VARCHAR(10) DEFAULT NULL,
    vehiculo_motor   VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (vehiculo_patente),
    CONSTRAINT fk_vehiculo_cliente FOREIGN KEY (cliente_DNI) REFERENCES clientes(cliente_DNI)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: turnos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS turnos (
    turno_id         INT(11)     NOT NULL AUTO_INCREMENT,
    turno_fecha      DATE        NOT NULL,
    turno_hora       TIME        NOT NULL,
    cliente_DNI      CHAR(8)     NOT NULL,
    vehiculo_patente VARCHAR(10) NOT NULL,
    mecanico_dni     CHAR(8)     NOT NULL,
    turno_estado     VARCHAR(50) DEFAULT 'pendiente' COMMENT 'pendiente | en_proceso | finalizado | cancelado',
    turno_comentario TEXT        DEFAULT NULL,
    PRIMARY KEY (turno_id),
    CONSTRAINT fk_turno_cliente  FOREIGN KEY (cliente_DNI)      REFERENCES clientes(cliente_DNI),
    CONSTRAINT fk_turno_vehiculo FOREIGN KEY (vehiculo_patente) REFERENCES vehiculos(vehiculo_patente),
    CONSTRAINT fk_turno_mecanico FOREIGN KEY (mecanico_dni)     REFERENCES empleados(empleado_DNI)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: ordenes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ordenes (
    orden_numero     INT(11)      NOT NULL AUTO_INCREMENT,
    orden_fecha      VARCHAR(255) DEFAULT NULL,
    vehiculo_patente VARCHAR(10)  NOT NULL,
    orden_costo      DECIMAL(8,2) DEFAULT 0.00,
    PRIMARY KEY (orden_numero),
    CONSTRAINT fk_orden_vehiculo FOREIGN KEY (vehiculo_patente) REFERENCES vehiculos(vehiculo_patente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: servicios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS servicios (
    servicio_codigo      VARCHAR(5)   NOT NULL,
    servicio_nombre      VARCHAR(35)  NOT NULL,
    servicio_descripcion VARCHAR(100) DEFAULT NULL,
    servicio_costo       DECIMAL(8,2) DEFAULT 0.00,
    servicio_disponible  TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (servicio_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: productos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
    prod_id              INT(11)       NOT NULL AUTO_INCREMENT,
    prod_codigo          VARCHAR(20)   DEFAULT NULL,
    prod_categoria       VARCHAR(100)  DEFAULT NULL,
    prod_descripcion     VARCHAR(255)  DEFAULT NULL,
    prod_stock           INT(11)       DEFAULT 0,
    prod_precio_proveedor DECIMAL(10,2) DEFAULT 0.00,
    prod_precio_venta    DECIMAL(10,2) DEFAULT 0.00,
    prod_disponible      TINYINT(1)    NOT NULL DEFAULT 1,
    PRIMARY KEY (prod_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: facturas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS facturas (
    factura_id       INT(11)       NOT NULL AUTO_INCREMENT,
    tipo             VARCHAR(10)   DEFAULT NULL COMMENT 'A | B | C',
    nro_comprobante  INT(11)       DEFAULT NULL,
    fecha_emision    DATETIME      DEFAULT NULL,
    orden_numero     INT(11)       DEFAULT NULL,
    servicio_codigo  VARCHAR(5)    DEFAULT NULL,
    cliente_dni      VARCHAR(10)   DEFAULT NULL,
    vehiculo_patente VARCHAR(10)   DEFAULT NULL,
    total            DECIMAL(10,2) DEFAULT 0.00,
    pdf_nombre       VARCHAR(255)  DEFAULT NULL,
    email_destino    VARCHAR(255)  DEFAULT NULL,
    email_enviado    TINYINT(1)    DEFAULT 0,
    empleado_emisor  VARCHAR(10)   DEFAULT NULL,
    PRIMARY KEY (factura_id),
    CONSTRAINT fk_factura_orden   FOREIGN KEY (orden_numero)    REFERENCES ordenes(orden_numero),
    CONSTRAINT fk_factura_cliente FOREIGN KEY (cliente_dni)     REFERENCES clientes(cliente_DNI),
    CONSTRAINT fk_factura_emisor  FOREIGN KEY (empleado_emisor) REFERENCES empleados(empleado_DNI)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: orden_trabajo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orden_trabajo (
    orden_numero     INT(11)       NOT NULL,
    servicio_codigo  VARCHAR(5)    NOT NULL,
    complejidad      INT(11)       DEFAULT 1,
    costo_ajustado   DECIMAL(8,2)  DEFAULT 0.00,
    orden_kilometros INT(10)       DEFAULT NULL,
    orden_comentario VARCHAR(255)  DEFAULT NULL,
    orden_estado     TINYINT(1)    DEFAULT 0 COMMENT '0=pendiente 1=finalizado',
    mecanico_DNI     VARCHAR(15)   DEFAULT NULL,
    turno_id         INT(11)       DEFAULT NULL,
    factura_id       INT(11)       DEFAULT NULL,
    PRIMARY KEY (orden_numero, servicio_codigo),
    CONSTRAINT fk_ot_orden    FOREIGN KEY (orden_numero)    REFERENCES ordenes(orden_numero),
    CONSTRAINT fk_ot_servicio FOREIGN KEY (servicio_codigo) REFERENCES servicios(servicio_codigo),
    CONSTRAINT fk_ot_mecanico FOREIGN KEY (mecanico_DNI)    REFERENCES empleados(empleado_DNI),
    CONSTRAINT fk_ot_turno    FOREIGN KEY (turno_id)        REFERENCES turnos(turno_id),
    CONSTRAINT fk_ot_factura  FOREIGN KEY (factura_id)      REFERENCES facturas(factura_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: orden_productos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orden_productos (
    id               INT(11)       NOT NULL AUTO_INCREMENT,
    orden_numero     INT(11)       NOT NULL,
    prod_id          INT(11)       DEFAULT NULL,
    prod_codigo      VARCHAR(32)   DEFAULT NULL,
    prod_descripcion VARCHAR(255)  DEFAULT NULL,
    cantidad         DECIMAL(10,2) DEFAULT 0.00,
    precio_unitario  DECIMAL(12,2) DEFAULT 0.00,
    mecanico_DNI     VARCHAR(20)   DEFAULT NULL,
    creado_en        DATETIME      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_op_orden    FOREIGN KEY (orden_numero) REFERENCES ordenes(orden_numero),
    CONSTRAINT fk_op_producto FOREIGN KEY (prod_id)      REFERENCES productos(prod_id),
    CONSTRAINT fk_op_mecanico FOREIGN KEY (mecanico_DNI) REFERENCES empleados(empleado_DNI)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- TABLA: factura_numeradores
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS factura_numeradores (
    tipo    VARCHAR(10) NOT NULL,
    proximo INT(11)     NOT NULL DEFAULT 1,
    PRIMARY KEY (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- DATOS INICIALES
-- ------------------------------------------------------------

-- Numeradores de factura
INSERT INTO factura_numeradores (tipo, proximo) VALUES
('A', 1),
('B', 1),
('C', 1);

-- Servicios base
INSERT INTO servicios (servicio_codigo, servicio_nombre, servicio_descripcion, servicio_costo, servicio_disponible) VALUES
('SV001', 'Cambio de aceite',      'Cambio de aceite de motor y filtro',           500.00, 1),
('SV002', 'Alineación y balanceo', 'Alineación de dirección y balanceo de ruedas', 800.00, 1),
('SV003', 'Frenos',                'Revisión y cambio de pastillas de freno',       950.00, 1),
('SV004', 'Diagnóstico general',   'Diagnóstico electrónico completo del vehículo', 400.00, 1),
('SV005', 'Revisión de motor',     'Inspección general del motor',                 1200.00, 1);

-- Empleado gerente inicial (contraseña: Admin1234)
INSERT INTO empleados (
    empleado_DNI, empleado_contrasena, empleado_nombre, empleado_roll,
    empleado_email, empleado_habilitado, empleado_estado
) VALUES (
    '00000001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrador General',
    'gerente',
    'admin@garagegt.com',
    1,
    'disponible'
);
