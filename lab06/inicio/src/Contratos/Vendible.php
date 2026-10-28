<?php
// src/Contratos/Vendible.php — El CONTRATO de todo lo que la asociación puede vender.
// Una interfaz dice QUÉ se puede hacer, no CÓMO: cada clase lo resuelve a su manera.
declare(strict_types=1);

namespace Apicultores\Contratos;

interface Vendible
{
    public function codigo(): string;

    public function nombre(): string;

    /** Total a cobrar por $cantidad unidades (cada tipo aplica sus propias reglas). */
    public function precioVenta(int $cantidad): float;

    public function estaDisponible(): bool;

    public function vender(int $cantidad): void;
}
