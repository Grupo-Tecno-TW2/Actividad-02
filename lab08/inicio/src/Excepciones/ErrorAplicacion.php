<?php
// src/Excepciones/ErrorAplicacion.php — Raíz de NUESTRAS excepciones: cada una sabe qué código HTTP le corresponde.
// Todo lo que NO herede de aquí es un error inesperado (500) y su detalle nunca se muestra al usuario.
declare(strict_types=1);

namespace Apicultores\Excepciones;

use RuntimeException;

abstract class ErrorAplicacion extends RuntimeException
{
    abstract public function codigoHttp(): int;

    /** Título corto para la página de error (el mensaje sí es seguro de mostrar: lo escribimos nosotros). */
    abstract public function titulo(): string;
}
