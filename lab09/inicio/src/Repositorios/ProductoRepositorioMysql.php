<?php
// src/Repositorios/ProductoRepositorioMysql.php — Implementación con MySQL + PDO. TODO el SQL de productos vive aquí.
declare(strict_types=1);

namespace Apicultores\Repositorios;

use Apicultores\Excepciones\Conflicto;
use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Categoria;
use Apicultores\Modelo\Producto;
use Apicultores\Modelo\Socio;
use PDO;
use PDOException;

final class ProductoRepositorioMysql implements ProductoRepositorio
{
    private const SELECCION = 'SELECT p.id, p.codigo, p.nombre, p.categoria, p.precio, p.stock,
                                    s.id AS socio_id, s.nombre AS socio_nombre, s.comunidad, s.telefono
                             FROM productos p
                             JOIN socios s ON s.id = p.socio_id';

    /** Lista permitida: clave pública → fragmento SQL. Los NOMBRES de columnas no pueden ir como marcadores. */
    private const ORDEN_SQL = [
        'nombre' => 'p.nombre ASC',
        'precio' => 'p.precio ASC, p.nombre ASC',
        'precio_desc' => 'p.precio DESC, p.nombre ASC',
        'stock' => 'p.stock ASC, p.nombre ASC',
    ];

    public function __construct(private readonly PDO $bd)
    {
    }

    public function porCodigo(string $codigo): ?Producto
    {
        $c = $this->bd->prepare(self::SELECCION . ' WHERE p.codigo = ?');
        $c->execute([$codigo]);
        $fila = $c->fetch();
        return $fila ? $this->hidratar($fila) : null;
    }

    public function buscar(string $texto, ?Categoria $categoria, string $orden, int $pagina, int $porPagina): Pagina
    {
        // 1) Filtros: se arman las condiciones con marcadores y sus valores por separado
        $condiciones = ['(p.nombre LIKE :patron OR p.codigo LIKE :patron2)'];
        $patron = '%' . addcslashes($texto, '%_\\') . '%';
        $valores = ['patron' => $patron, 'patron2' => $patron];
        
        // TODO 5: si llegó $categoria, agrega la condición 'p.categoria = :categoria' y su valor ($categoria->value).
        if ($categoria !== null) {
            $condiciones[] = 'p.categoria = :categoria';
            $valores['categoria'] = $categoria->value;
        }
        
        $donde = ' WHERE ' . implode(' AND ', $condiciones);

        // 2) Total (para saber cuántas páginas hay)
        $c = $this->bd->prepare('SELECT COUNT(*) FROM productos p' . $donde);
        $c->execute($valores);
        $total = (int) $c->fetchColumn();

        // 3) Solo la página pedida: LIMIT/OFFSET como ENTEROS (bindValue con PDO::PARAM_INT)
        // TODO 3: ¡el texto de $orden se pega en el SQL! Usa la lista permitida self::ORDEN_SQL
        $ordenSql = self::ORDEN_SQL[$orden] ?? self::ORDEN_SQL['nombre'];
        
        // TODO 2: trae SOLO la página pedida: agrega "LIMIT :limite OFFSET :desde" y enlaza ambos valores con bindValue.
        $c = $this->bd->prepare(self::SELECCION . $donde . " ORDER BY $ordenSql LIMIT :limite OFFSET :desde");
        
        foreach ($valores as $nombre => $valor) {
            $c->bindValue($nombre, $valor);
        }
        $c->bindValue('limite', $porPagina, PDO::PARAM_INT);
        $c->bindValue('desde', ($pagina - 1) * $porPagina, PDO::PARAM_INT);
        $c->execute();

        return new Pagina(array_map($this->hidratar(...), $c->fetchAll()), $total, $pagina, $porPagina);
    }

    public function deSocio(int $socioId): array
    {
        // TODO 6: los productos del socio (WHERE p.socio_id = ?) ordenados por nombre, convertidos con hidratar().
        $c = $this->bd->prepare(self::SELECCION . ' WHERE p.socio_id = ? ORDER BY p.nombre');
        $c->execute([$socioId]);
        return array_map($this->hidratar(...), $c->fetchAll());
    }

    public function existeCodigo(string $codigo): bool
    {
        $c = $this->bd->prepare('SELECT 1 FROM productos WHERE codigo = ?');
        $c->execute([$codigo]);
        return $c->fetchColumn() !== false;
    }

    public function guardar(array $d): void
    {
        $sql = $this->existeCodigo($d['codigo'])
            ? 'UPDATE productos SET nombre = :nombre, categoria = :categoria, precio = :precio, stock = :stock, socio_id = :socio_id
               WHERE codigo = :codigo'
            : 'INSERT INTO productos (codigo, nombre, categoria, precio, stock, socio_id)
               VALUES (:codigo, :nombre, :categoria, :precio, :stock, :socio_id)';
        try {
            $this->bd->prepare($sql)->execute(array_intersect_key($d, array_flip(['codigo', 'nombre', 'categoria', 'precio', 'stock', 'socio_id'])));
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new Conflicto("Ya existe un producto con el código {$d['codigo']}.", 0, $e);
            }
            throw $e;
        }
    }

    public function eliminar(string $codigo): void
    {
        $c = $this->bd->prepare('DELETE FROM productos WHERE codigo = ?');
        $c->execute([$codigo]);
        if ($c->rowCount() === 0) {
            throw new NoEncontrado("No existe el producto $codigo.");
        }
    }

    /** Fila de MySQL → objeto Producto (con su Socio). */
    private function hidratar(array $f): Producto
    {
        return new Producto(
            $f['codigo'], $f['nombre'], Categoria::from($f['categoria']), $f['precio'], (int) $f['stock'],
            new Socio((int) $f['socio_id'], $f['socio_nombre'], $f['comunidad'], $f['telefono']),
            (int) $f['id'],
        );
    }
}