<?php
// src/Repositorios/ProductoRepositorioMemoria.php — La MISMA interfaz, pero con un arreglo en memoria.
// Sirve para pruebas rápidas sin base de datos y demuestra el valor de programar contra una interfaz.
declare(strict_types=1);

namespace Apicultores\Repositorios;

use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Categoria;
use Apicultores\Modelo\Producto;
use Apicultores\Modelo\Socio;

final class ProductoRepositorioMemoria implements ProductoRepositorio
{
    /** @var array<string, Producto> */
    private array $productos = [];

    /** @param Producto[] $iniciales */
    public function __construct(array $iniciales = [])
    {
        foreach ($iniciales as $p) {
            $this->productos[$p->codigo] = $p;
        }
    }

    public function porCodigo(string $codigo): ?Producto
    {
        return $this->productos[$codigo] ?? null;
    }

    public function buscar(string $texto, ?Categoria $categoria, string $orden, int $pagina, int $porPagina): Pagina
    {
        $lista = array_values(array_filter($this->productos, fn(Producto $p): bool =>
            (mb_stripos($p->nombre, $texto) !== false || mb_stripos($p->codigo, $texto) !== false)
            && ($categoria === null || $p->categoria === $categoria)));
        usort($lista, match ($orden) {
            'precio' => fn($a, $b) => [(float) $a->precio, $a->nombre] <=> [(float) $b->precio, $b->nombre],
            'precio_desc' => fn($a, $b) => [(float) $b->precio, $a->nombre] <=> [(float) $a->precio, $b->nombre],
            'stock' => fn($a, $b) => [$a->stock, $a->nombre] <=> [$b->stock, $b->nombre],
            default => fn($a, $b) => strcoll($a->nombre, $b->nombre),
        });
        return new Pagina(array_slice($lista, ($pagina - 1) * $porPagina, $porPagina), count($lista), $pagina, $porPagina);
    }

    public function deSocio(int $socioId): array
    {
        return array_values(array_filter($this->productos, fn(Producto $p): bool => $p->socio->id === $socioId));
    }

    public function existeCodigo(string $codigo): bool
    {
        return isset($this->productos[$codigo]);
    }

    public function guardar(array $d): void
    {
        $socio = $this->productos[$d['codigo']]->socio ?? new Socio((int) $d['socio_id'], 'Socio ' . $d['socio_id'], '');
        $this->productos[$d['codigo']] = new Producto($d['codigo'], $d['nombre'], Categoria::from($d['categoria']),
            (string) $d['precio'], (int) $d['stock'], $socio);
    }

    public function eliminar(string $codigo): void
    {
        if (!isset($this->productos[$codigo])) {
            throw new NoEncontrado("No existe el producto $codigo.");
        }
        unset($this->productos[$codigo]);
    }
}
