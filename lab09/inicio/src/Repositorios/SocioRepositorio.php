<?php
// src/Repositorios/SocioRepositorio.php — Socios (solo MySQL en este laboratorio).
declare(strict_types=1);

namespace Apicultores\Repositorios;

use Apicultores\Modelo\Socio;
use PDO;

final class SocioRepositorio
{
    public function __construct(private readonly PDO $bd)
    {
    }

    /** @return Socio[] */
    public function todos(): array
    {
        $filas = $this->bd->query('SELECT id, nombre, comunidad, telefono FROM socios ORDER BY nombre')->fetchAll();
        return array_map(fn(array $f): Socio => new Socio((int) $f['id'], $f['nombre'], $f['comunidad'], $f['telefono']), $filas);
    }

    public function porId(int $id): ?Socio
    {
        $c = $this->bd->prepare('SELECT id, nombre, comunidad, telefono FROM socios WHERE id = ?');
        $c->execute([$id]);
        $f = $c->fetch();
        return $f ? new Socio((int) $f['id'], $f['nombre'], $f['comunidad'], $f['telefono']) : null;
    }
}
