<?php
// public/index.php — FRONT CONTROLLER: la ÚNICA puerta de entrada. Toda petición pasa por aquí.
//   Servidor de desarrollo:  php -S 127.0.0.1:8000 -t public public/index.php
//   Nginx (Tecnología Web I): location / { try_files $uri /index.php$is_args$args; }
declare(strict_types=1);

// Con php -S: si piden un archivo que existe (CSS, imágenes), que lo entregue el servidor
// TODO 4: si PHP_SAPI es 'cli-server' y la ruta pedida es un archivo que existe en public/ (is_file),
//         devuelve false para que el servidor lo entregue tal cual (hoy el CSS da 404).
if (
    PHP_SAPI === 'cli-server' &&
    is_file(
        __DIR__ .
        parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    )
) {
    return false;
}

require __DIR__ . '/../vendor/autoload.php';

use Apicultores\Catalogo;
use Apicultores\Controladores\CatalogoControlador;
use Apicultores\Excepciones\DatosInvalidos;
use Apicultores\Excepciones\ErrorAplicacion;
use Apicultores\Excepciones\MetodoNoPermitido;
use Apicultores\Http\Router;
use Apicultores\Vista\Plantilla;

ini_set('display_errors', '0');         // en producción NUNCA se muestran detalles internos
const REGISTRO_ERRORES = __DIR__ . '/../datos/errores.log';

// Avisos y advertencias de PHP también se convierten en excepciones: nada falla "a medias"
set_error_handler(function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});

$vista = new Plantilla(__DIR__ . '/../plantillas');

try {
    $catalogo = Catalogo::desdeArchivo(getenv('CATALOGO_ARCHIVO') ?: __DIR__ . '/../datos/catalogo.json');
    $c = new CatalogoControlador($catalogo, $vista);

    $router = new Router();
    $router->get('/', $c->inicio(...));
    $router->get('/producto/{codigo}', $c->producto(...));
    $router->get('/api/articulos', $c->api(...));
    $router->get('/cotizacion', $c->formularioCotizacion(...));
    $router->post('/cotizacion', $c->cotizar(...));

    $resultado = $router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    if (is_array($resultado)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    } else {
        echo $resultado;
    }
} catch (DatosInvalidos $e) {
    // 422: se vuelve a mostrar el formulario con lo que escribió el usuario y los errores por campo
    http_response_code($e->codigoHttp());
    echo $c->mostrarCotizacion((array) ($_POST['cantidad'] ?? []), $e->errores());
} catch (ErrorAplicacion $e) {
    // Errores PREVISTOS (404, 405, 409): su mensaje lo escribimos nosotros, es seguro mostrarlo
    http_response_code($e->codigoHttp());
    if ($e instanceof MetodoNoPermitido) {
        header('Allow: ' . implode(', ', $e->permitidos()));
    }
    echo $vista->pagina('error', ['codigo' => $e->codigoHttp(), 'titulo' => $e->titulo(), 'mensaje' => $e->getMessage()], $e->titulo());
} catch (Throwable $e) {
    // Errores INESPERADOS (500): el detalle va al registro; al usuario, solo un número de referencia
    // TODO 6: hoy se muestra al usuario la clase, el mensaje interno y la RUTA del archivo. En su lugar:
    //   - genera una referencia corta: bin2hex(random_bytes(4))
    //   - guarda el detalle: error_log(sprintf("[%s] ref=%s %s: %s en %s:%d\n", date('c'), $referencia, $e::class,
    //                         $e->getMessage(), $e->getFile(), $e->getLine()), 3, REGISTRO_ERRORES);
    //   - responde 500 con un mensaje amable que incluya "referencia <ref>"
    $referencia = bin2hex(random_bytes(4));
    error_log(
        sprintf(
            "[%s] ref=%s %s: %s en %s:%d\n",
            date('c'),
            $referencia,
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ),
        3,
        REGISTRO_ERRORES
    );
    http_response_code(500);
    echo $vista->pagina(
        'error',
        [
            'codigo' => 500,
            'titulo' => 'Algo salió mal',
            'mensaje' =>
                "Ya registramos el problema. Si llamas a soporte, " .
                "menciona la referencia $referencia.",
        ],
        'Error'
    );
}