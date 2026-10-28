<?php
// src/Vista/Crudo.php — Marca un texto como HTML CONFIABLE (lo generó otra plantilla): no se vuelve a escapar.
// Úsalo solo con HTML que producimos nosotros, nunca con datos del usuario.
declare(strict_types=1);

namespace Apicultores\Vista;

final class Crudo
{
    public function __construct(public readonly string $html)
    {
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
