<?php require APP_PATH . '/Views/partials/page_hero.php'; ?>

<section class="section">
    <div class="container">
        <div class="prose mx-auto text-center">
            <p class="fs-lg">
                Personaliza vasos, contenedores, servilletas, bolsas y cajas
                plegadizas para alimentos con el diseño de tu negocio.
            </p>
        </div>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="section__head reveal">
            <span class="section__eyebrow">Cómo funciona</span>
            <h2 class="section__title">Tu marca impresa en 4 pasos</h2>
        </div>
        <div class="steps">
            <article class="step-item reveal">
                <div class="step-item__num">1</div>
                <div class="step-item__title">Envías tu diseño</div>
                <p class="step-item__text">Compártenos tu logotipo o idea y la cantidad que necesitas.</p>
            </article>
            <article class="step-item reveal">
                <div class="step-item__num">2</div>
                <div class="step-item__title">Aprobamos el arte</div>
                <p class="step-item__text">Ajustamos colores y proporciones y te enviamos una vista previa.</p>
            </article>
            <article class="step-item reveal">
                <div class="step-item__num">3</div>
                <div class="step-item__title">Imprimimos</div>
                <p class="step-item__text">Producimos con tintas grado alimenticio en nuestro laboratorio.</p>
            </article>
            <article class="step-item reveal">
                <div class="step-item__num">4</div>
                <div class="step-item__title">Entregamos</div>
                <p class="step-item__text">Llevamos tu pedido a la puerta de tu negocio.</p>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="prose mx-auto text-center reveal">
            <span class="section__eyebrow">Ventajas</span>
            <h2 class="section__title">Calidad que promueve tu marca</h2>
            <p class="card__text">
                Ideal para negocios y agencias de mercadotecnia que buscan destacar con
                empaques a su medida.
            </p>
            <ul class="check-list check-list--center">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Desde 1,000 piezas</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Tintas grado alimenticio, libres de plomo</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Impresión detallada sobre cualquier superficie</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg> Opciones que se ajustan a cada cliente</li>
            </ul>
        </div>
    </div>
</section>

<section class="section section--soft">
    <div class="container">
        <div class="section__head reveal">
            <span class="section__eyebrow">Pruébalo tú mismo</span>
            <h2 class="section__title">Simula tu logo en un producto</h2>
            <p class="section__subtitle">Sube tu logo y ve una aproximación de cómo se vería impreso — es solo una idea, no el resultado final.</p>
        </div>
        <div class="reveal">
            <?php require APP_PATH . '/Views/partials/logo_simulator.php'; ?>
        </div>
        <div class="text-center mt-8">
            <a class="btn btn--accent" href="<?= url('/contacto') ?>">Cotiza tu diseño</a>
        </div>
    </div>
</section>

<?php require APP_PATH . '/Views/partials/cta_band.php'; ?>
