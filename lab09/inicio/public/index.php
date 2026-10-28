<?php
// public/index.php — Front controller + repositorios (lab 9).
//   php -S 127.0.0.1:8000 -t public public/index.php
declare(strict_types=1);

if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require __DIR__ . '/../vendor/autoload.php';

use Apicultores\BaseDatos\Conexion;
use Apicultores\Repositorios\ProductoRepositorioMysql;
use Apicultores\Repositorios\SocioRepositorio;
use Apicultores\Controladores\ProductosControlador;
use Apicultores\Excepciones\DatosInvalidos;
use Apicultores\Excepciones\ErrorAplicacion;
use Apicultores\Excepciones\MetodoNoPermitido;
use Apicultores\Http\Router;
use Apicultores\Validacion\ValidadorProducto;
use Apicultores\Vista\Plantilla;

ini_set('display_errors', '0');
const REGISTRO_ERRORES = __DIR__ . '/../datos/errores.log';
set_error_handler(function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});

$vista = new Plantilla(__DIR__ . '/../plantillas');

try {
    $bd = Conexion::crear(require __DIR__ . '/../config/bd.php');
    $productos = new ProductoRepositorioMysql($bd);
    $socios = new SocioRepositorio($bd);
    $c = new ProductosControlador($productos, $socios, new ValidadorProducto($productos, $socios), $vista);

    $router = new Router();
    $router->get('/', fn() => header('Location: /productos', true, 302));
    $router->get('/productos', $c->listar(...));
    $router->get('/productos/nuevo', $c->nuevo(...));
    $router->post('/productos', $c->crear(...));
    $router->get('/productos/{codigo}/editar', $c->editar(...));
    $router->post('/productos/{codigo}', $c->actualizar(...));
    $router->post('/productos/{codigo}/eliminar', $c->eliminar(...));
    $router->get('/socios', $c->socios(...));
    $router->get('/socios/{id}', $c->socio(...));

    echo $router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (DatosInvalidos $e) {
    http_response_code(422);
    $ruta = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $nuevo = $ruta === '/productos';
    echo $c->formulario($ruta, $_POST + ['codigo' => $nuevo ? '' : rawurldecode(basename($ruta))], $e->errores(), $nuevo);
} catch (ErrorAplicacion $e) {
    http_response_code($e->codigoHttp());
    if ($e instanceof MetodoNoPermitido) {
        header('Allow: ' . implode(', ', $e->permitidos()));
    }
    echo $vista->pagina('error', ['codigo' => $e->codigoHttp(), 'titulo' => $e->titulo(), 'mensaje' => $e->getMessage()], $e->titulo());
} catch (Throwable $e) {
    // Incluye PDOException: si MySQL no responde, el usuario NO ve usuario, clave ni servidor de la base de datos
    $referencia = bin2hex(random_bytes(4));
    error_log(sprintf("[%s] ref=%s %s: %s en %s:%d\n", date('c'), $referencia, $e::class, $e->getMessage(), $e->getFile(), $e->getLine()),
        3, REGISTRO_ERRORES);
    http_response_code(500);
    echo $vista->pagina('error', ['codigo' => 500, 'titulo' => 'Algo salió mal',
        'mensaje' => "Ya registramos el problema. Si llamas a soporte, menciona la referencia $referencia."], 'Error');
}
