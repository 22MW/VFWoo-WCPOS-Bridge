# VFWoo Webkul POS Bridge: qué añade y qué hace

Referencia funcional del plugin, versión **2.0.0**. Esta versión mayor cambia el modo de trabajar con las facturas: el bridge **detecta por capacidad** el core de VFWoo y el complemento Verifactu (con su módulo activado), permite **hacer factura a un ticket ya entregado con o sin Verifactu**, añade el **país del cliente**, el **cambio de estado Completado / Anular / Devolver** y la **impresión de la rectificativa**.

## Resumen

El **POS Bridge** se instala junto al POS de Webkul (*Point of Sale for WooCommerce*) y lo adapta a una tienda española: ticket y factura con su número, clientes con NIF, PDF de la factura, marcas, accesos a la tienda desde la caja y otros ajustes de uso diario. Se engancha a Webkul y a VFWoo con los filtros que ambos ofrecen.

| | |
|---|---|
| **Necesita** | WooCommerce y el POS de Webkul |
| **Opcional** | Un plugin de facturas (VFWoo Invoices, el core) y, encima, el complemento Verifactu. El bridge no depende de ninguno: cada función se enciende cuando existe lo que necesita |
| **No hace** | Emitir facturas ni asignar numeración (lo hace el plugin de facturas), hablar con la Agencia Tributaria (lo hace Verifactu), tocar precios, stock ni cobros de Webkul, **devolver un solo producto** (Webkul no lo trae), ni modificar archivos de Webkul o VFWoo |
| **Plantillas de Webkul** | No las instala ni cambia las guardadas. Solo si activas la plantilla propia, el ticket se imprime con la del bridge; las de Webkul quedan intactas |

## Índice

| Bloque | Funciones |
|---|---|
| [1. Facturas y ticket](#1-facturas-y-ticket-según-lo-instalado) *(según lo instalado)* | Ticket fiscal · Plantilla de ticket propia · Cliente con NIF y país · Venta con límite · Cambiar factura · Estado del pedido, devolución y rectificativa · Imprimir factura y correo |
| [2. Catálogo y marcas](#2-catálogo-y-marcas) | Marcas en la barra · Filtros del catálogo · Ventas por marca · Aviso de cambios en el catálogo |
| [3. Uso diario](#3-uso-diario-en-la-caja-y-en-el-escritorio) | Accesos de la cabecera · Botón POS-CAJA · Cajeros gestores · Pantalla de acceso |
| [4. Administración del plugin](#4-administración-del-plugin) | Avisos del admin · Pantalla de ajustes · Actualizaciones |
| [5. Referencia](#5-referencia) | Con y sin el bridge · Webkul y bridge · Tras actualizar · Limitaciones · Rutas |

---

## 1. Facturas y ticket *(según lo instalado)*

Este bloque se apoya en el plugin de facturas. Cada función se enciende cuando existe lo que necesita:

- **Con el core de facturas (VFWoo Invoices):** número y fecha de factura en el ticket, plantilla de ticket propia, «Imprimir factura», PDF en el correo y NIF del cliente con comprobación de formato.
- **Solo con Verifactu:** QR, leyenda legal y tipo de factura, NIF obligatorio, límite de venta, comprobación contra el censo de la AEAT y factura (F3).
- **Sin ninguno:** el ticket sale como lo imprime Webkul y el POS no muestra comprobaciones de venta.

### 1.1 Ticket fiscal

El ticket de la venta y de la reimpresión sale con:

- **Número de factura** y **fecha de expedición** (con el core de facturas) y **tipo** de documento (con Verifactu).
- **Código QR** y **leyenda legal**, solo con Verifactu; sin él, el ticket sale sin QR.
- **Etiquetas propias** `${vfwoo_*}` para el editor de plantillas de Webkul: datos de la tienda (incluido el pie del PDF), de la factura y del cliente. Los datos del cliente solo salen en facturas completas. El NIF de la tienda es el mismo que imprime el PDF de VFWoo.
- **Líneas que se ocultan solas:** una línea marcada con la clase `vfwoo-hide-empty` no se imprime si todas sus etiquetas están vacías (así no sale «NIF: » sin dato).
- **Logo:** `${vfwoo_company_logo}` mide como máximo el **50 % del ancho del ticket**, así que se adapta al rollo; un logo pequeño no se amplía.

Detalles que resuelve:

- El QR va **incrustado**, así que la primera impresión no sale con la imagen rota, y se genera aunque la factura aún no esté confirmada.
- Las etiquetas se protegen para que el editor de Webkul no se quede en blanco con una variable que no conoce.
- Todo texto insertado en el ticket se escapa.

### 1.2 Plantilla de ticket propia *(core de facturas)*

Casilla en **POS → Settings → VFWoo Bridge → Ticket**, **desactivada por defecto**.

- **Activada:** el ticket sale siempre con la plantilla del bridge y se ignoran las plantillas de factura de Webkul. Todos los datos de la tienda salen de VFWoo, los mismos que en el PDF: logo, razón social, **NIF del PDF**, dirección, teléfono, email y **pie del PDF**. Lleva también número de factura, tipo y fecha de expedición, QR (solo con VERI\*FACTU), leyenda legal y, en facturas completas, nombre, NIF y domicilio del cliente. Las líneas vacías no se imprimen. Productos, totales y pagos usan las variables normales de Webkul.
- **Desactivada:** todo sigue como siempre; cada plantilla de Webkul puede usar las variables `${vfwoo_*}` desde su editor.
- **Se aplica al recargar la caja**, sin Resync: el bridge la coloca en el momento de imprimir, así que no depende de la plantilla que Webkul guarda en el navegador.
- Con el rollo térmico, el ancho y el tamaño de letra los decide Webkul según el tipo de página y el ancho configurados.

### 1.3 Cliente con DNI/NIE/CIF *(core de facturas; obligatorio y censo con Verifactu)*

- El formulario de cliente del POS lleva el campo **DNI/NIE/CIF**: **obligatorio solo con Verifactu**; sin él es opcional (y si se deja vacío se borra el NIF que el cliente tuviera).
- Se valida el **formato** siempre que se escriba un NIF. **Solo con Verifactu** se comprueba además, para NIF españoles, el **nombre contra el censo de la AEAT**; si el censo no responde, el cliente se guarda como **«sin verificar»** y se avisa. Sin Verifactu no hay censo y no se avisa de nada.
- El NIF se guarda en el usuario y se **copia al pedido**, para que VFWoo emita factura. Sin NIF, el pedido sale como ticket.
- El **cliente por defecto** («Cliente mostrador») no lleva NIF y nunca se copia al pedido.
- **País** (España por defecto) y casilla **«No comprobar el documento»**: con España se comprueba el DNI/NIE/CIF; con otro país la casilla sale marcada y se acepta lo que se escriba (por ejemplo un pasaporte). Se puede marcar o desmarcar a mano. Un documento extranjero no genera el aviso de «sin verificar».
- **Empresa / razón social** y **domicilio** (domicilio, código postal y población) se pueden indicar al crear el cliente; Webkul pide además un **email**.
- Compatible con WP Armour Extended: se omite su antispam solo al guardar un cliente desde el POS.

### 1.4 Venta con límite *(solo con Verifactu)*

Antes de cobrar, el bridge comprueba si la venta necesita un cliente con NIF:

- El límite del POS es **fijo: 3.000 €** (el máximo, como con «Sector autorizado» activado). Una venta igual o superior a esa cifra sin cliente con NIF **no avanza**: ni desde el botón *Pay* ni desde el botón final de pago.
- No lee el límite ni el interruptor de facturas simplificadas del plugin de facturas: es una regla del POS.
- Cliente por defecto, o sin NIF por debajo del límite: se vende como ticket. Con NIF sin verificar, solo avisa.
- Sin Verifactu no hay ticket (factura simplificada) que limitar: no se bloquea ninguna venta.
- **Atención:** el tipo de factura que emite Verifactu para una venta sin NIF entre 400 € y 3.000 € depende de sus propios ajustes; «Sector autorizado» debe estar activado ahí para que coincidan.

### 1.5 Factura desde el ticket *(core de facturas; con Verifactu se registra como F3)*

Para quien ya se llevó el ticket y después pide factura, a su nombre o al de una empresa:

- Botón **«Cambiar factura»** junto a «Imprimir Invoice» en **Pedidos**, en cualquier pedido del POS.
- Se **busca o crea el cliente** con los servicios del POS (nombre, empresa / razón social, DNI/NIE/CIF, domicilio, código postal, población, teléfono y email). Nunca el cliente por defecto.
- **Sin Verifactu:** el ticket **pasa a ser la factura con el mismo número**. Los datos del cliente se escriben en el pedido y el PDF, el correo y el ticket salen con ellos. El NIF es opcional. **Si hubo un error, se puede cambiar de cliente.** Una nota del pedido guarda los datos anteriores.
- **Con Verifactu enviando a la AEAT:** con el ticket ya confirmado se emite la **F3 que la sustituye** (el ticket original se conserva); el NIF es obligatorio. Una factura ya registrada con cliente (F1 o F3) **no se cambia sin rectificarla**, y si la simplificada aún no está confirmada, hay que esperar.
- Una empresa se guarda como razón social (y su contacto, si lo hay). El ticket de un pedido convertido imprime los datos del cliente elegido y no los del cliente por defecto.
- **Clientes de otro país:** sin Verifactu se puede hacer la factura a cualquier país. **Con Verifactu, la factura a un cliente de otro país no está disponible todavía** (la F3 de Verifactu solo envía un NIF español, sin país ni tipo de documento): el POS lo avisa y no envía nada a la AEAT.
- **En pedidos reembolsados (total o parcialmente) y cancelados, el botón avisa y no deja cambiar nada**; tampoco en los fallidos o en borrador.
- **Verifactu solo cuenta si su módulo está activado** en VFWoo (Módulos): con el plugin activo pero el módulo apagado no hay QR, NIF obligatorio, límite de venta ni F3.

### 1.6 Estado del pedido, devolución y rectificativa *(estados: siempre; rectificativa: core de facturas)*

**Cambiar el estado desde el POS.** En el detalle del pedido, el desplegable **Pedido Estado** ofrece solo tres opciones:

| Opción | Estado de WooCommerce | Qué pasa |
|---|---|---|
| **Completado** | Completado | Como siempre |
| **Anular** | Cancelado | Cancela el pedido. Con Verifactu, anula su factura en la AEAT. Sin Verifactu, el core **no marca** la factura como anulada |
| **Devolver** | Reembolsado | **Devolución total**: WooCommerce crea el reembolso por todo el importe, el total pasa a 0 y VFWoo crea la rectificativa. **El dinero lo devuelve la tienda** |

- Los demás estados (Pendiente de pago, Procesando, En espera, Fallido y Borrador) se **ocultan**; la lista de pedidos conserva los nombres de siempre. El **estado actual** del pedido se ve siempre con su nombre y, si no es uno de los tres, sin poder elegirse. Safari no oculta opciones: ahí salen en gris.
- Al elegir **Anular** o **Devolver**, la pregunta de confirmación explica qué ocurre (con Verifactu, que la anulación se envía a la AEAT).
- **El servidor rechaza cualquier otro estado**: una llamada a mano a la API de Webkul tampoco puede ponerlo.
- Webkul **no devuelve un solo producto** desde la caja: la devolución parcial se hace en el escritorio de WooCommerce (**Reembolsar**).

**Imprimir la rectificativa.** Si el pedido tiene reembolsos, bajo «Imprimir factura» sale un botón **«Imprimir rectificativa»** por cada uno (con varios, «Imprimir rectificativa 1», «2»…):

- Imprime el PDF de la rectificativa de VFWoo con el mismo diálogo de imprimir, sin salir del POS.
- Sin Verifactu, VFWoo crea la rectificativa **la primera vez que se imprime** (o se adjunta a un correo). Con Verifactu la emite él.
- Probado por el usuario: el cambio a Reembolsado crea el reembolso y la rectificativa; **imprimirla desde el POS está pendiente de probar**.

### 1.7 Imprimir factura y correo con PDF *(core de facturas)*

**Botón «Imprimir factura»**, bajo «Cambiar factura» en el detalle del pedido (y, si el pedido tiene reembolsos, un botón **«Imprimir rectificativa»** por cada uno, con el PDF de la rectificativa de VFWoo):

- Abre el **diálogo de imprimir del navegador** con el PDF de VFWoo (A4) **sin salir del POS**, igual que Webkul imprime el ticket. Si el navegador no lo permite, abre el PDF en una **pestaña nueva**.
- Mientras VFWoo genera el PDF, el botón muestra **«Preparando factura…»** con un indicador de carga y no admite una segunda pulsación.
- Sale cuando el pedido **ya tiene número de factura** y el usuario **puede abrir pedidos en el admin**.
- El ticket de la térmica no cambia: es un botón aparte.

**PDF adjunto al correo del POS.** El botón «Send order email» de Webkul manda el correo de WooCommerce del estado del pedido. VFWoo, por su cuenta, no adjunta el PDF en el canal VERI\*FACTU y en el canal local lo adjunta una sola vez por pedido, así que un reenvío salía sin él. El bridge lo adjunta **siempre que se pulsa el botón**:

- Solo al **cliente** (no al admin, ni en correos de reembolso, ni en pedidos cancelados o fallidos), y solo si la factura **tiene número y se puede imprimir**.
- Sin duplicarlo si VFWoo ya lo había añadido.
- Casilla en **POS → Settings → VFWoo Bridge → Correo del pedido**, **activada por defecto**.
- El cliente necesita un **email**; el «Cliente mostrador» normalmente no lo tiene.
- No cambia el correo propio que VFWoo envía tras la aceptación de la AEAT.
- Con `WP_DEBUG` activo, si no adjunta, el motivo queda en el registro.

---

## 2. Catálogo y marcas

Funciona igual con VFWoo o sin él.

### 2.1 Marcas en la barra de categorías

- **«Marcas»** aparece en la barra de categorías del inicio del POS, junto a «Todos»: las marcas salen como subcategorías, con su imagen si la tienen, y al pulsar una se ven sus productos.
- Los productos llevan la imagen de su marca.
- Si ningún producto tiene marca, «Marcas» no aparece.

### 2.2 Filtros del catálogo

- **Filtro por marca** en el panel de filtros del catálogo.
- También por **otras taxonomías de producto** que se elijan en los ajustes (cualquiera registrada para producto, salvo categorías y atributos, que Webkul ya muestra). Por defecto, solo la marca.

### 2.3 Ventas por marca

Pantalla **«Marcas»** en el menú del POS, de solo lectura:

- **Periodos:** hoy, ayer, esta semana, este mes o un rango de fechas (máximo un año).
- **Por marca y por producto:** unidades, ventas sin IVA y con IVA, pedidos y devoluciones, con total.
- Cuenta **solo las ventas hechas en el POS**, descontando devoluciones.

### 2.4 Aviso de cambios en el catálogo

El POS guarda el catálogo en la caja. Cuando algo cambia, el bridge avisa para recargarlo:

- **Versión de catálogo:** sube al guardar los filtros, al pulsar **«Forzar recarga del catálogo»** y al **guardar, borrar o restaurar un producto** desde el admin.
- Cada caja **comprueba la versión** al abrir, al terminar una venta, al cambiar de pantalla y **cada 5 minutos**, sin recargar.
- Si el catálogo de la caja es anterior, sale un aviso: *Resync → Products*. **No borra datos**, y lo decide la persona de la caja para no perder ventas a medias.

---

## 3. Uso diario en la caja y en el escritorio

Funciona igual con VFWoo o sin él.

### 3.1 Accesos de la cabecera del POS

Cuatro iconos en la cabecera, antes de los de Webkul:

| Acceso | Qué hace | Quién lo ve |
|---|---|---|
| **Productos** | Abre el catálogo del admin (con sus pestañas) | Quien tiene permiso |
| **Informes de ventas** | Informes clásicos de WooCommerce | Quien tiene permiso |
| **Análisis** | Panel de análisis de WooCommerce | Quien tiene permiso |
| **Tienda online** | La tienda pública, en una pestaña nueva | Todos |

Las pantallas de administración se abren **dentro del POS**, en una ventana a la derecha del menú, sin el menú ni la barra de WordPress. Cada icono muestra al pasar el ratón (o con el teclado) un **cuadro con su nombre y para qué sirve**, con los colores del POS en tema claro y oscuro.

### 3.2 Botón «POS-CAJA» en el escritorio

Un botón en la **barra de administrador** que lleva a la caja, en la misma pestaña. Construye la dirección siempre con el endpoint guardado en Webkul, así que **se actualiza solo** si ese endpoint cambia. Lo ve quien tiene permiso de POS o de gestión de WooCommerce.

### 3.3 Cajeros gestores

Interruptor (**desactivado por defecto**) que añade el rol **«Gestor de tienda»** a los cajeros que se creen desde Webkul. Con él, los accesos de la cabecera y el botón «Imprimir factura» funcionan para ese cajero. **Solo afecta a los cajeros creados después de activarlo.**

### 3.4 Pantalla de acceso del POS

Cambia la pantalla de login sin tocar Webkul: **titular propio**, **texto del pie** en lugar de «Un producto de Webkul», opción de **ocultar la línea de versión** y **modo demo** que rellena usuario y contraseña (visibles para cualquiera: solo para cajas de demostración). Con todo vacío, no cambia nada.

---

## 4. Administración del plugin

### 4.1 Avisos del admin

- En el **editor de plantillas de factura** de Webkul no se muestran avisos, para que no se guarden dentro del ticket. No se puede desactivar.
- Interruptor opcional (desactivado por defecto) para **ocultar el aviso de WooCommerce** sobre plugins que no declaran compatibilidad con sus funciones. Los que se declaran incompatibles siguen avisando.

### 4.2 Pantalla de ajustes

En **POS → Settings → VFWoo Bridge**, en este orden:

| Sección | Qué contiene | Por defecto |
|---|---|---|
| Estado y «Qué está activo» | Estado de WooCommerce, Webkul, facturas y Verifactu, y qué funciones están activas | Solo lectura |
| Ticket | Usar la plantilla de ticket propia | Desactivada |
| Correo del pedido | Adjuntar el PDF al correo de «Send order email» | Activada |
| Pantalla de login | Titular, pie, ocultar versión, modo demo | Vacío (no cambia nada) |
| Cajeros | Rol de gestor para los cajeros nuevos | Desactivado |
| Avisos del admin | Ocultar el aviso de compatibilidad de WooCommerce | Desactivado |
| Filtros del catálogo | Taxonomías que se muestran como filtro | Solo la marca |
| Recarga del catálogo | Versión actual y botón «Forzar recarga del catálogo» | — |

### 4.3 Actualizaciones

El plugin aparece en **Escritorio → Actualizaciones** y se actualiza con un clic desde las versiones publicadas en GitHub. Solo considera versiones propias del POS Bridge y rechaza paquetes de otra procedencia. En una copia de desarrollo (con `.git`) está apagado por diseño.

---

## 5. Referencia

### 5.1 Con y sin el bridge

Todo lo de este documento lo añade el bridge: ni Webkul ni el plugin de facturas lo traen. Entre paréntesis, lo que necesita cada función; sin eso, el bridge la deja apagada.

| Función | Sin el bridge | Con el bridge |
|---|---|---|
| Número y fecha de factura en el ticket | No | Sí (core de facturas) |
| Tipo de factura, QR y leyenda legal en el ticket | No | Sí (Verifactu) |
| Plantilla de ticket propia | No | Sí, opcional (core de facturas) |
| PDF de la factura (imprimir y correo) | No | Sí (core de facturas) |
| NIF del cliente con comprobación de formato | No | Sí (core de facturas; opcional) |
| NIF obligatorio, límite de venta y censo de la AEAT | No | Sí (Verifactu) |
| Factura a un ticket ya entregado («Cambiar factura») | No | Sí (core de facturas; con Verifactu, como F3) |
| País del cliente y «No comprobar el documento» | No | Sí |
| Cambio de estado solo a Completado, Anular y Devolver | No (todos los estados) | Sí |
| «Imprimir rectificativa» | No | Sí (core de facturas) |
| Marcas, filtros y ventas por marca | No | Sí |
| Aviso de cambios en el catálogo | No | Sí |
| Accesos de la cabecera y botón POS-CAJA | No | Sí |
| Cajeros gestores, pantalla de acceso y avisos del admin | No | Sí |

### 5.2 Qué es de Webkul y qué añade el bridge

| Webkul (nativo) | El bridge añade |
|---|---|
| Ventas, cobro, descuentos, cupones | Comprobación de cliente con NIF antes de cobrar |
| Ticket y plantillas de factura | Número de factura, QR y etiquetas fiscales; plantilla propia opcional con los datos de VFWoo |
| Pedidos, devoluciones y reimpresión | Botones «Cambiar factura», «Imprimir factura» e «Imprimir rectificativa» |
| Desplegable de estado con todos los estados | Solo Completado, Anular y Devolver, con confirmación y regla en el servidor |
| «Imprimir Invoice» (el ticket) | «Imprimir factura» (A4, con el PDF de VFWoo) |
| «Send order email» (correo nativo de WooCommerce) | PDF de la factura adjunto, siempre |
| Categorías y atributos como filtros | Marcas y otras clasificaciones como filtros |
| Informes de pedidos, clientes y stock | Ventas por marca |
| Resync manual | Aviso automático de cambios en el catálogo |
| Cabecera con sus iconos | Accesos a Productos, Informes, Análisis y Tienda |
| Pantalla de acceso fija | Pantalla de acceso con tu nombre y mensaje |

### 5.3 Después de actualizar o cambiar datos

- Tras instalar una versión con marcas o clientes nuevos, hacer **Resync → Products** (y de clientes) en cada caja.
- Si un cliente antiguo no tiene NIF, se le pide al editarlo en el POS.
- Para que una caja use un cambio del bridge (plantilla propia, botón «Imprimir factura»), basta **recargarla** (Ctrl+F5); no hace falta Resync.
- **Al pasar a la 2.0.0:** recarga cada caja con Ctrl+F5 (no hace falta Resync). El bridge funciona con el VFWoo de un solo plugin, con el core y con el core más Verifactu. Si cambias al core, haz antes una **copia de seguridad**, desactiva el VFWoo antiguo (no pueden estar activos a la vez), activa «VFWoo Invoices» y, si lo usas, «VFWoo Verifactu» **con su módulo activado** en VFWoo → Módulos.
- Las rutas `f3-status` e `issue-f3` ya no existen: ahora son `full-invoice-status` y `full-invoice-issue`.

### 5.4 Limitaciones conocidas

**Facturas y ticket**

- La comprobación de cliente antes de cobrar funciona **en la pantalla del POS**. Una venta hecha con un atajo de teclado o sin conexión y enviada después **no pasa por esa comprobación**: pendiente una red de seguridad en el servidor.
- Las **rectificativas** (devolución total o parcial de un ticket, una factura y una F3) las genera VFWoo; con Verifactu está **pendiente verificarlas en sandbox**. Sin Verifactu se crean al imprimirlas por primera vez.
- Si se colocan las etiquetas `${vfwoo_*}` en una plantilla de ticket, **desactivar el bridge sin quitarlas antes** deja el ticket sin imprimir.
- Con el core solo, el ticket muestra el número de factura solo si el core ya la creó al cobrar (depende del estado de expedición configurado en el plugin de facturas).
- Sin Verifactu no hay límite de venta ni NIF obligatorio.
- **Factura sin Verifactu:** conserva el número del ticket y cambia los datos del cliente del pedido, así que el PDF original (a nombre del mostrador) deja de existir tal cual; solo queda la nota del pedido con los datos anteriores. Si la factura debe poder referirse al ticket, hay que confirmarlo con la gestoría.
- El límite del POS (3.000 €) es fijo y no sigue los ajustes del plugin de facturas.

**Estados, devoluciones y cancelados**

- Desde el POS solo se devuelve **el pedido entero**. La devolución de un solo producto no existe en la caja (Webkul no la trae; su readme habla de un «Return POS» aparte): se hace en WooCommerce.
- **El dinero de una devolución no se devuelve solo**: lo hace la tienda. No está comprobado cómo apunta Webkul esa devolución en la caja de efectivo del cajero.
- **Anular con el core solo:** el core de VFWoo no marca como anulada la factura de un pedido cancelado y su PDF sale como una factura normal. Con Verifactu, la factura se anula y el PDF sale marcado. Está en el roadmap para VFWoo.
- El desplegable se ordena en el navegador: ocultar opciones depende del navegador (Safari las deja en gris).

**Venta sin conexión (Webkul)**

- **Con inventario centralizado** (el stock compartido con la tienda online) **la caja no cobra sin conexión**: avisa «Cannot process orders with centralized inventory at offline mode». Solo funciona con **Master Stock** (stock propio del POS). No está comprobado cómo se reparte el stock entre la caja y la web en ese modo.
- **El service worker de Webkul no se registra** en esta versión (7.1.1): el POS lo pide en `<web>/assets/service-worker.js`, que da 404 (el archivo está en la carpeta del plugin). Resultado: la caja **no arranca sin conexión**; solo sigue funcionando si ya estaba abierta. Pendiente de consultar a Webkul (`_dev/temp/informe-soporte-webkul-service-worker.md`).
- Sin conexión no se pueden aplicar cupones ni añadir, editar o borrar clientes (Webkul), ni sincronizar hasta que vuelva la red.
- **El ticket sin conexión saldría sin número de factura ni QR** (el pedido no existe en WooCommerce hasta sincronizar); no probado. Con Verifactu, consulta con tu gestor antes de entregar un ticket así.
- La regla de 3.000 € sí se evalúa en el navegador, pero el cliente con NIF no se puede crear sin conexión: debe existir antes.

**PDF de la factura**

- «Imprimir factura» **tarda unos segundos**, porque VFWoo genera el PDF de cero cada vez. La causa exacta está sin medir.
- La **impresión directa** depende del navegador: Chrome y Edge suelen aceptarla; Firefox y Safari pueden fallar, y en ese caso se abre en una pestaña nueva. La ventana de imprimir siempre la abre el navegador (no se imprime en silencio).
- Los correos de **reembolso** y los de pedidos **cancelados o fallidos** salen **sin PDF** adjunto por el bridge.
- Con una **F3 que sustituye a una F2**, el PDF del pedido es el que VFWoo da como factura más reciente; no está comprobado qué documento sale en cada caso.
- El PDF adjunto y el botón usan piezas internas del plugin de facturas (su motor de PDF y su ayudante de facturas). Si una versión futura las cambia, el bridge no adjunta ni imprime el PDF y el resto del correo sale como siempre.

### 5.5 Rutas internas

- `admin-post.php?action=vfwoo_webkul_pdf`: redirige al PDF de VFWoo con el nonce de la sesión; pide permiso para abrir pedidos. Con `refund_id` redirige a la **rectificativa** de ese reembolso (`credit_note`), solo si es de ese pedido.
- `vfwoo-webkul/v1/`: `catalog-version` (versión del catálogo), `brand-report` (ventas por marca), `full-invoice-status` y `full-invoice-issue` (hacer factura a un ticket ya entregado; con Verifactu, F3).
- Filtro sobre `pos/v1/update-order-status` (Webkul): solo admite `completed`, `cancelled` y `refunded` (o el mismo estado del pedido).
