<?php
// src/Controladores/CatalogoControlador.php — Recibe la petición, usa el modelo y elige la vista. Nada de HTML aquí.
declare(strict_types=1);

namespace Apicultores\Controladores;

use Apicultores\Catalogo;
use Apicultores\Contratos\Vendible;
use Apicultores\Excepciones\Conflicto;
use Apicultores\Excepciones\DatosInvalidos;
use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Producto;
use Apicultores\Vista\Plantilla;
use DomainException;

final class CatalogoControlador
{
    public function __construct(private readonly Catalogo $catalogo, private readonly Plantilla $vista)
    {
    }

    public function inicio(): string
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $articulos = array_filter($this->catalogo->todos(),
            fn(Vendible $a): bool => $q === '' || mb_stripos($a->nombre(), $q) !== false);
        return $this->vista->pagina('catalogo', [
            'q' => $q,
            'articulos' => array_map($this->fila(...), array_values($articulos)),
            'inventario' => bs($this->catalogo->valorInventario()),
        ], 'Catálogo');
    }

    public function producto(string $codigo): string
    {
        $a = $this->catalogo->buscar($codigo) ?? throw new NoEncontrado("No existe el producto $codigo.");
        return $this->vista->pagina('producto', $this->fila($a), $a->nombre());
    }

    /** @return array<int, array> datos para la API (json_encode usa jsonSerialize) */
    public function api(): array
    {
        return $this->catalogo->todos();
    }

    public function formularioCotizacion(): string
    {
        return $this->mostrarCotizacion([], []);
    }

    public function cotizar(): string
    {
        $cantidades = (array) ($_POST['cantidad'] ?? []);
        $pedido = [];
        $errores = [];
        foreach ($cantidades as $codigo => $texto) {
            $codigo = (string) $codigo;
            if ($this->catalogo->buscar($codigo) === null) {
                continue;                                   // se ignoran códigos que no existen
            }
            $n = filter_var(trim((string) $texto) === '' ? '0' : $texto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000]]);
            if ($n === false) {
                $errores[$codigo] = 'Escribe un número entero entre 0 y 1000.';
            } elseif ($n > 0) {
                $pedido[$codigo] = $n;
            }
        }
        if ($errores === [] && $pedido === []) {
            $errores['general'] = 'Indica al menos una cantidad.';
        }
        if ($errores !== []) {
            throw new DatosInvalidos($errores);
        }
        // TODO 5: verificarStock() lanza DomainException (regla del MODELO). Atrápala y lanza en su lugar un
        //         Conflicto (error HTTP 409) con el mismo mensaje, pasando $e como tercer argumento ($previous).
        try {
            $this->catalogo->verificarStock($pedido);
        } catch (DomainException $e) {
            throw new Conflicto($e->getMessage(), 0, $e);
        }
        return $this->mostrarCotizacion($cantidades, [], $pedido);
    }

    /** Se usa también desde el manejador de errores para volver a mostrar el formulario con los errores (422). */
    public function mostrarCotizacion(array $cantidades, array $errores, array $pedido = []): string
    {
        $lineas = [];
        foreach ($pedido as $codigo => $n) {
            $a = $this->catalogo->buscar($codigo);
            $lineas[] = ['nombre' => $a->nombre(), 'cantidad' => $n, 'subtotal' => bs($a->precioVenta($n))];
        }
        return $this->vista->pagina('cotizacion', [
            'articulos' => array_map($this->fila(...), $this->catalogo->disponibles()),
            'cantidades' => array_map('strval', $cantidades),
            'errores' => $errores,
            'lineas' => $lineas,
            'total' => $pedido === [] ? null : bs($this->catalogo->totalPedido($pedido)),
        ], 'Cotización');
    }

    /** 
     * Convierte un objeto en un arreglo de textos para la vista (la vista no recibe objetos). 
     * @param Vendible|object $a
     */
    private function fila(Vendible $a): array
    {
        return [
            'codigo' => $a->codigo(), 
            'nombre' => $a->nombre(), 
            'tipo' => $a->tipo(),
            'precio' => $a->precioFormateado(), 
            'detalle' => $a->detalle(),
            'categoria' => $a instanceof Producto ? $a->categoria()->value : 'Servicios',
        ];
    }
}