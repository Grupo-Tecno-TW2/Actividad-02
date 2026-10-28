<?php
// verificar.php — Comprueba el Laboratorio 6 (Composer PSR-4, herencia, interfaces, traits, enums). Uso: php verificar.php
declare(strict_types=1);
require is_file(__DIR__ . '/Verificador.php') ? __DIR__ . '/Verificador.php' : __DIR__ . '/../../comun/Verificador.php';

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ Falta vendor/autoload.php. Primero ejecuta:  composer dump-autoload" . PHP_EOL;
    exit(1);
}
require __DIR__ . '/vendor/autoload.php';

use Apicultores\Catalogo;
use Apicultores\Contratos\Vendible;
use Apicultores\Modelo\{Articulo, Categoria, Producto, Servicio};

function lanza(string $clase, callable $accion): bool
{
    try {
        $accion();
    } catch (Throwable $e) {
        return $e instanceof $clase;
    }
    return false;
}

$v = new Verificador();
$composer = json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true);

$v->revisar('composer.json declara PSR-4: Apicultores\\ → src/', fn() =>
    ($composer['autoload']['psr-4']['Apicultores\\'] ?? null) === 'src/');
$v->revisar('El autoload encuentra clases, interfaz, trait y enum por su espacio de nombres', fn() =>
    class_exists(Producto::class) && class_exists(Servicio::class) && class_exists(Catalogo::class)
    && interface_exists(Vendible::class) && trait_exists('Apicultores\Soporte\ConBolivianos') && enum_exists(Categoria::class));
$v->revisar('Las páginas no usan require de src/: solo vendor/autoload.php', function () {
    foreach (glob(__DIR__ . '/public/*.php') as $f) {
        $codigo = (string) file_get_contents($f);
        if (preg_match('#require[^;]*src/#', $codigo) || !str_contains($codigo, "vendor/autoload.php")) {
            return false;
        }
    }
    return true;
});
$v->revisar('Enum Categoria: from, tryFrom, cases y descripcion()', fn() =>
    Categoria::from('Miel') === Categoria::Miel && Categoria::tryFrom('Hongos') === null && count(Categoria::cases()) === 3
    && Categoria::Derivados->descripcion() === 'Polen, propóleo y más');
$v->revisar('Articulo es abstracta: no se puede instanciar', fn() =>
    (new ReflectionClass(Articulo::class))->isAbstract() && lanza(Error::class, fn() => (new ReflectionClass(Articulo::class))->newInstance('ABC-10', 'x', 1.0)));
$v->revisar('Producto y Servicio heredan de Articulo e implementan Vendible y JsonSerializable', fn() =>
    is_subclass_of(Producto::class, Articulo::class) && is_subclass_of(Servicio::class, Articulo::class)
    && is_subclass_of(Servicio::class, Vendible::class) && is_subclass_of(Producto::class, JsonSerializable::class));

$c = Catalogo::desdeArchivo(__DIR__ . '/datos/catalogo.json');
$miel = $c->buscar('MIE-500');
$taller = $c->buscar('SRV-TAL');
$v->revisar('Polimorfismo: 2 miel = 90 · 12 miel = 486 (−10 %) · 12 personas al taller = 2400 (sin descuento)', fn() =>
    $miel->precioVenta(2) === 90.0 && $miel->precioVenta(12) === 486.0 && $taller->precioVenta(12) === 2400.0);
$v->revisar('Producto no vende sin stock; un servicio siempre está disponible', fn() =>
    lanza(DomainException::class, fn() => $c->buscar('CER-1K')->vender(5)) && !$c->buscar('MIE-CUS')->estaDisponible()
    && $taller->estaDisponible());
$v->revisar('Trait ConBolivianos: Articulo lo usa y su método es protected', fn() =>
    in_array('Apicultores\Soporte\ConBolivianos', class_uses(Articulo::class), true)
    && (new ReflectionMethod(Articulo::class, 'enBolivianos'))->isProtected() && $miel->precioFormateado() === 'Bs 45,00');
$v->revisar('Catálogo: 6 productos + 2 servicios, 7 disponibles, inventario 5030 (sin servicios)', fn() =>
    count($c->productos()) === 6 && count($c->servicios()) === 2 && count($c->disponibles()) === 7 && $c->valorInventario() === 5030.0);
$v->revisar('totalPedido: 12 MIE-500 + 1 SRV-EXT = 636; código inexistente → DomainException', fn() =>
    $c->totalPedido(['MIE-500' => 12, 'SRV-EXT' => 1]) === 636.0 && lanza(DomainException::class, fn() => $c->totalPedido(['XXX-99' => 1])));
$v->revisar('json_encode(Producto) usa jsonSerialize con parent:: (tipo, código, categoría, stock)', function () use ($miel) {
    $j = json_decode(json_encode($miel), true);
    return $j['tipo'] === 'Producto' && $j['codigo'] === 'MIE-500' && $j['categoria'] === 'Miel' && $j['stock'] === 32;
});

$v->servidor(__DIR__ . '/public');
$pag = $v->pedir('/');
$v->revisar('La página muestra categorías del enum, precio por docena y servicios', fn() =>
    $pag['estado'] === 200 && str_contains($pag['cuerpo'], '<section id="derivados">') && str_contains($pag['cuerpo'], 'docena: Bs 486,00')
    && str_contains($pag['cuerpo'], 'Taller de iniciación apícola — Bs 200,00 por persona') && str_contains($pag['cuerpo'], 'Polen &amp; propóleo'));
$api = $v->pedir('/api.php');
$v->revisar('api.php responde JSON con los 8 artículos', function () use ($api) {
    $datos = json_decode($api['cuerpo'], true);
    return str_contains($api['cabeceras'], 'application/json') && is_array($datos) && count($datos) === 8
        && array_count_values(array_column($datos, 'tipo')) === ['Producto' => 6, 'Servicio' => 2];
});
exit($v->fin());
