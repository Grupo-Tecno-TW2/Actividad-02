<?php /** plantillas/catalogo.php — TODAS las variables llegan escapadas: aquí no hace falta e(). */ ?>
<form method="get" action="/" role="search">
  <label for="q">Buscar</label>
  <input id="q" name="q" type="search" value="<?= $q ?>">
  <button type="submit">Buscar</button>
</form>
<?php if ($q !== ''): ?><p>Resultados para «<?= $q ?>»: <?= count($articulos) ?></p><?php endif; ?>
<p>Valor del inventario: <strong><?= $inventario ?></strong></p>
<table>
  <thead><tr><th scope="col">Artículo</th><th scope="col">Categoría</th><th scope="col">Precio</th><th scope="col">Detalle</th></tr></thead>
  <tbody>
  <?php foreach ($articulos as $a): ?>
    <tr>
      <td><a href="/producto/<?= $a['codigo'] ?>"><?= $a['nombre'] ?></a></td>
      <td><?= $a['categoria'] ?></td>
      <td class="num"><?= $a['precio'] ?></td>
      <td><?= $a['detalle'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
