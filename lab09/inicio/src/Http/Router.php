<?php
// src/Http/Router.php — Enrutador mínimo: relaciona "MÉTODO + ruta" con un controlador.
declare(strict_types=1);

namespace Apicultores\Http;

use Apicultores\Excepciones\MetodoNoPermitido;
use Apicultores\Excepciones\NoEncontrado;

final class Router
{
    /** @var array<int, array{metodo: string, patron: string, accion: callable}> */
    private array $rutas = [];

    public function get(string $ruta, callable $accion): void
    {
        $this->agregar('GET', $ruta, $accion);
    }

    public function post(string $ruta, callable $accion): void
    {
        $this->agregar('POST', $ruta, $accion);
    }

    /** '/producto/{codigo}' → expresión regular con un grupo con nombre: #^/producto/(?P<codigo>[^/]+)$# */
    private function agregar(string $metodo, string $ruta, callable $accion): void
    {
        $patron = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $ruta) . '$#';
        $this->rutas[] = ['metodo' => $metodo, 'patron' => $patron, 'accion' => $accion];
    }

    /** Ejecuta el controlador que corresponde y devuelve lo que este devuelva (HTML o datos). */
    public function despachar(string $metodo, string $uri): mixed
    {
        $ruta = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        $permitidos = [];
        foreach ($this->rutas as $r) {
            if (!preg_match($r['patron'], $ruta, $coincide)) {
                continue;
            }
            if ($r['metodo'] !== $metodo) {
                $permitidos[] = $r['metodo'];      // la ruta existe, pero con otro método
                continue;
            }
            $parametros = array_filter($coincide, 'is_string', ARRAY_FILTER_USE_KEY);
            return ($r['accion'])(...$parametros);  // argumentos con nombre: $codigo
        }
        if ($permitidos !== []) {
            throw new MetodoNoPermitido(array_values(array_unique($permitidos)));
        }
        throw new NoEncontrado("No existe la página $ruta.");
    }
}
