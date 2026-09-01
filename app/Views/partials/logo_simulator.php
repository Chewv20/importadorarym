<?php /** Simulador interactivo: el visitante sube su logo y lo ve sobre un vaso, servilleta o bolsa. Todo el procesamiento ocurre en el navegador — el archivo nunca se sube al servidor. */ ?>
<div class="logo-sim" data-logo-sim>
    <div class="logo-sim__tabs" role="tablist" aria-label="Elige un producto">
        <button type="button" class="filter-tab is-active" data-logo-sim-tab="vaso" role="tab" aria-selected="true">Vaso</button>
        <button type="button" class="filter-tab" data-logo-sim-tab="servilleta" role="tab" aria-selected="false">Servilleta</button>
        <button type="button" class="filter-tab" data-logo-sim-tab="bolsa" role="tab" aria-selected="false">Bolsa</button>
    </div>

    <div class="logo-sim__stage">
        <canvas class="logo-sim__canvas" role="img" aria-label="Vista previa de tu logo sobre el producto elegido"></canvas>
        <div class="logo-sim__empty" data-logo-sim-empty>
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
            <span>Sube tu logo para verlo aquí</span>
        </div>
    </div>

    <p class="logo-sim__error" data-logo-sim-error hidden></p>

    <div class="logo-sim__controls">
        <div class="logo-sim__field">
            <label for="logoSimFile">Tu logo (PNG, JPG, WEBP o SVG · máx. 5 MB)</label>
            <input type="file" id="logoSimFile" data-logo-sim-file accept="image/png,image/jpeg,image/webp,image/svg+xml">
        </div>
        <div class="logo-sim__field">
            <label for="logoSimScale">Tamaño</label>
            <input type="range" id="logoSimScale" data-logo-sim-scale min="40" max="200" value="100">
        </div>
        <button type="button" class="btn btn--outline btn--sm" data-logo-sim-reset>Reiniciar</button>
    </div>

    <p class="logo-sim__hint text-muted fs-sm">Arrastra el logo para moverlo (o usa las flechas del teclado). Es una simulación aproximada — el resultado real de impresión puede variar según el material, la técnica y el tamaño de la pieza.</p>
    <p class="logo-sim__privacy text-muted fs-sm">🔒 Tu logo no se sube a nuestros servidores: todo el proceso ocurre en tu navegador.</p>
</div>
