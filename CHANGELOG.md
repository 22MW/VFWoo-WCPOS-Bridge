# Historial de cambios

Este proyecto sigue el formato de Keep a Changelog y utiliza versionado semántico.

## [2.1.0] - 2026-11-23

Versión menor. Consolida las entradas de desarrollo 2.0.0.1 a 2.0.0.6: el módulo «Productos» y la base de módulos, las pantallas con los estilos del propio POS, la venta sin conexión con inventario centralizado y la traducción al castellano de Woo Point of Sale 7.4.1.

### Añadido

- **Venta sin conexión con inventario centralizado.** Con un solo stock (el de WooCommerce), Webkul bloqueaba el cobro sin conexión («No se pueden procesar pedidos con inventario centralizado sin conexión») porque valida el carrito contra el servidor antes de cobrar. El bridge mantiene esa comprobación con conexión y la salta sin ella, de modo que la caja vende con el stock que tiene guardado y el pedido se sincroniza después (WooCommerce resta el stock al llegar). Un cambio pequeño en `pos-bridge.js` (la bandera `apif_script.wk_pos_validate_product_at_pay` responde según `navigator.onLine`); no toca Webkul y, si Webkul cambia esa bandera en una actualización, vuelve el bloqueo sin romper nada. Riesgo asumido: si otra caja o la web venden lo mismo mientras tanto, se puede vender más de lo que queda. La caja solo funciona sin conexión si ya estaba abierta cuando se cayó internet (el service worker de Webkul no la cubre). Ver D-036. Probado en local por el usuario (2026-11-23).
- **Traducción al castellano de Woo Point of Sale 7.4.1, revisada.** Se fusionó el `.pot` de la 7.4.1 y se tradujeron sus 182 textos nuevos (servicio de impresión, asignación masiva, precios y columnas por punto de venta). Se corrigieron a mano los textos de la caja que estaban sin traducir o a medias («Pay By Efectivo», «Pedido Note», «Grand Total», «Hold»…): pago, pedidos, avisos de «pedido realizado», sincronización sin conexión y toda la terminología de «caja» (antes «Drawer»/«Counter»). Se regenera el `.json` del JS desde el `.po` entero, con las entradas con contexto en el formato estándar. Solo documentación y datos de traducción: el plugin no cambia.
- **Traducción al castellano de Woo Point of Sale guardada en el repo** (`_dev/traducciones/woo-point-of-sale/es_ES/`: `.po`, `.mo` y el `.json` del JS del POS, con instrucciones). Los archivos activos viven en `wp-content/languages/plugins/`, que las actualizaciones del plugin de Webkul no borran. Solo documentación: el plugin no cambia (versión dev 2.0.0.4).
- **Módulo «Productos» en la caja.** Nueva pestaña **Productos** en el menú del POS, con el diseño de la caja (tema claro y oscuro), solo para cajeros con el modo de gestor de tienda (`edit_products`; quien no puede editar productos ajenos ve solo los suyos). Lista paginada con foto, SKU, código de barras, precio y stock; buscador por nombre, SKU o código de barras; orden por nombre, precio, stock, más recientes o más vendidos; filtros por tienda, caja, stock, categoría, marca, ofertas e incompletos (sin foto, precio, SKU, código de barras o SKU repetido); interruptores **En la tienda** (visibilidad del catálogo) y **En la caja** (con el helper de Webkul); acciones masivas (hasta 100 productos); popup de edición básica de productos simples y variables (solo variaciones ya existentes: nombre, estado, precios, oferta con fechas, SKU, GTIN y stock con stock centralizado); **alta rápida** de un producto simple (también al escanear un código que no existe). No crea categorías, etiquetas, marcas ni variaciones. Sustituye al acceso «Productos» de la cabecera. Se puede desactivar desde el nuevo bloque **Módulos** de la página del bridge. Las fotos y la clasificación se editan en el panel lateral (ver «Cambiado»).

### Cambiado

- **«Productos» y «Marcas» con los estilos del propio POS.** Ambas pantallas se construyen con el HTML y las clases de Pedidos (pestaña, lista, filas, paginación, preloader, interruptores) mediante componentes compartidos en `core.js` (`window.vfwooBridge.ui`) y la hoja de disposición `ui.css`; el CSS inventado desapareció. La edición de un producto se hace en un **panel lateral** (como el detalle de un pedido), con **fotos** (principal y galería, desde la cámara o el equipo; quitar una foto solo la desvincula) y **clasificación** con las categorías, etiquetas y marcas que ya existen (no se crea ninguna). La paginación va solo abajo, con el total y la página en la misma línea. «Marcas» se rehace como la pantalla **Informe** del POS (periodo, fechas y marca, botón «Filtrar», tarjetas de totales y tabla de informe) y ya no tiene botón «Volver»; el filtro de marca pasa de botones múltiples a un desplegable.
- **Base de módulos.** Nuevo registro de módulos (`includes/core/class-module-registry.php`) y `assets/js/core.js` con los ayudantes compartidos de la caja (`window.vfwooBridge`). Sin módulos instalados no cambia nada visible: es la base para añadir funciones nuevas (el módulo «Productos», el primero) sin ampliar `pos-bridge.js`.

### Corregido

- **Desplegables del POS vacíos.** El ajuste del desplegable de estado del pedido se aplicaba a cualquier desplegable que se pulsara en el POS y le ocultaba todas las opciones que no fueran Completado, Anular y Devolver. Ahora solo se aplica al desplegable de estado del pedido.

### Notas de actualización

- Recargar cada caja con Ctrl+F5 (sin Resync).
- La traducción al castellano de Webkul **no va en el ZIP**: copiar `wc_pos-es_ES.mo` y `wc_pos-es_ES-d1e66de3a56a538cf5ad3e8ebd2ee5a9.json` desde `_dev/traducciones/woo-point-of-sale/es_ES/` del repositorio a `wp-content/languages/plugins/` (instrucciones en el `README.md` de esa carpeta).
- Probado con Woo Point of Sale **7.4.1**. En local, servir la caja por HTTPS (por HTTP el navegador no permite service workers).
- Para vender sin conexión hay que tener el inventario en «Centralized Stock Inventory» y la caja ya abierta antes de que se caiga internet.

### Riesgos conocidos al publicar

- **Venta sin conexión:** si otra caja o la web venden lo mismo mientras tanto, se puede vender más de lo que queda (el stock de WooCommerce puede quedar negativo). Recargar la caja sin conexión falla: el service worker de Webkul 7.4.1 no cubre la ruta de la caja. Tras actualizar Webkul hay que repetir la prueba (la bandera `wk_pos_validate_product_at_pay` puede cambiar).
- **Sin probar:** cajero sin modo de gestor (no debe ver la pestaña Productos), vendedor con WCFM, Master Stock, Safari y tablet, y el tema oscuro con el diseño nuevo.
- **Producción:** el sitio de pruebas `pos.vfwoo.online` sigue en una versión de desarrollo antigua; actualizarlo requiere `validar-deploy` y OK.

## [2.0.0] - 2026-11-17

Versión mayor. El bridge deja de depender de un plugin de VFWoo en concreto y detecta por capacidad lo que hay instalado; permite hacer factura a un ticket ya entregado con o sin Verifactu; añade el país del cliente, el cambio de estado del pedido Completado / Anular / Devolver y la impresión de la rectificativa. Cambia el vocabulario de la caja a «ticket» y «factura».

### Añadido

- **Factura a nombre del cliente desde el ticket, con o sin Verifactu.** El botón **«Cambiar factura»** del detalle del pedido (antes «Factura completa (F3)», solo con Verifactu) sale en cualquier pedido del POS. Se elige o se crea el cliente (con empresa / razón social, domicilio, código postal y población) y:
  - **Sin Verifactu** (o con Verifactu sin enviar nada a la AEAT): el ticket pasa a ser la factura **con el mismo número**. Los datos del cliente se escriben en el pedido y el PDF y el correo salen con ellos. Se puede cambiar de cliente si hubo un error; una nota del pedido guarda los datos anteriores.
  - **Con Verifactu enviando a la AEAT:** con la simplificada aceptada se emite la **F3** que la sustituye (ahora también con el domicilio en el pedido). Una factura ya registrada con cliente (F1 o F3) no se cambia sin rectificarla; si la simplificada aún no está confirmada, pide esperar.
  - El NIF es opcional sin Verifactu y obligatorio con él. Una empresa se guarda como razón social (y su contacto, si lo hay).
  - El ticket de un pedido convertido sale como factura (F1) con los datos del cliente.
  - Se bloquea en pedidos reembolsados (total o parcialmente), cancelados, fallidos y en borrador.
- **País del cliente en el formulario del POS** (España por defecto) y casilla **«No comprobar el documento»**. Con España se comprueba el DNI, NIE o CIF (letra de control) y, con Verifactu, el censo de la AEAT; con cualquier otro país la casilla sale marcada y no se comprueba nada (cabe un pasaporte), salvo que el cajero la desmarque. El país se guarda en el cliente y en el pedido. Con Verifactu, la factura a un cliente de otro país se bloquea con un aviso (la F3 de Verifactu solo envía un NIF español).
- **Cambio de estado del pedido desde el POS: solo «Completado», «Anular» y «Devolver».** El desplegable oculta Pendiente de pago, Procesando, En espera, Fallido y Borrador, y «Cancelado» y «Reembolsado» pasan a leerse como las acciones «Anular» y «Devolver» (el estado actual del pedido siempre se ve con su nombre). La lista de pedidos conserva los nombres de siempre. La pregunta de confirmación dice qué ocurre (con Verifactu, que la anulación se envía a la AEAT). **El servidor rechaza cualquier otro estado.**
- **Botón «Imprimir rectificativa»** en el detalle de un pedido con reembolso (uno por reembolso): imprime el PDF de la rectificativa de VFWoo con el mismo diálogo de impresión, sin salir del POS. Sin Verifactu, VFWoo la crea la primera vez que se imprime.
- **Panel «Qué está activo»** en los ajustes: lista cada función con lo que necesita.

### Cambiado

- **El bridge ya no depende de un plugin de VFWoo en concreto: detecta por capacidad.** Funciona con el core de facturas (VFWoo Invoices) solo, con el core más el complemento Verifactu o con el VFWoo de un solo plugin anterior.
  - **Con el core:** número y fecha de factura en el ticket (leídos de la factura del core), plantilla de ticket propia, «Imprimir factura», PDF en el correo del pedido y NIF del cliente con comprobación de formato (opcional).
  - **Solo con Verifactu y su módulo activado:** QR, leyenda legal y tipo de factura en el ticket, NIF obligatorio, límite de venta, censo de la AEAT y F3.
- **Verifactu solo cuenta si su módulo está activado en VFWoo** (Módulos), no solo el plugin. Antes, con el plugin activo y el módulo apagado, seguía saliendo el QR.
- **Límite de venta fijo en el POS: 3.000 €** (el máximo, con sector autorizado), solo con Verifactu. Ya no lee el límite ni el interruptor de simplificadas del plugin de facturas.
- **El NIF del cliente es obligatorio solo con Verifactu.** Sin él es opcional, se comprueba su formato si se escribe y no se avisa de «sin verificar». Si se deja vacío, se borra el NIF que el cliente tuviera.
- **Textos de la caja: «ticket» y «factura».** Botones «Cambiar factura» e «Imprimir factura» (antes «Factura PDF»); el diálogo dice «Hacer factura»; los avisos no hablan de «simplificada» ni de «completa». Esos términos se mantienen en la documentación técnica.
- Las rutas internas pasan de `f3-status` / `issue-f3` a `full-invoice-status` / `full-invoice-issue`, y la clase `F3_Emission` a `Ticket_Invoice`.
- El correo con PDF no adjunta la factura a los correos de reembolso ni a los de pedidos cancelados o fallidos.

### Corregido

- **Con el core nuevo de VFWoo el POS fallaba con un error fatal** al cargar su configuración (el límite de simplificada se había movido de clase) y al emitir una F3. Ahora cada llamada comprueba que exista la pieza y prueba la clase nueva antes que la antigua.
- **El PDF en el correo no se adjuntaba con el VFWoo anterior** (0.9.17), que no tiene `Channel_Registry`: ahora ese dato es opcional.

### Notas de actualización

- Recarga cada caja con Ctrl+F5; no hace falta Resync.
- Si pasas del VFWoo antiguo al core: copia de seguridad, desactiva el antiguo, activa «VFWoo Invoices» y, si lo usas, «VFWoo Verifactu» con su módulo activado.

### Riesgos conocidos al publicar

- **Sin probar en una tienda real:** la factura desde el ticket con Verifactu enviando a la AEAT, la impresión de la rectificativa desde el POS, el cambio de estado en Safari y la venta sin conexión.
- **Factura sin Verifactu con el mismo número del ticket:** el PDF original deja de existir tal cual; pendiente de confirmar con la gestoría.
- **Con el core solo**, una factura de un pedido cancelado no se marca como anulada (pendiente en VFWoo).
- **Venta sin conexión:** con inventario centralizado Webkul no deja cobrar sin conexión, y su service worker no se registra en esta versión (7.1.1); pendiente de consultar a Webkul.

## [0.1.4] - 2026-11-17

### Añadido

- **Accesos en la cabecera del POS**, antes de los iconos de Webkul: Productos, Informes de ventas, Análisis y Tienda online. Los de administración abren la pantalla real del admin en una ventana dentro del POS (a la derecha de su menú, sin el menú ni la barra de WordPress) y solo salen a quien tiene permiso para esa pantalla; Productos trae sus pestañas (todos, añadir, categorías, atributos, etiquetas). La tienda abre la página pública de compra en una pestaña nueva y la ven todos. Iconos Dashicons.
- Cada acceso muestra, al pasar el ratón o con el teclado, un cuadro bajo el icono con su nombre y para qué sirve (colores del POS, tema claro y oscuro). Sustituye al `title` nativo del navegador.
- Los iconos de acceso de la cabecera toman el color de acento del POS al pasar el ratón, con el teclado y mientras su ventana está abierta.
- **Botón «POS-CAJA» en la barra de administrador**, en la misma pestaña. Construye la dirección siempre con el endpoint guardado en Webkul (`_pos_endpoint_name`).
- **Cajeros:** interruptor, desactivado por defecto, para añadir el rol «Gestor de tienda» a los cajeros que se creen desde Webkul.
- **El aviso de catálogo salta al guardar, borrar o restaurar un producto desde el admin.** Texto: «Hay cambios en los productos. Pulsa Resync → Products para actualizar el catálogo.»
- Interruptor «Ocultar el aviso de WooCommerce sobre plugins que no declaran compatibilidad», desactivado por defecto, en POS → Settings → VFWoo Bridge.
- **Plantilla de ticket propia del bridge** (POS → Settings → VFWoo Bridge → Ticket), desactivada por defecto. Activada, el ticket sale siempre con esa plantilla y se ignoran las de Webkul: logo, razón social, NIF, dirección, teléfono, email y pie salen de VFWoo, y lleva número de factura, QR (solo si VFWoo lo genera) y datos del cliente en F1/F3. Se aplica al instante al recargar la caja, sin depender de la plantilla que Webkul guarda en el navegador. Requiere VFWoo. Desactivada, todo sigue como antes y las plantillas de Webkul pueden usar las variables `${vfwoo_*}`.
- **El PDF de la factura va adjunto al correo del botón «Send order email» del POS** (casilla en POS → Settings → VFWoo Bridge → Correo del pedido, activada por defecto). VFWoo no basta: en el canal VERI\*FACTU no adjunta el PDF a los correos de WooCommerce y en el local lo adjunta una sola vez por pedido, así que un reenvío desde el POS salía sin él. Ahora el bridge lo adjunta siempre desde ese botón, sin duplicarlo si VFWoo ya lo había añadido. Solo va al cliente, solo en el correo que envía ese botón y solo cuando la factura tiene número y se puede imprimir; si falta algo, el correo sale como antes y, con `WP_DEBUG` activo, queda en el registro el motivo. No cambia el correo propio de VFWoo tras la aceptación de la AEAT.
- **Botón «Factura PDF» en el detalle del pedido del POS**, bajo «Factura completa (F3)». Imprime el PDF de VFWoo como Webkul imprime el ticket (iframe oculto y `print()`, sin salir del POS); si el navegador no lo permite, abre el PDF en una pestaña nueva. Sale solo si el pedido ya tiene número de factura y el usuario puede abrir pedidos en el admin. Mientras VFWoo genera el PDF (lo hace de cero en cada pulsación y puede tardar unos segundos) el botón muestra un indicador de carga («Preparando PDF…»), se desactiva para evitar pulsaciones repetidas y vuelve a su estado al empezar la impresión o, como máximo, al cabo de un minuto.
- Variable `${vfwoo_pdf_footer}`: el pie del PDF de VFWoo.
- **El logo del ticket mide como máximo el 50 % del ancho del ticket** (`${vfwoo_company_logo}`, en la plantilla propia y en las de Webkul que la usen), así que se adapta al rollo. Un logo más pequeño no se amplía. Lleva `!important` porque, con el rollo térmico (T88V), Webkul fuerza `max-width:100% !important` a todo el ticket al imprimir y anulaba el límite. No afecta al PDF de VFWoo.
- Un párrafo de una plantilla con la clase `vfwoo-hide-empty` desaparece si todas sus variables `${vfwoo_*}` están vacías (evita «NIF: » sin dato).

### Corregido

- **`${vfwoo_company_nif}` ahora da el NIF que imprime el PDF de VFWoo** (el de «Diseño PDF», `seller_nif`, o el de la licencia si está vacío). Antes leía solo el de la licencia y el ticket podía mostrar un NIF distinto al del PDF.
- **Los avisos del admin ya no se guardan dentro de la plantilla del ticket.** WordPress mueve cada aviso bajo el primer título de la página y, en el editor de plantillas de factura de Webkul, ese título es el del ticket: al guardar, el aviso quedaba grabado y salía impreso. Ahora esa pantalla no muestra avisos.

## [0.1.3] - 2026-11-10

### Añadido

- **Pantalla de login del POS configurable** (POS → Settings → VFWoo Bridge): titular propio, texto del pie en lugar de «Un producto de Webkul», opción para ocultar la línea «versión - … - Webkul» y modo demo que rellena usuario y contraseña. Todo vacío por defecto: sin ajustes guardados no cambia nada. Confirmado en local.
- Tabla «Módulos fiscales» en la pantalla de ajustes, con su estado.

### Cambiado

- **VFWoo pasa a ser opcional.** Ya no figura en `Requires Plugins` ni impide cargar el bridge. Sin VFWoo quedan desactivados el ticket fiscal con QR, la factura completa (F3) y el NIF obligatorio con su límite de factura simplificada; el resto (filtros y barra «Marcas», aviso de catálogo, informes por marca, login) sigue funcionando. El POS tampoco muestra el campo NIF ni las comprobaciones de venta en ese caso. Confirmado en local con VFWoo desactivado para la petición.

## [0.1.2] - 2026-09-20

### Añadido

- **«Marcas» en la barra de categorías del inicio del POS**, junto a «Todos»: al pulsarla salen las marcas como subcategorías y al pulsar una se ven sus productos, igual que con las categorías. Funciona con la barra nueva y con la antigua. Se añade en el navegador (el POS solo pide sus categorías al servidor cuando su tabla local está vacía) y usa la imagen de la marca de WooCommerce si la tiene. Confirmado en local.
- Los productos llevan ahora la imagen de su marca (`thumbnail` dentro de `vfwoo_webkul_brands`).

### Cambiado

- Pantalla «Marcas» y ventana de la factura completa (F3) con los estilos del propio POS: colores tomados de sus variables de tema (`--primary`, `--secondary`, `--text-color`, `--text-color-light`, `--primary-accent`, `--fixed`) y botones con su clase `primary`, para que se vean bien en el tema claro y en el oscuro. Antes fijaban colores a mano y en oscuro se veían textos blancos sobre fondo blanco. Confirmado en local.

### Notas de actualización

- Después de actualizar, hacer **Resync → Products** en cada caja: así los productos traen las marcas y sus imágenes. Si ningún producto tiene marca, «Marcas» no aparece en la barra.
- La copia de desarrollo (una carpeta con `.git`) no ofrece la actualización, por diseño.

## [0.1.1] - 2026-09-20

Primera versión estable: consolida las entradas de desarrollo 0.1.0.1 a 0.1.0.13.

### Ticket fiscal

- Impresión de la venta y de la reimpresión con número de factura, tipo, QR y leyenda legal. Confirmado en local.
- QR incrustado como PNG base64 (`qr_data_uri`) en la venta inicial y en la reimpresión: la primera impresión ya no sale con la imagen rota (Webkul imprime 500 ms después de montar el ticket y la ruta REST del QR tarda más). Si falta la clase `VFWoo\QR`, se usa la URL como antes.
- Con la factura aún sin confirmar, el QR del ticket se incrusta y su URL lleva `temp=1`, porque la ruta del QR no sirve facturas pendientes.
- El ticket ya no muestra la URL de cotejo en texto; se mantiene número de factura, QR y leyenda legal.
- Variables de VFWoo en el editor de plantillas del ticket (`${vfwoo_*}`): tienda (nombre, NIF, dirección, teléfono, email y logo), factura (número, tipo, fecha, leyenda legal, URL de cotejo y QR) y cliente (nombre, NIF, domicilio, email y teléfono). Los datos del cliente solo salen en F1 y F3; en F2 se vacían también las variables de cliente de Webkul.
- El editor de plantillas de Webkul evalúa la plantilla como código y se quedaba en blanco con una variable desconocida; ahora las `${vfwoo_*}` se protegen para que se vean como texto y se guarden sin cambios.
- Todo texto insertado en el ticket se escapa para no romper ni inyectar código en la evaluación de la plantilla.
- Estilo del bridge permitido en la lista blanca de estilos del POS (`wkwcpos_add_custom_css`).

### Catálogo y filtros

- Filtro `BRANDS` en el panel nativo de filtros del catálogo POS: se añade una entrada `BRANDS` a `attributes` con los términos de `product_brand`. Confirmado en local.
- Taxonomías de filtro configurables en `POS → Settings → VFWoo Bridge`: cualquier taxonomía registrada para `product` (salvo categorías, `pa_*` e internas de WooCommerce). Por defecto `product_brand`. Confirmado en local.
- Versión de catálogo (`vfwoo_webkul_catalog_version`): botón `Forzar recarga del catálogo` en `POS → Settings → VFWoo Bridge`; también sube al guardar los filtros. Cada producto lleva la versión con la que se cargó.
- Aviso en el POS cuando el catálogo en caché es anterior: pide usar Resync → Products. No borra datos. Se quita solo tras el Resync.
- Consulta automática de la versión sin recargar el POS (ruta REST `vfwoo-webkul/v1/catalog-version`): al abrir, al terminar una venta, al cambiar de pantalla y cada 5 minutos.

### Clientes, F1 y F2

- Clientes del POS: campo obligatorio DNI/NIE/CIF en el formulario. Se valida el formato y, para NIF españoles, el censo de la AEAT al guardar (si el censo no responde, queda «sin verificar»). Se guarda en el usuario (`billing_nif`).
- El NIF del cliente se copia al pedido (`_billing_nif`) salvo para el cliente por defecto, de modo que VFWoo emite factura completa F1. Sin NIF, F2.
- Los datos de cliente que recibe el POS incluyen `vfwoo_webkul_is_default` junto al NIF y su estado; hace falta Resync de clientes.
- Compatibilidad con WP Armour Extended: se omite su comprobación antispam solo al guardar un cliente desde el POS.
- Parada de la venta antes de cobrar cuando hará falta un cliente con NIF: con las simplificadas activas, una venta igual o superior al límite (400 €, o 3.000 € en sector autorizado) sin cliente con NIF no avanza desde el botón Pay ni desde el botón final de pago; con las simplificadas desactivadas, toda venta lo exige. Cliente por defecto o sin NIF por debajo del límite se vende como F2; NIF sin verificar solo avisa. Límite y ajuste leídos de VFWoo. Confirmado en local.

### Factura completa (F3)

- Botón «Factura completa (F3)» junto a «Imprimir factura» en pedidos vendidos como F2. Solo con la F2 confirmada; el cliente se busca o se crea con los servicios del POS (solo clientes con NIF, nunca el cliente por defecto) y VFWoo emite la F3 (`Invoice_Helper::emitir_sustitutiva`). Cualquier cajero autenticado puede. Endpoints `vfwoo-webkul/v1/f3-status` e `issue-f3`. Confirmado en local.
- El ticket F3 imprime al comprador de la F3 y no al cliente mostrador; guarda el cliente elegido en el pedido (`_vfwoo_webkul_f3_customer`) para su domicilio y contacto.
- Autenticación de los endpoints del bridge centralizada en `Pos_Auth`.

### Pantalla «Marcas» (ventas)

- Entrada «Marcas» en el menú de la izquierda del POS: ventas del POS por marca con unidades, ventas sin y con IVA, pedidos y devoluciones, y desglose por producto. Periodos Hoy, Ayer, Esta semana, Este mes y rango Desde/Hasta (máximo 1 año). Endpoint de solo lectura `vfwoo-webkul/v1/brand-report`. Confirmado en local.

### Actualizaciones

- Actualizador desde GitHub Releases: el plugin aparece en Escritorio › Actualizaciones y se actualiza con un clic. Solo considera releases con tag `pos-v…` del repositorio compartido y rechaza paquetes de otra procedencia. Se registra antes de comprobar dependencias y no se activa en una copia de desarrollo (con `.git`), para que una actualización no borre el historial.

### Corregido

- Identidad, dependencias y bootstrap alineados con el puente VFWoo original.
- El script del ticket usa el hook nativo `wkwcpos_enqueue_pos_scripts`.
- La lectura fiscal admite pedidos Webkul en estado `processing`.
- La pestaña de ajustes se identifica como `VFWoo Bridge`.
- Se preparan los términos `product_brand` en la respuesta de productos de Webkul.
- Retirado el debug temporal (`error_log` en PHP y `console.info` en el ticket).

### Pendiente

- Verificar en sandbox las rectificativas (devolución total y parcial de F2, F1 y F3); se hacen en WooCommerce y VFWoo genera la rectificativa.
- Red de seguridad en el servidor para ventas que se salten el navegador (atajo de teclado, ventas sin conexión sincronizadas después).

## [0.1.0] - 2026-09-18

### Añadido

- Núcleo del plugin y comprobación de dependencias.
- Adaptador de solo lectura a `VFWoo\\Ticket_Fiscal_Data`.
- Integración con venta inicial e historial de Webkul POS.
- Extensión del ticket mediante los filtros JavaScript de Webkul.
- Pantalla de estado y pestaña `VFWoo Bridge`.
- Documentación de investigación, arquitectura y desarrollo.

### Garantías

- El bridge no emite facturas ni asigna numeración.
- El bridge no comunica directamente con Hacienda.
- El bridge no modifica archivos de VFWoo o Webkul.
- El bridge no instala ni sustituye plantillas de Webkul.
