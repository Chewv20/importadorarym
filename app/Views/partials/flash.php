<?php
/** Muestra los mensajes flash del portal (éxito / error). */
$ok  = flash('portal_ok');
$err = flash('portal_error');
?>
<?php if ($ok): ?><div class="alert alert--ok mb-6"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert--error mb-6"><?= e($err) ?></div><?php endif; ?>
