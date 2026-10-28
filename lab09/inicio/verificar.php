<?php
// verificar.php — Comprueba el Laboratorio 9 (repositorio, paginación, filtros, orden seguro, socios). Uso: php verificar.php
// Requiere MySQL en marcha (docker compose up -d). Restaura los datos (36 productos) al empezar y al terminar.
declare(strict_types=1);
require is_file(__DIR__ . '/Verificador.php') ? __DIR__ . '/Verificador.php' : __DIR__ . '/../../comun/Verificador.php';

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    exit("❌ Falta vendor/autoload.php. Primero ejecuta:  composer dump-autoload" . PHP_EOL);
}
require __DIR__ . '/vendor/autoload.php';

use Apicultores\BaseDatos\Conexion;
use Apicultores\Modelo\{Categoria, Producto, Socio};
use Apicultores\Repositorios\{Pagina, ProductoRepositorio, ProductoRepositorioMemoria, ProductoRepositorioMysql};

try {
    $bd = Conexion::crear(require __DIR__ . '/config/bd.php');
} catch (PDOException $e) {
    exit("❌ No hay conexión con MySQL ({$e->getMessage()}).\n   Inicia la base de datos:  docker compose up -d" . PHP_EOL);
}
function restaurar(PDO $bd): void
{
    foreach (['01-esquema.sql', '02-datos.sql', '03-mas-datos.sql'] as $archivo) {
        $bd->exec((string) file_get_contents(__DIR__ . "/sql/$archivo"));
    }
}
/** Códigos de los productos que muestra una página HTML, en orden */
function codigos(string $html): array
{
    preg_match_all('#<tr>\s*<td>([A-Z]{3}-[A-Z0-9]{2,4})</td>#', $html, $m);
    return $m[1];
}
restaurar($bd);
$repo = new ProductoRepositorioMysql($bd);
$v = new Verificador();

$v->revisar('Pagina: 36 resultados de 10 en 10 → 4 páginas; rango "Mostrando 11–20 de 36"', function () {
    $p = new Pagina(array_fill(0, 10, 'x'), 36, 2, 10);
    return $p->totalPaginas() === 4 && $p->tieneAnterior() && $p->tieneSiguiente() && $p->rango() === 'Mostrando 11–20 de 36'
        && (new Pagina([], 0, 1, 10))->totalPaginas() === 1;
});
$v->revisar('Repositorio MySQL devuelve OBJETOS Producto con su Socio', function () use ($repo) {
    $p = $repo->porCodigo('POL-250');
    return $p instanceof Producto && $p->socio instanceof Socio && $p->socio->nombre === 'Elena Masabí'
        && $p->categoria === Categoria::Derivados && $p->precio === '70.00';
});
$v->revisar('buscar(): página 4 trae los 6 últimos y el total es 36 (LIMIT/OFFSET)', function () use ($repo) {
    $p = $repo->buscar('', null, 'nombre', 4, 10);
    return $p->total === 36 && count($p->elementos) === 6 && !$p->tieneSiguiente();
});
$v->revisar('Filtro por categoría: Cera → 10 productos; Cera + "vela" → 2', fn() =>
    $repo->buscar('', Categoria::Cera, 'nombre', 1, 10)->total === 10 && $repo->buscar('vela', Categoria::Cera, 'nombre', 1, 10)->total === 2);
$v->revisar('Orden por precio: el más barato es CAR-MIE (Bs 12) y el más caro CER-BLO (Bs 540)', fn() =>
    $repo->buscar('', null, 'precio', 1, 10)->elementos[0]->codigo === 'CAR-MIE'
    && $repo->buscar('', null, 'precio_desc', 1, 10)->elementos[0]->codigo === 'CER-BLO');
$v->revisar('deSocio(4) → los 6 productos de Julián Pesoa', fn() => count($repo->deSocio(4)) === 6);
$v->revisar('Interfaz: el repositorio en MEMORIA responde igual que el de MySQL', function () use ($repo) {
    $todos = $repo->buscar('', null, 'nombre', 1, 100)->elementos;
    $mem = new ProductoRepositorioMemoria($todos);
    $a = array_map(fn(Producto $p) => $p->codigo, $repo->buscar('miel', Categoria::Miel, 'precio_desc', 2, 5)->elementos);
    $b = array_map(fn(Producto $p) => $p->codigo, $mem->buscar('miel', Categoria::Miel, 'precio_desc', 2, 5)->elementos);
    return $mem instanceof ProductoRepositorio && $a === $b && count($a) === 5;
});

$v->servidor(__DIR__ . '/public', __DIR__ . '/public/index.php');
$p1 = $v->pedir('/productos');
$v->revisar('Página 1: 10 filas, "Mostrando 1–10 de 36", enlace Siguiente y sin Anterior', fn() =>
    count(codigos($p1['cuerpo'])) === 10 && str_contains($p1['cuerpo'], 'Mostrando 1–10 de 36')
    && str_contains($p1['cuerpo'], 'href="/productos?pagina=2" rel="next"') && !str_contains($p1['cuerpo'], 'rel="prev"'));
$f = $v->pedir('/productos?categoria=Miel&orden=precio_desc&pagina=2');
$v->revisar('Los enlaces de paginación conservan los filtros (categoría y orden)', fn() =>
    str_contains($f['cuerpo'], 'href="/productos?categoria=Miel&amp;orden=precio_desc&amp;pagina=1" rel="prev"')
    && str_contains($f['cuerpo'], 'Mostrando 11–13 de 13'));
$malo = $v->pedir('/productos?orden=' . rawurlencode('p.precio; DROP TABLE productos') . '&pagina=-5');
$v->revisar('orden desconocido → orden por nombre (lista permitida); pagina=-5 → página 1', fn() =>
    $malo['estado'] === 200 && codigos($malo['cuerpo']) === codigos($p1['cuerpo']) && (int) $bd->query('SELECT COUNT(*) FROM productos')->fetchColumn() === 36);
$v->revisar('pagina=abc → 1 · pagina=99 → la última (4)', fn() =>
    codigos($v->pedir('/productos?pagina=abc')['cuerpo']) === codigos($p1['cuerpo'])
    && str_contains($v->pedir('/productos?pagina=99')['cuerpo'], 'Página 4 de 4'));
$soc = $v->pedir('/socios/3');
$v->revisar('Socio 3 (Elena Masabí): sus 4 productos y el valor en stock', fn() =>
    $soc['estado'] === 200 && str_contains($soc['cuerpo'], 'Elena Masabí') && count(codigos($soc['cuerpo'])) === 4
    && $v->pedir('/socios/999')['estado'] === 404);
$alta = $v->pedir('/productos', 'POST', ['codigo' => 'MIE-ROS', 'nombre' => 'Miel de romero 250 g', 'categoria' => 'Miel', 'precio' => '30', 'stock' => '5', 'socio_id' => '4']);
$v->revisar('CRUD con el repositorio: registrar → 303 y aparece en el socio 4 (7 productos)', fn() =>
    $alta['estado'] === 303 && count($repo->deSocio(4)) === 7 && $repo->porCodigo('MIE-ROS')?->precio === '30.00');
$v->revisar('Editar y eliminar siguen funcionando (303)', fn() =>
    $v->pedir('/productos/MIE-ROS', 'POST', ['nombre' => 'Miel de romero 250 g', 'categoria' => 'Miel', 'precio' => '32', 'stock' => '5', 'socio_id' => '4'])['estado'] === 303
    && $repo->porCodigo('MIE-ROS')->precio === '32.00'
    && $v->pedir('/productos/MIE-ROS/eliminar', 'POST', [])['estado'] === 303 && $repo->porCodigo('MIE-ROS') === null);
@unlink(__DIR__ . '/datos/errores.log');
restaurar($bd);
exit($v->fin());
