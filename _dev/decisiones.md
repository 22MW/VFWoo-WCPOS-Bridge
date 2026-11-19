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
Campo obligatorio DNI/NIE/CIF en el formulario de cliente; se valida al guardar (formato + censo AEAT para NIF españoles) y se copia al pedido para que VFWoo emita F1. Cliente por defecto y clientes sin NIF: F2. Sin exención de IVA desde el POS. Detalle en `temp/plan-cliente-fiscal.md`. Pendiente de QA.

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
- **Ajustes tras la primera prueba:** (1) el tema oscuro del POS pintaba de blanco el texto de botones y campos de la ventana; primero se fijaron colores propios y después se sustituyó por las variables del POS (ver D-020). (1c) Tras emitir, el botón se **oculta** con `display:none` en vez de eliminarse: borrar a mano un nodo del árbol de React de Webkul rompía el siguiente render y todos los pedidos mostraban «Algo salió mal» hasta hacer Resync de pedidos. Regla: el bridge nunca elimina nodos que crea vía `wp.element` dentro de la aplicación de Webkul. (1b) El botón del detalle usa las clases del propio POS (`pos-order-invoice` + `primary`, como «Imprimir Invoice»), no estilos propios, para que se vea igual en cualquier tema. (2) Webkul imprime desde el mismo objeto de pedido que pinta el detalle, así que `issue-f3` devuelve el bloque fiscal y de cliente ya actualizado (`Order_Integration::bridge_block`) y la ventana lo asigna al pedido: el ticket sale con la F3 sin recargar. (3) Mientras la factura no está confirmada, la ruta REST del QR se niega a servir la imagen (`Cotejo_Url::for_order` sin `temp`); con la F3 recién enviada, el QR del ticket fallaba. Ahora, si está pendiente, se incrusta la imagen y `qr_src` lleva `temp=1`.

## D-019 — Sistema de release
- Rama `pos-release` (sin `_dev`) en el remoto compartido, tag y release `pos-v<versión>`, ZIP `vfwoo-webkul-pos-bridge.zip`. Script `_dev/deploy-release.sh` (`--dry-run`, `--branch-only`).
- Distinto del script de ai-knowledge: no se fusiona en `main` (es de otro plugin), la rama se prepara en un `git worktree` para no borrar `_dev/.env`, y el tag lleva prefijo porque «última release» es de todo el repositorio.
- Actualizador `Github_Updater`: filtra por prefijo, ignora borradores y prereleases, solo acepta paquetes de `github.com/22MW/VFWoo-WCPOS-Bridge/`, caché de una hora.
- Seguridad: el actualizador **no se activa en una copia con `.git`** (una actualización de WordPress reemplaza la carpeta entera y borraría el historial y `_dev/` de la copia de desarrollo). `VFWOO_WEBKUL_ALLOW_DEV_UPDATES` lo fuerza.
- Detalle en `proceso-release.md`.

## D-020 — Estilos: primero los del POS, en tema claro y oscuro
- **Regla:** todo lo que el bridge pinte dentro del POS usa **primero los estilos del propio POS**, y debe verse bien en el tema claro y en el oscuro. Los colores fijos son la última opción.
- Cómo: variables que Webkul define según el tema (`--primary` y `--secondary` para superficies, `--fixed` para la superficie opaca, `--text-color` y `--text-color-light` para el texto, `--primary-accent` para el naranja) y sus clases (`primary` para acciones, `pos-order-invoice` para botones del detalle, el estilo de tabla con bordes de acento).
- Por qué: la pantalla «Marcas» y la ventana F3 se veían mal en oscuro (botones y etiquetas blancos sobre fondo claro) porque fijé colores a mano; el POS cambia su paleta según el tema.
- Excepciones documentadas: el aviso amarillo de catálogo (color de advertencia, con su texto oscuro propio), el velo translúcido detrás de la ventana y el rojo de error.
- Antes de dar una pantalla por buena, mirarla en los dos temas.
- **Confirmado en local** en «Marcas» y en la ventana F3, en tema claro y oscuro.
- **Ampliación (2026-11-19):** las pantallas se construyen con el **HTML y las clases del propio POS**; el CSS propio solo es de disposición. La referencia completa (variables, globales, esqueleto, lista, paginación, ventana, formularios, interruptor, preloader, panel de detalle y cómo inspeccionar) está en `_dev/estilos-pos.md` y se consulta **antes** de escribir una pantalla. «Productos» ya está migrada; «Marcas» queda pendiente.

## D-021 — Orden de `_dev/`
- En `_dev/` solo van los archivos que el plugin y el sistema usan: la memoria (`contexto-activo.md`, `decisiones.md`, `roadmap.md`, `release-notes.md`), los scripts (`deploy-release.sh`), el `.env`, la documentación del proceso de release y la guía de funcionamiento.
- Los **planes, investigaciones, ideas e informes** van en `_dev/temp/` (`plan-*.md`, `precheck-y-plan.md`, `resumen-investigacion.md`, referencias técnicas e informes a terceros).
- Los documentos de memoria enlazan a los de `temp/` con su ruta.

## D-022 — «Marcas» como una categoría más en la barra del inicio del POS
- La barra de categorías del POS se pinta desde el árbol `categories.list` (nodos `{name, cat_id, child, thumbnail}`); al pulsar una categoría con hijas muestra las hijas como subcategorías y navega a `/category/{cat_id}`, y los productos se filtran en el navegador con `product.categories.includes(cat_id)`.
- El bridge añade un nodo **«Marcas»** al principio de esa lista, con las marcas (`product_brand`) como hijas. Al pulsar «Marcas» salen las marcas como subcategorías y todos los productos con marca; al pulsar una marca, los productos de esa marca. Funciona con la barra nueva y con la antigua.
- **Se hace en el navegador, no en el servidor.** El POS solo pide sus categorías al servidor cuando su tabla local está vacía (ni «Resync Products» las refresca), así que un cambio en PHP no llegaría sin un «Resync All», que borra ventas sin conexión. El bridge se suscribe a `window.posStore` y despacha `POS_CATEGORIES` con el nodo añadido cada vez que la lista se carga; no toca la base local. Sin bucles: no despacha si ya está el nodo con las mismas marcas.
- Los productos se filtran con `wkwcpos_modify_load_category_products` (solo para el nodo raíz y los identificadores de marca; las categorías reales no se tocan). Las marcas salen de `vfwoo_webkul_brands`, que ya viaja con cada producto (ahora con `thumbnail`, la imagen de la marca de WooCommerce).
- Identificadores: el nodo raíz usa `2000000000`; las marcas usan su `term_id`, que no choca con los de categorías (los `term_id` son únicos entre taxonomías).
- Requiere haber hecho Resync → Products tras instalar esta versión (para que los productos lleven `vfwoo_webkul_brands`, y `thumbnail` si se quieren imágenes). Si no hay productos con marca, el nodo no aparece.
- Solo `product_brand`; otras taxonomías siguen en el panel de filtros.
- **Confirmado en local:** funciona perfectamente (marcas como subcategorías, productos por marca e imágenes).

## D-023 — Límite de simplificada propio del POS: filtro en VFWoo
- El bridge no puede forzar F2 por encima del límite, y VFWoo decide el tipo en un único sitio; el límite propio del POS exige que **VFWoo lo aplique**. Se resuelve con un filtro `vfwoo_simplified_limit( $limite, $pedido )` en `Invoice_Type_Calculator::calculate_from_order` (no en el checkout web), que solo acepta 400 o 3.000. Lo implementa el usuario en VFWoo; el bridge lo usa después (ajuste en Settings, solo pedidos del POS, versión mínima de VFWoo).
- Regla de trabajo entre plugins: un cambio en VFWoo se prepara como prompt en `_dev/temp/` y lo aplica el usuario en el workspace de VFWoo; el bridge espera a su confirmación y a la versión resultante.
- Detalle en `temp/plan-limite-pos.md`.

## D-024 — Plantilla de ticket propia, aplicada en el JS y con casilla
- Casilla «Usar la plantilla de ticket del bridge» (`vfwoo_webkul_own_template`, desactivada por defecto, requiere VFWoo). Activada, el ticket **se fuerza** y se ignoran las plantillas de Webkul; desactivada, siguen valiendo las variables `${vfwoo_*}` en el editor (D-016).
- **Se aplica en el JS** (`fillInvoiceVariables`), no con el filtro PHP `wkwcpos_modify_invoice_template_at_pos`: el POS guarda la plantilla en IndexedDB (`pos_invoice`) y solo la pide al servidor si no la tiene, así que un filtro PHP no se notaría hasta un Resync en cada caja. El servidor pasa la plantilla en `vfwooWebkulBridge.ownTemplate`.
- La plantilla (`Own_Template`) repite la estructura y las clases de la de Webkul para que líneas y totales salgan igual; el CSS va dentro, como en la de Webkul (el ticket se pinta en un contexto de impresión sin las hojas del POS).
- **NIF de la tienda = `seller_nif`** (`Settings_Compat::get_seller_nif()`), el mismo que el PDF; el shortcode `[verifacwoo_config]` solo devuelve `company_nif`. Respaldo: el shortcode, si VFWoo no ofrece el método.
- No hay variable de ancho de logo: el ancho del PDF (px de A4) no se traslada a un rollo. El logo del ticket mide como máximo el 50 % del ancho del ticket (`max-width:50%` en `${vfwoo_company_logo}`); se adapta al rollo y no se amplía si es menor. Va con `!important` en el estilo en línea: con el rollo térmico (T88V) Webkul añade al imprimir `.wkwcpos-invoice-wrapper * { max-width:100% !important; }` (`fitCss`) y un `max-width` normal no gana.
- «Datos adicionales de empresa» (`company_extra`) no se incluye: VFWoo no lo expone.

## D-026 — El bridge detecta por capacidad, no por plugin de VFWoo
- VFWoo se divide en un **core** gratuito (`vfwoo-invoices`: numeración, PDF, NIF con formato, correos) y un **complemento Verifactu** (`vfwoo-verifactu`: QR, tipo F1/F2/F3, censo AEAT, reglas de simplificada, emisión de F3). El plugin de un solo bloque (`vfwoo` 0.9.x) sigue existiendo en algunos sitios.
- El bridge «solo está pendiente del POS»: `Capabilities` pregunta por las piezas (`invoices`, `verifactu`, `census`, `nif_format`, `f3_emitter`) y cada función se enciende cuando existe la suya. Los módulos se registran en `init` (prio 1), porque los plugins de facturas cargan sus clases en `plugins_loaded`. Cada llamada a VFWoo usa `class_exists` + `method_exists` y prueba primero la clase nueva y después la antigua (`Invoice_Helper_Verifactu` → `Invoice_Helper`).
- **Con el core:** número y fecha del ticket (`Fiscal_Provider::from_invoice`, de `Invoice_Helper::get_main_invoice_by_order_id`), plantilla propia, «Factura PDF», PDF en el correo (sin `Channel_Registry`, que el 0.9.17 no tiene), NIF con formato opcional. **Solo con Verifactu:** QR/leyenda/tipo, NIF obligatorio, límite, censo, F3.
- **Regla de venta fija del POS: 3.000 €** (`Capabilities::SIMPLIFIED_LIMIT`, «sector autorizado» activado), solo con Verifactu y sin leer los ajustes del plugin de facturas. Sustituye a la idea de D-023 de leer el límite de VFWoo.
- **Atención:** el bridge decide cuándo exige NIF, pero el **tipo de factura** de una venta sin NIF entre 400 € y 3.000 € lo decide Verifactu con sus propios ajustes: «Sector autorizado» debe estar activado en Verifactu (o aplicar el filtro `vfwoo_simplified_limit` previsto en D-023) para que ambos coincidan.
- Sin Verifactu el NIF es opcional, no hay censo y el estado «sin verificar» no se muestra (`unchecked`).
- «Convertir el ticket en factura completa» con el core solo: decidido en D-027.

- **El módulo Verifactu manda, no el plugin (corrección del usuario):** `Capabilities::verifactu()` exige, además de las clases del complemento (`Ticket_Fiscal_Data`, que carga aunque el módulo esté apagado), que `\VFWoo\Modules::is_active('verifactu')` sea verdadero. El complemento solo carga su bootstrap con el módulo activo, y su censo se engancha entonces al filtro `vfwoo_nif_census_validate`, así que el censo y la F3 también exigen el módulo. Sin la clase `Modules` (VFWoo de un solo plugin) basta con que esté instalado. `Capabilities::verifactu_installed()` distingue «instalado» de «en uso» para la pantalla de ajustes.

## D-025 — PDF de VFWoo en el POS: correo adjunto e impresión
- **Correo:** el botón «Send order email» de Webkul dispara el correo nativo de WooCommerce del estado del pedido (`wkwc_send_custom_emails`). VFWoo no lo cubre: en el canal VERI*FACTU `attaches_to_wc_emails()` es `false` (manda su propio correo con PDF tras la aceptación de la AEAT, `Post_Emission_Mailer`) y en el local adjunta **una sola vez** por destinatario (`_vfwoo_pdf_emailed_customer`), así que el reenvío desde el POS salía sin PDF. El bridge lo marca con el filtro `wkwcpos_modify_send_mail_id` (solo para saber el pedido) y adjunta **siempre** en `woocommerce_email_attachments` (prio 120) el PDF de `VFWOO_PDF_Engine::generate_pdf`: solo al cliente, sin correos de reembolso, con número de factura y `Channel::is_printable()`. VFWoo escribe el PDF de un pedido en la misma ruta, así que si ya lo adjuntó no se duplica. Con `WP_DEBUG`, el motivo por el que no adjunta queda en el registro. Primera versión (error): se apartaba en el canal local dando por hecho que VFWoo adjuntaba. Casilla `vfwoo_webkul_email_pdf`, activada por defecto (el bridge no está en producción).
- **Imprimir:** botón «Factura PDF» (`wkwcpos_add_after_print_invoice_button`, namespace propio) con iframe oculto + `print()` a los 500 ms, igual que Webkul con el ticket (`openPrintWindow`); si falla, `window.open`. El enlace pasa por `admin-post.php?action=vfwoo_webkul_pdf` (nonce propio, `edit_shop_orders`), que redirige a `vfwoo_pdf_preview` con el nonce de VFWoo de **esa** sesión (ligado al navegador, no se puede generar desde la API del POS).
- El botón es un componente propio (`useState`) con estado de carga: spinner y «Preparando PDF…», desactivado hasta que empieza la impresión o pasan 60 s. VFWoo genera el PDF con dompdf en **cada** petición (`preview_pdf`; además `maybe_save_pdf` antes, que lo genera dos veces si `auto_save_pdf` está activo), por eso tarda más que el ticket, que ya está dibujado. Causa exacta del tiempo: sin medir (pendiente; opción de guardar el PDF generado por factura si es dompdf puro).
- Acoplamiento con piezas internas de VFWoo (`VFWOO_PDF_Engine`, `Invoice_Helper`, `Channel_Registry`) protegido con `class_exists`: si falta algo, no adjunta y el correo sale como antes.
- Pendiente de confirmar en el navegador de la caja: que `print()` sobre un PDF en iframe abre el diálogo (Chrome/Edge normalmente sí; Firefox y Safari pueden fallar).

## D-008 — Taxonomías configurables (implementado y confirmado en local)
Opción `vfwoo_webkul_filter_taxonomies`. Lista de taxonomías registradas para `product` (incluye las de CPT/plugins propios) con `show_ui`. Lista elegible en la pestaña `VFWoo Bridge`. Por defecto solo `product_brand`. Excluir `product_cat`, `pa_*` y taxonomías internas de WooCommerce.

## D-027 — «Factura completa» desde el ticket, con o sin Verifactu (sustituye al botón F3 de D-018)
- Petición del usuario: la función principal es dar factura a nombre del cliente (persona o empresa, con DNI/NIE/CIF o sin NIF) a quien ya se llevó el ticket; **F3 es solo la forma de registrarlo con Verifactu**, y no se nombra en el botón («Factura completa»).
- **Sin Verifactu (o sin enviar a la AEAT):** el ticket pasa a ser la factura completa **con el mismo número** (decisión del usuario). El bridge escribe en el pedido `_billing_nif`, nombre o empresa y domicilio del cliente (el PDF del core los lee en vivo: `Pdf_Document_Data`), vincula el cliente al pedido, guarda `_vfwoo_webkul_f3_customer` (valor conservado) y añade una nota con los datos anteriores. Un CIF o una empresa guardada van a `billing_company`; si hay contacto, queda como nombre. No hace falta nada del core: el PDF se regenera en cada petición.
- **Con Verifactu enviando (`Mode_Detector::is_basic()` falso):** F2 aceptada (`ROK`) → `emitir_sustitutiva` (F3); F2 sin enviar o pendiente → hay que esperar (cambiar el cliente cambiaría lo que Verifactu envía); F1/F3 registradas → no se cambian sin rectificar. Tras la F3 el bridge escribe también el domicilio (Verifactu ya escribe NIF y nombre; no lo hace con el domicilio).
- **El cliente se elige o se crea en el diálogo**, con los servicios del POS (`WK_CREATE_CUSTOMER_ENDPOINT`): así queda como cliente de WooCommerce para próximas compras, con su NIF y comprobación. Webkul pide nombre y email. El diálogo añade empresa (`billing_company`, guardada por `Customer_Integration`) y domicilio (campos nativos de Webkul). Sin Verifactu el NIF es opcional; con él es obligatorio.
- **Se permite cambiar de cliente** (decisión del usuario: «es una tienda, se pueden equivocar»), salvo con una factura ya registrada en la AEAT. El PDF anterior no se conserva: solo la nota del pedido.
- El ticket de un pedido convertido sale como **F1** (`Ticket_Invoice::mark_complete`) con los datos del cliente elegido.
- **Riesgo a confirmar con la gestoría:** mismo número que el ticket, sin factura nueva que lo sustituya.
- Rutas: `full-invoice-status` y `full-invoice-issue` (antes `f3-status`, `issue-f3`); clase `Ticket_Invoice` (antes `F3_Emission`).
- **País y comprobación del documento (decidido por el usuario):** el formulario de cliente del POS lleva país (España por defecto, `pos_customer_country`, campo nativo de Webkul) y una casilla «No comprobar el documento» (`pos_customer_nocheck`) que sale marcada si el país no es España. Servidor: sin la casilla (otros formularios), el documento se comprueba solo si el país es España o está vacío. España → `NIF_Format::is_valid_spanish` (DNI/NIE/CIF estricto) + censo con Verifactu; otro país con la casilla desmarcada → `NIF_Format::is_valid` (VAT genérico). Un documento extranjero no genera el aviso de «sin verificar». El país se escribe en el pedido.
- **Con Verifactu, la conversión a un cliente de otro país se bloquea** (`Ticket_Invoice`): la F3 solo envía nif y nombre y la copia del envío de la F2, sin país ni tipo de documento (`IDOtro`). Hasta que VFWoo lo soporte (ver roadmap) es mejor no enviar a la AEAT una F3 mal formada. Sin Verifactu no se bloquea.
- **Pasaporte y otros documentos:** se aparcan hasta que VFWoo soporte el tipo de documento (ver roadmap). Sin Verifactu el documento es texto libre y funciona hoy con la casilla «No comprobar».
- **Vocabulario de la caja (decidido por el usuario):** en la interfaz y la ayuda de la caja solo «**ticket**» (sin datos de cliente, F2) y «**factura**» (con cliente, F1/F3); no se usan «simplificada» ni «completa». Botones: **«Cambiar factura»** (convertir el ticket en factura; diálogo «Hacer factura») e **«Imprimir factura»** (antes «Factura PDF»). En `_dev`, el CHANGELOG histórico y la gestoría se mantiene «factura simplificada / completa». Aviso: junto al botón nativo de Webkul «Imprimir Invoice» (el ticket) queda «Imprimir factura» (el PDF); si se confunden, valorar «Imprimir factura (PDF)».

## D-028 — Pedidos reembolsados y cancelados; rectificativa imprimible
- **Webkul ya cambia el estado del pedido** (`update-order-status`: `$order->update_status()` + caja de efectivo + stock del catálogo), como WooCommerce. Confirmado por el usuario con #39212 → Reembolsado: reembolso total, total a 0, y VFWoo creó la rectificativa (`MAC-R-2026-0001`, visible en la caja «PDF Factura»). Webkul **no crea reembolsos parciales** (sin `wc_create_refund` ni interfaz; su readme habla de un «Return POS» aparte): se hacen en WooCommerce.
- **«Imprimir rectificativa»** (`Pdf_Actions::redirect_to_pdf` con `refund_id`): comprueba que el reembolso es del pedido y redirige a `vfwoo_pdf_preview` con `type=credit_note`, `order_id` = id del reembolso y su nonce. El JS lo pinta por cada `order.refunds[]` que Webkul ya envía.
- **«Cambiar factura» se bloquea** (`Ticket_Invoice::state`) en pedidos reembolsados (estado o `get_total_refunded() > 0`) y cancelados; fallidos y borradores no se pueden. El correo con PDF no adjunta la factura a correos de reembolso ni a pedidos cancelados o fallidos.
- **Cancelados:** solo Verifactu reacciona (`woocommerce_order_status_cancelled` → `cancel_invoices`, estado `CAN`, PDF marcado como anulado). **El core solo no hace nada:** la factura de un pedido cancelado sigue como válida y su PDF sale normal (ver roadmap).

## D-029 — Desplegable de estado del pedido: Completado, Anular, Devolver
- Petición del usuario: quitar Pendiente de pago, Procesando, En espera, Fallido y Borrador del desplegable del detalle del pedido y renombrar Cancelado → «Anular» y Reembolsado → «Devolver».
- Webkul pinta una opción por cada entrada de `apif_script.wc_order_statuses` (`wc_get_order_statuses()`), la misma lista con la que nombra el estado en la tabla de pedidos, y no ofrece filtro: recortarla dejaría los nombres en blanco. Por eso el JS ajusta las opciones **en el sitio** (`tidyStatusSelect`, con `MutationObserver` y al pulsar el desplegable): oculta con `hidden` (nunca las quita, porque el árbol es de React), deshabilita las que no se pueden elegir y cambia el texto de la opción por el verbo en el nodo de texto existente. **El estado actual del pedido siempre se ve con su nombre** (y no se puede elegir si no es uno de los tres), para que el control no enseñe un valor falso. Safari no respeta `hidden` en las opciones: ahí salen en gris.
- La pregunta de confirmación de Webkul (`jQuery.confirm`, contenido `<h4>…`) se sustituye, solo para Anular y Devolver, por un texto propio (con Verifactu menciona la anulación en la AEAT). Envoltorio defensivo: si algo falla, sale la pregunta de Webkul.
- **Servidor:** `Order_Status` filtra `rest_pre_dispatch` para `/pos/v1/update-order-status` y devuelve el fallo con la forma de Webkul si el estado no es `completed`, `cancelled` o `refunded` (el mismo estado que ya tiene el pedido pasa, sin efecto). El único llamador de ese endpoint en el POS es este desplegable.
- Pendiente de probar en `pos.local`: en el navegador del usuario, y en Safari.

## D-030 — Sistema de módulos del bridge (fase 0 del módulo «Productos»)
- **Cada función nueva va en su módulo:** carpeta `includes/modules/{id}/` con un `module.php` que devuelve el manifiesto (`label`, `description`, `default`, `requires`, `requires_label`, `bootstrap`, `script`, `style`, `config`). El registro `includes/core/class-module-registry.php` descubre los módulos, decide cuáles están activos (elección del administrador, o `default`, y requisitos cumplidos) y carga su PHP (`init`, prioridad 2) y sus assets en la caja (`wkwcpos_enqueue_pos_scripts`, prioridad 20, y lista blanca de estilos de Webkul).
- **Seguridad:** un manifiesto no puede cargar PHP, JS ni CSS fuera de su carpeta (`realpath`). Opción `vfwoo_webkul_modules` (id → `1`/`0`), guardada desde un bloque «Módulos» de la página del bridge (`manage_woocommerce` + nonce). Sin módulos, el bloque no se muestra.
- **`assets/js/core.js`** carga antes que `pos-bridge.js` y que cualquier módulo y ofrece `window.vfwooBridge`: `escapeHtml`, `escapeAttribute`, `node`, `posUrl`, `posPost` (con reintento de sesión de Webkul), `moduleConfig(id)` y `addScreen({id, path, title, iconPaths, build})` (menú + ruta con el mismo patrón que «Marcas»). La configuración `window.vfwooWebkulBridge` cuelga ahora del handle de `core.js` (mismo orden que antes).
- **Lo existente no se ha movido:** `pos-bridge.js` solo usa los ayudantes de `core.js` en lugar de sus copias. «Marcas» y el resto siguen en él y se migrarán uno a uno cuando se toquen por otra razón.
- Decisiones del módulo «Productos» (usuario, 2026-11-19): variables en la primera versión pero solo actualizando lo existente (no crea variaciones, categorías, etiquetas ni marcas); fotos después si dan problemas; edita el cajero con modo de gestor de tienda; sustituye al acceso «Productos» de la cabecera; primero la estructura, luego el módulo. Detalle en `temp/plan-modulo-productos.md`.

## D-031 — Módulo «Productos»: lo que hace, lo que no y por qué
- **Qué es «se ve»:** *En la tienda* = producto publicado y `catalog_visibility` distinto de `hidden` (el interruptor solo alterna `visible` ↔ `hidden` y no se puede tocar en borradores, privados o pendientes). *En la caja* = fila `enabled` en la tienda (outlet) del usuario, que es lo que lee `get_pos_user_outlet_products` (Webkul). Se escribe con el helper de Webkul (`save_pos_product_status` / `update_pos_product_status`), nunca con SQL propio. Con stock **no** centralizado la caja exige además `pos_stock > 0`: en v1 el módulo solo edita stock con stock centralizado (`wk_wc_pos_enable_centralized_inventory`).
- **Permisos (servidor):** sesión del POS (`Pos_Auth`) + `edit_products` del usuario de la caja + `edit_post` por producto en cada escritura; crear exige `publish_products`; quien no tiene `edit_others_products` solo lista los suyos (marketplace). El menú solo se registra si el usuario puede editar productos. Un producto de otro idioma (WPML) no sale en la lista: lo filtra WPML.
- **Solo actualizar lo que existe:** no crea variaciones, categorías, etiquetas ni marcas. Las variaciones se editan solo si pertenecen al producto. Excepción pedida: el alta rápida crea un producto **simple** (nombre, precio, cantidad, GTIN, SKU), oculto en la tienda por defecto y activado en la caja del usuario.
- **SKU y GTIN solo se validan si cambian:** WooCommerce rechaza cualquier guardado de un producto cuyo SKU ya está repetido (por ejemplo, las traducciones WPML que comparten SKU); solo se llama a `set_sku` / `set_global_unique_id` si el valor cambió.
- **El producto antiguo «Productos» de la cabecera** lo quita el módulo con el filtro `vfwoo_webkul_shortcuts` (nuevo, en `Admin_Shortcuts::items()`); con el módulo desactivado vuelve.
- **Fuera de v1:** fotos, categorías, marcas, descripciones, productos agrupados o externos, stock con Master Stock, ajuste de stock con nota.

## D-032 — Panel lateral, fotos, clasificación y componentes compartidos
- **Edición en panel lateral** (pedido del usuario, 2026-11-19): al pulsar un producto se edita a la derecha, con la estructura del detalle de Pedidos (`ui.detail()`); la ventana emergente desaparece. «Nuevo producto» usa el mismo panel y, al crear, abre el producto creado.
- **Fotos** (`POST /products/image`, multipart con `FormData` y la sesión del POS): `add_main`, `add_gallery`, `make_main`, `remove`. Exige `edit_post` del producto y `upload_files`; solo JPG, PNG, GIF y WebP (`wp_check_filetype_and_ext`) y límite de `wp_max_upload_size()`; el autor del adjunto es el cajero (`wp_set_current_user` durante la petición); el navegador reduce la foto a 1600 px y JPEG antes de subirla. **Quitar solo desvincula**: nunca borra de la biblioteca. Las fotos de variaciones quedan fuera.
- **Clasificación**: categorías, etiquetas y marcas (si existe `product_brand`) con **ids de términos que ya existen** (`term_exists`; se guardan con `wp_set_object_terms` y ids enteros, así WordPress no puede crear ninguno) y solo si el usuario tiene `assign_terms` de esa taxonomía.
- **Componentes compartidos** en `core.js` (`window.vfwooBridge.ui`) + `ui.css`: ver `estilos-pos.md` §13. La rejilla de dos columnas del POS cuelga del **id `pos-order-tab`**.
- **Paginación solo abajo** (pedido del usuario): el total y «página X de N» van en la misma línea que los números.
- **«Marcas»** rehecha con estos componentes y sin botón «Volver».

## D-033 — Regla de oro: usar el sistema de diseño de Webkul, mínimo código y preparado para updates
- **Decisión del usuario (2026-11-19):** el bridge no inventa formularios, botones, desplegables, tablas, ventanas ni estilos. Usa **las clases, el HTML y las variables de color de Webkul** (el administrador elige colores, modo claro/oscuro y plantillas en *Ajustes › Apariencia* y todo debe adaptarse). **No** se usan los componentes de WordPress (`wp.components`): no siguen esos ajustes. Referencia: `estilos-pos.md` §0.
- **Modelos:** formulario → Clientes «Editar cliente»; lista con detalle → Pedidos; resumen → Pedidos o Informe.
- **Preparado para updates:** el acoplamiento a las clases de Webkul vive en `core.js` y en `estilos-pos.md`; si algo falta, la pantalla se ve peor pero nunca rompe la caja; lista de comprobación tras cada update (incluye cambiar el color primario y comprobar que las pantallas lo siguen).
- **Estado:** el editor lateral de D-032 **no gustó** (feo y poco práctico, no copiaba ninguna pantalla de Webkul); se rehace copiando «Editar cliente». El servidor de fotos y clasificación (D-032) se mantiene.

## D-034 — Forma de construir pantallas: reutilizar sin preguntar, avisar y crear componentes una sola vez
- **Decisión del usuario (2026-11-19), tras varias discusiones:** todo lo que el bridge pinte en el POS **reutiliza lo que ya hay de Webkul sin preguntar** (clases, HTML, **ids**, variables), copiando la pantalla modelo entera. Si hace falta algo que Webkul no tiene: **avisar**, crearlo **una sola vez** como componente en `core.js` (`ui`), documentarlo y reutilizarlo en todas las pantallas. No se cambia un diseño aprobado sin avisar.
- **Dónde está:** `_dev/estilos-pos.md` (las 8 reglas, el procedimiento, cómo crear un componente, los errores ya cometidos, el inventario de piezas de Webkul y de `core.js`), `_dev/CLAUDE.md` (resumen que se carga al trabajar en el plugin), aviso al inicio de `contexto-activo.md` y la memoria de usuario.
- **Lecciones clave:** los ids dan estilo (`#order-main-summary`, `#pos-order-tab`); copiar «parecido» no vale; los formularios de pantalla copian «Editar cliente» y no las clases de las ventanas; comprobar con valores calculados contra el modelo y sin scroll horizontal.

## D-035 — «Marcas» como la pantalla Informe de Webkul
- Pedido del usuario (2026-11-19, con capturas de Informes): el resumen de marcas copia el modelo **Informe** (`wkreport-*`: filtros con etiqueta, botón «Filtrar», tarjetas de totales y tabla de informe), no una lista de Pedidos. Para ello se crean, una sola vez, los componentes `ui.report`, `ui.reportField`, `ui.reportButton`, `ui.reportCards` y `ui.reportTable` en `core.js` (regla 5 de `estilos-pos.md`).
- Cambio de comportamiento: el filtro de marca pasa de botones (multiselección) a un desplegable de una marca; se pide con «Filtrar», como en Informe.
