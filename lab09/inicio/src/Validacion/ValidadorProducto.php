<?php
// src/Validacion/ValidadorProducto.php — Validación de productos; consulta los repositorios (lab 9).
declare(strict_types=1);

namespace Apicultores\Validacion;

use Apicultores\Repositorios\ProductoRepositorio;
use Apicultores\Repositorios\SocioRepositorio;
use Apicultores\Excepciones\DatosInvalidos;
use Apicultores\Modelo\Categoria;

final class ValidadorProducto
{
    public function __construct(
        private readonly ProductoRepositorio $productos,
        private readonly SocioRepositorio $socios,
    ) {
    }

    /**
     * @param bool $nuevo true al registrar (se valida el código); false al editar (el código no cambia)
     * @return array datos normalizados, listos para guardar
     * @throws DatosInvalidos
     */
    public function validar(array $entrada, bool $nuevo): array
    {
        $errores = [];
        foreach ($entrada as $valor) {
            if (is_string($valor) && !mb_check_encoding($valor, 'UTF-8')) {
                throw new DatosInvalidos(['general' => 'Los datos enviados no tienen una codificación válida (UTF-8).']);
            }
        }
        $codigo = strtoupper(trim((string) ($entrada['codigo'] ?? '')));
        $nombre = trim((string) ($entrada['nombre'] ?? ''));
        $categoria = Categoria::tryFrom((string) ($entrada['categoria'] ?? ''));
        $precio = filter_var(str_replace(',', '.', trim((string) ($entrada['precio'] ?? ''))), FILTER_VALIDATE_FLOAT,
            ['options' => ['min_range' => 0.01, 'max_range' => 10000]]);
        $stock = filter_var(trim((string) ($entrada['stock'] ?? '')), FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 100000]]);
        $socio = filter_var($entrada['socio_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($nuevo) {
            if (!preg_match('/^[A-Z]{3}-[A-Z0-9]{2,4}$/', $codigo)) {
                $errores['codigo'] = 'El código debe tener el formato AAA-999 (ej. MIE-500).';
            } elseif ($this->productos->existeCodigo($codigo)) {
                $errores['codigo'] = "Ya existe un producto con el código $codigo.";
            }
        }
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 80) {
            $errores['nombre'] = 'El nombre debe tener entre 3 y 80 caracteres.';
        }
        if ($categoria === null) {
            $errores['categoria'] = 'Elige una categoría de la lista.';
        }
        if ($precio === false) {
            $errores['precio'] = 'El precio debe ser un número entre 0,01 y 10.000.';
        }
        if ($stock === false) {
            $errores['stock'] = 'El stock debe ser un número entero entre 0 y 100.000.';
        }
        if ($socio === false || $this->socios->porId($socio) === null) {
            $errores['socio_id'] = 'Elige un socio de la lista.';
        }
        if ($errores !== []) {
            throw new DatosInvalidos($errores);
        }
        return ['codigo' => $codigo, 'nombre' => $nombre, 'categoria' => $categoria->value,
                'precio' => number_format($precio, 2, '.', ''),   // DECIMAL: se envía como texto exacto "32.50"
                'stock' => $stock, 'socio_id' => $socio];
    }
}
