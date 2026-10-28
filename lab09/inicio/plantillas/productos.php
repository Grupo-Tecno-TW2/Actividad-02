<?php /** plantillas/productos.php — Lista paginada con filtros. Todas las variables llegan escapadas. */ ?>
<?php if ($mensaje !== ''): ?><p class="exito" role="status"><?= $mensaje ?></p><?php endif; ?>
<form method="get" action="/productos" role="search" class="filtros">
  <label for="q">Buscar</label>
  <input id="q" name="q" type="search" value="<?= $q ?>">
  <label for="categoria">Categoría</label>
  <select id="categoria" name="categoria"><option value="">Todas</option>
    <?php foreach ($categorias as $cat): ?><option<?= $categoria === $cat ? ' selected' : '' ?>><?= $cat ?></option><?php endforeach; ?>
  </select>
  <label for="orden">Ordenar</label>
  <select id="orden" name="orden">
    <?php foreach ($ordenes as $clave => $texto): ?><option value="<?= $clave ?>"<?= $orden === $clave ? ' selected' : '' ?>><?= $texto ?></option><?php endforeach; ?>
  </select>
  <button type="submit">Aplicar</button>
</form>
<table>
  <caption><?= $rango ?></caption>
  <thead><tr><th scope="col">Código</th><th scope="col">Producto</th><th scope="col">Categoría</th><th scope="col">Precio</th><th scope="col">Stock</th><th scope="col">Socio</th><th scope="col">Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($productos as $p): ?>
    <tr>
      <td><?= $p['codigo'] ?></td>
      <td><?= $p['nombre'] ?></td>
      <td><?= $p['categoria'] ?></td>
      <td class="num"><?= $p['precio_bs'] ?></td>
      <td class="num"><?= $p['stock'] ?></td>
      <td><a href="/socios/<?= $p['socio_id'] ?>"><?= $p['socio'] ?></a></td>
      <td>
        <a href="/productos/<?= $p['codigo'] ?>/editar">Editar</a>
        <form method="post" action="/productos/<?= $p['codigo'] ?>/eliminar" class="en-linea"><button type="submit">Eliminar</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<nav aria-label="Paginación" class="paginacion">
  <?php if ($anterior !== null): ?><a href="<?= $anterior ?>" rel="prev">← Anterior</a><?php endif; ?>
  <span aria-current="page">Página <?= $numero ?> de <?= $totalPaginas ?></span>
  <?php if ($siguiente !== null): ?><a href="<?= $siguiente ?>" rel="next">Siguiente →</a><?php endif; ?>
</nav>
