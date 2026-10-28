<?php
// src/Modelo/Producto.php — El producto como OBJETO (no como fila suelta): el repositorio lo construye desde MySQL.
declare(strict_types=1);

namespace Apicultores\Modelo;

final readonly class Producto
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public Categoria $categoria,
        public string $precio,          // DECIMAL de MySQL como texto exacto ("45.00")
        public int $stock,
        public Socio $socio,
        public ?int $id = null,         // null mientras no se haya guardado
    ) {
    }

    public function estaDisponible(): bool
    {
        return $this->stock > 0;
    }

    public function precioBs(): string
    {
        return bs((float) $this->precio);
    }
}
