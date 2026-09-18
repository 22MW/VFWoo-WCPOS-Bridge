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

## Pendiente

- Activar y probar en local con el pedido 39084.
- Confirmar que el objeto fiscal llega a impresión inicial y reimpresión.
- Sustituir la prueba visual por QA del QR real.
- Crear filtros de catálogo en una fase posterior.
- Activar el plugin y hacer QA real con el pedido 39084.
