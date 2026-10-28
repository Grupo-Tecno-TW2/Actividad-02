-- sql/02-datos.sql — Datos iniciales (los mismos productos de los laboratorios 1 a 7).
SET NAMES utf8mb4;

INSERT INTO socios (id, nombre, comunidad, telefono) VALUES
    (1, 'Rosa Chuvé', 'San Javier', '71234501'),
    (2, 'Mario Surubí', 'Concepción', '71234502'),
    (3, 'Elena Masabí', 'San Ignacio de Velasco', NULL);

INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id) VALUES
    ('MIE-500', 'Miel multifloral 500 g', 'Miel', 45.00, 32, 1),
    ('MIE-1K', 'Miel multifloral 1 kg', 'Miel', 85.00, 18, 1),
    ('MIE-CUS', 'Miel de cusi 350 g', 'Miel', 55.50, 0, 2),
    ('POL-250', 'Polen & propóleo (pack 250 g)', 'Derivados', 70.00, 9, 3),
    ('PRO-30', 'Propóleo en gotas 30 ml', 'Derivados', 38.00, 25, 3),
    ('CER-1K', 'Cera pura 1 kg', 'Cera', 120.00, 4, 2);
