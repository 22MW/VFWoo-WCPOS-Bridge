# VFWoo + Webkul WooCommerce POS

## Estado

Documento inicial de investigación. No se ha creado todavía el plugin ni se ha modificado VFWoo, Webkul WooCommerce POS o el puente existente.

## Objetivo

Estudiar un addon independiente que conecte VFWoo con el plugin **WooCommerce Point of Sale de Webkul** y permita:

1. Incorporar al ticket del POS los datos fiscales y el QR generados por VFWoo.
2. Mejorar la navegación del catálogo del POS, especialmente con filtros por marca y otras taxonomías de producto.
3. Identificar otras mejoras que puedan añadirse sin modificar directamente los plugins originales.

## Plugins implicados

- VFWoo: responsable de emitir y conservar la información fiscal.
- Puente existente: `app/public/wp-content/plugins/vfwoo-wcpos-bridge`.
- Nuevo POS estudiado: `app/public/wp-content/plugins/woo-point-of-sale`.
- Producto identificado: **WooCommerce Point of Sale**, de Webkul, versión **7.1.1**.

## Referencia: puente VFWoo–WCPOS existente

El puente actual se diseñó para otro POS y utiliza contratos propios de ese sistema. Su responsabilidad está separada:

- VFWoo emite, numera y aporta los datos fiscales.
- WCPOS gestiona la venta, el cobro y la impresión.
- El puente solamente lee los datos de VFWoo y los incorpora al recibo.

El contrato principal utilizado es:

```php
VFWoo\Ticket_Fiscal_Data::for_order( $order_id )
```

Según el puente actual, este contrato puede aportar:

- disponibilidad de los datos fiscales;
- número y serie de factura;
- tipo de factura;
- fecha de emisión;
- total;
- URL de cotejo;
- origen o contenido del QR;
- leyenda legal;
- datos de confirmación fiscal.

Este reparto debe mantenerse en la futura integración: el addon no debe emitir, numerar ni comunicarse por su cuenta con Hacienda.

## Diferencia fundamental con Webkul

El puente existente **no es compatible directamente** con Webkul. Depende de endpoints y filtros específicos del POS anterior, entre ellos el enriquecimiento del snapshot fiscal y el recibo live.

Webkul utiliza:

- su propia REST API bajo `pos/v1`;
- su propio flujo de creación de pedidos WooCommerce;
- plantillas HTML de factura almacenadas en una tabla propia;
- una aplicación frontend construida en React/JavaScript;
- caché y funcionamiento offline mediante service worker.

Por ello, la integración debe implementarse como un addon nuevo y específico.

## Integración del QR de VFWoo

### Viabilidad

La integración parece viable porque Webkul crea pedidos WooCommerce normales y VFWoo trabaja a partir del pedido. Lo que falta confirmar es el momento exacto en que VFWoo termina la emisión respecto al momento en que Webkul prepara e imprime el recibo.

### Puntos técnicos localizados

Webkul permite modificar la plantilla entregada al POS mediante:

```php
wkwcpos_modify_invoice_template_at_pos
```

La plantilla utiliza variables frontend como:

```text
${order_id}
${order_date}
${order_products_data}
${order_total}
${cashier_name}
${cashier_note}
```

La mera inclusión de HTML nuevo en la plantilla no garantiza que una variable fiscal personalizada sea sustituida. Será necesario proporcionar los datos fiscales al frontend o transformar la respuesta final del recibo.

### Flujo propuesto

1. Webkul crea el pedido WooCommerce.
2. VFWoo procesa el pedido mediante su flujo normal.
3. El addon obtiene los datos con `Ticket_Fiscal_Data::for_order()`.
4. El addon añade al recibo:
   - número de factura;
   - fecha de emisión;
   - URL de cotejo;
   - leyenda legal;
   - QR fiscal.
5. Webkul imprime el ticket con esa información.

### Decisión pendiente

Antes de desarrollar se debe realizar una venta de prueba y capturar:

- endpoint utilizado para recuperar o construir el ticket;
- respuesta real de creación del pedido;
- respuesta utilizada al reimprimir un pedido;
- estado del pedido cuando VFWoo expone los datos;
- comportamiento online y offline;
- formato aceptado por el impresor para el QR: URL, imagen, SVG, canvas o base64.

Sin esta prueba no se puede afirmar todavía cuál es el punto de integración definitivo.

## Filtros por marca y taxonomías

### Estado actual comprobado

Webkul incluye categorías de producto y el endpoint:

```text
pos/v1/get-all-categories
```

También devuelve los productos del outlet y dispone de estos filtros PHP:

```php
wkwcpos_modify_products_response_at_pos
wkwcpos_modify_categories_response_at_pos
wkwcpos_modify_product_ids_response_at_pos
```

El código de productos ya procesa atributos de WooCommerce. Sin embargo, no se ha localizado una interfaz visual genérica que permita seleccionar cualquier taxonomía de producto.

### Propuesta

El addon podría permitir elegir desde ajustes qué taxonomías estarán disponibles como filtros, por ejemplo:

- `product_cat` para categorías;
- `product_brand` si la tienda utiliza WooCommerce Brands;
- `pa_marca` si la marca está modelada como atributo global;
- cualquier taxonomía personalizada asociada a `product`.

La implementación necesita dos capas:

1. **PHP/API:** añadir a cada producto sus términos y entregar al POS la lista de filtros disponibles.
2. **Frontend:** añadir controles de selección y aplicar el filtrado en el catálogo React.

### Riesgos que deben comprobarse

- El catálogo puede quedar almacenado en caché para trabajar offline.
- Modificar únicamente PHP no creará controles nuevos en la interfaz.
- Modificar directamente los bundles compilados de Webkul sería frágil ante actualizaciones.
- Hay que comprobar si Webkul expone filtros JavaScript públicos suficientes para extender la pantalla sin recompilar el plugin.
- Con catálogos grandes, descargar todas las taxonomías por producto puede afectar al peso y velocidad de sincronización.

## Otras mejoras posibles a investigar

- filtro por stock disponible en el outlet;
- filtro por atributos como talla, color o formato;
- búsqueda combinada por nombre, SKU, GTIN o código de barras;
- productos favoritos o accesos rápidos por cajero;
- identificación visual de productos sin stock o con stock bajo;
- visualización de marca en la tarjeta del producto;
- datos fiscales del cliente para facturas F1;
- elección clara entre factura simplificada y factura completa;
- reimpresión de tickets con datos fiscales ya emitidos;
- aviso cuando VFWoo todavía no haya generado la factura;
- control de impresoras y anchuras 58/80 mm;
- traducciones y textos específicos del comercio;
- mejoras de informes por marca, categoría, cajero u outlet.

Estas son posibilidades técnicas, no funcionalidades confirmadas ni aprobadas.

## Arquitectura recomendada

Nombre provisional:

```text
vfwoo-webkul-pos-bridge
```

El futuro plugin debería:

- vivir fuera de VFWoo y de Webkul;
- usar solamente APIs y filtros públicos cuando existan;
- conservar VFWoo como única autoridad fiscal;
- no modificar archivos del plugin Webkul;
- detectar versiones compatibles;
- separar la integración fiscal de las mejoras del catálogo;
- registrar errores sin guardar secretos ni datos innecesarios;
- degradarse de forma segura si VFWoo no tiene datos disponibles.

Estructura conceptual posible:

```text
vfwoo-webkul-pos-bridge/
├── vfwoo-webkul-pos-bridge.php
├── includes/
│   ├── class-requirements.php
│   ├── class-vfwoo-data-provider.php
│   ├── class-webkul-order-integration.php
│   ├── class-webkul-receipt-integration.php
│   ├── class-product-taxonomy-provider.php
│   └── class-settings.php
└── assets/
    ├── js/
    └── css/
```

La estructura es orientativa y deberá ajustarse cuando se confirme el contrato real del ticket y los puntos de extensión JavaScript.

## Fases propuestas

### Fase 1 — Prueba y captura

- realizar una venta POS de prueba;
- observar las peticiones REST;
- comprobar el pedido creado y sus metadatos;
- verificar cuándo están disponibles los datos de VFWoo;
- imprimir y reimprimir el ticket;
- comprobar comportamiento offline.

### Fase 2 — Prototipo fiscal

- crear el esqueleto del addon independiente;
- incorporar número fiscal, QR y leyenda;
- probar F1 y F2;
- probar ticket de 58 y 80 mm;
- verificar que no se duplica ninguna emisión.

### Fase 3 — Filtros de catálogo

- identificar la taxonomía real utilizada para las marcas;
- medir volumen de productos y términos;
- añadir datos al endpoint;
- crear el filtro visual;
- comprobar caché, sincronización y modo offline.

### Fase 4 — Mejoras adicionales

- priorizar las mejoras según necesidad real;
- implementar cada mejora de forma independiente;
- verificar compatibilidad después de actualizar Webkul.

## Preguntas pendientes

- ¿Qué plugin o taxonomía representa actualmente las marcas?
- ¿El negocio necesita solo filtrar por marca o también por talla, color, proveedor u otras taxonomías?
- ¿Qué impresora y ancho de papel se utilizan?
- ¿La impresión se hace desde navegador, impresora de red, USB o servicio externo?
- ¿Debe funcionar el QR cuando el POS está temporalmente offline?
- ¿El ticket debe mostrar siempre datos fiscales o solo cuando VFWoo confirme la emisión?
- ¿Debe poder reimprimirse el ticket fiscal desde el historial?
- ¿Se necesitan facturas F1 con datos completos del cliente desde el propio POS?

## Siguiente decisión

No desarrollar todavía. El siguiente paso recomendado es validar este planteamiento y, si se aprueba, ejecutar únicamente la **Fase 1 — Prueba y captura**. Sus resultados permitirán diseñar el addon con evidencia real y sin alterar los plugins originales.

## Contratos VFWoo confirmados

Se han revisado `vfwoo/_dev/docs/HOOKS.md` y `vfwoo/_dev/TECHNICAL_ONBOARDING.md`.

La llamada recomendada para un TPV es:

```php
$data = \\VFWoo\\Ticket_Fiscal_Data::for_order( $order, true );
```

El segundo parámetro (`true`) prepara el QR cuando todavía no hay respuesta definitiva. La llamada es de solo lectura: no emite, no numera y no modifica el pedido.

Claves relevantes del resultado:

```text
available, invoice_number, invoice_series, invoice_type, issued_at,
total, currency, taxpayer_id, verification_url, qr_payload, qr_src,
qr_is_prepared, confirmed, legal_legend
```

### QR

El QR debe mostrarse mediante una URL normal, no como `data:image/png;base64`, porque WordPress puede alterar ese protocolo al filtrar HTML.

Endpoint documentado:

```text
/wp-json/vfwoo/v1/qr/{pedido}?size=300&key={clave-del-pedido}&temp=1
```

El tamaño permitido es de 60 a 1000 píxeles. `temp=1` devuelve la URL oficial si ya existe; si no, prepara el cotejo con el número reservado. No convierte un número provisional en definitivo.

### Estados del pedido

VFWoo acepta por defecto solo `completed` para los datos del ticket. Hay que comprobar si Webkul deja una venta cobrada en `processing`. Si es así, el addon podrá ampliar de forma controlada:

```php
add_filter( 'vfwoo_ticket_estados_validos', ... );
```

La ampliación no debe hacer pasar una venta sin factura como emitida.

### Emisión desde el mismo WordPress

VFWoo expone el puente PHP interno `\\VFWoo\\Vitamin_Bridge::emitir( $order_id )`, además de `anular`, `subsanar`, `corregir_nif` y `poll_estado`. Solo funciona dentro del mismo WordPress. Cualquier llamada deberá autorizarse y auditarse mediante `vfwoo_vitamin_bridge_allow` y las actions `vfwoo_vitamin_bridge_before`/`vfwoo_vitamin_bridge_after`.

### Timeout de caja

Para evitar esperas largas en el TPV, VFWoo documenta:

```php
add_filter( 'vfwoo_api_timeout', fn() => \\VFWoo\\Helpers\\Provider_Client::TIMEOUT_TPV );
```

Un fallo de comunicación debe conservar el estado pendiente/reintentable y no duplicar numeración.

### Regla de diseño para el addon

El futuro addon Webkul debe limitarse a leer `Ticket_Fiscal_Data`, mostrar el QR y los datos fiscales, ampliar estados solo si la prueba de Webkul lo exige y, únicamente con aprobación, disparar `Vitamin_Bridge`. No debe duplicar numeración, emisión ni llamadas al proveedor.
