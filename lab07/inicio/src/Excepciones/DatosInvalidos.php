<?php
declare(strict_types=1);

namespace Apicultores\Excepciones;

/** 422: los datos llegaron, pero no son válidos. Lleva los errores por campo para volver a mostrar el formulario. */
final class DatosInvalidos extends ErrorAplicacion
{
    /** @param array<string, string> $errores */
    public function __construct(private readonly array $errores)
    {
        parent::__construct('Revisa los datos marcados.');
    }

    public function errores(): array { return $this->errores; }
    public function codigoHttp(): int { return 422; }
    public function titulo(): string { return 'Datos inválidos'; }
}
