# Contexto activo

## Plugin objetivo

`vfwoo-webkul-pos-bridge` (v0.1.0.13 dev, rama `dev-vfwoo-webkul-pos-bridge`, Webkul POS 7.1.1)

## Confirmado en local

- Ticket fiscal con QR: venta inicial y reimpresión (pedidos 39085 y 39084).
- Filtro `BRANDS` en el panel nativo del catálogo POS (D-004): aparece y funciona.
- Taxonomías configurables desde Settings (D-008): funciona.
- QR incrustado en base64 y sin URL en el ticket (D-012): funciona a la primera impresión.
- Versión de catálogo, aviso en POS y consulta automática (D-009, D-010, D-011): funcionan.
- La caché local de Webkul conserva respuestas antiguas; limpiar los datos de `pos.local` fuerza la carga nueva.

## Release

- Versión 0.1.1 preparada (`proceso-release.md`, D-019). Rama `pos-release` y release en GitHub según estado del script.

## Pendiente

- QA detallada del filtro: producto variable, marca + categoría + búsqueda, paginación.
- Pestaña `VFWoo Bridge` en `POS → Settings`: confirmar en runtime.
- Diagnóstico de compatibilidad y recarga segura de caché.
- Informes por marca: sin gancho de cliente conocido (D-007).
- Versión de catálogo (D-009) y aviso (D-010): confirmados en local.
- Pantalla «Marcas» del POS (D-015): funciona en local. Opcional: contrastar cifras con más pedidos y periodos.
- Cliente y NIF (D-013, `plan-cliente-fiscal.md`): fases 1-3 implementadas y confirmadas en local (alta con DNI, antispam D-014). Faltan bloqueo en Pay, aviso post-venta y herramienta de NIF de clientes existentes.
- Ticket con variables `${vfwoo_*}` (tienda, factura, cliente F1/F3) y F2 sin datos del cliente (D-016): confirmado en local: el editor muestra la plantilla, el ticket imprime con QR, F1 con datos del cliente y F2 sin ellos.
- Bloqueo de venta ≥ límite sin cliente con NIF (D-017): funciona en local (aviso de Webkul con ×, dura 8 s). Requiere Resync de clientes. Sin guarda de servidor todavía.
- F3 desde el POS (D-018, `plan-f3-rectificativas.md`): funciona en local (botón, ventana, emisión, ticket tras Resync). Corregido el «Algo salió mal» al cambiar de pedido; falta confirmar. Requiere una F2 confirmada (sandbox de VFWoo). Devoluciones por WooCommerce: verificar R5/R1 en sandbox.
- Idea: botón en `POS → Settings → VFWoo Bridge` para borrar la caché local del POS (ver roadmap Fase 5).

## Siguiente paso

Elegir entre QA detallada del filtro, diagnóstico en Settings o informes por marca.
