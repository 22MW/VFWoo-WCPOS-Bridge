# Historial de cambios

Este proyecto sigue el formato de Keep a Changelog y utiliza versionado semántico.

## [Sin publicar]

## [0.1.1] - 2026-09-20

Primera versión estable: consolida las entradas de desarrollo 0.1.0.1 a 0.1.0.13.

### Añadido

- Actualizador desde GitHub Releases: el plugin aparece en Escritorio › Actualizaciones y se actualiza con un clic. Solo considera releases con tag `pos-v…` del repositorio compartido y rechaza paquetes de otra procedencia. Se registra antes de comprobar dependencias y no se activa en una copia de desarrollo (con `.git`), para que una actualización no borre el historial.

### Corregido

- Identidad, dependencias y bootstrap alineados con el puente VFWoo original.
- El script del ticket usa el hook nativo `wkwcpos_enqueue_pos_scripts`.
- La lectura fiscal admite pedidos Webkul en estado `processing`.
- La pestaña de ajustes se identifica como `VFWoo Bridge`.
- Se preparan los términos `product_brand` en la respuesta de productos de Webkul.

### Añadido

- Filtro `BRANDS` en el panel nativo de filtros del catálogo POS: se añade una entrada `BRANDS` a `attributes` con los términos de `product_brand`. Confirmado en local.
- Taxonomías de filtro configurables en `POS → Settings → VFWoo Bridge`: cualquier taxonomía registrada para `product` (salvo categorías, `pa_*` e internas de WooCommerce). Por defecto `product_brand`. Confirmado en local.

- Versión de catálogo (`vfwoo_webkul_catalog_version`): botón `Forzar recarga del catálogo` en `POS → Settings → VFWoo Bridge`; también sube al guardar los filtros. Cada producto lleva la versión con la que se cargó.
- Aviso en el POS cuando el catálogo en caché es anterior: pide usar Resync → Products. No borra datos. Se quita solo tras el Resync.
- Consulta automática de la versión sin recargar el POS (ruta REST `vfwoo-webkul/v1/catalog-version`): al abrir, al terminar una venta, al cambiar de pantalla y cada 5 minutos.
- Factura completa (F3) desde el POS: botón «Factura completa (F3)» junto a «Imprimir factura» en pedidos vendidos como F2. Solo con la F2 confirmada; el cliente se busca o se crea con los servicios del POS (solo clientes con NIF, nunca el cliente por defecto) y VFWoo emite la F3 (`Invoice_Helper::emitir_sustitutiva`). Cualquier cajero autenticado puede. Al imprimir sale la F3 con los datos del comprador. Endpoints `vfwoo-webkul/v1/f3-status` e `issue-f3`.
- El ticket F3 imprime al comprador de la F3 y no al cliente mostrador; guarda el cliente elegido en el pedido (`_vfwoo_webkul_f3_customer`) para su domicilio y contacto.
- Con la factura aún sin confirmar, el QR del ticket se incrusta y su URL lleva `temp=1`, porque la ruta del QR no sirve facturas pendientes.
- Autenticación de los endpoints del bridge centralizada en `Pos_Auth`.
- Parada de la venta antes de cobrar cuando hará falta un cliente con NIF: con las simplificadas activas, una venta igual o superior al límite (400 €, o 3.000 € en sector autorizado) sin cliente con NIF no avanza desde el botón Pay ni desde el botón final de pago; con las simplificadas desactivadas, toda venta lo exige. Cliente por defecto o sin NIF por debajo del límite se vende como F2; NIF sin verificar solo avisa. Límite y ajuste leídos de VFWoo. Confirmado en local.
- Los datos de cliente que recibe el POS incluyen ahora `vfwoo_webkul_is_default` junto al NIF y su estado; hace falta Resync de clientes.
- Variables de VFWoo en el editor de plantillas del ticket (`${vfwoo_*}`): tienda (nombre, NIF, dirección, teléfono, email y logo), factura (número, tipo, fecha, leyenda legal, URL de cotejo y QR) y cliente (nombre, NIF, domicilio, email y teléfono). Los datos del cliente solo salen en F1 y F3; en F2 se vacían también las variables de cliente de Webkul.
- El editor de plantillas de Webkul evalúa la plantilla como código y se quedaba en blanco con una variable desconocida; ahora las `${vfwoo_*}` se protegen para que se vean como texto y se guarden sin cambios.
- Todo texto insertado en el ticket se escapa para no romper ni inyectar código en la evaluación de la plantilla.
- Retirado el debug temporal (`error_log` en PHP y `console.info` en el ticket).
- Pantalla «Marcas» en el POS (menú de la izquierda): ventas del POS por marca con unidades, ventas sin y con IVA, pedidos y devoluciones, y desglose por producto. Periodos Hoy, Ayer, Esta semana, Este mes y rango Desde/Hasta (máximo 1 año). Endpoint de solo lectura `vfwoo-webkul/v1/brand-report`. Confirmado en local.
- Clientes del POS: campo obligatorio DNI/NIE/CIF en el formulario. Se valida el formato y, para NIF españoles, el censo de la AEAT al guardar (si el censo no responde, queda «sin verificar»). Se guarda en el usuario (`billing_nif`).
- El NIF del cliente se copia al pedido (`_billing_nif`) salvo para el cliente por defecto, de modo que VFWoo emite factura completa F1. Sin NIF, F2.
- Compatibilidad con WP Armour Extended: se omite su comprobación antispam solo al guardar un cliente desde el POS.
- QR incrustado en el ticket como PNG base64 (`qr_data_uri`) en venta inicial y reimpresión de un pedido: la primera impresión ya no sale con la imagen rota (Webkul imprime 500 ms después de montar el ticket y la ruta REST del QR tarda más). Si falta la clase `VFWoo\QR`, se usa la URL como antes.
- El ticket ya no muestra la URL de cotejo en texto; se mantiene número de factura, QR y leyenda legal.
- Estilo del bridge permitido en la lista blanca de estilos del POS (`wkwcpos_add_custom_css`).

### Validado localmente

- Impresión inicial y reimpresión del pedido 39084.
- QR, número y datos fiscales recibidos por el ticket.
- La caché local de Webkul puede conservar respuestas anteriores; limpiarla fuerza la carga enriquecida.

### Pendiente de validación

- Filtros: productos variables, combinación con categoría y búsqueda, y paginación.
- Recarga automática o acción controlada para esa caché.

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
