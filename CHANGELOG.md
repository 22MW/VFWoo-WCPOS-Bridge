# Historial de cambios

Este proyecto sigue el formato de Keep a Changelog y utiliza versionado semántico.

## [Sin publicar] — dev 0.1.0.7

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
