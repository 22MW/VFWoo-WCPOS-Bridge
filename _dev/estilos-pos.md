# Cómo se construye una pantalla del bridge en el POS (referencia obligatoria)

Se lee **antes** de escribir o cambiar cualquier pantalla dentro del POS de Webkul (Productos, Marcas, y las que vengan). Está hecho a base de errores: cada punto de «Lecciones» costó una discusión.

---

## LEER PRIMERO: las 8 reglas

1. **Siempre se reutiliza lo que ya hay de Webkul** (sus clases, su HTML, sus ids y sus variables de color). **Sin preguntar** al usuario si se puede: se hace.
2. **Se copia la pantalla modelo de Webkul entera**: estructura, clases, **ids** y atributos `style`. Un id puede dar fondo, radio, margen y alto (por ejemplo `#order-main-summary`). Copiar «parecido» no vale; se copia.
3. **Cero CSS de aspecto** (colores, bordes, sombras, radios, tipografías, botones, campos). Solo CSS de **disposición** (flex, grid, anchos, alturas, scroll).
4. **No se usan otros frameworks** (`wp.components`, WooCommerce, etc.): no siguen los ajustes de Apariencia de Webkul.
5. **Si hace falta algo que Webkul no tiene: se AVISA al usuario, se crea UNA sola vez como componente en `core.js` (`window.vfwooBridge.ui`), se documenta aquí y se reutiliza en todas las pantallas.** Nunca se copia y pega el mismo bloque en dos pantallas.
6. **Si hay dos opciones de Webkul, se elige la más parecida y se avisa; no se pregunta** por lo que ya existe. Sí se pregunta antes de cambiar un diseño que el usuario ya aprobó.
7. **Mínimo código.** Cada línea propia es una línea que un update de Webkul puede romper.
8. **Se comprueba contra el modelo con números** (valores calculados de fondo, radio, margen, alto y posición) y a ojo, en tema claro y oscuro y **sin scroll horizontal**.

**Por qué:** el administrador elige en *POS › Ajustes › Apariencia* el color primario, el secundario, el modo claro/oscuro y las plantillas (`_pos_primary_color`, `_pos_secondary_color`, `_pos_primary_light_color`, `_pos_theme_mode`, `_pos_color_schemes`). Webkul convierte esos ajustes en variables CSS y todas sus pantallas las siguen. **Una pantalla del bridge que use sus clases, ids y variables cambia sola con esos ajustes; una que invente estilos, no.** Y como Webkul puede cambiar su HTML en un update, todo lo acoplado a él vive en un solo sitio.

---

## Procedimiento para una pantalla nueva (lista de comprobación)

1. **Elegir el modelo** en la tabla de abajo.
2. **Inspeccionar el modelo con todos sus atributos** (ver «Cómo inspeccionar»): clases, **ids** y `style`. No quitar nada del volcado.
3. **Construirla con los componentes de `core.js`** (`ui.screen`, `ui.detail`, `ui.line`, `ui.pager`, `ui.formShell`…). Si falta uno, regla 5.
4. **Escribir solo lógica y datos**: peticiones REST, qué se muestra, qué se guarda.
5. **CSS solo de disposición**: en `ui.css` si sirve a varias pantallas, en el CSS del módulo si es solo suyo. Cada regla lleva un comentario con su porqué.
6. **Comprobar** (regla 8): comparar con el modelo los valores calculados (`background-color`, `border-radius`, `margin-top`, alto, `top`); abrir en claro y oscuro; estrechar la ventana y ver que no hay scroll horizontal (`scrollWidth` = `clientWidth` en el contenedor); con permiso, cambiar el color primario en Apariencia y ver que la pantalla lo sigue.
7. **Documentar** aquí lo nuevo (componente, regla de disposición, id copiado).

**Modelos por tipo de pantalla:**

| Si la pantalla es… | Modelo de Webkul |
|---|---|
| Un formulario de edición | **Clientes → «Editar cliente»**: `div#pos-customer.pos-tabContent.pos-customers` › `div.pos-customer` › `form#new-customer` con un `div` por campo (`label` + `input` + `span.error`), `h3.address-fields` para secciones, `div.address-group` para pares de campos y `div.pos-customer-add` para el botón `primary`. Las reglas cuelgan de `.pos-customers .pos-customer …` |
| Una lista con detalle a la derecha | **Pedidos** (§3) |
| Una lista con ficha | **Clientes** (título con botón de acción, ficha con avatar y botones, buscador y lista) |
| Un resumen o informe | **Informe** (§6 bis): título, filtros con etiqueta, botón «Filtrar», tarjetas de totales y tabla `wkreport-report-table` |
| Un aviso, error o «sin resultados» | `.no-result`; en el detalle de Pedidos, `.no-order-result` |

---

## Crear un elemento nuevo y reutilizarlo

Solo cuando Webkul no tiene nada parecido, y **avisando** al usuario:

1. Buscar de verdad en el POS (modelo, piezas sueltas, CSS y bundle) antes de dar algo por inexistente.
2. Construirlo **con clases y variables de Webkul** (nunca colores fijos) como una función de `ui` en `core.js`, con su marcado en un solo sitio.
3. Si necesita CSS, solo de disposición, en `ui.css`.
4. Documentarlo en «Componentes de `core.js`» (qué construye y de qué clases del POS depende).
5. Usarlo desde las pantallas. **Si un bloque se repite en dos pantallas, es un componente que falta.**

---

## Lecciones: errores ya cometidos (no repetir)

- **Los ids mandan.** Webkul da estilo por id: `#pos-order-tab` coloca las dos columnas (`2.8fr 1.2fr`), `#order-main-summary` da fondo, radio, margen y alto al detalle. Al volcar el DOM no se quitan `id` ni `style`.
- **Copiar «parecido» no vale.** El panel salió con el borde cuadrado y desplazado porque faltaba el id del contenedor.
- **Las clases `pos-form-*` y `pos-modal-*` son de las ventanas** (etiquetas en mayúsculas pequeñas, ligadas a `.pos-modal-body`). Un formulario de pantalla copia «Editar cliente», no la ventana.
- **`.pos-customers` trae su propia altura y scroll** (pensada para una pantalla entera): dentro de un panel hay que anularlos o salen dos scrolls.
- **El desplegable de estado del pedido** (`tidyStatusSelect`, en `pos-bridge.js`) solo debe actuar sobre selects con `option[value="wc-completed"]`; si no, vacía cualquier desplegable del POS.
- **`--primary` es blanco en claro**; el naranja es `--primary-accent`.
- **Los `button`, `button.primary`, `input`, `select` y `.no-result` ya vienen estilados.** No se les escribe CSS.
- **El checkbox queda invisible** fuera de los formularios del POS: se arregla con `appearance: auto; accent-color: var(--primary-accent)`, la única excepción de aspecto.
- **No cambiar un diseño aprobado sin avisar** (se cambiaron desplegables por botones sin preguntar y molestó).
- **`wp.components` no**: está cargado en la caja, pero no sigue la Apariencia de Webkul.
- **Scroll horizontal:** los `fr` crecen con su contenido (usar `minmax(0, …)`), las listas con `nowrap` y huecos suman más que el ancho, la paginación y los botones deben poder bajar de línea (`flex-wrap`).
- **Ventana de pruebas estrecha** (≈600 px): sirve para detectar desbordes, no para juzgar el aspecto; el aspecto lo valida el usuario en su pantalla.
- **Caché del POS:** tras un cambio, `fetch(url, {cache: 'reload'})` y recargar dos veces.
- **La herramienta del navegador bloquea** los resultados con `=`, `?` o `&`: sustituirlos antes de devolver texto.

---

## 1. Variables y temas

El tema lo marca `body[theme="light"]` / `body[theme="dark"]`. Todo el aspecto cuelga de estas variables:

| Variable | Qué es | Nota |
|---|---|---|
| `--primary-accent` | Naranja de acento (bordes, marca, activo) | **Usar este** para acentos |
| `--primary` | «Primario» del tema | En claro es **blanco** |
| `--secondary` | Gris de filas, botones y campos | |
| `--fixed` | Superficie opaca (blanco en claro, negro en oscuro) | Tarjetas, detalle, preloader |
| `--text-color` | Texto normal | |
| `--text-color-light` | Texto secundario | Etiquetas, notas |

## 2. Elementos que el POS ya estila globalmente

Probado creándolos en la caja y leyendo el estilo calculado:

- **`<button>`:** fondo `--secondary`, texto `--text-color`, padding 12px, radio 8px, sin borde.
- **`<button class="primary">`** (o `pos-btn-primary`): fondo `--primary-accent`, texto blanco. Acción principal.
- **`<input>`, `<select>`, `<textarea>`:** aspecto del POS.
- **`.no-result`:** caja centrada gris, radio, negrita. Sin resultados, avisos y errores.

## 3. Esqueleto de una pantalla (Pedidos, el modelo)

```
main.pos-spa
  div.grid-container.grid-exception
    div#pos-order.pos-tabContent.pos-order.orders      ← un solo elemento: este id y estas 3 clases
      div > nav.pos-tabs > ul > li > a.wkwcpos-tabs.pos-active
      div#pos-order-tab.postabContainer.orders         ← el id pone 2 columnas: grid 2.8fr 1.2fr, gap 10px
        div.pos-order-list                             ← lista (izquierda): fondo --fixed, padding 15, radio 8, margin-top 10, alto calc(100dvh - 145px)
        div#order-main-summary                         ← detalle (derecha): el id da fondo --fixed, radio 8, margin-top 10, alto calc(100dvh - 145px)
          div.pos-order-short > div.pos-order-section  título + datos (borde inferior fino)
          div.pos-sale-summary                         cuerpo con scroll
          (vacío: div.no-order-result)
```

Las reglas del POS cuelgan de esa cadena (`.pos-order .pos-order-list .dropdownlist-order …`): **se respeta la anidación** para heredar el aspecto. Una pantalla de una sola columna (Marcas) usa el mismo esqueleto sin el id `pos-order-tab`.

## 4. Lista (tabla)

```
div.pos-order-list
  div.dropdownlist-order                          zona con scroll
    div.wkwcpos-order-list-header > div.wkwcpos-order-data > span …     cabecera pegajosa
    div.wkwcpos-list-item-odd|even > div.wkwcpos-order-data > span …    filas; la activa lleva wkwcpos-active-row
  div.wkpos-paginition-wrapper                    paginación (§5)
```

- `.wkwcpos-order-data`: flex en fila, `gap`, `nowrap`; cada `span` es `flex:0 0 15%` y va centrado; el primero en naranja.
- **Anchos de columna:** con otro número de columnas hace falta un `flex` por columna (disposición permitida, con la misma cadena de selectores para ganar en especificidad).
- Buscador del POS: `div.pos-order-search` (lupa svg + input), solo dentro de `.pos-order-list`.

## 5. Paginación

```
div.wkpos-paginition-wrapper
  div.wkpos-total-items > span
  div.wkpos-paginition > ul.pos-pagination-widget
    li.previous(.disabled) > a   |   li(.active) > a   |   li.next(.disabled) > a
```

Primera y última página: mismo patrón. El total y la página van en la misma línea que los números.

## 6 bis. Informe (resumen con filtros, tarjetas y tabla)

```
div.pos-tabContent.wkreport-pos-report-container
  div.wkreport-report-wrapper
    div.wkreport-filter-container > div.wkreport-filter-wrapper
      div.wkreport-filter-body
        div.wkreport-filter-top > h3 (título)
        div.wkreport-all-filter-wrapper > div.wkreport-filter-by.wkreport-width-250 > p (etiqueta) + control   (uno por filtro)
      div.wkreport-filter-footer > button.wkreport-filter-btn   «Filtrar»
    div.wkreport-report-container
      div.wkreport-card-report-container > div.wkreport-card-wrapper > div.wkreport-card-title > span + div > h5   (tarjetas)
      div.wkreport-table-report-container > div.wkreport-grid-report-wrapper > div.wkreport-table-view-report-wrapper
        div.wkreport-table-view-report-header > h6.wkreport-title-text
        div.wkreport-report-table-wrapper > table.wkreport-report-table > thead > tr > th > div.wkreport-table-head > div.wkreport-table-head-title
```

Los selectores de Webkul en Informe son `react-select` (propios de su React); en el bridge se usa un `select` nativo, que el POS ya estila. Se construye con `ui.report`, `ui.reportField`, `ui.reportButton`, `ui.reportCards` y `ui.reportTable` (§10).

## 6. Ventana (modal) del POS

```
div.pos-modal-overlay
  div.pos-modal-content
    div.pos-modal-header > span.pos-modal-heading + button.pos-modal-close-btn
    div.pos-modal-body                 aquí valen pos-form-field, pos-form-checkbox-field, pos-form-switch-field
    div.pos-modal-footer > button.pos-btn-cancel + button.pos-btn-apply (con span.pos-btn-spinner)
```

Solo para **ventanas** (por ejemplo «Cambiar factura», pendiente de migrar). Un formulario de pantalla copia «Editar cliente» (tabla de modelos).

## 7. Interruptor

`div.pos-form-switch-field > label.pos-switch > input[type=checkbox] + span.pos-slider` (encendido = `--primary-accent`). El contenedor es una fila con caja discontinua: en una lista se le quitan `margin`, `padding`, `border` y `background` (`ui.css`).

## 8. Preloader

```
div.wkpos-loader-wrapper.wkpos-loader-fullscreen
  span.wkpos-loader
  span.wkpos-loader-text
```

Se añade al `body` al empezar a cargar y se quita al terminar. Sin CSS propio.

## 9. Cómo inspeccionar el POS (para añadir piezas a este documento)

Desde la consola de la pestaña del POS (sesión abierta; no se escriben contraseñas):

- **Marcado completo de una pieza:** `el.cloneNode(false).outerHTML` para ver sus atributos (id, class, **style**) y recorrer sus hijos igual. **No quitar ids ni estilos.**
- **Valores calculados:** `getComputedStyle(el)` (fondo, radio, margen, alto) y `getBoundingClientRect()` (posición); comparar el modelo y la copia.
- **CSS del POS:** `fetch` de `…/woo-point-of-sale/assets/dist/app/style.css` (≈800 KB) y filtrar reglas por selector (ids incluidos: `#order-main-summary`).
- **Marcado de React:** `fetch` de `…/assets/dist/app/index.js` y buscar la clase: se ve el `createElement`.
- **Ajustes de Apariencia:** `Webkul › Ajustes › Apariencia` (opciones `_pos_*`); el PHP que genera las variables está en `includes/front/class-wc-pos-script-loader.php`.

## 10. Componentes de `core.js` (el único sitio acoplado a las clases de Webkul)

En `assets/js/core.js` (`window.vfwooBridge.ui`) y su hoja de disposición `assets/css/ui.css`:

| Componente | Qué construye |
|---|---|
| `ui.screen(root, título, unaColumna)` | El esqueleto de §3 (`#pos-order`, pestañas, `#pos-order-tab`). Devuelve `{ tabs, list, container }` |
| `ui.detail()` | Columna derecha `#order-main-summary` (`pos-order-short` + `pos-sale-summary`) con `body` que se desplaza y `footer` fijo abajo: `setTitle`, `setExtra`, `clear` |
| `ui.listHeader(celdas)` / `ui.line(clase, celdas)` | Cabecera y filas de lista; cada celda lleva `vfwoo-col-{nombre}` para su ancho |
| `ui.report(root, título)` | Esqueleto de **Informe** (§6 bis). Devuelve `{ fields, footer, container }` |
| `ui.reportField(texto, control)` / `ui.reportButton(texto)` | Un filtro con su etiqueta (`wkreport-filter-by`) y el botón «Filtrar» (`wkreport-filter-btn`) |
| `ui.reportCards([[título, valor], …])` / `ui.reportTable(título, cabeceras)` | Tarjetas de totales y tabla de informe (`reportTable` devuelve `{ element, body }`; las filas son `tr > td`) |
| `ui.pager(contenedor, datos, textos, alIr)` | Paginación (§5) con primera y última |
| `ui.loader(texto)` | Preloader (§8), `show`/`hide` anidables |
| `ui.switchField({...})` | Interruptor (§7) |
| `ui.formShell()` | Formulario de «Editar cliente»: `div.pos-customers > div.pos-customer > form` |
| `ui.field(texto, control)` / `ui.pair()` / `ui.heading(texto)` | Campo (`div > label + control + span.error`), pares de columnas (`div.address-group`) y título de sección (`h3.address-fields`) |
| `ui.checkField(texto, casilla)` | Opción sí/no con el interruptor del POS |
| `ui.input`, `ui.select`, `ui.note`, `ui.noResult(texto, clase)` | Campos y avisos |
| `core.posPost` / `core.posForm` | Peticiones REST con la sesión del POS (JSON y `FormData`) |
| `core.addScreen({id, path, title, iconPaths, build})` | Menú y ruta de una pantalla nueva |

## 11. CSS propio permitido (todo de disposición)

- **`ui.css`:** nota, interruptor sin caja, checkbox con `accent-color`, el detalle como columna (título fijo, cuerpo con scroll, botones fijos que bajan de línea si no caben), `.pos-customers` sin su altura y scroll dentro del panel, líneas finas entre secciones (`.vfwoo-section`), sin scroll horizontal (`minmax(0, …)`, paginación con `flex-wrap`).
- **`modules/products.css`:** filas de buscador, filtros y barra masiva; anchos de las 7 columnas; foto de 40 px; fotos y botones de clasificación del panel.
- **`pos-bridge.css` (ventana «Cambiar factura»):** conserva CSS propio de botones: **pendiente de migrar**.

## 12. Estado

| Pantalla | Estado |
|---|---|
| **Productos** (`modules/products`) | Hecha: lista + detalle como Pedidos, formulario como «Editar cliente» |
| **Marcas** (`pos-bridge.js`) | Hecha como **Informe**: filtros (periodo, fechas, marca), «Filtrar», tarjetas de totales y tabla |
| Ventana «Cambiar factura» (`.vfwoo-full-*`) | Pendiente de rehacer con §6 |
| Aviso del catálogo (`.vfwoo-webkul-notice`) | Pendiente de revisar |

## 13. Preparado para cualquier update de Webkul

- Lo acoplado a clases e ids de Webkul vive en `core.js` y en este documento: tras un update se revisa **eso** contra el HTML real.
- **Degradar sin romper:** si falta una pieza o cambia una clase, la pantalla del bridge se ve peor pero **nunca** puede dejar la caja en «Algo salió mal» ni tocar la venta (código defensivo, sin suposiciones).
- **Lista tras un update de Webkul:** abrir Pedidos, Clientes, Informe, Marcas y Productos en claro y oscuro; comparar los valores de §3 (fondo, radio, margen, alto de lista y detalle); cambiar el color primario en Apariencia y ver que el bridge lo sigue; comprobar paginación, formularios y preloader; revisar que no hay scroll horizontal.
