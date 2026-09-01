<?php
/**
 * Paginación reutilizable.
 * @var int    $page     Página actual
 * @var int    $pages    Total de páginas
 * @var string $baseUrl  URL base (sin ?page)
 */
if (($pages ?? 1) <= 1) {
    return;
}
$mk = static function (int $p) use ($baseUrl): string {
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    return e($baseUrl . $sep . 'page=' . $p);
};
?>
<nav class="pagination" aria-label="Paginación">
    <?php if ($page > 1): ?>
        <a class="pagination__link" href="<?= $mk($page - 1) ?>" rel="prev" aria-label="Anterior">&laquo;</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a class="pagination__link <?= $i === $page ? 'is-active' : '' ?>" href="<?= $mk($i) ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($page < $pages): ?>
        <a class="pagination__link" href="<?= $mk($page + 1) ?>" rel="next" aria-label="Siguiente">&raquo;</a>
    <?php endif; ?>
</nav>
