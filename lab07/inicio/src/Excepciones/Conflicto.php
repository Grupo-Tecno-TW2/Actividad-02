<?php
declare(strict_types=1);

namespace Apicultores\Excepciones;

/** 409: la petición es válida pero choca con el estado actual (por ejemplo, no hay stock suficiente). */
final class Conflicto extends ErrorAplicacion
{
    // TODO 1: ¿qué código HTTP corresponde a una petición válida que choca con el estado actual (sin stock)?
    public function codigoHttp(): int { return 409; }
    public function titulo(): string { return 'No se puede completar'; }
}