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

## D-013 — NIF del cliente en el POS
Campo obligatorio DNI/NIE/CIF en el formulario de cliente; se valida al guardar (formato + censo AEAT para NIF españoles) y se copia al pedido para que VFWoo emita F1. Cliente por defecto y clientes sin NIF: F2. Sin exención de IVA desde el POS. Detalle en `plan-cliente-fiscal.md`. Pendiente de QA.

## D-014 — Antispam en el alta de clientes del POS
El plugin WP Armour Extended (`wpa_woocommerce_register_validation` en `woocommerce_registration_errors`) marca como spam cualquier alta sin su campo oculto de formulario web, y eso rompía la creación de clientes desde el POS («Spamming or your Javascript is disabled !!»). El bridge quita ese filtro solo durante el guardado de un cliente del POS, que ya exige cajero autenticado en Webkul. En la web sigue activo.

## D-015 — Pantalla «Marcas» en el POS
Pantalla propia con menú (`wkwcpos_menus_list`) y ruta añadida a la lista de páginas de Webkul (`wkwcpos_pages_list`, que pinta cada página dentro de su layout con el menú; los ganchos `wkwcpos_show_custom_pages_component`/`render_custom_pages_component` no sirven para esto: un `/pos/marcas` sin ruta da su 404), construida con DOM nativo (sin hooks de React) porque el bundle de Webkul trae su propia copia de React. Datos por `POST vfwoo-webkul/v1/brand-report` (solo lectura), autenticado con `WKWCPOS_API_Authentication` y la cabecera `authkey` del POS. Solo pedidos con `_wk_wc_pos_outlet`, estados completed y processing, máximo 1 año. Marca actual del producto (`product_brand`); varias marcas cuentan en cada una. Confirmado en local.

## D-016 — Variables de VFWoo en el editor de plantillas del ticket
- El editor (`POS → Invoice Templates`) lista las variables con el filtro `wkwcpos_add_new_invoice_variables`; el bridge añade las `${vfwoo_*}` (tienda, factura, cliente). Script `assets/js/invoice-editor.js`, cargado cuando `?page=wc-pos-invoice-templates` (no por el id de pantalla, que lleva el título traducible del menú de Webkul).
- Al imprimir, Webkul evalúa la plantilla como *template literal* con sus propias variables. El bridge sustituye las suyas antes, en `wkwcpos_summary_modify_invoice_data`. Todo texto insertado se escapa (`\`, comilla invertida y `${`) para que un dato no rompa ni inyecte código en esa evaluación.
- Datos de tienda: shortcode público `[verifacwoo_config return="..."]` de VFWoo (nombre, NIF, dirección, teléfono, email, logo).
- Datos de cliente solo en F1 y F3 (`Invoice_Variables::CUSTOMER_TYPES`, ampliable a rectificativas). Sin factura aún: pedido con NIF se trata como F1. En F2 se vacían también `${customer_fname}`, `${customer_lname}` y `${customer_phone}` de Webkul.
- Si la plantilla usa alguna `${vfwoo_*}`, se elimina el bloque fiscal automático para no repetir el QR. Sin ellas, todo sigue como antes.
- El propio editor de plantillas también evalúa la plantilla guardada (`eval` de un template literal) y se quedaba en blanco con una `${vfwoo_*}` desconocida. `invoice-editor.js` la escapa antes de que el editor la evalúe (`DOMContentLoaded`, registrado antes que el del editor) para que se vea como texto, igual que las de Webkul, y se guarde sin cambios.
- Riesgo: con una variable `${vfwoo_*}` en la plantilla y el bridge desactivado, la impresión falla (Webkul no la conoce).
- El domicilio del cliente sale de su perfil (`WC_Customer`), no de la dirección del pedido, que Webkul rellena con la del local. Los campos de domicilio del formulario del POS siguen siendo opcionales.

## D-017 — Parar la venta antes de cobrar si hará falta un cliente con NIF
- Regla (misma que aplica VFWoo al elegir F1/F2): con las simplificadas activas, una venta con total **mayor o igual** al límite (400 €, o 3.000 € en sector autorizado) exige cliente con NIF; con las simplificadas desactivadas, toda venta lo exige. Límite y ajuste se leen de VFWoo (`NIF_Config::settings()`, `NIF_Rules::simplified_limit()`).
- Dos puntos de bloqueo en el navegador, con el estado real del POS (`window.posStore`: total `cart.total.cart_total`, cliente `customers.default[0]`): botón Pay del carrito (`wkwcpos_allow_pay_btn_add_product_in_cart` devuelve `false`) y botón final de la pantalla de pago (`wkwcpos_stop_execution_payment_order` devuelve `true`, con mensaje en la acción `wkwcpos_stop_order_execution_text`; cubre entrar a `/pay` directamente). Mensaje con `window.posToast`.
- Cliente por defecto (marca `vfwoo_webkul_is_default`, que ahora el bridge envía junto al NIF) o cliente sin NIF, por debajo del límite: se vende como F2, sin error. Cliente con NIF «sin verificar»: aviso, no bloquea.
- Cliente guardado en la caché del POS antes de que el bridge enviara el NIF (`vfwoo_webkul_nif` sin definir): no se puede saber, no se bloquea. Se corrige con Resync de clientes.
- **Sin protección de servidor** de momento: el total exacto solo se conoce en el cliente. Los pagos que llegan al servidor incluyen el efectivo entregado (`cash_pay` = 115 € en una venta de 103,99 €), así que compararlos con el límite bloquearía ventas legítimas. Una excepción lanzada tras crear el pedido (`wkwcpos_after_creating_order`) dejaría el POS con el cargando puesto. Pendiente: aviso posterior a la venta con `Emission_Check::comprobar` como red para lo que se cuele (atajo de teclado no cubierto, ventas offline sincronizadas).

## D-018 — F3 (factura sustitutiva) desde el POS
- Botón «Factura completa (F3)» tras «Imprimir factura» en el detalle del pedido (`wkwcpos_add_after_print_invoice_button`), solo si el pedido se vendió como F2 (`fiscal.invoice_type`).
- Al abrir la ventana se consulta `POST vfwoo-webkul/v1/f3-status`: solo se puede emitir con la F2 **confirmada** (`ROK`); pendiente ofrece «Actualizar estado» (relee la base de datos de VFWoo, no llama a la AEAT).
- El cliente se **busca** con `get-customers-search` o se **crea** con `create-customer` (mismos servicios del POS; la validación de NIF de D-013 se aplica igual). No se usa la pantalla nativa de Clientes porque cambia el cliente de la venta en curso y la siguiente venta anónima saldría a su nombre. Un cliente sin NIF o el cliente por defecto no se pueden elegir.
- Emisión: `POST vfwoo-webkul/v1/issue-f3` (sesión del POS, `Pos_Auth`) llama a `\VFWoo\Helpers\Invoice_Helper::emitir_sustitutiva`, que exige F2 en ROK, comprueba el censo y actualiza el pedido. Cualquier cajero autenticado puede emitirla.
- Se guarda `_vfwoo_webkul_f3_customer` en el pedido para poder imprimir su domicilio y contacto; el ticket F3 usa el comprador que VFWoo guarda en la facturación del pedido, no el cliente mostrador.
- `Pos_Auth` centraliza la autenticación de los endpoints del bridge (también usada por «Marcas»).
- `emitir_sustitutiva` es pública pero no es contrato documentado de VFWoo: pedirle un método oficial (otro plugin).
- Devoluciones: por WooCommerce; VFWoo genera la rectificativa.
- **Ajustes tras la primera prueba:** (1) el tema oscuro del POS pintaba de blanco el texto de botones y campos de la ventana: los colores se fijan explícitamente. (1c) Tras emitir, el botón se **oculta** con `display:none` en vez de eliminarse: borrar a mano un nodo del árbol de React de Webkul rompía el siguiente render y todos los pedidos mostraban «Algo salió mal» hasta hacer Resync de pedidos. Regla: el bridge nunca elimina nodos que crea vía `wp.element` dentro de la aplicación de Webkul. (1b) El botón del detalle usa las clases del propio POS (`pos-order-invoice` + `primary`, como «Imprimir Invoice»), no estilos propios, para que se vea igual en cualquier tema. (2) Webkul imprime desde el mismo objeto de pedido que pinta el detalle, así que `issue-f3` devuelve el bloque fiscal y de cliente ya actualizado (`Order_Integration::bridge_block`) y la ventana lo asigna al pedido: el ticket sale con la F3 sin recargar. (3) Mientras la factura no está confirmada, la ruta REST del QR se niega a servir la imagen (`Cotejo_Url::for_order` sin `temp`); con la F3 recién enviada, el QR del ticket fallaba. Ahora, si está pendiente, se incrusta la imagen y `qr_src` lleva `temp=1`.

## D-008 — Taxonomías configurables (implementado y confirmado en local)
Opción `vfwoo_webkul_filter_taxonomies`. Lista de taxonomías registradas para `product` (incluye las de CPT/plugins propios) con `show_ui`. Lista elegible en la pestaña `VFWoo Bridge`. Por defecto solo `product_brand`. Excluir `product_cat`, `pa_*` y taxonomías internas de WooCommerce.
