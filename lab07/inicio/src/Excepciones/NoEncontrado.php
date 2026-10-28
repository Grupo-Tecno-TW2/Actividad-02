<?php
declare(strict_types=1);

namespace Apicultores\Excepciones;

final class NoEncontrado extends ErrorAplicacion
{
    public function codigoHttp(): int { return 404; }
    public function titulo(): string { return 'No encontrado'; }
}
