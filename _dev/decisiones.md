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

## D-009 — Recarga del catálogo: aviso, no borrado
Versión de catálogo en servidor, sellada en cada producto; el POS avisa al cajero de usar Resync → Products. No se automatiza `Resync All` (destruye ventas offline, carritos aparcados y cajón). Recarga automática de `pos_products` sin probar.

## D-010 — Estilos en el POS
Webkul desregistra en la pantalla POS todo estilo fuera de su lista blanca. Los estilos del bridge se permiten con el filtro `wkwcpos_add_custom_css`.

## D-011 — Aviso de catálogo sin recargar el POS
Ruta REST pública `GET /wp-json/vfwoo-webkul/v1/catalog-version` (solo devuelve un entero, `Cache-Control: no-store`). El script POS la consulta: 2 s tras abrir, al terminar una venta (`wkwcpos_modify_order_success_popup`), al cambiar de pantalla (`pushState`/`popstate`/`hashchange`) y cada 5 min; mínimo 60 s entre consultas; fallo silencioso offline. El aviso se quita solo cuando el catálogo en caché alcanza la versión del servidor (tras Resync → Products). Pendiente de QA; posible caché del service worker de Webkul sin confirmar.

## D-012 — QR incrustado en el ticket
Webkul imprime 500 ms después de montar el ticket; la ruta REST del QR tarda 0,7–1,3 s en local, así que la primera impresión salía con la imagen rota (la reimpresión funcionaba por la caché del navegador). El bridge incrusta el QR como PNG en base64 (`qr_data_uri`) en las respuestas de un solo pedido (venta inicial y reimpresión), usando `VFWoo\QR::png_url` con `qr_payload`. Es una clase interna de VFWoo no documentada como contrato: si falta o falla, se usa `qr_src` (URL). No se calcula en el listado del historial para no engordar la respuesta.
El ticket ya no muestra la URL de cotejo en texto; sigue en los datos (`verification_url`).

## D-008 — Taxonomías configurables (implementado y confirmado en local)
Opción `vfwoo_webkul_filter_taxonomies`. Lista de taxonomías registradas para `product` (incluye las de CPT/plugins propios) con `show_ui`. Lista elegible en la pestaña `VFWoo Bridge`. Por defecto solo `product_brand`. Excluir `product_cat`, `pa_*` y taxonomías internas de WooCommerce.
