-- sql/03-mas-datos.sql — Más socios y productos (36 en total) para practicar paginación, filtros y orden.
SET NAMES utf8mb4;

INSERT INTO socios (id, nombre, comunidad, telefono) VALUES
    (4, 'Julián Pesoa', 'San Rafael', '71234504'),
    (5, 'Carmen Tomichá', 'Lomerío', '71234505'),
    (6, 'Hernán Parapaino', 'San Miguel de Velasco', NULL),
    (7, 'Luisa Poñé', 'Santa Ana de Velasco', '71234507'),
    (8, 'Andrés Chuvirú', 'Roboré', NULL);

INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id) VALUES
    ('MIE-250', 'Miel multifloral 250 g', 'Miel', 25.00, 40, 4),
    ('MIE-2K', 'Miel multifloral 2 kg', 'Miel', 160.00, 6, 4),
    ('MIE-SMO', 'Miel de bosque ahumada 500 g', 'Miel', 58.00, 11, 5),
    ('MIE-CAM', 'Miel de campo 750 g', 'Miel', 65.00, 14, 6),
    ('MIE-OR', 'Miel orgánica certificada 500 g', 'Miel', 72.00, 8, 7),
    ('MIE-SAC', 'Miel en sachet (caja x 20)', 'Miel', 48.00, 22, 8),
    ('MIE-PAN', 'Miel en panal 400 g', 'Miel', 95.00, 3, 5),
    ('MIE-JEN', 'Miel con jengibre 350 g', 'Miel', 52.00, 17, 6),
    ('MIE-LIM', 'Miel con limón 350 g', 'Miel', 52.00, 0, 7),
    ('MIE-TOC', 'Miel de tajibo 500 g', 'Miel', 68.00, 9, 4),
    ('POL-100', 'Polen seco 100 g', 'Derivados', 32.50, 15, 3),
    ('POL-500', 'Polen seco 500 g', 'Derivados', 140.00, 5, 5),
    ('PRO-60', 'Propóleo en gotas 60 ml', 'Derivados', 65.00, 12, 3),
    ('PRO-SPR', 'Spray de propóleo 30 ml', 'Derivados', 45.00, 20, 6),
    ('JAL-30', 'Jalea real 30 g', 'Derivados', 110.00, 4, 7),
    ('JAB-MIE', 'Jabón de miel y avena', 'Derivados', 18.00, 35, 8),
    ('JAB-PRO', 'Jabón de propóleo', 'Derivados', 18.00, 28, 8),
    ('CRE-MIE', 'Crema de manos con miel 60 g', 'Derivados', 35.00, 16, 5),
    ('HID-500', 'Hidromiel artesanal 500 ml', 'Derivados', 55.00, 10, 4),
    ('CAR-MIE', 'Caramelos de miel (bolsa)', 'Derivados', 12.00, 60, 6),
    ('VIN-MIE', 'Vinagre de miel 500 ml', 'Derivados', 30.00, 0, 7),
    ('CER-500', 'Cera pura 500 g', 'Cera', 65.00, 7, 4),
    ('CER-LAM', 'Láminas de cera estampada (x10)', 'Cera', 85.00, 12, 6),
    ('VEL-CER', 'Velas de cera (par)', 'Cera', 28.00, 25, 5),
    ('CER-COS', 'Cera cosmética 250 g', 'Cera', 42.00, 9, 8),
    ('BAL-LAB', 'Bálsamo labial de cera', 'Cera', 15.00, 40, 7),
    ('CER-ENV', 'Envoltorio de cera para alimentos (x3)', 'Cera', 60.00, 13, 5),
    ('CER-BLO', 'Bloque de cera 5 kg', 'Cera', 540.00, 1, 2),
    ('VEL-ARO', 'Vela aromática de cera 200 g', 'Cera', 38.00, 0, 6),
    ('CER-MAD', 'Cera para madera 250 g', 'Cera', 45.00, 11, 4);
