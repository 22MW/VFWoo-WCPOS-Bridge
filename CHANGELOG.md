# Historial de cambios

Este proyecto sigue el formato de Keep a Changelog y utiliza versionado semántico.

## [Sin publicar]

### Añadido

- **Accesos en la cabecera del POS**, antes de los iconos de Webkul: Panel de WooCommerce, Productos, Informes de ventas, Análisis y Tienda online. Los de administración abren la pantalla real del admin en una ventana dentro del POS (a la derecha de su menú, sin el menú ni la barra de WordPress) y solo salen a quien tiene permiso para esa pantalla; Productos trae sus pestañas (todos, añadir, categorías, atributos, etiquetas). La tienda abre la página pública de compra en una pestaña nueva y la ven todos. Iconos de WooCommerce: su logo y Dashicons.
- Los iconos de acceso de la cabecera toman el color de acento del POS al pasar el ratón, con el teclado y mientras su ventana está abierta.
- **Botón «POS-CAJA» en la barra de administrador**, en la misma pestaña. Construye la dirección siempre con el endpoint guardado en Webkul (`_pos_endpoint_name`).
- **Cajeros:** interruptor, desactivado por defecto, para añadir el rol «Gestor de tienda» a los cajeros que se creen desde Webkul.
- **El aviso de catálogo salta al guardar, borrar o restaurar un producto desde el admin.** Texto: «Hay cambios en los productos. Pulsa Resync → Products para actualizar el catálogo.»
- Interruptor «Ocultar el aviso de WooCommerce sobre plugins que no declaran compatibilidad», desactivado por defecto, en POS → Settings → VFWoo Bridge.

### Corregido

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
