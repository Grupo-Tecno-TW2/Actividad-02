<?php
// src/Modelo/Categoria.php — Enum respaldado por string: SOLO existen estas tres categorías.
// Reemplaza a la constante CATEGORIAS del lab 3 y a la clase Categoria del lab 5.
declare(strict_types=1);

namespace Apicultores\Modelo;

enum Categoria: string
{
    case Miel = 'Miel';
    case Derivados = 'Derivados';
    case Cera = 'Cera';

    public function descripcion(): string
    {
        return match ($this) {
            self::Miel => 'Miel pura de la Chiquitanía',
            self::Derivados => 'Polen, propóleo y más',
            self::Cera => 'Cera de abeja',
        };
    }

    public function slug(): string
    {
        return strtolower($this->value);
    }
}
