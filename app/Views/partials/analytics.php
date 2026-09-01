<?php
/**
 * Google Analytics 4. Solo expone el ID en un <meta>; la lógica vive en
 * assets/js/analytics.js (nada de JS embebido en PHP).
 */
$gaId = (string) config('app.analytics_id');
if ($gaId === '') {
    return;
}
?>
<meta name="ga-id" content="<?= e($gaId) ?>">
<script src="<?= asset('assets/js/analytics.js') ?>" defer></script>
