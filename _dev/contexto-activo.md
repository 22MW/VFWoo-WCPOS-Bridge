# Contexto activo

## Plugin objetivo

`vfwoo-webkul-pos-bridge` (v0.1.0.7 dev, rama `dev-vfwoo-webkul-pos-bridge`, Webkul POS 7.1.1)

## Confirmado en local

- Ticket fiscal con QR: venta inicial y reimpresión (pedidos 39085 y 39084).
- Filtro `BRANDS` en el panel nativo del catálogo POS (D-004): aparece y funciona.
- Taxonomías configurables desde Settings (D-008): funciona.
- Versión de catálogo, aviso en POS y consulta automática (D-009, D-010, D-011): funcionan.
- La caché local de Webkul conserva respuestas antiguas; limpiar los datos de `pos.local` fuerza la carga nueva.

## Pendiente

- QA detallada del filtro: producto variable, marca + categoría + búsqueda, paginación.
- Pestaña `VFWoo Bridge` en `POS → Settings`: confirmar en runtime.
- Diagnóstico de compatibilidad y recarga segura de caché.
- Informes por marca: sin gancho de cliente conocido (D-007).
- Versión de catálogo (D-009) y aviso (D-010): confirmados en local.
- Retirar el debug temporal antes de release.
- Idea: botón en `POS → Settings → VFWoo Bridge` para borrar la caché local del POS (ver roadmap Fase 5).

## Siguiente paso

Elegir entre QA detallada del filtro, diagnóstico en Settings o informes por marca.
