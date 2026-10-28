<?php
// src/Soporte/ConBolivianos.php — Trait: código reutilizable que se "copia" en cualquier clase que lo use.
// No es herencia: una clase puede usar varios traits aunque ya herede de otra.
declare(strict_types=1);

namespace Apicultores\Soporte;

trait ConBolivianos
{
    protected function enBolivianos(float $monto): string
    {
        return 'Bs ' . number_format($monto, 2, ',', '.');
    }
}
