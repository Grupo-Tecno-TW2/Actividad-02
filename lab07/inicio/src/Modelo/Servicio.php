<?php
// src/Modelo/Servicio.php — Un servicio (alquiler, taller): hereda de Articulo pero NO tiene stock.
declare(strict_types=1);

namespace Apicultores\Modelo;

use InvalidArgumentException;

final class Servicio extends Articulo
{
    public function __construct(
        string $codigo,
        string $nombre,
        float $precio,
        private readonly string $unidad = 'día',
    ) {
        parent::__construct($codigo, $nombre, $precio);
    }

    public function unidad(): string { return $this->unidad; }

    public function tipo(): string
    {
        return 'Servicio';
    }

    public function detalle(): string
    {
        return "por {$this->unidad}";
    }

    /** Sin descuento mayorista: se cobra por día o por persona. */
    public function precioVenta(int $cantidad): float
    {
        return round($this->precio * $cantidad, 2);
    }

    public function estaDisponible(): bool
    {
        return true;       // un servicio no se agota (la agenda llegará con la base de datos)
    }

    public function vender(int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser positiva.');
        }
        // no hay stock que descontar
    }

    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + ['unidad' => $this->unidad];
    }
}
