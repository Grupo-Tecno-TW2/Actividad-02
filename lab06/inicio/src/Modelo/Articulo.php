<?php
// src/Modelo/Articulo.php — Clase ABSTRACTA: lo común a productos y servicios. No se puede instanciar.
declare(strict_types=1);

namespace Apicultores\Modelo;

use Apicultores\Contratos\Vendible;
use Apicultores\Soporte\ConBolivianos;
use InvalidArgumentException;
use JsonSerializable;

abstract class Articulo implements Vendible, JsonSerializable
{
    use ConBolivianos;

    public const FORMATO_CODIGO = '/^[A-Z]{3}-[A-Z0-9]{2,4}$/';

    public function __construct(
        protected readonly string $codigo,
        protected string $nombre,
        protected float $precio,
    ) {
        if (!preg_match(self::FORMATO_CODIGO, $codigo)) {
            throw new InvalidArgumentException("Código inválido: $codigo");
        }
        if ($precio <= 0) {
            throw new InvalidArgumentException('El precio debe ser mayor que cero.');
        }
    }

    public function codigo(): string { return $this->codigo; }
    public function nombre(): string { return $this->nombre; }
    public function precio(): float { return $this->precio; }

    public function precioFormateado(): string
    {
        return $this->enBolivianos($this->precio);
    }

    /** Cada hija DEBE decir qué tipo es y cómo se describe al venderse. */
    abstract public function tipo(): string;

    abstract public function detalle(): string;

    /** Lo que se envía al convertir el objeto con json_encode() (JsonSerializable). */
    public function jsonSerialize(): array
    {
        return ['tipo' => $this->tipo(), 'codigo' => $this->codigo, 'nombre' => $this->nombre, 'precio' => $this->precio];
    }
}
