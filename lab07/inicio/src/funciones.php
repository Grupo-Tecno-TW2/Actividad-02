<?php
// src/funciones.php — Funciones globales de ayuda. Composer las carga con "files" (no son clases: PSR-4 no aplica).
declare(strict_types=1);

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function bs(float $monto): string
{
    return 'Bs ' . number_format($monto, 2, ',', '.');
}
