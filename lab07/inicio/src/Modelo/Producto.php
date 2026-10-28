<?php
// src/Modelo/Producto.php — Un producto físico: HEREDA de Articulo y agrega stock, categoría y socio.
declare(strict_types=1);

namespace Apicultores\Modelo;

use DomainException;
use InvalidArgumentException;

final class Producto extends Articulo
{
    public const DESCUENTO_MAYORISTA = 10;   // % desde 12 unidades
    public const MINIMO_MAYORISTA = 12;

    private int $stock;

    public function __construct(
        string $codigo,
        string $nombre,
        float $precio,
        int $stock,
        private readonly Categoria $categoria,
        private readonly string $socio = '',
    ) {
        parent::__construct($codigo, $nombre, $precio);   // primero, lo que valida el padre
        if ($stock < 0) {
            throw new InvalidArgumentException('El stock no puede ser negativo.');
        }
        $this->stock = $stock;
    }

    public function stock(): int { return $this->stock; }
    public function categoria(): Categoria { return $this->categoria; }
    public function socio(): string { return $this->socio; }

    public function tipo(): string
    {
        return 'Producto';
    }

    public function detalle(): string
    {
        return $this->stock > 0 ? "{$this->stock} en stock" : 'Agotado';
    }

    /** Precio mayorista: 10 % de descuento desde 12 unidades. */
    public function precioVenta(int $cantidad): float
    {
        $total = $this->precio * $cantidad;
        if ($cantidad >= self::MINIMO_MAYORISTA) {
            $total *= 1 - self::DESCUENTO_MAYORISTA / 100;
        }
        return round($total, 2);
    }

    public function estaDisponible(): bool
    {
        return $this->stock > 0;
    }

    public function vender(int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser positiva.');
        }
        if ($cantidad > $this->stock) {
            throw new DomainException("Stock insuficiente de {$this->codigo}: hay {$this->stock}, se pidieron $cantidad.");
        }
        $this->stock -= $cantidad;
    }

    public function valorEnStock(): float
    {
        return $this->precio * $this->stock;
    }

    public function jsonSerialize(): array
    {
        // parent:: reutiliza lo del padre y agrega lo propio
        return parent::jsonSerialize() + ['categoria' => $this->categoria->value, 'stock' => $this->stock, 'socio' => $this->socio];
    }
}
