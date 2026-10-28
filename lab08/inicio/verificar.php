<?php
// verificar.php — Comprueba el Laboratorio 8 (MySQL 8.4 + PDO: CRUD con consultas preparadas). Uso: php verificar.php
// Requiere la base de datos en marcha:  docker compose up -d   (restaura los datos al empezar y al terminar)
declare(strict_types=1);
require is_file(__DIR__ . '/Verificador.php') ? __DIR__ . '/Verificador.php' : __DIR__ . '/../../comun/Verificador.php';

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    exit("❌ Falta vendor/autoload.php. Primero ejecuta:  composer dump-autoload" . PHP_EOL);
}
require __DIR__ . '/vendor/autoload.php';

use Apicultores\BaseDatos\{Conexion, ProductoConsultas};
use Apicultores\Excepciones\Conflicto;

try {
    $bd = Conexion::crear(require __DIR__ . '/config/bd.php');
} catch (PDOException $e) {
    exit("❌ No hay conexión con MySQL ({$e->getMessage()}).\n   Inicia la base de datos:  docker compose up -d   y espera a que esté 'healthy' (docker compose ps)." . PHP_EOL);
}
function restaurar(PDO $bd): void
{
    foreach (['01-esquema.sql', '02-datos.sql'] as $archivo) {
        $bd->exec((string) file_get_contents(__DIR__ . "/sql/$archivo"));
    }
}
function fila(PDO $bd, string $codigo): array|false
{
    $c = $bd->prepare('SELECT * FROM productos WHERE codigo = ?');
    $c->execute([$codigo]);
    return $c->fetch();
}
function codigoError(callable $accion): ?int
{
    try {
        $accion();
    } catch (PDOException $e) {
        return $e->errorInfo[1] ?? null;
    }
    return null;
}
restaurar($bd);
$v = new Verificador();

$v->revisar('Conexión a MySQL 8.4 con utf8mb4 y consultas preparadas reales', fn() =>
    str_starts_with((string) $bd->query('SELECT VERSION()')->fetchColumn(), '8.4')
    && $bd->query('SELECT @@character_set_connection')->fetchColumn() === 'utf8mb4'
    && !$bd->getAttribute(PDO::ATTR_EMULATE_PREPARES));   // PHP 8.3 devuelve int(0); PHP 8.5, false
$v->revisar('La BD protege sus reglas: CHECK precio > 0 (3819) y FOREIGN KEY de socio (1452)', fn() =>
    codigoError(fn() => $bd->exec("INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id) VALUES ('ZZZ-01', 'X', 'Miel', -1, 1, 1)")) === 3819
    && codigoError(fn() => $bd->exec("INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id) VALUES ('ZZZ-02', 'X', 'Miel', 10, 1, 999)")) === 1452);
$v->revisar('Código duplicado directo en la BD → Conflicto (traduce el error 1062)', function () use ($bd) {
    try {
        (new ProductoConsultas($bd))->insertar(['codigo' => 'MIE-500', 'nombre' => 'Otra', 'categoria' => 'Miel', 'precio' => '10.00', 'stock' => 1, 'socio_id' => 1]);
    } catch (Conflicto $e) {
        return $e->getPrevious() instanceof PDOException;
    }
    return false;
});
$v->revisar('escaparComodines: % y _ se buscan literalmente', fn() =>
    ProductoConsultas::escaparComodines('50%_x') === '50\%\_x');

$v->servidor(__DIR__ . '/public', __DIR__ . '/public/index.php');
$lista = $v->pedir('/productos');
$v->revisar('Lista: 6 productos con el socio (JOIN) y "Polen &amp; propóleo" escapado', fn() =>
    $lista['estado'] === 200 && str_contains($lista['cuerpo'], '6 productos') && str_contains($lista['cuerpo'], 'Elena Masabí <small>(San Ignacio de Velasco)')
    && str_contains($lista['cuerpo'], 'Polen &amp; propóleo'));
$v->revisar('Búsqueda "miel" → 3 productos (LIKE sin distinguir mayúsculas)', fn() => str_contains($v->pedir('/productos?q=miel')['cuerpo'], '3 productos'));
$inyeccion = $v->pedir('/productos?q=' . rawurlencode("zzz' OR 1=1 -- -"));
$v->revisar("Inyección SQL: zzz' OR 1=1 -- - → 0 productos (el texto es un DATO, no SQL)", fn() =>
    $inyeccion['estado'] === 200 && str_contains($inyeccion['cuerpo'], '0 productos'));
$v->revisar("Una comilla suelta (') no rompe la consulta y % no devuelve todo", fn() =>
    $v->pedir('/productos?q=' . rawurlencode("'"))['estado'] === 200 && str_contains($v->pedir('/productos?q=%25')['cuerpo'], '0 productos'));

$nuevo = ['codigo' => 'pol-100', 'nombre' => 'Polen seco 100 g', 'categoria' => 'Derivados', 'precio' => '32,5', 'stock' => '15', 'socio_id' => '3'];
$alta = $v->pedir('/productos', 'POST', $nuevo);
$v->revisar('Registrar → 303 y en MySQL queda POL-100 con precio DECIMAL 32.50 y socio 3', function () use ($alta, $bd) {
    $f = fila($bd, 'POL-100');
    return $alta['estado'] === 303 && str_contains($alta['cabeceras'], 'Location: /productos?creado=POL-100')
        && $f !== false && $f['precio'] === '32.50' && $f['socio_id'] === 3;
});
$mal = $v->pedir('/productos', 'POST', ['codigo' => 'MIE-500', 'precio' => '-5', 'socio_id' => '999'] + $nuevo);
$v->revisar('Datos inválidos → 422: código repetido, precio y socio inexistente (sin tocar la BD)', fn() =>
    $mal['estado'] === 422 && str_contains($mal['cuerpo'], 'Ya existe un producto con el código MIE-500')
    && str_contains($mal['cuerpo'], 'Elige un socio de la lista') && substr_count($mal['cuerpo'], 'class="error"') === 3);
$ed = $v->pedir('/productos/POL-100', 'POST', ['precio' => '35', 'stock' => '12'] + $nuevo);
$v->revisar('Editar → 303; MySQL guarda precio 35.00 y stock 12 (el código no cambia)', function () use ($ed, $bd) {
    $f = fila($bd, 'POL-100');
    return $ed['estado'] === 303 && $f['precio'] === '35.00' && $f['stock'] === 12;
});
$v->revisar('Editar un producto inexistente → 404', fn() => $v->pedir('/productos/NO-EXISTE/editar')['estado'] === 404);
$borra = $v->pedir('/productos/POL-100/eliminar', 'POST', []);
$v->revisar('Eliminar por POST → 303 y la fila desaparece; repetir → 404', fn() =>
    $borra['estado'] === 303 && fila($bd, 'POL-100') === false && $v->pedir('/productos/POL-100/eliminar', 'POST', [])['estado'] === 404);
$v->revisar('Eliminar por GET → 405', fn() => $v->pedir('/productos/MIE-500/eliminar')['estado'] === 405 && fila($bd, 'MIE-500') !== false);

// Base de datos caída: el servidor se reinicia apuntando a un puerto sin MySQL
$v->servidor(__DIR__ . '/public', __DIR__ . '/public/index.php', ['BD_PUERTO' => '3399']);
$caida = $v->pedir('/productos');
$v->revisar('MySQL no disponible → 500 amable, sin usuario, clave ni servidor', fn() =>
    $caida['estado'] === 500 && str_contains($caida['cuerpo'], 'referencia') && !str_contains($caida['cuerpo'], 'miel2026')
    && !str_contains($caida['cuerpo'], '3399') && !str_contains($caida['cuerpo'], 'SQLSTATE'));
@unlink(__DIR__ . '/datos/errores.log');
restaurar($bd);
exit($v->fin());
