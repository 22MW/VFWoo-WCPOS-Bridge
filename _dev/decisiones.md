# Decisiones

## D-001 — VFWoo es la única autoridad fiscal
El bridge solo lee `Ticket_Fiscal_Data::for_order( $order, true )`. No emite, numera ni habla con Hacienda.

## D-002 — Sin editar Webkul
Integración solo por filtros PHP y `wp.hooks`. No se toca `assets/dist/app/index.js`.

## D-003 — Ticket: filtros y punto de inserción
- PHP: `wkwcpos_modify_order_details_response_at_pos` (venta) y `wkwcpos_modify_get_orders_api_response` (historial/reimpresión).
- JS: `wkwcpos_invoice_after_footer_details_block`, cargado con `wkwcpos_enqueue_pos_scripts`.
- Estado `processing` admitido vía `vfwoo_ticket_estados_validos`.
- Sin instalador de plantillas: Webkul conserva su editor nativo.

## D-004 — BRANDS entra por `product.attributes` (validado en local)
Evidencia (bundle Webkul 7.1.1): el panel de filtros construye sus secciones con `buildAttributeFilterMap` a partir de `product.attributes` (`{slug, name, options}`), guarda la selección en `selectedAttributeFilters` y aplica OR dentro de una clave y AND entre claves. Los demás usos de `.attributes` en el bundle son del DOM, no de producto.
Estado: aplicado en `class-catalog-integration.php` y confirmado por el usuario en local: el filtro aparece y funciona.
Decisión: añadir en PHP una entrada `BRANDS` a `attributes` con los términos `product_brand`, en lugar de un componente React propio. Si la validación falla (carrito, variaciones), se usa la alternativa con `wkwcpos_modify_homepage_products`.

## D-005 — `wkwcpos_show_menu_filter` no sirve para inyectar UI
Recibe `(true, componente)` y solo decide si se muestra el menú. No es un punto de extensión del panel de filtros.

## D-006 — Fallo seguro
Si falta el dato de marca (caché antigua) o cambia el contrato, no se oculta ningún producto; se desactiva solo `BRANDS`.

## D-007 — Informes por marca: sin gancho de cliente conocido
Servidor: `wkwcpos_modify_report_single_order_data` y `wkwcpos_modify_report_search_result`. No se ha encontrado gancho para añadir opciones a `Filter By`. Decisión aplazada: inyección propia, informe propio del bridge o pedir gancho a Webkul.

## D-008 — Taxonomías configurables (implementado y confirmado en local)
Opción `vfwoo_webkul_filter_taxonomies`. Lista de taxonomías registradas para `product` (incluye las de CPT/plugins propios) con `show_ui`. Lista elegible en la pestaña `VFWoo Bridge`. Por defecto solo `product_brand`. Excluir `product_cat`, `pa_*` y taxonomías internas de WooCommerce.
