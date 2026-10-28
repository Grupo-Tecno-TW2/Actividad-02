<?php
declare(strict_types=1);

namespace Apicultores\Excepciones;

final class MetodoNoPermitido extends ErrorAplicacion
{
    /** @param string[] $permitidos métodos que SÍ acepta la ruta (van en la cabecera Allow) */
    public function __construct(private readonly array $permitidos)
    {
        parent::__construct('Esta dirección no acepta ese método.');
    }

    public function permitidos(): array { return $this->permitidos; }
    public function codigoHttp(): int { return 405; }
    public function titulo(): string { return 'Método no permitido'; }
}
