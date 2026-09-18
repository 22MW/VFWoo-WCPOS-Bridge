# Historial de cambios

Este proyecto sigue el formato de Keep a Changelog y utiliza versionado semántico.

## [Sin publicar]

### Corregido

- Identidad, dependencias y bootstrap alineados con el puente VFWoo original.
- El script del ticket usa el hook nativo `wkwcpos_enqueue_pos_scripts`.
- La lectura fiscal admite pedidos Webkul en estado `processing`.
- La pestaña de ajustes se identifica como `VFWoo Bridge`.
- Se preparan los términos `product_brand` en la respuesta de productos de Webkul.

### Validado localmente

- Impresión inicial y reimpresión del pedido 39084.
- QR, número y datos fiscales recibidos por el ticket.
- La caché local de Webkul puede conservar respuestas anteriores; limpiarla fuerza la carga enriquecida.

### Pendiente de validación

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
