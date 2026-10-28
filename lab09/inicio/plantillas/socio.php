<?php /** plantillas/socio.php — Un socio con sus productos (relación 1 a N). */ ?>
<p><a href="/socios">← Socios</a></p>
<h2><?= $nombre ?></h2>
<p><?= $comunidad ?> · <?= $telefono ?></p>
<table>
  <caption><?= count($productos) ?> productos · valor en stock <?= $valor ?></caption>
  <thead><tr><th scope="col">Código</th><th scope="col">Producto</th><th scope="col">Precio</th><th scope="col">Stock</th></tr></thead>
  <tbody>
  <?php foreach ($productos as $p): ?>
    <tr><td><?= $p['codigo'] ?></td><td><?= $p['nombre'] ?></td><td class="num"><?= $p['precio_bs'] ?></td><td class="num"><?= $p['stock'] ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
