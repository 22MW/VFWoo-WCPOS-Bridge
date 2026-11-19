# Roadmap futuro

Orden de ejecución recomendado: 1 → 4 → 5 → 3 → 6.

## Fase 1 — Precheck y contrato Webkul (casi cerrada)

- Hecho: versión 7.1.1, repo propio, hooks localizados, panel de filtros nativo analizado (ver `temp/WEBKUL-REACT-EXTENSION.md` y `decisiones.md`).
- Pendiente: confirmar en runtime que `product.attributes` con una entrada `BRANDS` aparece en el panel y no rompe el alta al carrito ni el popup de variaciones.
- Pendiente: confirmar la taxonomía real de marcas (`product_brand`) y que las variaciones no necesitan el dato propio.

## Fase 2 — Transporte de datos (hecha en código, sin QA)

- `vfwoo_webkul_brands` se añade a la respuesta de producto.
- Pendiente: añadir la entrada `BRANDS` a `attributes` (ver decisión D-004) y validar.

## Fase 3 — Diagnóstico en Settings

- Mostrar en `POS → Settings → VFWoo Bridge` versiones (bridge, VFWoo, WooCommerce, Webkul POS), dependencias, estado de `product_brand` y número de términos.
- Botón `Comprobar compatibilidad` de solo lectura: versiones, clases, taxonomía, hooks y una muestra de producto. Sin crear pedidos ni emitir facturas.
- Informar si hace falta limpiar la caché local del POS.
- Si falla algo, desactivar solo `BRANDS` y mantener ventas, tickets y fiscalidad.
- Confirmar en runtime que aparece la pestaña `VFWoo Bridge`.

## Fase 4 — Filtro BRANDS

- Paso 1 HECHO y confirmado en local: entrada `BRANDS` en `attributes` para `product_brand`; el filtro aparece y funciona.
- Paso 2 HECHO y confirmado en local: taxonomías configurables desde la pestaña, sin duplicar categorías ni `pa_*`.
- Camino preferente: alimentar el panel nativo mediante `product.attributes` (sin React propio).
- Alternativa solo si falla: filtro propio con `wkwcpos_modify_homepage_products` y `wkwcpos_modify_product_page_component`.
- Selección múltiple: OR entre marcas, AND con color, talla, categoría y búsqueda (ya es el comportamiento nativo).
- Pendiente de validación detallada: productos variables, marca + categoría + búsqueda, paginación.

## Fase 5 — Caché y compatibilidad

- Observación del usuario: en el navegador, «Clear site data» (caché, IndexedDB, almacenamiento local, service workers, cookies) desconecta el POS; al volver a conectar carga todo desde cero (impuestos, pedidos, productos). Sin datos locales, la recarga es completa. Además, si se desconecta y reconecta sin borrar, no se recargan impuestos, pedidos ni productos. Idea: forzar esa recarga completa desde el POS (equivalente a limpiar los datos del sitio) cuando cambie la «versión de catálogo».
- Hallazgos Webkul 7.1.1 (bundle): los datos viven en IndexedDB `pos` (Dexie). El menú nativo `Resync` ofrece: Products (vacía solo `pos_products` y recarga), Orders (borra solo pedidos online sincronizados; conserva los offline), Customers y All (borra toda la base y recarga: destruye ventas offline pendientes, carritos aparcados y estado del cajón). Acciones disparadas: `wkwcpos_after_reset_products_action`, `wkwcpos_after_reset_orders_action`, `wkwcpos_reset_action`. Para los filtros basta Resync Products.
- HECHO y confirmado en local: «versión de catálogo» (opción `vfwoo_webkul_catalog_version`). Se sella en cada producto; sube al guardar filtros o con el botón `Forzar recarga del catálogo` en Settings; el script POS (filtro `wkwcpos_modify_homepage_products`) muestra un aviso si el catálogo en caché tiene versión menor. No borra nada. Consulta automática al servidor (al abrir, tras una venta, al cambiar de pantalla y cada 5 min; D-011). Recarga automática de `pos_products` desde el bridge: no probada, descartada de momento; `Resync All` automático descartado.
- Idea original (superada): la caché vive en el navegador de cada caja (IndexedDB), así que un botón en wp-admin no puede borrarla directamente. Diseño posible: el bridge guarda una «versión de catálogo» (opción que sube al pulsar un botón en `VFWoo Bridge` o al cambiar la lógica de marcas); el script POS compara esa versión con la guardada en el navegador y, si difiere, avisa al cajero o lanza la recarga nativa de Webkul (botón `Restablecer`). Pendiente de confirmar qué borra exactamente `Restablecer` antes de reutilizarlo.
- Detectar respuestas antiguas del catálogo (sin `vfwoo_webkul_brands`) y no ocultar productos en ese caso.
- Estudiar recarga automática o acción controlada que limpie solo `pos.local`.
- No borrar pedidos, configuración ni datos fiscales.

## Fase 6 — QA y mantenimiento

- Tras actualizar VFWoo: comprobar que `VFWoo\QR::png_url` sigue existiendo (QR incrustado, D-012).
- Productos simples, variables, categorías, búsqueda y paginación.
- Ticket fiscal y reimpresión tras cada actualización de Webkul.
- Registrar resultados en `contexto-activo.md` y `CHANGELOG.md`.
- Alinear `readme.txt` (Stable tag, estado) y `CHANGELOG.md` con la versión al preparar release.

## Pantalla «Marcas» en el POS (hecha y confirmada en local)

- Entrada «Marcas» en el menú del POS (`wkwcpos_menus_list`) y pantalla propia (ruta añadida con `wkwcpos_pages_list`) con botón Volver.
- Periodos: Hoy, Ayer, Esta semana (desde lunes), Este mes y rango Desde/Hasta (máximo 1 año). Selector de marcas.
- Por marca: unidades, ventas sin IVA, ventas con IVA, pedidos y devoluciones; al desplegar, lo mismo por producto.
- Endpoint REST del bridge `POST vfwoo-webkul/v1/brand-report`, solo lectura, autenticado con `WKWCPOS_API_Authentication` de Webkul (cabecera `authkey` + `logged_in_user_id`). Solo pedidos del POS (meta `_wk_wc_pos_outlet`), estados completed y processing.
- Limitaciones: la marca es la del producto hoy; un producto con varias marcas cuenta en cada una; variaciones con la marca del padre.
- Opcional a futuro: página «Marcas» en WooCommerce Analytics (más esfuerzo; `ReportTable` no es público).

## Ticket: variables de VFWoo (hecho en código, pendiente de QA)

- Variables `${vfwoo_*}` de tienda, factura y cliente en el editor de plantillas; datos del cliente solo en F1/F3; F2 sin datos del cliente (D-016).
- Pendiente: probar en el POS con una plantilla que las use (F1 y F2), y valorar una plantilla de ejemplo.
- Futuro: facturas F3 (sustitutiva) y rectificativas; ampliar `CUSTOMER_TYPES` y el cálculo del tipo. Herramienta para convertir a F3 y crear rectificativas.
- Pendiente de decidir: domicilio del cliente obligatorio en el formulario del POS para las F1.

## Ticket: plantilla propia del bridge (publicada en 0.1.4; F2 y logo confirmados en local)

- Casilla en POS → Settings → VFWoo Bridge → Ticket (D-024). Probada la sustitución y el relleno con un simulador de JS (F2 sin cliente, F1 con cliente, sin datos fiscales, casilla desactivada).
- **Confirmado en local (2026-11-17):** F2 impresa con la plantilla propia (logo, razón social, NIF, dirección, teléfono, email, factura y líneas) y logo al 50 % del ancho del ticket.
- Pendiente: F1 con cliente, reimpresión y F3; con y sin QR; con el rollo térmico real (anchos 58/80 mm).
- Pendiente: contrastar en el sitio que `seller_nif` y `company_nif` difieren o no, y que el ticket muestra el de «Diseño PDF».
- Futuro: `company_extra` (datos adicionales) si VFWoo lo expone.

## Adaptación al core y al complemento de VFWoo (hecha en código; pendiente de activar y probar)

- Ver D-026. Probado con simuladores en cuatro escenarios (core solo, core + Verifactu, VFWoo antiguo, sin plugin).
- **Pendiente de probar en `pos.local`** al activar el core y el complemento (con el VFWoo antiguo desactivado): que el POS carga, que el ticket sale con número de factura al cobrar (el core crea la factura en el estado de expedición que se configure), el QR con Verifactu, la regla de 3.000 € y el NIF opcional/obligatorio.
- **Hecho en código (D-027, publicado en la 2.0.0): «Factura completa» desde el ticket con o sin Verifactu.** Ruta local (mismo número, datos del cliente en el pedido) y ruta Verifactu (F3). Pendiente de probar en `pos.local`: crear cliente con empresa y domicilio, convertir un ticket sin cliente, cambiar de cliente, PDF y correo con los datos nuevos, ticket reimpreso como factura completa, y con Verifactu enviando: F2 aceptada → F3, F1/F3 registradas → mensaje, y que el PDF de la F3 saque el cliente y el domicilio.
- **Pendiente de confirmar con la gestoría:** que una factura completa con el mismo número que el ticket (sin Verifactu) sirva, o si debe llevar número nuevo y referirse al ticket (requeriría que el core de VFWoo emita una segunda factura por pedido).
- Pendiente: avisar a VFWoo de que, tras cambiar el cliente en una factura local, el PDF se regenera a nombre del nuevo (no se conserva el anterior).
- **Cambios que hacen falta en VFWoo / Verifactu (no sabemos cuándo estarán; de momento el bridge se protege):**
  1. **F3 y factura completa a clientes de otro país.** `Invoice_Helper_Verifactu::emitir_sustitutiva` solo acepta `nif` y `nombre` y copia el envío de la F2: debe aceptar **país** y **tipo de documento** (`IDOtro`: 02 NIF-IVA, 03 pasaporte, 04 documento del país de residencia, 05 certificado de residencia, 06 otro, 07 no censado) y no comprobar contra el censo un documento que no es español (`Emission_Check::aviso_de_censo`). Hoy el bridge **bloquea** la conversión con Verifactu si el cliente no es de España.
  2. **Tipo de documento en el pedido.** `Payload_Builder` elige el tipo del `IDOtro` solo (02 o 04) y nunca el pasaporte (03): debería leerlo de un dato del pedido (p. ej. meta `_billing_nif_type`) cuando exista. El bridge lo guardaría desde el formulario (selector «Tipo de documento»: DNI/NIE/CIF, NIF-IVA, Pasaporte, Otro).
  3. **IVA al facturar a un extranjero.** Con país ≠ ES, Verifactu trata la operación como exportación exenta (art. 21): un ticket ya vendido con IVA no se puede convertir sin decidir qué pasa con los importes. Lo decide VFWoo, no el bridge.
  4. **El plugin Verifactu activo con su módulo apagado** no debería exponer datos de ticket (`Ticket_Fiscal_Data::for_order`) ni el censo: hoy el bridge lo evita preguntando `Modules::is_active('verifactu')`; ideal que VFWoo ofrezca una función pública («¿Verifactu en uso?») y el bridge la use.
  5. **Filtro `vfwoo_simplified_limit`** (prompt en `temp/prompt-vfwoo-filtro-limite.md`) y comprobar que «Sector autorizado» coincide con el límite fijo de 3.000 € del POS.
  6. Probar F3 con una venta real en sandbox: que el PDF de la F3 saque el cliente y el domicilio (el bridge escribe el domicilio en el pedido tras la F3; Verifactu solo escribe NIF y nombre).
- **Pedidos cancelados y reembolsados (D-028):** hechos «Imprimir rectificativa» y el bloqueo de «Cambiar factura». Pendiente de probar en `pos.local`: imprimir la rectificativa de #39212 desde el POS, un pedido con varios reembolsos, y un pedido cancelado. **Cambio que hace falta en el core de VFWoo:** un pedido cancelado debería marcar su factura (hoy solo lo hace Verifactu) y el PDF debería salir como anulado; con el core solo, «Imprimir factura» de un pedido cancelado imprime una factura normal.
- **Devolución de un solo producto desde el POS (decidir):** Webkul no la trae. Opciones: (a) comprar el «Return POS» de Webkul; (b) hacerla en el bridge (ventana «Devolver productos» con líneas y cantidades → `wc_create_refund` con stock; tendría que anotar el movimiento en la caja de efectivo como hace `update_pos_order_status` con `create_order_drawer_transactions`); (c) de momento, solo en el escritorio de WooCommerce.
- **Pendiente en el bridge cuando VFWoo lo soporte:** selector «Tipo de documento» (pasaporte, NIF-IVA, otro) en el formulario del POS, guardarlo en el cliente y el pedido, validarlo por tipo y quitar el bloqueo de la conversión a extranjeros con Verifactu.
- Pendiente: comprobar con VFWoo que «Sector autorizado» en Verifactu coincide con el límite fijo del POS (3.000 €) o aplicar el filtro `vfwoo_simplified_limit`.
- Pendiente: si el core 1.2.1 lee los datos (tablas y opciones) del VFWoo antiguo al activarlo en un sitio con facturas.

## PDF de VFWoo en el POS (publicado en 0.1.4; pendiente de QA en la caja)

- «Send order email» con el PDF adjunto **siempre** desde el botón y botón «Factura PDF» con indicador de carga, que imprime sin salir del POS (D-025). Probado con simuladores de PHP y JS; **falta** probarlo con una venta real: que llega el correo con el PDF, que el diálogo de imprimir se abre y que el fallback a pestaña funciona.
- **Mejora futura: acelerar el PDF.** VFWoo lo genera con dompdf en cada petición y tarda unos segundos (el ticket sale al momento). Causa **sin medir**. Pasos: (1) medir con un `wp eval` cronometrado o con el Network del navegador; (2) si es la doble generación, apagar `auto_save_pdf` en VFWoo; (3) si son las imágenes remotas (logo y QR por HTTP), corregirlo en VFWoo o en el entorno; (4) si es dompdf puro, guardar el PDF generado por factura (clave: número de factura + fecha de modificación + ajustes del PDF) y reutilizarlo, o generarlo por adelantado cuando la factura se confirme.
- **Mejora futura: el botón «Factura PDF» y el correo con rectificativas y F3.** Hoy el correo de reembolso sale sin PDF, y con una F3 que sustituye a una F2 sale la factura que VFWoo da como más reciente (sin comprobar).
- Pendiente de comprobar en el sitio: que el motivo queda en el registro cuando no se adjunta (`WP_DEBUG_LOG`), para diagnosticar casos reales.
- Idea: pedir a VFWoo un filtro oficial para adjuntar o forzar el PDF en correos de WooCommerce (hoy el bridge usa `VFWOO_PDF_Engine` y `Invoice_Helper`, internos).

## Bloqueo de venta ≥ límite sin cliente con NIF (hecho y confirmado en local)

- Parada en el navegador en el botón Pay y en el botón final de pago (D-017); aviso si el NIF del cliente está sin verificar.
- Requiere Resync de clientes en cada caja para que lleguen `vfwoo_webkul_nif` y `vfwoo_webkul_is_default`.
- El aviso rojo es el emergente de Webkul (con ×, desaparece a los 8 s). Posible mejora: aviso propio que se quede hasta cerrarlo.
- Pendiente: red de seguridad en servidor / aviso posterior a la venta (`Emission_Check::comprobar`) para lo que se cuele; herramienta de NIF de clientes existentes.

## Herramienta de NIF de clientes existentes (planificada, aparcada)

- Plan completo en `temp/plan-cliente-fiscal.md`, Fase 8: lista en `POS → Settings → VFWoo Bridge` (por defecto clientes con compras en el POS), guardar y verificar con la misma validación que el POS, sugerencia de NIF de pedidos anteriores sin guardar solo, aviso de «Resync» y trazabilidad.
- Datos del sitio local: 619 clientes y solo 1 con NIF; 7 clientes registrados con compras en el POS.
- Decisiones pendientes: lista por defecto, sugerencia de NIF, formulario por fila o AJAX, columna de domicilio, verificar todos por lotes.
- Aparcada: primero F3 (sustitutiva) y rectificativas.

## F3 (factura sustitutiva) y rectificativas desde el POS (F3 hecha; confirmada la emisión en local)

- Plan en `temp/plan-f3-rectificativas.md`. VFWoo ya emite la F3 (`Invoice_Helper::emitir_sustitutiva`, solo desde una F2 confirmada, una por F2); el bridge añadiría un botón junto a «Imprimir factura» (`wkwcpos_add_after_print_invoice_button`), comprobación de NIF y nombre antes de enviar, y un endpoint `issue-f3`.
- Al reimprimir tras la F3 saldría ya F3; hay que corregir `customer_block` para que use el comprador de la F3 y no el cliente mostrador.
- Implementado (D-018): botón, ventana buscar/crear cliente, `f3-status` e `issue-f3`, y ticket F3 con el comprador de la F3. Confirmado en local: botón, ventana, emisión de la F3 y ticket con el cliente tras Resync. Pendiente de confirmar: cambiar de pedido e imprimir la F3 sin Resync tras la corrección de v0.1.0.13.
- Rectificativas: el POS no crea reembolsos (solo los muestra); VFWoo las genera desde los reembolsos de WooCommerce. Primero verificar en sandbox (R5 sobre F2, R1 sobre F1, con F3); un botón «Devolver» en el POS quedaría para más adelante.

## «Marcas» en la barra de categorías del inicio (hecha y confirmada en local)

- Nodo «Marcas» junto a «Todos» con las marcas como subcategorías, sus productos y sus imágenes (D-022). **Funciona perfectamente en local.** Requiere Resync → Products tras actualizar.
- Pendiente opcional de probar: la barra antigua, una marca sin imagen y la combinación con el buscador y el panel de filtros.
- Posible ampliación: otras taxonomías como nodos propios (hoy solo `product_brand`), y una opción para ocultarlo.

## Límite de factura simplificada propio del POS (planificado; esperando a VFWoo)

- **Plan completo:** `temp/plan-limite-pos.md`. **Prompt para el desarrollador de VFWoo:** `temp/prompt-vfwoo-filtro-limite.md`.
- **Necesidad:** web en 400 € y tienda física en 3.000 € (o al revés). Hoy hay un único ajuste global de VFWoo (`authorized_sector`).
- **Decisiones (2026-09-20):** filtro en VFWoo (`vfwoo_simplified_limit`); el límite del POS admite solo 400 o 3.000 (interpretación del «sí», por confirmar); el cambio de VFWoo lo hace el usuario en su workspace y avisará al aplicarlo.
- **Orden:** Fase A VFWoo (filtro, documentación y versión) → Fase B bridge (ajuste en `POS → Settings → VFWoo Bridge`, enganche al filtro solo para pedidos del POS, versión mínima de VFWoo y parada de venta con el mismo límite) → Fase C pruebas en sandbox. Primero VFWoo, luego el bridge.
- **Riesgo principal:** el bridge no puede forzar F2; si pusiera 3.000 € con un VFWoo antiguo, el TPV dejaría vender sin cliente y VFWoo emitiría F1 sin NIF. Por eso el ajuste queda desactivado con aviso si VFWoo no tiene el filtro.
- **Bloqueado hasta que el usuario confirme que el filtro está aplicado y diga la versión de VFWoo.**

## Release 0.1.1 y actualizaciones (publicada)

- Publicada `pos-v0.1.1` (`proceso-release.md`). Pendiente: comprobar en un sitio de staging, instalado desde el ZIP, que la siguiente release aparece en Actualizaciones y se instala.
- Publicadas `pos-v0.1.1` y `pos-v0.1.2` (Marcas en la barra de categorías y estilos del POS). El desarrollo sigue en `0.1.2.1`.
- Pendiente: comprobar en un sitio de staging, instalado desde el ZIP, que la actualización aparece y se instala (solo verificada con las releases reales en simulación).
- Publicadas también `pos-v0.1.3` (login configurable, VFWoo opcional) y **`pos-v0.1.4` (2026-11-17)**. Publicada también la **`pos-v2.0.0` (2026-11-17)**; el desarrollo sigue en `2.0.0.1`. Pendiente: actualizar `pos.vfwoo.online` a la 0.1.4 (hoy tiene la dev 0.1.3.5) con `validar-deploy` y `desplegar`.
- Recomendación: repositorio propio para este plugin.

## Fase futura: informes

- Añadir `Brands` al selector acumulable de Informes.
- Filtrar las líneas de producto de cada pedido por `product_brand`.
- Recalcular totales, unidades, impuestos y devoluciones.
- Mantener fechas, estados, outlet y cajero.
- Imprimir el informe filtrado.

## Nota: Informes por marca (investigado)

- Servidor: se puede añadir la marca a cada línea con `wkwcpos_modify_report_single_order_data` y responder búsquedas por tipo con `wkwcpos_modify_report_search_result`.
- Cliente: no se ha encontrado gancho para añadir opciones a `Filter By`; la lista está en el bundle. No confirmado si el filtrado es de servidor o de navegador.
- Vías: (1) inyección propia (frágil), (2) informe propio del bridge, (3) pedir gancho a Webkul. Decidir tras completar filtros de producto.

## Ampliación posterior

- Reutilizar el mismo mecanismo para cualquier taxonomía pública de productos.
- Mantener la lógica en el bridge, sin modificar Webkul, WooCommerce ni VFWoo.

## Módulos del bridge y módulo «Productos» (hecho y subido a dev 2.0.0.2; probado en `pos.local`)

Plan en `temp/plan-modulo-productos.md`; decisiones D-030 y D-031.

- [x] **Fase 0 — Base de módulos:** registro de módulos, `core.js`, bloque «Módulos» en el admin. Probada con la caja real.
- [x] **Fase 1 — Lista:** pantalla «Productos» con filtros e interruptores tienda / caja; sustituye al acceso de la cabecera.
- [x] **Fase 2 — Edición básica:** simples y variaciones existentes (sin fotos).
- [x] **Fase 3 — Permisos:** servidor (`edit_products`, `edit_post`, `publish_products`, autor si no puede editar ajenos). Probado con usuarios simulados; **sin probar con un vendedor real**.
- [x] **Fase 4 — Mejoras:** incompletos, acciones masivas, alta rápida por código de barras.
- [ ] **Edición en panel lateral** (como el detalle de Pedidos) en vez de la ventana emergente; las clases de formulario del POS solo existen dentro de una ventana, hay que meter el formulario en esa estructura.
- [ ] **Componentes compartidos en `core.js`** (`window.vfwooBridge.ui`): ventana, paginación, preloader, interruptor, campo, fila de lista.
- [ ] **Rehacer «Marcas»** (y revisar «Cambiar factura» y el aviso de catálogo) con `estilos-pos.md`.
- [ ] **Fotos** en el panel (subida con la sesión del POS, `FormData` + `authkey`; la cámara de la tablet con `capture`). Aplazadas por decisión del usuario.
- [ ] Probar con **otro usuario cajero con modo gestor** y con un usuario **sin** modo gestor (no debe ver la pestaña).
- [ ] Probar con **Master Stock** (hoy el popup deja el stock bloqueado) y con **WCFM** (vendedor: solo sus productos).
- [ ] Ajuste de stock rápido con nota (idea aparcada).
- [ ] Migrar «Marcas», login, atajos y facturas a módulos, de uno en uno, cuando se toquen.
- Otros planes aparcados: `temp/plan-cashlogy.md` (máquina de efectivo) y `temp/plan-marketplace-vendedores.md` (una caja por vendedor).

## Restricciones

- No crear pedidos ni emitir facturas durante las pruebas.
- No modificar archivos del plugin Webkul.
- No duplicar numeración, emisión ni llamadas fiscales de VFWoo.
- No borrar datos de WooCommerce para resolver caché.
