<?php
// src/Vista/Plantilla.php — Motor de plantillas mínimo con ESCAPE AUTOMÁTICO (la idea de Twig y Blade).
// La plantilla recibe los datos ya escapados: olvidar e() deja de ser un agujero de seguridad.
declare(strict_types=1);

namespace Apicultores\Vista;

use InvalidArgumentException;

final class Plantilla
{
    public function __construct(private readonly string $carpeta)
    {
    }

    /** Renderiza plantillas/$nombre.php dentro de plantillas/base.php. */
    public function pagina(string $nombre, array $datos = [], string $titulo = ''): string
    {
        $contenido = $this->parcial($nombre, $datos);
        return $this->parcial('base', ['titulo' => $titulo, 'contenido' => new Crudo($contenido)]);
    }

    public function parcial(string $nombre, array $datos = []): string
    {
        if (!preg_match('/^[a-z_]+$/', $nombre) || !is_file($archivo = "{$this->carpeta}/$nombre.php")) {
            throw new InvalidArgumentException("No existe la plantilla $nombre.");
        }
        $variables = self::escapar($datos);
        return (static function (string $__archivo, array $__variables): string {
            extract($__variables, EXTR_SKIP);   // cada clave es una variable en la plantilla
            ob_start();                         // captura el HTML en lugar de enviarlo
            try {
                require $__archivo;
                return (string) ob_get_contents();
            } finally {
                ob_end_clean();                 // se limpia SIEMPRE, aunque la plantilla falle
            }
        })($archivo, $variables);
    }

    /** Escapa recursivamente los textos; los números y booleanos pasan igual; Crudo pasa sin tocar. */
    public static function escapar(mixed $valor): mixed
    {
        return match (true) {
            is_string($valor) => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'),
            is_array($valor) => array_map(self::escapar(...), $valor),
            $valor instanceof Crudo, is_int($valor), is_float($valor), is_bool($valor), $valor === null => $valor,
            default => throw new InvalidArgumentException(
                'La vista recibe textos, números o arreglos; convierte los objetos antes (' . get_debug_type($valor) . ').'),
        };
    }
}
