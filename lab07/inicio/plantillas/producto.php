<?php /** plantillas/producto.php */ ?>
<p><a href="/">← Catálogo</a></p>
<h2><?= $nombre ?></h2>
<dl>
  <dt>Código</dt><dd><?= $codigo ?></dd>
  <dt>Tipo</dt><dd><?= $tipo ?> · <?= $categoria ?></dd>
  <dt>Precio</dt><dd><?= $precio ?></dd>
  <dt>Detalle</dt><dd><?= $detalle ?></dd>
</dl>
