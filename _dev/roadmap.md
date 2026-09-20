# Roadmap futuro

Orden de ejecución recomendado: 1 → 4 → 5 → 3 → 6.

## Fase 1 — Precheck y contrato Webkul (casi cerrada)

- Hecho: versión 7.1.1, repo propio, hooks localizados, panel de filtros nativo analizado (ver `WEBKUL-REACT-EXTENSION.md` y `decisiones.md`).
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

## Bloqueo de venta ≥ límite sin cliente con NIF (hecho y confirmado en local)

- Parada en el navegador en el botón Pay y en el botón final de pago (D-017); aviso si el NIF del cliente está sin verificar.
- Requiere Resync de clientes en cada caja para que lleguen `vfwoo_webkul_nif` y `vfwoo_webkul_is_default`.
- El aviso rojo es el emergente de Webkul (con ×, desaparece a los 8 s). Posible mejora: aviso propio que se quede hasta cerrarlo.
- Pendiente: red de seguridad en servidor / aviso posterior a la venta (`Emission_Check::comprobar`) para lo que se cuele; herramienta de NIF de clientes existentes.

## Herramienta de NIF de clientes existentes (planificada, aparcada)

- Plan completo en `plan-cliente-fiscal.md`, Fase 8: lista en `POS → Settings → VFWoo Bridge` (por defecto clientes con compras en el POS), guardar y verificar con la misma validación que el POS, sugerencia de NIF de pedidos anteriores sin guardar solo, aviso de «Resync» y trazabilidad.
- Datos del sitio local: 619 clientes y solo 1 con NIF; 7 clientes registrados con compras en el POS.
- Decisiones pendientes: lista por defecto, sugerencia de NIF, formulario por fila o AJAX, columna de domicilio, verificar todos por lotes.
- Aparcada: primero F3 (sustitutiva) y rectificativas.

## F3 (factura sustitutiva) y rectificativas desde el POS (F3 hecha; confirmada la emisión en local)

- Plan en `plan-f3-rectificativas.md`. VFWoo ya emite la F3 (`Invoice_Helper::emitir_sustitutiva`, solo desde una F2 confirmada, una por F2); el bridge añadiría un botón junto a «Imprimir factura» (`wkwcpos_add_after_print_invoice_button`), comprobación de NIF y nombre antes de enviar, y un endpoint `issue-f3`.
- Al reimprimir tras la F3 saldría ya F3; hay que corregir `customer_block` para que use el comprador de la F3 y no el cliente mostrador.
- Implementado (D-018): botón, ventana buscar/crear cliente, `f3-status` e `issue-f3`, y ticket F3 con el comprador de la F3. Confirmado en local: botón, ventana, emisión de la F3 y ticket con el cliente tras Resync. Pendiente de confirmar: cambiar de pedido e imprimir la F3 sin Resync tras la corrección de v0.1.0.13.
- Rectificativas: el POS no crea reembolsos (solo los muestra); VFWoo las genera desde los reembolsos de WooCommerce. Primero verificar en sandbox (R5 sobre F2, R1 sobre F1, con F3); un botón «Devolver» en el POS quedaría para más adelante.

## Release 0.1.1 y actualizaciones (preparada)

- Script, actualizador y rama `pos-release` según `proceso-release.md`. Pendiente: publicar la release en GitHub (necesita `GITHUB_TOKEN`) y comprobar en un sitio que aparece la actualización.
- Tras publicar: subir el desarrollo a `0.1.1.1`.
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

## Restricciones

- No crear pedidos ni emitir facturas durante las pruebas.
- No modificar archivos del plugin Webkul.
- No duplicar numeración, emisión ni llamadas fiscales de VFWoo.
- No borrar datos de WooCommerce para resolver caché.
