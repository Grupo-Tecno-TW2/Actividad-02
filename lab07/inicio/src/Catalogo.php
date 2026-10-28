<?php
// src/Catalogo.php — Trabaja con la INTERFAZ Vendible: no le importa si es producto o servicio (polimorfismo).
declare(strict_types=1);

namespace Apicultores;

use Apicultores\Contratos\Vendible;
use Apicultores\Modelo\Categoria;
use Apicultores\Modelo\Producto;
use Apicultores\Modelo\Servicio;
use DomainException;

final class Catalogo
{
    /** @var array<string, Vendible> */
    private array $articulos = [];

    public static function desdeArchivo(string $ruta): self
    {
        $catalogo = new self();
        foreach (json_decode((string) file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR) as $f) {
            $catalogo->agregar(match ($f['tipo']) {
                'producto' => new Producto($f['codigo'], $f['nombre'], (float) $f['precio'], (int) $f['stock'],
                    Categoria::from($f['categoria']), $f['socio'] ?? ''),
                'servicio' => new Servicio($f['codigo'], $f['nombre'], (float) $f['precio'], $f['unidad'] ?? 'día'),
            });
        }
        return $catalogo;
    }

    public function agregar(Vendible $articulo): void
    {
        if (isset($this->articulos[$articulo->codigo()])) {
            throw new DomainException("Ya existe el código {$articulo->codigo()}.");
        }
        $this->articulos[$articulo->codigo()] = $articulo;
    }

    public function buscar(string $codigo): ?Vendible
    {
        return $this->articulos[strtoupper(trim($codigo))] ?? null;
    }

    /** @return Vendible[] */
    public function todos(): array
    {
        return array_values($this->articulos);
    }

    /** @return Producto[] */
    public function productos(?Categoria $categoria = null): array
    {
        return array_values(array_filter(
            $this->articulos,
            fn(Vendible $a): bool => $a instanceof Producto && ($categoria === null || $a->categoria() === $categoria)
        ));
    }

    /** @return Servicio[] */
    public function servicios(): array
    {
        return array_values(array_filter($this->articulos, fn(Vendible $a): bool => $a instanceof Servicio));
    }

    /** @return Vendible[] */
    public function disponibles(): array
    {
        return array_values(array_filter($this->articulos, fn(Vendible $a): bool => $a->estaDisponible()));
    }

    /** Solo los productos tienen inventario: los servicios no suman. */
    public function valorInventario(): float
    {
        return array_sum(array_map(fn(Producto $p): float => $p->valorEnStock(), $this->productos()));
    }

    /** Comprueba el stock de un pedido SIN venderlo. @param array<string,int> $pedido @throws DomainException */
    public function verificarStock(array $pedido): void
    {
        foreach ($pedido as $codigo => $cantidad) {
            $a = $this->buscar($codigo);
            if ($a instanceof Producto && $cantidad > $a->stock()) {
                throw new DomainException("Stock insuficiente de $codigo: hay {$a->stock()}, se pidieron $cantidad.");
            }
        }
    }

    /** Total de un pedido: cada artículo calcula SU precio (polimorfismo). @param array<string,int> $pedido */
    public function totalPedido(array $pedido): float
    {
        $total = 0.0;
        foreach ($pedido as $codigo => $cantidad) {
            $articulo = $this->buscar($codigo) ?? throw new DomainException("No existe el código $codigo.");
            $total += $articulo->precioVenta($cantidad);
        }
        return round($total, 2);
    }
}
