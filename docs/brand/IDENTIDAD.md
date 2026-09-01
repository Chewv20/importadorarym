# Identidad de marca — Importadora RYM S.A. de C.V.

Resumen operativo extraído del *Manual de Identidad Gráfica*
(`docs/brand/Manual de identidad Gráfiica Rym.pdf`) para uso en el desarrollo de la landing page.

## Sobre la empresa

Importadora RYM se dedica a la **fabricación, distribución y venta de insumos y
desechables** para empresas (giro alimentos/food service: vasos, cubiertos,
servilletas, etc. — reflejado en el símbolo de la marca).

## Colores corporativos

Los 5 colores provienen de los elementos del símbolo (Manual pág. 8). Valores
Pantone oficiales convertidos a sRGB (Coated) para web.

| Rol | Elemento | Pantone | HEX | RGB |
|-----|----------|---------|-----|-----|
| **Primario** (navy) | Círculo | Blue 072 C | `#10069F` | 16, 6, 159 |
| **Acento cálido** | Servilleta | 193 C | `#C6093B` | 198, 9, 59 |
| **Highlight** | Vaso | 381 C | `#CEDC00` | 206, 220, 0 |
| **Secundario** (azul brillante) | Cubiertos | 300 C | `#005EB8` | 0, 94, 184 |
| **Gris de texto** | Logotipo | Cool Gray 9 C | `#75787B` | 117, 120, 123 |

> Nota: el azul `Blue 072 C` es un azul profundo con matiz índigo. Si en pantalla
> se percibe más violeta que el navy del logo JPG, se ajusta **solo** el token
> `--rym-primary` sin tocar el resto de la paleta.

### Aplicación sugerida en la landing
- **Primario `#10069F`** — barra de navegación, footer, títulos fuertes, fondos de sección de confianza.
- **Secundario `#005EB8`** — enlaces, botones principales, iconos.
- **Carmín `#C6093B`** — CTA de mayor peso ("Cotiza ahora"), badges, detalles.
- **Lima `#CEDC00`** — subrayados, highlights, acentos puntuales (usar con moderación).
- **Gris `#75787B`** — texto secundario, párrafos, líneas divisorias.

## Tipografía

- **Nombre de marca / wordmark:** *Aldus Roman*, **siempre en MAYÚSCULAS**
  (Manual pág. 9 — "por ningún motivo puede ir en minúsculas").
  Aldus es una serif humanista tipo Palatino (Hermann Zapf).
  - Stack web del wordmark: `"Palatino Linotype", "Book Antiqua", Palatino, Georgia, serif`.
  - Alternativa display en Google Fonts (caps elegantes): **Marcellus**.
- **Títulos de sección (UI):** **Poppins** — sans geométrica, moderna y confiable
  (afín a los títulos redondeados del propio manual).
- **Cuerpo de texto:** **Inter** — alta legibilidad en pantalla.

## Uso del logo

- Archivo header: `public/assets/img/logos/importadorarym.jpg` (versión horizontal,
  284×92 px, fondo blanco). Conviene generar una versión **SVG o PNG @2x** para
  pantallas retina.
- **Área de protección:** mínimo `1x` perimetral libre alrededor de la identidad
  (Manual pág. 13), donde `x` = alto de la "I" de IMPORTADORA.
- **Usos incorrectos** (Manual pág. 15): no deformar, no recolorear, no fragmentar,
  no cambiar la tipografía ni las proporciones.
- Versiones válidas: color (CMYK original), 3 tintas, escala de grises y negro.

## Supergráfico

Elementos del símbolo (plato, vaso, cubiertos) usados como gráfico de fondo con
**opacidad entre 10 % y 20 %** y contorno blanco de separación (Manual pág. 16).
Útil como textura sutil en secciones hero/CTA.
