<?php
// demo.php — Recorrido por consola: php demo.php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use Apicultores\Catalogo;
use Apicultores\Contratos\Vendible;
use Apicultores\Modelo\{Articulo, Categoria, Producto, Servicio};

$c = Catalogo::desdeArchivo(__DIR__ . '/datos/catalogo.json');
$miel = $c->buscar('MIE-500');
$taller = $c->buscar('SRV-TAL');

echo "1) Nombre completo de la clase:  ", $miel::class, PHP_EOL;
echo "2) Enum:                         ", Categoria::from('Miel')->descripcion(), ' | tryFrom("Hongos") → ', var_export(Categoria::tryFrom('Hongos'), true), PHP_EOL;
echo "3) Herencia e interfaz:          ", var_export($miel instanceof Articulo, true), ' ', var_export($taller instanceof Vendible, true), ' ', var_export($taller instanceof Producto, true), PHP_EOL;
echo "4) Polimorfismo precioVenta:     2 miel → ", bs($miel->precioVenta(2)), ' · 12 miel → ', bs($miel->precioVenta(12)), ' · 12 taller → ', bs($taller->precioVenta(12)), PHP_EOL;
echo "5) detalle():                    ", $miel->detalle(), ' | ', $taller->detalle(), PHP_EOL;
echo "6) Trait:                        ", implode(', ', class_uses(Articulo::class)), ' → ', $miel->precioFormateado(), PHP_EOL;
try {
    new Articulo('ABC-10', 'x', 1.0);     // ¿un "artículo" a secas? No existe: es abstracto
} catch (Error $e) {
    echo "7) new Articulo(...):            ", $e->getMessage(), PHP_EOL;
}
echo "8) Pedido mixto:                 12 MIE-500 + 1 SRV-EXT → ", bs($c->totalPedido(['MIE-500' => 12, 'SRV-EXT' => 1])), PHP_EOL;
echo "9) json_encode:                  ", json_encode($taller, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION), PHP_EOL;
echo "10) Inventario (sin servicios):  ", bs($c->valorInventario()), PHP_EOL;
