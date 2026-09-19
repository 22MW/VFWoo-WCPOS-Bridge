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
- Idea (sin implementar): la caché vive en el navegador de cada caja (IndexedDB), así que un botón en wp-admin no puede borrarla directamente. Diseño posible: el bridge guarda una «versión de catálogo» (opción que sube al pulsar un botón en `VFWoo Bridge` o al cambiar la lógica de marcas); el script POS compara esa versión con la guardada en el navegador y, si difiere, avisa al cajero o lanza la recarga nativa de Webkul (botón `Restablecer`). Pendiente de confirmar qué borra exactamente `Restablecer` antes de reutilizarlo.
- Detectar respuestas antiguas del catálogo (sin `vfwoo_webkul_brands`) y no ocultar productos en ese caso.
- Estudiar recarga automática o acción controlada que limpie solo `pos.local`.
- No borrar pedidos, configuración ni datos fiscales.

## Fase 6 — QA y mantenimiento

- Productos simples, variables, categorías, búsqueda y paginación.
- Ticket fiscal y reimpresión tras cada actualización de Webkul.
- Registrar resultados en `contexto-activo.md` y `CHANGELOG.md`.
- Retirar el debug temporal (PHP `error_log` y `console.info`) antes de cualquier release.
- Alinear `readme.txt` (Stable tag, estado) y `CHANGELOG.md` con la versión al preparar release.

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
