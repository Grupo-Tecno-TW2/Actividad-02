<?php
// src/Modelo/Socio.php — Un socio de la asociación. Clase readonly (PHP 8.2): todas sus propiedades son de solo lectura.
declare(strict_types=1);

namespace Apicultores\Modelo;

final readonly class Socio
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $comunidad,
        public ?string $telefono = null,
    ) {
    }
}
