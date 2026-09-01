<?php /* Widget de chat de WhatsApp — solo en el sitio público (layouts/main.php). */ ?>
<div class="wa-widget" data-wa-widget>

    <div class="wa-panel" data-wa-panel role="dialog" aria-label="Chat de WhatsApp">
        <div class="wa-panel__head">
            <span class="wa-avatar" aria-hidden="true">RYM</span>
            <div class="wa-panel__id">
                <span class="wa-panel__name">Importadora RYM</span>
                <span class="wa-panel__status"><span class="wa-dot" aria-hidden="true"></span> En línea</span>
            </div>
            <button type="button" class="wa-panel__close" data-wa-close aria-label="Cerrar chat">&times;</button>
        </div>

        <div class="wa-panel__body">
            <p class="wa-bubble">¡Hola! 👋 ¿En qué te ayudamos hoy? Elige una opción y seguimos la conversación por WhatsApp.</p>
            <div class="wa-options">
                <a class="wa-opt" target="_blank" rel="noopener"
                   href="<?= e(whatsapp_url('Hola, quiero solicitar una cotización.')) ?>">💬 Solicitar cotización</a>
                <a class="wa-opt" href="<?= url('/productos') ?>">📦 Ver catálogo</a>
                <a class="wa-opt" target="_blank" rel="noopener"
                   href="<?= e(whatsapp_url('Hola, me gustaría hablar con un asesor.')) ?>">🧑‍💼 Hablar con un asesor</a>
                <a class="wa-opt" target="_blank" rel="noopener"
                   href="<?= e(whatsapp_url('Hola, quiero información sobre impresión personalizada de productos.')) ?>">🎨 Personalización / impresión</a>
            </div>
        </div>

        <div class="wa-panel__foot">Te responderemos por WhatsApp</div>
    </div>

    <button type="button" class="wa-fab" data-wa-toggle aria-expanded="false" aria-label="Abrir chat de WhatsApp">
        <svg class="wa-fab__icon" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true" focusable="false">
            <path d="M16.04 4C9.9 4 4.92 8.98 4.92 15.12c0 2.02.54 3.98 1.56 5.7L4.8 27.2l6.56-1.66c1.66.9 3.52 1.38 5.4 1.38h.01c6.14 0 11.12-4.98 11.12-11.12S22.18 4 16.04 4zm0 20.36h-.01c-1.68 0-3.32-.45-4.76-1.3l-.34-.2-3.9.98 1.04-3.8-.22-.36a9.2 9.2 0 0 1-1.4-4.9c0-5.08 4.14-9.22 9.24-9.22 2.47 0 4.78.96 6.52 2.7a9.16 9.16 0 0 1 2.7 6.52c0 5.1-4.14 9.24-9.22 9.24zm5.06-6.9c-.28-.14-1.64-.8-1.9-.9-.26-.1-.44-.14-.62.14-.18.28-.72.9-.88 1.08-.16.18-.32.2-.6.07-.28-.14-1.18-.44-2.24-1.38-.83-.74-1.38-1.65-1.55-1.93-.16-.28-.02-.43.12-.57.13-.13.28-.32.42-.48.14-.16.18-.28.28-.46.09-.18.05-.34-.02-.48-.07-.14-.62-1.5-.86-2.06-.22-.54-.45-.46-.62-.47l-.53-.01c-.18 0-.48.07-.73.34-.25.28-.96.94-.96 2.3 0 1.35.98 2.66 1.12 2.84.14.18 1.94 2.96 4.7 4.15.66.28 1.17.45 1.57.58.66.21 1.26.18 1.73.11.53-.08 1.64-.67 1.87-1.32.23-.65.23-1.2.16-1.32-.07-.12-.25-.19-.53-.33z"/>
        </svg>
        <svg class="wa-fab__close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" focusable="false">
            <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
    </button>

</div>
