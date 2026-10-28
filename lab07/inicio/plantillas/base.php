<?php /** plantillas/base.php — Diseño común. $contenido llega como Crudo (HTML de otra plantilla); $titulo ya viene escapado. */ ?>
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
  <nav><a href="/">Catálogo</a> · <a href="/cotizacion">Cotización</a> · <a href="/api/articulos">API</a></nav>
</header>
<main>
<?= $contenido ?>
</main>
</body>
</html>
