-- sql/01-esquema.sql — Estructura de la base de datos de la asociación (MySQL 8.4).
SET NAMES utf8mb4;

DROP TABLE IF EXISTS tokens_api;     -- existe desde el lab 13
DROP TABLE IF EXISTS venta_detalle;  -- existen desde el lab 11
DROP TABLE IF EXISTS ventas;
DROP TABLE IF EXISTS acopios;
DROP TABLE IF EXISTS usuarios;      -- existe desde el lab 10 (depende de socios)
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS socios;

CREATE TABLE socios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(80) NOT NULL,
    comunidad  VARCHAR(60) NOT NULL,
    telefono   VARCHAR(20) NULL,
    creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE productos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo         VARCHAR(8)  NOT NULL,
    nombre         VARCHAR(80) NOT NULL,
    categoria      ENUM('Miel', 'Derivados', 'Cera') NOT NULL,
    precio         DECIMAL(10, 2) NOT NULL,            -- dinero: DECIMAL, nunca FLOAT
    stock          INT NOT NULL DEFAULT 0,
    socio_id       INT UNSIGNED NOT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_productos_codigo UNIQUE (codigo),
    CONSTRAINT chk_productos_precio CHECK (precio > 0),
    CONSTRAINT chk_productos_stock CHECK (stock >= 0),
    CONSTRAINT fk_productos_socio FOREIGN KEY (socio_id) REFERENCES socios (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
