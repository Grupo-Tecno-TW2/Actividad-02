<?php /** plantillas/socios.php */ ?>
<h2>Socios</h2>
<ul>
<?php foreach ($socios as $s): ?>
  <li><a href="/socios/<?= $s['id'] ?>"><?= $s['nombre'] ?></a> — <?= $s['comunidad'] ?></li>
<?php endforeach; ?>
</ul>
