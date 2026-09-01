# Guía de estilos — Importadora RYM

Convención para mantener el CSS estandarizado y reutilizable en **todo** el proyecto.

## Regla de oro

> En las vistas (`app/Views/**`) **solo se usan clases CSS**.
> Prohibido: `style="..."` inline, colores (`#fff`, `rgba(...)`) o medidas (`px`, `rem`)
> hardcodeadas en el HTML. Todo valor sale de un **token** o de una **clase**.

## Arquitectura CSS (orden de carga)

Los tres archivos se cargan en este orden en `layouts/main.php`:

| Archivo | Responsabilidad | Qué contiene |
|---|---|---|
| `assets/css/brand.css` | **Tokens** | Variables `--rym-*`: colores, tipografía, espaciado, sombras, radios. Única fuente de verdad. |
| `assets/css/app.css` | **Base + utilidades** | Reset, estilos base de etiquetas y clases utilitarias genéricas. |
| `assets/css/site.css` | **Componentes** | Bloques con nombre: `.btn`, `.card`, `.hero`, `.section`, `.form`, `.site-header`, `.site-footer`, etc. |

## Cómo decidir qué usar

1. ¿Existe un **componente** para esto? (`.btn`, `.card`, `.section`, `.hero`…) → úsalo.
2. ¿Es un ajuste puntual de layout/espaciado/texto? → usa una **utilidad** (`.mt-8`, `.text-center`, `.flex`…).
3. ¿Es un patrón nuevo que se repetirá? → **crea un componente** en `site.css` (no lo resuelvas inline).
4. ¿Necesitas un color o medida? → usa un **token** `var(--rym-*)`. Si falta, agrégalo a `brand.css`.

## Utilidades disponibles (`app.css`)

- **Texto:** `.text-center`, `.text-left`, `.text-right`, `.text-muted`, `.text-white`, `.text-on-dark`
- **Tipografía:** `.fs-sm`, `.fs-lg`, `.fw-semibold`, `.fw-bold`, `.underline`
- **Flex:** `.flex`, `.inline-flex`, `.flex-col`, `.flex-wrap`, `.items-center`, `.items-start`, `.justify-center`, `.justify-between`, `.gap-2|3|4|6`
- **Ancho/medida:** `.w-full`, `.mx-auto`, `.measure` (60ch), `.measure-narrow` (42ch)
- **Márgenes:** `.mt-0|2|3|4|6|8|12|16`, `.mb-0|2|3|4|6|8|12`
- **Contenedor:** `.container`, `.container--narrow`

## Componentes clave (`site.css`)

- **Botones:** `.btn` + variante (`--accent`, `--primary`, `--outline`, `--ghost`) + tamaño (`--lg`) + `--block`
- **Secciones:** `.section` (`--soft`, `--primary`), `.section__head`, `.section__eyebrow`, `.section__title`, `.section__subtitle`
- **Hero:** `.hero`, `.hero__inner`, `.hero__title`, `.hero__text`, `.hero__actions`, `.hero__card`; centrado: `.hero--center` + `.hero__stage`
- **Cards:** `.card` (`.card__icon`, `.card__title`, `.card__text`), `.prod-card`, `.cat-card`
- **Grid:** `.grid` + `.grid--3` / `.grid--4`
- **Formulario:** `.form`, `.form__row`, `.field`, `.alert` (`--ok`, `--error`)
- **CTA:** `.cta`, `.cta__grid`, `.cta__text`, `.cta__note`, `.link-on-dark`

## Animaciones

- Aparición al scroll: agrega la clase `.reveal` al elemento (lo activa `site.js` vía IntersectionObserver). El escalonado es automático por `:nth-child`.
- Conteo numérico: `<span data-count="2000" data-suffix="+">0</span>`.
- Todo se desactiva con `prefers-reduced-motion`.

## Al crear una vista nueva

- Reutiliza `.section`, `.container`, `.card`, `.btn`, etc.
- Si repites una estructura visual, extráela a un **partial** (`app/Views/partials`) y/o a un **componente** en `site.css`.
- Nunca dupliques colores/medidas: si algo no tiene token, créalo en `brand.css`.
