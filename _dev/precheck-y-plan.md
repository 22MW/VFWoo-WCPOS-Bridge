# Precheck y plan de desarrollo

## 1. Precheck

### Plugin objetivo

Nombre provisional:

```text
vfwoo-webkul-pos-bridge
```

El plugin todavía no existe en:

```text
app/public/wp-content/plugins/vfwoo-webkul-pos-bridge/
```

Por tanto, este documento bloquea el alcance del futuro addon, pero no autoriza todavía su creación ni implementación.

### Plugin de referencia

Se ha revisado:

```text
app/public/wp-content/plugins/vfwoo-wcpos-bridge/
```

Su estructura y decisiones sirven como referencia, no como código reutilizable sin adaptación. El destino técnico es Webkul WooCommerce Point of Sale, que tiene contratos REST, recibos, frontend y plantillas diferentes.

### Repo, rama y cambios pendientes de la referencia

- Repo propio detectado en `vfwoo-wcpos-bridge/.git/`.
- Rama actual detectada: `posDevMisha`.
- Hay cambios locales pendientes y archivos nuevos no confirmados.
- Esos cambios no se han tocado ni se deben mezclar con el nuevo addon.

### Dependencias conocidas

- WordPress.
- WooCommerce.
- VFWoo.
- WooCommerce Point of Sale de Webkul, versión local revisada: 7.1.1.

La versión de Webkul debe volver a comprobarse en el entorno real antes de implementar o publicar.

### Evidencia revisada

- `vfwoo-wcpos-bridge/docs/ARQUITECTURA.md`.
- `vfwoo-wcpos-bridge/docs/PLAN-DESARROLLO.md`.
- `vfwoo-wcpos-bridge/docs/ROADMAP.md`.
- Código de `vfwoo-wcpos-bridge/includes/` y sus plantillas.
- Código de Webkul `api/includes/products/`, `api/includes/orders/`, `api/includes/misc/` y `includes/front/`.
- `vfwoo/_dev/docs/HOOKS.md`.
- `vfwoo/_dev/docs/TECHNICAL_ONBOARDING.md`.

### Clasificación

- Tamaño: grande.
- Tipo: plugin nuevo basado en integración y arquitectura modular.
- Riesgo: alto.
- Áreas sensibles: pedidos, facturación, numeración fiscal, QR, impresión, REST, frontend y modo offline.
- Modo recomendado: completo, por fases y con pruebas en staging.

### Riesgos de alcance

- No está confirmado todavía el endpoint exacto que Webkul utiliza al imprimir o reimprimir el ticket.
- No está confirmado si el POS imprime desde HTML almacenado, respuesta REST, estado React o una combinación.
- No está confirmado el estado final del pedido creado por Webkul (`processing`, `completed` u otro).
- El catálogo offline puede exigir una extensión JavaScript además de PHP.
- Modificar bundles compilados de Webkul sería frágil y queda fuera del diseño inicial.
- Se afectan pedidos y facturación: antes de cualquier prueba con datos reales se requiere staging y backup verificado.

## 2. Evaluación del cambio

### Cambio solicitado

Crear y planificar un addon independiente que conecte VFWoo con Webkul POS, empezando por el ticket fiscal con QR y dejando una base ampliable para filtros de catálogo y futuras extensiones.

### Tipo

- Funcionalidad nueva.
- Arquitectura de integración.
- Extensión de WooCommerce.
- Seguridad y datos sensibles.
- Sin release ni deploy en esta fase.

### Impacto

| Área | Impacto |
|---|---|
| Funcionalidad | Alto: ticket fiscal y filtros POS |
| Arquitectura | Alto: addon modular y contratos externos |
| WooCommerce | Alto: pedidos, estados y facturación |
| Seguridad | Alto: permisos, QR, claves de pedido y datos fiscales |
| Frontend | Medio/alto: posible extensión React de Webkul |
| QA | Alto: cobro, timeout, reimpresión, QR, offline |
| Documentación | Medio: contratos, compatibilidad y soporte |
| Release | Fuera de alcance por ahora |

### Modo

Modo completo. El cambio no debe pasar directamente a implementación: primero hay que cerrar la captura real del flujo de Webkul y validar el MVP.

## 3. Plan funcional

### Problema

Webkul POS crea y cobra pedidos WooCommerce, pero todavía no está conectado al contrato fiscal de VFWoo ni ofrece, según la revisión estática, filtros genéricos por marca y otras taxonomías.

### MVP

El MVP debe hacer solo esto:

1. Detectar pedidos creados por Webkul.
2. Leer VFWoo con `Ticket_Fiscal_Data::for_order( $order, true )`.
3. Añadir al ticket número, fecha, URL de cotejo, QR y leyenda legal.
4. Mantener el pedido y la numeración bajo WooCommerce/VFWoo.
5. Degradar de forma segura si la factura todavía no está disponible.
6. Dejar puntos de extensión para filtros de catálogo sin incluirlos todavía en el MVP fiscal.

### Fase posterior de catálogo

Como módulo separado:

- seleccionar taxonomías configurables (`product_brand`, `pa_marca`, `product_cat` u otras);
- entregar términos y productos al catálogo POS;
- añadir filtros visuales sin modificar el core de Webkul;
- revisar caché y modo offline;
- evitar descargar datos innecesarios en catálogos grandes.

### Fuera del MVP

- rectificativas y devoluciones;
- emisión automática nueva si no es necesaria para el flujo real;
- modificar pagos o stock;
- modificar archivos del plugin Webkul;
- editar bundles compilados sin contrato estable;
- soporte offline fiscal completo;
- informes comerciales avanzados;
- publicación o despliegue.

## 4. Arquitectura propuesta

El addon debe tener un núcleo pequeño y módulos independientes:

```text
vfwoo-webkul-pos-bridge/
├── vfwoo-webkul-pos-bridge.php
├── includes/
│   ├── class-plugin.php
│   ├── class-requirements.php
│   ├── Contracts/
│   │   ├── interface-pos-adapter.php
│   │   └── interface-feature-module.php
│   ├── Integrations/
│   │   ├── class-vfwoo-data-provider.php
│   │   ├── class-webkul-order-integration.php
│   │   ├── class-webkul-receipt-integration.php
│   │   └── class-webkul-rest-integration.php
│   ├── Modules/
│   │   ├── Fiscal/
│   │   ├── Catalog/
│   │   └── Diagnostics/
│   ├── Settings/
│   └── Support/
├── assets/
│   ├── admin/
│   └── pos/
├── docs/
└── readme.txt
```

### Reglas de extensibilidad

- El núcleo descubre dependencias y registra módulos.
- Cada módulo puede activarse o desactivarse sin alterar los demás.
- La integración fiscal no debe depender del módulo de catálogo.
- Los adaptadores de POS deben encapsular los hooks y endpoints específicos de Webkul.
- Los contratos internos deben recibir y devolver arrays normalizados, con namespace propio.
- Las extensiones futuras deben poder añadir otro POS sin copiar el núcleo VFWoo.
- Las plantillas y transformaciones de ticket deben estar separadas del proveedor fiscal.

## 5. Plan técnico por fases

### F0 — Precheck y contrato real

- Capturar una venta Webkul en staging.
- Registrar endpoint de creación, consulta, impresión y reimpresión.
- Confirmar estado del pedido y momento de disponibilidad VFWoo.
- Confirmar online/offline y formato QR aceptado.

**Salida:** contrato real documentado y decisión del punto de integración.

### F1 — Esqueleto del addon

- Crear repo propio del nuevo plugin cuando se autorice.
- Crear requisitos, cargador de módulos, diagnóstico y versionado.
- No incluir todavía cambios de Webkul ni emisión fiscal.

**Salida:** plugin instalable vacío, con dependencias detectadas y sin efectos sobre pedidos.

### F2 — Adaptador fiscal Webkul

- Implementar proveedor de lectura VFWoo.
- Adaptar estados permitidos solo si la evidencia lo exige.
- Enriquecer el payload o recibo real de Webkul.
- Añadir número, fecha, QR por URL, cotejo y leyenda.

**Salida:** ticket fiscal normal y reimpresión manual con datos coherentes.

### F3 — Ajustes y diagnóstico

- Activación independiente del módulo fiscal.
- Estado de dependencias y contratos.
- Registro mínimo de errores sin secretos ni datos personales innecesarios.
- Comprobación de compatibilidad de versiones.

**Salida:** soporte operativo y diagnóstico sin tocar pedidos.

### F4 — Filtros de catálogo

- Confirmar taxonomías de marca reales.
- Ampliar respuesta de productos mediante APIs/filtros disponibles.
- Añadir frontend POS solo mediante extensión estable.
- Probar caché, rendimiento y offline.

**Salida:** filtros por marca y taxonomías configurables.

### F5 — Revisión de extensiones

- rectificativas;
- reimpresión fiscal avanzada;
- reglas por outlet/impresora;
- avisos de emisión pendiente;
- informes comerciales.

Cada módulo tendrá su propio alcance y validación.

## 6. Seguridad y WooCommerce

- Usar `WC_Order`, `WC_Product` y APIs oficiales.
- No recrear pedidos, pagos, stock ni numeración.
- Validar capabilities y nonces en ajustes y acciones administrativas.
- Sanitizar IDs con `absint()` y entradas según contexto.
- Escapar HTML, atributos, URLs y datos JavaScript.
- No exponer claves de pedido, tokens ni respuestas completas del proveedor en logs.
- Revisar compatibilidad HPOS.
- Probar siempre en staging con backup antes de acciones sobre facturación o pedidos.

## 7. Criterios de aceptación del MVP

- El plugin no altera pedidos si VFWoo o Webkul están desactivados.
- Una venta Webkul crea un pedido WooCommerce normal.
- El ticket muestra el número fiscal correcto cuando VFWoo lo tiene.
- El QR se carga desde una URL válida y escaneable.
- Con respuesta pendiente se utiliza `temp=1` sin duplicar numeración.
- Un fallo de red no emite una segunda factura ni cambia el número.
- La reimpresión conserva los mismos datos fiscales.
- Los datos no disponibles se muestran como pendientes o se omiten de forma segura.
- No se modifica ningún archivo del plugin Webkul.
- PHP, seguridad y compatibilidad quedan validados antes de cualquier release.

## 8. Decisiones pendientes antes de implementar

- Confirmar el contrato real del ticket Webkul con una venta de staging.
- Confirmar si el MVP debe disparar `Vitamin_Bridge::emitir()` o solo leer una emisión existente.
- Confirmar qué taxonomía representa las marcas.
- Confirmar impresora y ancho de papel.
- Confirmar si el ticket fiscal debe salir solo con factura confirmada o también pendiente.
- Confirmar estrategia de offline.
- Autorizar expresamente la creación del nuevo plugin y su repo propio.

## 9. Scope lock actual

- **Objetivo:** precheck y plan de un addon extensible VFWoo + Webkul POS.
- **Referencia:** `vfwoo-wcpos-bridge`, sin mezclar sus cambios locales.
- **Archivos creados en esta fase:** solo documentación bajo `_dev/vfwoo-webkul-pos-bridge/`.
- **Código:** no autorizado todavía.
- **Base de datos / pedidos / settings reales:** fuera de alcance.
- **Git commit, push, release y deploy:** fuera de alcance.
- **Validación prevista antes de implementar:** captura real Webkul en staging, después precheck del nuevo repo y aprobación del MVP.
- **Rollback:** al no haber código ni cambios runtime, no aplica; las notas documentales son reversibles mediante Git.

## 10. Evidencia F0 — primera venta Webkul

### Prueba recibida

- Pedido: `39084`.
- Entorno: WordPress local `http://pos.local`.
- Webkul generó un PDF desde la pantalla de pedido.
- La plantilla utilizada es la factura HTML de Webkul.
- El PDF contiene comercio, cliente, líneas, impuestos, total, pago y cambio.

### Resultado observado

El PDF se genera correctamente, pero no contiene todavía:

- número de factura VFWoo;
- URL de cotejo;
- QR VERI*FACTU;
- leyenda legal VFWoo;
- estado o confirmación fiscal.

### Hipótesis técnica pendiente de confirmar

El primer punto de integración puede ser la plantilla de factura de Webkul o el endpoint que entrega los datos de esa plantilla. La captura visual demuestra la salida PDF, pero todavía no demuestra qué endpoint o filtro la construye.

### Para cerrar esta parte de F0

Hay que comprobar en el pedido `39084`:

1. Si VFWoo ha creado una factura y qué número tiene.
2. El estado WooCommerce del pedido.
3. Si el metabox de VFWoo muestra QR o URL de cotejo.
4. Si la reimpresión desde Webkul genera exactamente el mismo PDF.
5. Si el navegador permite identificar las peticiones REST al abrir/imprimir la factura.

### Evidencia fiscal añadida

- VFWoo confirma: `Factura MACROPUS-2026-0027 registrada (F2, PEN)`.
- La factura corresponde al pedido `39084`.
- La reimpresión desde Webkul produce el mismo PDF.
- El QR todavía está pendiente de comprobar tras la ejecución del cron.

### Panel VFWoo comprobado

La captura del pedido `39084` muestra:

- factura `F2: MACROPUS-2026-0027`;
- estado `Correcta`;
- QR visible en el panel Veri*Factu;
- base `65,18 €`, cuota `13,69 €` y total `78,87 €`;
- botón de impresión propio de VFWoo.

Esto confirma que VFWoo ya dispone del QR y de los datos fiscales correctos. El problema de integración queda acotado a transportar esos datos al PDF/ticket generado por Webkul. El QR en la salida Webkul sigue pendiente de prueba.

### Flujo de impresión Webkul confirmado en código

La captura de Network solo mostró el avatar `assets/images/17241-200.png`, iniciado por `assets/dist/app/index.js`. La revisión del bundle confirma por qué:

1. Webkul obtiene la plantilla mediante `WK_GET_INVOICE_TEMPLATE_ENDPOINT`.
2. La guarda localmente en IndexedDB, tabla `pos_invoice`.
3. `getOrderInvoice()` sustituye las variables de la plantilla con los datos del pedido.
4. `openPrintWindow()` crea un `iframe` oculto llamado `orderReceiptFrame`.
5. Escribe el HTML con `document.write()` y llama a `window.frames.orderReceiptFrame.print()`.

No existe un PDF independiente descargado desde el servidor en este flujo. El navegador convierte el HTML del `iframe` en salida de impresión/PDF.

### Puntos de extensión JavaScript confirmados

Webkul aplica filtros de `wp.hooks` antes de evaluar e imprimir la plantilla. Entre ellos:

```text
wkwcpos_summary_modify_invoice_data
wkwcpos_invoice_after_products_list_block
wkwcpos_invoice_after_outlet_name_block
wkwcpos_invoice_before_invoice_details_block
wkwcpos_invoice_before_order_details_block
wkwcpos_invoice_before_outlet_details_block
wkwcpos_invoice_before_product_details_block
wkwcpos_invoice_after_subtotal_block
wkwcpos_invoice_after_order_tax_block
wkwcpos_invoice_after_discount_block
wkwcpos_invoice_after_coupon_block
wkwcpos_invoice_after_order_total_block
wkwcpos_invoice_after_cash_pay_block
wkwcpos_invoice_after_other_pay_block
wkwcpos_invoice_after_order_change_block
wkwcpos_invoice_after_cashier_note_block
wkwcpos_invoice_after_footer_details_block
```

También existen:

```text
wkwcpos_modify_invoice_modifications_data
wkwcpos_want_to_print_reciept
wkwcpos_perform_action_after_invoice_print
```

### Consecuencia arquitectónica

El MVP puede implementarse sin editar Webkul:

- PHP añade al objeto del pedido un bloque fiscal con namespace propio mediante los filtros de respuesta de pedidos de Webkul.
- Un script del addon usa `wp.hooks.addFilter()` para insertar el HTML fiscal antes del pie del ticket.
- El bloque usa `qr_src` como URL HTTPS/HTTP normal, junto con número, cotejo y leyenda.
- El filtro recomendado para la primera prueba es `wkwcpos_invoice_after_cashier_note_block` o `wkwcpos_invoice_after_footer_details_block`.

Queda pendiente comprobar qué filtro PHP de respuesta de pedido cubre tanto venta inicial como historial/reimpresión y verificar que el script del addon carga antes de construir el ticket.

## 11. Investigación F0 — contratos reales de Webkul

### Endpoints localizados

Webkul registra estos endpoints en namespace `pos/v1`:

```text
POST /wp-json/pos/v1/create-order
POST /wp-json/pos/v1/get-orders
POST /wp-json/pos/v1/get-invoice-template
```

El pedido de venta inicial termina en `WKWCPOS_API_Create_Order` y devuelve:

```php
wkwcpos_modify_order_details_response_at_pos
```

Firma observada:

```php
apply_filters(
    'wkwcpos_modify_order_details_response_at_pos',
    $order_detail_by_order_id,
    $order,
    $user_id
);
```

La respuesta ya contiene el pedido normalizado, estado, cliente, productos, totales, impuestos, descuentos, pagos y reembolsos.

El historial/reimpresión termina en `WKWCPOS_API_Get_Orders` y devuelve:

```php
wkwcpos_modify_get_orders_api_response
```

Firma observada:

```php
apply_filters(
    'wkwcpos_modify_get_orders_api_response',
    $order_detail_by_order_id,
    $pos_user,
    $outlet_id,
    $request
);
```

Antes de esa respuesta, cada pedido puede pasar por `manage_custom_order_type_support`, pero ese filtro no es el contrato general recomendado para el addon.

### Decisión de integración PHP

El addon debe centralizar el enriquecimiento fiscal en un servicio común y conectarlo a ambos filtros:

```text
venta inicial       → wkwcpos_modify_order_details_response_at_pos
historial/reimpresión → wkwcpos_modify_get_orders_api_response
```

El bloque añadido tendrá namespace propio, por ejemplo:

```php
$order_data['vfwoo_webkul_bridge']['fiscal'] = array(
    'available'        => true,
    'invoice_number'   => 'MACROPUS-2026-0027',
    'invoice_type'     => 'F2',
    'issued_at'        => '18-09-2026',
    'verification_url' => '...',
    'qr_src'           => '...',
    'legal_legend'     => '...',
);
```

El nombre exacto del campo y la forma final del objeto quedan encapsulados en el adaptador del addon, no se deben repartir por el código.

### Carga JavaScript confirmada

Webkul carga su aplicación principal con:

```php
wp_enqueue_script( 'wk-wc-pos-script', ... 'assets/dist/app/index.js', $dependencies, ... );
```

Las dependencias incluyen `wp-hooks`. También permite un script personalizado desde:

```text
wp-content/uploads/pos-script/script.js
```

Para el addon, la vía mantenible será encolar un script propio solo en la pantalla POS, con `wp-hooks` como dependencia, y registrar filtros como:

```js
wp.hooks.addFilter(
    'wkwcpos_invoice_after_footer_details_block',
    'vfwoo-webkul-pos-bridge',
    renderFiscalBlock
);
```

No se debe editar `assets/dist/app/index.js`.

### Forma del ticket y momento de los filtros

`getOrderInvoice()` recibe la plantilla y el objeto `order`, sustituye variables (`${order_id}`, `${order_products_data}`, `${order_total}`, etc.) y después llama a `addFiltersToInvoice()`.

Los filtros JavaScript de bloques reciben el HTML actual y el objeto `order`. El filtro recomendado para el primer prototipo es:

```text
wkwcpos_invoice_after_footer_details_block
```

Alternativa si se quiere mostrar el QR antes del pie:

```text
wkwcpos_invoice_after_cashier_note_block
```

El HTML resultante se evalúa y se imprime en el `iframe` `orderReceiptFrame`.

### F0 — resultado técnico

La investigación de código confirma que el MVP puede implementarse sin tocar Webkul:

1. PHP añade datos fiscales normalizados a las dos respuestas de pedido.
2. JavaScript propio registra un filtro `wp.hooks` de factura.
3. El filtro añade QR por URL y datos fiscales al HTML.
4. Webkul imprime el resultado dentro de su flujo normal.

Pendiente antes de crear el plugin: hacer una prueba mínima en el navegador que demuestre que el objeto `order` enriquecido está disponible en ambos caminos y que el script propio se carga antes de pulsar imprimir.
