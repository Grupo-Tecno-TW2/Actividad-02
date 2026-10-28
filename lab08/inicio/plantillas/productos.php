<?php /** plantillas/productos.php — Lista con búsqueda. Todas las variables llegan escapadas. */ ?>
<?php if ($mensaje !== ''): ?><p class="exito" role="status"><?= $mensaje ?></p><?php endif; ?>
<form method="get" action="/productos" role="search">
  <label for="q">Buscar por nombre o código</label>
  <input id="q" name="q" type="search" value="<?= $q ?>">
  <button type="submit">Buscar</button>
</form>
<table>
  <caption><?= count($productos) ?> productos<?= $q !== '' ? " para «{$q}»" : '' ?></caption>
  <thead><tr><th scope="col">Código</th><th scope="col">Producto</th><th scope="col">Categoría</th><th scope="col">Precio</th><th scope="col">Stock</th><th scope="col">Socio</th><th scope="col">Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($productos as $p): ?>
    <tr>
      <td><?= $p['codigo'] ?></td>
      <td><?= $p['nombre'] ?></td>
      <td><?= $p['categoria'] ?></td>
      <td class="num"><?= $p['precio_bs'] ?></td>
      <td class="num"><?= $p['stock'] ?></td>
      <td><?= $p['socio'] ?> <small>(<?= $p['comunidad'] ?>)</small></td>
      <td>
        <a href="/productos/<?= $p['codigo'] ?>/editar">Editar</a>
        <form method="post" action="/productos/<?= $p['codigo'] ?>/eliminar" class="en-linea">
          <button type="submit">Eliminar</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
