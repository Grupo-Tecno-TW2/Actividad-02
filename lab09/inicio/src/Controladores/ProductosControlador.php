<?php
// src/Controladores/ProductosControlador.php — Usa REPOSITORIOS (interfaces) en lugar de SQL. Pagina, filtra y ordena.
declare(strict_types=1);

namespace Apicultores\Controladores;

use Apicultores\Excepciones\NoEncontrado;
use Apicultores\Modelo\Categoria;
use Apicultores\Modelo\Producto;
use Apicultores\Repositorios\ProductoRepositorio;
use Apicultores\Repositorios\SocioRepositorio;
use Apicultores\Validacion\ValidadorProducto;
use Apicultores\Vista\Plantilla;

final class ProductosControlador
{
    public const POR_PAGINA = 10;

    public function __construct(
        private readonly ProductoRepositorio $productos,
        private readonly SocioRepositorio $socios,
        private readonly ValidadorProducto $validador,
        private readonly Plantilla $vista,
    ) {
    }

    public function listar(): string
    {
        // Todo lo que llega por GET se normaliza: texto, categoría válida o null, orden permitido, página >= 1
        $q = trim((string) ($_GET['q'] ?? ''));
        $categoria = Categoria::tryFrom((string) ($_GET['categoria'] ?? ''));
        
        // TODO 4: normaliza lo que llega por GET:
        //   - $orden: solo una clave de ProductoRepositorio::ORDENES; si no, 'nombre'
        //   - $pedida: entero >= 1 (filter_var con FILTER_VALIDATE_INT y min_range); si no, 1
        $orden = array_key_exists(
            $_GET['orden'] ?? '', ProductoRepositorio::ORDENES
        ) ? $_GET['orden'] : 'nombre';
        
        $pedida = filter_var(
            $_GET['pagina'] ?? 1,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        ) ?: 1;

        $pagina = $this->productos->buscar($q, $categoria, $orden, $pedida, self::POR_PAGINA);
        if ($pedida > $pagina->totalPaginas()) {            // ?pagina=99 → la última página que exista
            $pagina = $this->productos->buscar($q, $categoria, $orden, $pagina->totalPaginas(), self::POR_PAGINA);
        }

        $filtros = array_filter(['q' => $q, 'categoria' => $categoria?->value, 'orden' => $orden === 'nombre' ? null : $orden]);
        $enlace = fn(int $n): string => '/productos?' . http_build_query($filtros + ['pagina' => $n]);
        return $this->vista->pagina('productos', [
            'q' => $q, 'categoria' => $categoria?->value ?? '', 'orden' => $orden,
            'categorias' => array_map(fn(Categoria $c): string => $c->value, Categoria::cases()),
            'ordenes' => ProductoRepositorio::ORDENES,
            'productos' => array_map($this->fila(...), $pagina->elementos),
            'rango' => $pagina->rango(), 'numero' => $pagina->numero, 'totalPaginas' => $pagina->totalPaginas(),
            'anterior' => $pagina->tieneAnterior() ? $enlace($pagina->numero - 1) : null,
            'siguiente' => $pagina->tieneSiguiente() ? $enlace($pagina->numero + 1) : null,
            'mensaje' => $this->mensaje(),
        ], 'Productos');
    }

    public function socios(): string
    {
        return $this->vista->pagina('socios', [
            'socios' => array_map(fn($s): array => ['id' => $s->id, 'nombre' => $s->nombre, 'comunidad' => $s->comunidad],
                $this->socios->todos()),
        ], 'Socios');
    }

    public function socio(string $id): string
    {
        $socio = $this->socios->porId((int) $id) ?? throw new NoEncontrado('No existe ese socio.');
        $productos = $this->productos->deSocio($socio->id);
        return $this->vista->pagina('socio', [
            'nombre' => $socio->nombre, 'comunidad' => $socio->comunidad, 'telefono' => $socio->telefono ?? 'sin teléfono',
            'productos' => array_map($this->fila(...), $productos),
            'valor' => bs(array_sum(array_map(fn(Producto $p): float => (float) $p->precio * $p->stock, $productos))),
        ], $socio->nombre);
    }

    public function nuevo(): string
    {
        return $this->formulario('/productos', [], [], true);
    }

    public function crear(): never
    {
        $this->productos->guardar($this->validador->validar($_POST, nuevo: true));
        $this->redirigir('/productos?creado=' . urlencode(strtoupper(trim((string) $_POST['codigo']))));
    }

    public function editar(string $codigo): string
    {
        $p = $this->productos->porCodigo($codigo) ?? throw new NoEncontrado("No existe el producto $codigo.");
        return $this->formulario('/productos/' . rawurlencode($codigo), [
            'codigo' => $p->codigo, 'nombre' => $p->nombre, 'categoria' => $p->categoria->value,
            'precio' => $p->precio, 'stock' => $p->stock, 'socio_id' => $p->socio->id], [], false);
    }

    public function actualizar(string $codigo): never
    {
        $this->productos->porCodigo($codigo) ?? throw new NoEncontrado("No existe el producto $codigo.");
        $this->productos->guardar($this->validador->validar(['codigo' => $codigo] + $_POST, nuevo: false));
        $this->redirigir('/productos?actualizado=' . urlencode($codigo));
    }

    public function eliminar(string $codigo): never
    {
        $this->productos->eliminar($codigo);
        $this->redirigir('/productos?eliminado=' . urlencode($codigo));
    }

    public function formulario(string $accion, array $datos, array $errores, bool $nuevo): string
    {
        $campos = ['codigo', 'nombre', 'categoria', 'precio', 'stock', 'socio_id'];
        $socios = [];
        foreach ($this->socios->todos() as $s) {
            $socios[$s->id] = $s->nombre;
        }
        return $this->vista->pagina('formulario', [
            'accion' => $accion, 'nuevo' => $nuevo, 'errores' => $errores,
            'datos' => array_map('strval', array_intersect_key($datos, array_flip($campos)) + array_fill_keys($campos, '')),
            'categorias' => array_map(fn(Categoria $c): string => $c->value, Categoria::cases()),
            'socios' => $socios,
        ], $nuevo ? 'Registrar producto' : 'Editar producto');
    }

    private function fila(Producto $p): array
    {
        return ['codigo' => $p->codigo, 'nombre' => $p->nombre, 'categoria' => $p->categoria->value, 'precio_bs' => $p->precioBs(),
                'stock' => $p->stock, 'socio' => $p->socio->nombre, 'socio_id' => $p->socio->id, 'comunidad' => $p->socio->comunidad];
    }

    private function mensaje(): string
    {
        foreach (['creado' => 'registrado', 'actualizado' => 'actualizado', 'eliminado' => 'eliminado'] as $clave => $verbo) {
            if (isset($_GET[$clave])) {
                return "Producto {$_GET[$clave]} $verbo.";
            }
        }
        return '';
    }

    private function redirigir(string $url): never
    {
        header("Location: $url", true, 303);
        exit;
    }
}