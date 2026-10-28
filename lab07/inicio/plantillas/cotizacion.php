<?php /** plantillas/cotizacion.php — Formulario de cotización; con 422 vuelve con lo escrito y los errores. */ ?>
<h2>Cotización para la feria</h2>
<?php if ($errores !== []): ?>
  <p class="alerta" role="alert"><?= $errores['general'] ?? 'Revisa las cantidades marcadas.' ?></p>
<?php endif; ?>
<form method="post" action="/cotizacion" novalidate>
  <table>
    <thead><tr><th scope="col">Artículo</th><th scope="col">Precio</th><th scope="col">Cantidad</th></tr></thead>
    <tbody>
    <?php foreach ($articulos as $a): $id = 'c-' . $a['codigo']; $error = $errores[$a['codigo']] ?? null; ?>
      <tr>
        <td><label for="<?= $id ?>"><?= $a['nombre'] ?></label></td>
        <td class="num"><?= $a['precio'] ?> <small><?= $a['detalle'] ?></small></td>
        <td>
          <input id="<?= $id ?>" name="cantidad[<?= $a['codigo'] ?>]" inputmode="numeric" size="4"
                 value="<?= $cantidades[$a['codigo']] ?? '' ?>"<?= $error ? " aria-invalid=\"true\" aria-describedby=\"$id-error\"" : '' ?>>
          <?php if ($error): ?><span class="error" id="<?= $id ?>-error"><?= $error ?></span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <button type="submit">Calcular</button>
</form>
<?php if ($total !== null): ?>
  <section aria-live="polite">
    <h3>Resumen</h3>
    <ul><?php foreach ($lineas as $l): ?><li><?= $l['cantidad'] ?> × <?= $l['nombre'] ?> = <?= $l['subtotal'] ?></li><?php endforeach; ?></ul>
    <p>Total: <strong><?= $total ?></strong> (desde 12 unidades de un producto: −10 %)</p>
  </section>
<?php endif; ?>
