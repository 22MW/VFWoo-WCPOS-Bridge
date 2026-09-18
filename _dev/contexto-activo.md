# Contexto activo

## Plugin objetivo

`vfwoo-webkul-pos-bridge`

## Alcance actual

- Integración inicial VFWoo + Webkul POS.
- Página de estado bajo WooCommerce.
- Enriquecimiento de respuestas de venta e historial.
- Bloque fiscal en el ticket mediante `wp.hooks`.
- Pestaña `VFWoo Fiscal` dentro de `POS → Settings` y página fallback bajo WooCommerce.
- Rama `origin/dev-vfwoo-webkul-pos-bridge` en el repositorio compartido.
- Identidad y requisitos alineados con el puente VFWoo WCPOS.
- Carga del script mediante el hook confirmado `wkwcpos_enqueue_pos_scripts`.
- Sin instalador ni selector paralelo de plantillas: Webkul conserva su sistema nativo.
- Debug temporal activo con prefijo `[VFWoo Webkul Bridge]`; no registra QR, cookies ni datos personales.

## Confirmado en QA local

- Pedido nuevo `39085`: el ticket recibe datos fiscales y QR.
- Reimpresión del pedido completado `39084`: funciona después de limpiar los datos locales de `pos.local`.
- La consola confirmó `hasBridgeData: true`, `available: true`, `hasNumber: true` y `hasQr: true`.
- La causa observada fue caché local de Webkul; no se borró ni modificó ningún pedido.

## Pendiente

- Diseñar una recarga segura para evitar depender de la limpieza manual de caché.
- Filtro de marca en catálogo POS planificado en `_dev/plan-filtro-marca.md`; aún no implementado.
- Confirmar en runtime que aparece `POS → Settings → VFWoo Bridge`.
