<?php /** plantillas/base.php — $contenido llega como Crudo; $titulo ya viene escapado. */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $titulo ?> · Flor de la Chiquitanía</title>
  <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
<header>
  <h1>Apicultores Flor de la Chiquitanía</h1>
  <nav><a href="/productos">Productos</a> · <a href="/socios">Socios</a> · <a href="/productos/nuevo">Registrar producto</a></nav>
</header>
<main>
<?= $contenido ?>
</main>
</body>
</html>
