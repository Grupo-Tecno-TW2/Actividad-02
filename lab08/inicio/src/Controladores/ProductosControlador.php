<?php
// src/Controladores/ProductosControlador.php — CRUD de productos contra MySQL.
declare(strict_types=1);

namespace Apicultores\Controladores;

use Apicultores\BaseDatos\ProductoConsultas;
use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Categoria;
use Apicultores\Validacion\ValidadorProducto;
use Apicultores\Vista\Plantilla;

final class ProductosControlador
{
    public function __construct(
        private readonly ProductoConsultas $consultas,
        private readonly ValidadorProducto $validador,
        private readonly Plantilla $vista,
    ) {
    }

    public function listar(): string
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $filas = $this->consultas->buscar($q);
        $mensaje = match (true) {
            isset($_GET['creado']) => "Producto {$_GET['creado']} registrado.",
            isset($_GET['actualizado']) => "Producto {$_GET['actualizado']} actualizado.",
            isset($_GET['eliminado']) => "Producto {$_GET['eliminado']} eliminado.",
            default => '',
        };
        return $this->vista->pagina('productos', [
            'q' => $q, 'mensaje' => (string) $mensaje,
            'productos' => array_map(fn(array $f): array => $f + ['precio_bs' => bs((float) $f['precio'])], $filas),
        ], 'Productos');
    }

    public function nuevo(): string
    {
        return $this->formulario('/productos', ['codigo' => '', 'nombre' => '', 'categoria' => '', 'precio' => '', 'stock' => '', 'socio_id' => ''], [], true);
    }

    public function crear(): never
    {
        $datos = $this->validador->validar($_POST, nuevo: true);
        $this->consultas->insertar($datos);
        $this->redirigir('/productos?creado=' . urlencode($datos['codigo']));
    }

    public function editar(string $codigo): string
    {
        $fila = $this->consultas->porCodigo($codigo) ?? throw new NoEncontrado("No existe el producto $codigo.");
        return $this->formulario('/productos/' . rawurlencode($codigo), $fila, [], false);
    }

    public function actualizar(string $codigo): never
    {
        $this->consultas->porCodigo($codigo) ?? throw new NoEncontrado("No existe el producto $codigo.");
        $datos = $this->validador->validar(['codigo' => $codigo] + $_POST, nuevo: false);
        $this->consultas->actualizar($codigo, $datos);
        $this->redirigir('/productos?actualizado=' . urlencode($codigo));
    }

    public function eliminar(string $codigo): never
    {
        $this->consultas->eliminar($codigo);
        $this->redirigir('/productos?eliminado=' . urlencode($codigo));
    }

    /** Usado también por el front controller para volver a mostrar el formulario con errores (422). */
    public function formulario(string $accion, array $datos, array $errores, bool $nuevo): string
    {
        return $this->vista->pagina('formulario', [
            'accion' => $accion, 'nuevo' => $nuevo, 'errores' => $errores,
            'datos' => array_map('strval', array_intersect_key($datos + array_fill_keys(['codigo', 'nombre', 'categoria', 'precio', 'stock', 'socio_id'], ''),
                array_flip(['codigo', 'nombre', 'categoria', 'precio', 'stock', 'socio_id']))),
            'categorias' => array_map(fn(Categoria $c): string => $c->value, Categoria::cases()),
            'socios' => $this->consultas->socios(),
        ], $nuevo ? 'Registrar producto' : 'Editar producto');
    }

    private function redirigir(string $url): never
    {
        header("Location: $url", true, 303);   // PRG (clase 3)
        exit;
    }
}
