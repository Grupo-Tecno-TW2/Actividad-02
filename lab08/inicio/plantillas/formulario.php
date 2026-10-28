<?php /** plantillas/formulario.php — Registrar y editar (mismo formulario). Variables escapadas. */
$campo = function (string $nombre, string $etiqueta, string $extra = '') use ($datos, $errores): void {
    $error = $errores[$nombre] ?? null;
    echo "<p><label for=\"f-$nombre\">$etiqueta</label>"
        . "<input id=\"f-$nombre\" name=\"$nombre\" value=\"{$datos[$nombre]}\" $extra"
        . ($error ? " aria-invalid=\"true\" aria-describedby=\"f-$nombre-error\">" : '>')
        . ($error ? "<span class=\"error\" id=\"f-$nombre-error\">$error</span>" : '') . '</p>';
};
?>
<h2><?= $nuevo ? 'Registrar producto' : "Editar {$datos['codigo']}" ?></h2>
<?php if ($errores !== []): ?><p class="alerta" role="alert"><?= $errores['general'] ?? 'Revisa los campos marcados.' ?></p><?php endif; ?>
<form method="post" action="<?= $accion ?>" novalidate>
  <?php if ($nuevo) { $campo('codigo', 'Código (ej. MIE-500)', 'maxlength="8" required'); } ?>
  <?php $campo('nombre', 'Nombre', 'maxlength="80" required'); ?>
  <p><label for="f-categoria">Categoría</label>
    <select id="f-categoria" name="categoria"><option value="">— elegir —</option>
      <?php foreach ($categorias as $cat): ?><option<?= $datos['categoria'] === $cat ? ' selected' : '' ?>><?= $cat ?></option><?php endforeach; ?>
    </select>
    <?php if (isset($errores['categoria'])): ?><span class="error"><?= $errores['categoria'] ?></span><?php endif; ?></p>
  <?php $campo('precio', 'Precio (Bs)', 'inputmode="decimal" required'); ?>
  <?php $campo('stock', 'Stock', 'inputmode="numeric" required'); ?>
  <p><label for="f-socio_id">Socio productor</label>
    <select id="f-socio_id" name="socio_id"><option value="">— elegir —</option>
      <?php foreach ($socios as $id => $nombre): ?><option value="<?= $id ?>"<?= $datos['socio_id'] === (string) $id ? ' selected' : '' ?>><?= $nombre ?></option><?php endforeach; ?>
    </select>
    <?php if (isset($errores['socio_id'])): ?><span class="error" id="f-socio_id-error"><?= $errores['socio_id'] ?></span><?php endif; ?></p>
  <button type="submit"><?= $nuevo ? 'Registrar' : 'Guardar cambios' ?></button>
</form>
