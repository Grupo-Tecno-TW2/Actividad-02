<?php
// public/index.php — Catálogo de productos y servicios. Un solo require: el autoload de Composer.
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use Apicultores\Catalogo;
use Apicultores\Modelo\Categoria;

$catalogo = Catalogo::desdeArchivo(__DIR__ . '/../datos/catalogo.json');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catálogo · Flor de la Chiquitanía</title>
  <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
<header>
  <h1>Apicultores Flor de la Chiquitanía</h1>
  <p><?php echo count($catalogo->todos()); ?> artículos · <?php echo count($catalogo->disponibles()); ?> disponibles ·
     inventario: <strong><?php echo bs($catalogo->valorInventario()); ?></strong></p>
</header>
<main>
<?php foreach (Categoria::cases() as $categoria): ?>
  <section id="<?php echo $categoria->slug(); ?>">
    <h2><?php echo e($categoria->value); ?> <small><?php echo e($categoria->descripcion()); ?></small></h2>
    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th>Detalle</th>
          <th class="num">Precio</th>
          <th class="num">Stock</th>
          <th class="num">Docena</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($catalogo->productos($categoria) as $p): ?>
        <?php 
            /** @var \Apicultores\Modelo\Producto $producto */
            $producto = $p;
            $esAgotado = !$producto->estaDisponible();
            $claseFila = $esAgotado ? 'class="agotado"' : '';
        ?>
        <tr <?php echo $claseFila; ?>>
          <td><?php echo e($producto->nombre()); ?></td>
          <td><?php echo e($producto->detalle()); ?></td>
          <td class="num"><?php echo $producto->precioFormateado(); ?></td>
          <td class="num">
            <?php if ($esAgotado): ?>
              <span class="insignia">Agotado</span>
            <?php else: ?>
              <?php echo $producto->stock(); ?> en stock
            <?php endif; ?>
          </td>
          <td class="num">docena: <?php echo bs($producto->precioVenta(12)); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
<?php endforeach; ?>

  <section id="servicios">
    <h2>Servicios</h2>
    <table>
      <thead>
        <tr>
          <th>Servicio</th>
          <th>Descripción</th>
          <th class="num">Tarifa</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($catalogo->servicios() as $s): ?>
        <?php 
            /** @var \Apicultores\Modelo\Servicio $servicio */
            $servicio = $s;
        ?>
        <tr>
          <td><?php echo e($servicio->nombre()); ?></td>
          <td>
            <?php echo e($servicio->nombre()); ?> — <?php echo $servicio->precioFormateado(); ?> <?php echo e($servicio->detalle()); ?>
          </td>
          <td class="num"><?php echo $servicio->precioFormateado(); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <p><a href="/api.php">Ver el catálogo en JSON</a></p>
</main>
<footer>
  <p>Apicultores Flor de la Chiquitanía — Sistema de Gestión</p>
</footer>
</body>
</html>