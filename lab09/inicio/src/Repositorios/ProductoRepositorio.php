<?php
// src/Repositorios/ProductoRepositorio.php — El CONTRATO del almacenamiento de productos.
// El controlador depende de esta interfaz, no de MySQL: se puede cambiar la implementación sin tocarlo.
declare(strict_types=1);

namespace Apicultores\Repositorios;

use Apicultores\Modelo\Categoria;
use Apicultores\Modelo\Producto;

interface ProductoRepositorio
{
    /** Órdenes permitidos: clave pública → descripción (la consulta SQL NUNCA recibe el texto del usuario). */
    public const ORDENES = [
        'nombre' => 'Nombre (A-Z)',
        'precio' => 'Precio: menor a mayor',
        'precio_desc' => 'Precio: mayor a menor',
        'stock' => 'Stock: menor a mayor',
    ];

    public function porCodigo(string $codigo): ?Producto;

    public function buscar(string $texto, ?Categoria $categoria, string $orden, int $pagina, int $porPagina): Pagina;

    /** @return Producto[] */
    public function deSocio(int $socioId): array;

    public function existeCodigo(string $codigo): bool;

    /** Inserta o actualiza (según exista el código). */
    public function guardar(array $datos): void;

    public function eliminar(string $codigo): void;
}
