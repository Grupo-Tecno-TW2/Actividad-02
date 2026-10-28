<?php
// verificar.php — Comprueba el Laboratorio 7 (excepciones, front controller, plantillas). Uso: php verificar.php
declare(strict_types=1);
require is_file(__DIR__ . '/Verificador.php') ? __DIR__ . '/Verificador.php' : __DIR__ . '/../../comun/Verificador.php';

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ Falta vendor/autoload.php. Primero ejecuta:  composer dump-autoload" . PHP_EOL;
    exit(1);
}
require __DIR__ . '/vendor/autoload.php';

use Apicultores\Excepciones\{Conflicto, DatosInvalidos, ErrorAplicacion, MetodoNoPermitido, NoEncontrado};
use Apicultores\Http\Router;
use Apicultores\Vista\{Crudo, Plantilla};

$registro = __DIR__ . '/datos/errores.log';
@unlink($registro);
$v = new Verificador();

// --- Piezas por separado ---
$v->revisar('Jerarquía: NoEncontrado, Conflicto y DatosInvalidos heredan de ErrorAplicacion (RuntimeException)', fn() =>
    is_subclass_of(NoEncontrado::class, ErrorAplicacion::class) && is_subclass_of(Conflicto::class, ErrorAplicacion::class)
    && is_subclass_of(DatosInvalidos::class, ErrorAplicacion::class) && is_subclass_of(ErrorAplicacion::class, RuntimeException::class));
$v->revisar('Cada excepción sabe su código HTTP: 404, 405, 409, 422', fn() =>
    (new NoEncontrado('x'))->codigoHttp() === 404 && (new MetodoNoPermitido(['GET']))->codigoHttp() === 405
    && (new Conflicto('x'))->codigoHttp() === 409 && (new DatosInvalidos(['a' => 'b']))->codigoHttp() === 422);
$v->revisar('Router: parámetros {codigo}, 404 si no existe y 405 (con métodos permitidos) si cambia el método', function () {
    $r = new Router();
    $r->get('/producto/{codigo}', fn(string $codigo) => "ficha $codigo");
    try {
        $r->despachar('POST', '/producto/MIE-500');
        return false;
    } catch (MetodoNoPermitido $e) {
        $ok405 = $e->permitidos() === ['GET'];
    }
    try {
        $r->despachar('GET', '/nada');
        return false;
    } catch (NoEncontrado) {
    }
    return $ok405 && $r->despachar('GET', '/producto/MIE-500?x=1') === 'ficha MIE-500';
});
$v->revisar('Plantilla escapa automáticamente (también en arreglos) y respeta Crudo', function () {
    $html = Plantilla::escapar(['a' => '<b>', 'b' => ['"x"'], 'n' => 5, 'c' => new Crudo('<i>ok</i>')]);
    return $html['a'] === '&lt;b&gt;' && $html['b'][0] === '&quot;x&quot;' && $html['n'] === 5 && (string) $html['c'] === '<i>ok</i>';
});
$v->revisar('Plantilla rechaza objetos (la vista recibe datos, no modelos) y nombres con ../', function () {
    $p = new Plantilla(__DIR__ . '/plantillas');
    foreach ([fn() => Plantilla::escapar(new DateTime()), fn() => $p->parcial('../composer')] as $f) {
        try { $f(); return false; } catch (InvalidArgumentException) { }
    }
    return true;
});

// --- Aplicación completa por el front controller ---
$v->servidor(__DIR__ . '/public', __DIR__ . '/public/index.php');
$v->revisar('Front controller: / y /producto/MIE-1K (200); el CSS se sirve como archivo', fn() =>
    $v->pedir('/')['estado'] === 200 && str_contains($v->pedir('/producto/MIE-1K')['cuerpo'], 'Miel multifloral 1 kg')
    && str_contains($v->pedir('/css/estilo.css')['cabeceras'], 'text/css'));
$v->revisar('404 con página propia para un producto o una ruta inexistente', function () use ($v) {
    $a = $v->pedir('/producto/NO-EXISTE');
    return $a['estado'] === 404 && str_contains($a['cuerpo'], 'No existe el producto NO-EXISTE') && str_contains($a['cuerpo'], 'Volver al catálogo')
        && $v->pedir('/no/existe')['estado'] === 404;
});
$p405 = $v->pedir('/', 'POST', 'x=1');
$v->revisar('POST a / → 405 con cabecera Allow: GET', fn() => $p405['estado'] === 405 && str_contains($p405['cabeceras'], 'Allow: GET'));
$xss = $v->pedir('/?q=' . rawurlencode('<script>alert(1)</script>'));
$v->revisar('Escape automático en la página: la búsqueda <script> se muestra como texto', fn() =>
    str_contains($xss['cuerpo'], '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($xss['cuerpo'], '<script>alert')
    && str_contains($v->pedir('/')['cuerpo'], 'Polen &amp; propóleo'));
$api = $v->pedir('/api/articulos');
$v->revisar('/api/articulos devuelve JSON (8 artículos)', fn() =>
    str_contains($api['cabeceras'], 'application/json') && count(json_decode($api['cuerpo'], true)) === 8);
$ok = $v->pedir('/cotizacion', 'POST', ['cantidad[MIE-500]' => '12', 'cantidad[SRV-EXT]' => '1']);
$v->revisar('Cotización válida: 12 MIE-500 + 1 SRV-EXT → 200, total Bs 636,00', fn() =>
    $ok['estado'] === 200 && str_contains($ok['cuerpo'], 'Total: <strong>Bs 636,00</strong>'));
$mal = $v->pedir('/cotizacion', 'POST', ['cantidad[MIE-500]' => '-3', 'cantidad[PRO-30]' => '"><b>']);
$v->revisar('Cantidades inválidas → 422, error por campo y lo escrito vuelve escapado', fn() =>
    $mal['estado'] === 422 && substr_count($mal['cuerpo'], 'class="error"') === 2 && str_contains($mal['cuerpo'], 'value="&quot;&gt;&lt;b&gt;"'));
$sin = $v->pedir('/cotizacion', 'POST', ['cantidad[CER-1K]' => '5']);
$v->revisar('Pedir 5 CER-1K (hay 4) → 409 con el mensaje de stock', fn() =>
    $sin['estado'] === 409 && str_contains($sin['cuerpo'], 'Stock insuficiente de CER-1K: hay 4, se pidieron 5.'));
// --- Error inesperado: un archivo de datos dañado (el servidor se reinicia con otra variable de entorno) ---
$roto = sys_get_temp_dir() . '/catalogo-roto.json';
file_put_contents($roto, '[{"tipo": "producto", "codigo": ');
$v->servidor(__DIR__ . '/public', __DIR__ . '/public/index.php', ['CATALOGO_ARCHIVO' => $roto]);
$r500 = $v->pedir('/');
$v->revisar('Error inesperado → 500 amable con referencia, SIN clase, ruta ni traza', fn() =>
    $r500['estado'] === 500 && preg_match('/referencia [0-9a-f]{8}/', $r500['cuerpo']) === 1
    && !str_contains($r500['cuerpo'], 'JsonException') && !str_contains($r500['cuerpo'], 'Catalogo.php') && !str_contains($r500['cuerpo'], 'Stack trace'));
$v->revisar('El detalle quedó en datos/errores.log con la misma referencia', function () use ($r500, $registro) {
    preg_match('/referencia ([0-9a-f]{8})/', $r500['cuerpo'], $m);
    $log = (string) @file_get_contents($registro);
    return isset($m[1]) && str_contains($log, "ref={$m[1]}") && str_contains($log, 'JsonException');
});
@unlink($roto);
@unlink($registro);
exit($v->fin());
