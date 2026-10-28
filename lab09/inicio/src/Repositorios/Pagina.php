<?php
// src/Repositorios/Pagina.php — Un "trozo" de resultados + lo necesario para dibujar la paginación.
declare(strict_types=1);

namespace Apicultores\Repositorios;

final readonly class Pagina
{
    /** @param array $elementos los elementos de ESTA página */
    public function __construct(
        public array $elementos,
        public int $total,          // total de resultados (todas las páginas)
        public int $numero,         // página actual, desde 1
        public int $porPagina,
    ) {
    }

    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->total / $this->porPagina));
    }

    public function tieneAnterior(): bool
    {
        return $this->numero > 1;
    }

    public function tieneSiguiente(): bool
    {
        return $this->numero < $this->totalPaginas();
    }

    /** "Mostrando 11–20 de 36" */
    public function rango(): string
    {
        if ($this->total === 0) {
            return 'Sin resultados';
        }
        $desde = ($this->numero - 1) * $this->porPagina + 1;
        return sprintf('Mostrando %d–%d de %d', $desde, $desde + count($this->elementos) - 1, $this->total);
    }
}