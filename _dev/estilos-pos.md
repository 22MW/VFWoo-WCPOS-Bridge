# Estilos y componentes del POS (referencia obligatoria)

**Regla:** toda pantalla del bridge dentro del POS se construye con el **HTML y las clases que ya usa el POS**. No se inventa CSS de botones, bordes, colores, tablas, desplegables, ventanas, paginación, interruptores ni preloader. Antes de escribir una pantalla, se mira **este documento**. Si falta una pieza, se inspecciona el POS (ver «Cómo inspeccionar»), se **apunta aquí** y se reutiliza.

El CSS propio solo puede ser de **disposición** (flex, grid, anchos de columna, alturas) y nunca de aspecto (colores, bordes, sombras, radios, tipografías). Si hace falta color, se usan las variables del POS.

Verificado en Webkul POS 7.1.1, leyendo su hoja `assets/dist/app/style.css`, su bundle `assets/dist/app/index.js` y el DOM de las pantallas Pedidos y Clientes (2026-11-19). Webkul puede cambiar estas clases en una actualización: revisar tras cada una.

---

## 1. Variables y temas

El tema lo marca `body[theme="light"]` / `body[theme="dark"]`. Todo el aspecto cuelga de estas variables, así que usarlas da claro y oscuro gratis:

| Variable | Qué es | Nota |
|---|---|---|
| `--primary-accent` | El naranja de acento (bordes, marca, activo) | **Usar este** para acentos |
| `--primary` | «Primario» del tema | En claro es **blanco**: no sirve para acentos |
| `--secondary` | Gris de fondo de filas, botones y campos | |
| `--fixed` | Superficie opaca (blanco en claro, negro en oscuro) | Fondo de tarjetas, ventanas, preloader |
| `--text-color` | Texto normal | |
| `--text-color-light` | Texto secundario | Etiquetas, notas |

## 2. Elementos que el POS ya estila globalmente

Probado creándolos dentro del POS y leyendo el estilo calculado:

- **`<button>` normal:** fondo `--secondary`, texto `--text-color`, padding 12px, radio 8px, sin borde. **No hace falta CSS.**
- **`<button class="primary">`** (o `pos-btn-primary`): fondo `--primary-accent`, texto blanco. Es el botón de acción principal.
- **`<input>`, `<select>`, `<textarea>`:** ya tienen el aspecto del POS.
- **`<input type="checkbox">`:** el POS le quita el aspecto nativo y queda **invisible** fuera de sus formularios. Se arregla igual que el POS en sus ventanas: `appearance: auto; accent-color: var(--primary-accent)` (única excepción permitida).
- **`.no-result`:** caja centrada gris (`--secondary`), radio, texto en negrita. Para «sin resultados», avisos y errores.
- **Cuidado:** el ajuste del desplegable de estado del pedido (`tidyStatusSelect`, en `pos-bridge.js`) solo debe actuar sobre selects con `option[value="wc-completed"]`. Si no, vacía cualquier desplegable del POS (arreglado en esta versión).

## 3. Esqueleto de una pantalla (Pedidos, la referencia)

```
main.pos-spa
  div.grid-container.grid-exception
    div.pos-tabContent.pos-order.orders          ← un solo elemento con estas 3 clases
      div > nav.pos-tabs > ul > li > a.wkwcpos-tabs.pos-active   (pestañas; la activa lleva pos-active)
      div.postabContainer.orders                 (overflow:auto; alto: 100vh - 135px)
        div.pos-order-list                       ← lista (izquierda)
        div > div.pos-order-short + div.pos-sale-summary   ← detalle (derecha)
```

- `.pos-order .pos-order-list`: tarjeta con fondo `--fixed`, padding 15px, radio 8px, flex en columna y alto `calc(100dvh - 145px)`.
- Las reglas del POS cuelgan de esa cadena de clases (`.pos-order .pos-order-list .dropdownlist-order …`): **hay que respetar la anidación** para heredar el aspecto.
- En el bridge, `core.addScreen()` ya registra la ruta y el menú; la pantalla recibe un `div` raíz dentro de `grid-container`.

## 4. Lista (tabla)

```
div.pos-order-list
  div.pos-order-search            buscador: svg de lupa + <input>; borde inferior, sin caja
  div.dropdownlist-order          zona con scroll (flex-basis: calc(100% - 110px))
    div.wkwcpos-order-list-header > div.wkwcpos-order-data > span …      cabecera pegajosa (sticky)
    div.wkwcpos-list-item-odd|even > div.wkwcpos-order-data > span …     filas; la activa lleva wkwcpos-active-row
  div.wkpos-paginition-wrapper    paginación (ver 5)
```

- `.wkwcpos-order-data`: flex en fila, `gap:15px`, `nowrap`; cada `span` es `flex:0 0 15%` y va centrado. El primero va en naranja y los `span` anidados en `--text-color`.
- **Anchos de columna:** el POS reparte 15% a todo; una lista con otro número de columnas necesita un `flex` por columna (CSS de disposición permitido, con la misma cadena de selectores para ganar en especificidad).
- Estado de pedido: `span.wkwcpos-order-status > span.wkwcpos-order-status-icon + span.wkwcpos-order-status-label`.
- Filas pares/impares: `wkwcpos-list-item-odd` (fondo `--secondary`) y `-even`.

## 5. Paginación

```
div.wkpos-paginition-wrapper
  div.wkpos-total-items > span              «1 - 25 / 25»
  div.wkpos-paginition > ul.pos-pagination-widget
    li.previous(.disabled) > a              «Previous»
    li(.active) > a                         número; la activa se pinta con --primary-accent
    li.next(.disabled) > a                  «Siguiente»
```

Primera y última página: mismo patrón con `li.previous` / `li.next` y texto ««» / «»».

## 6. Ventana (modal)

```
div.pos-modal-overlay                       fondo difuminado, clic fuera cierra
  div.pos-modal-content [clase propia para el ancho]
    div.pos-modal-header
      span.pos-modal-heading                título
      button.pos-modal-close-btn            círculo con ×
    div.pos-modal-body                      aquí valen pos-form-field y pos-form-checkbox-field
    div.pos-modal-footer
      button.pos-btn-cancel                 secundario
      button.pos-btn-apply                  acción (con span.pos-btn-spinner mientras guarda)
```

## 7. Campos de formulario (solo dentro de `.pos-modal-body`)

- `div.pos-form-field` > `label` + `input|select`: columna con etiqueta en mayúsculas 12px, `--text-color-light`.
- `div.pos-form-checkbox-field` > `input[type=checkbox]` + `label`.
- `div.pos-form-switch-field` > `label.pos-switch` > `input[type=checkbox]` + `span.pos-slider` (44×24, encendido = `--primary-accent`). El contenedor es una fila con caja discontinua: en una tabla se le quitan `margin`, `padding`, `border` y `background`. Con etiqueta: `div.pos-switch-label > span.pos-switch-title + span.pos-switch-desc`.
- **Fuera de una ventana** estas clases pierden el estilo (están ligadas a `.pos-modal-content .pos-modal-body`): para un panel lateral se mete el formulario dentro de `div.pos-modal-content > div.pos-modal-body` o se replica la etiqueta (12px, mayúsculas, `--text-color-light`).

## 8. Preloader

```
div.wkpos-loader-wrapper.wkpos-loader-fullscreen   (fixed, fondo --fixed al 80%)
  span.wkpos-loader                                (cuadrados naranjas animados)
  span.wkpos-loader-text                           texto
```

Se añade al `body` al empezar a cargar y se quita al terminar. Sin CSS propio.

## 9. Panel de detalle (derecha de Pedidos)

```
div (envoltorio)
  div.pos-order-short > div.pos-order-section
    div > h3                                       título («ID del pedido #…»)
    div.pos-order-extra-wrapper > h5.wkwcpos-order-customer + h5.order-date (span + span)
  div.pos-sale-summary
    div.sale-summary-products > ul > li …          líneas
    div.pos-sale-summary-total
      ul.sale-summary-subtotal > li …   ul.sale-summary-total > li …   ul.order-balance > li
      div.pos-order-invoice > button.primary …     botones de acción
```

## 10. Cómo inspeccionar el POS (para añadir piezas a este documento)

Desde la consola de la pestaña del POS (con la sesión abierta; no se escriben contraseñas):

- **Clases de una pantalla:** recorrer `document.querySelectorAll('body *')` y listar `tag.clase` sin las `css-*` ni las del propio bridge.
- **CSS del POS:** `fetch` de `…/woo-point-of-sale/assets/dist/app/style.css` (≈800 KB) y filtrar reglas con una expresión regular sobre el selector.
- **Marcado de React:** `fetch` de `…/assets/dist/app/index.js` y buscar el nombre de la clase: se ve el `createElement` completo.
- **Estilo real de un elemento:** crearlo en la página y leer `getComputedStyle`.
- El resultado de la herramienta se bloquea si contiene `=`, `?` o `&`: sustituirlos antes de devolver el texto.
- El POS guarda el JS y el CSS en caché: tras un cambio, `fetch(url, {cache:'reload'})` y recargar.

## 11. CSS propio permitido (estado actual)

`assets/css/modules/products.css` (≈130 líneas, solo disposición): filas flex de buscador, filtros y barra masiva; anchos de las 8 columnas; foto de 40 px; el `small` del SKU en bloque; neutralizar la caja del interruptor en la tabla; checkbox con `accent-color`; ancho de la ventana y rejilla de campos; nota y tarjeta de variación.

## 12. Estado de la migración

| Pantalla | Estado |
|---|---|
| **Productos** (`modules/products`) | Hecha con las clases del POS |
| **Marcas** (`pos-bridge.js`, `.vfwoo-brands-*`) | **Pendiente:** tiene CSS inventado (botones, tabla, cabecera) en `pos-bridge.css`. Rehacer con este documento |
| Ventana «Cambiar factura» (`.vfwoo-full-*`) | Pendiente de revisar con este documento |
| Aviso del catálogo (`.vfwoo-webkul-notice`) | Pendiente de revisar |

## 13. Componentes compartidos (framework del bridge)

Idea acordada: los componentes de arriba (ventana, paginación, preloader, interruptor, campo, fila de lista) viven **una vez** en `core.js` (`window.vfwooBridge.ui`) y las pantallas los usan, para que Productos, Marcas y las siguientes salgan iguales. Hoy están dentro de `modules/products/…/products.js`; extraerlos a `core.js` es el siguiente paso.
