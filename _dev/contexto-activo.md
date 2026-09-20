# Contexto activo

## Plugin objetivo

`vfwoo-webkul-pos-bridge` (v0.1.2, última release estable 0.1.2, rama `dev-vfwoo-webkul-pos-bridge`, Webkul POS 7.1.1)

## Confirmado en local

- Ticket fiscal con QR: venta inicial y reimpresión (pedidos 39085 y 39084).
- Filtro `BRANDS` en el panel nativo del catálogo POS (D-004): aparece y funciona.
- Taxonomías configurables desde Settings (D-008): funciona.
- QR incrustado en base64 y sin URL en el ticket (D-012): funciona a la primera impresión.
- Versión de catálogo, aviso en POS y consulta automática (D-009, D-010, D-011): funcionan.
- La caché local de Webkul conserva respuestas antiguas; limpiar los datos de `pos.local` fuerza la carga nueva.

## Instrucciones vigentes

- **Estilos:** primero los del POS (variables `--primary`, `--secondary`, `--text-color`, `--primary-accent`… y clases como `primary`), y siempre en tema claro y oscuro. Ver D-020.
- **Orden de `_dev/`:** solo memoria, scripts y lo que usamos; planes, ideas e investigaciones en `_dev/temp/`. Ver D-021.
- **Preguntas y comprobaciones:** cuando el usuario pregunta, pide «mira si se puede» o «comprueba», se **investiga y se entrega un plan**; no se aplica ningún cambio hasta que diga «hazlo» (o equivalente). Regla de la sesión del 2026-09-20.
- **Release:** con `_dev/deploy-release.sh`; token en `_dev/.env`; el actualizador no se activa en copias con `.git`. Ver `proceso-release.md` y D-019.

## Release

- Versión 0.1.1 **publicada** (`pos-v0.1.1`); versión 0.1.2 **preparada para publicar** (`pos-v0.1.2`). Ver `proceso-release.md` y D-019.
- Falta probar la actualización en un sitio de staging instalado desde el ZIP (la copia de desarrollo no se actualiza por diseño).
- Token de GitHub en `/Users/22mw/Local Sites/pos/.env` (fuera de la raíz web, sin versionar).

## Pendiente

- Opcional: probar «Marcas» de la barra de categorías con la barra antigua, con una marca sin imagen y combinada con el buscador y el panel de filtros.

- QA detallada del filtro: producto variable, marca + categoría + búsqueda, paginación.
- Pestaña `VFWoo Bridge` en `POS → Settings`: confirmar en runtime.
- Diagnóstico de compatibilidad y recarga segura de caché.
- Informes por marca: sin gancho de cliente conocido (D-007).
- Versión de catálogo (D-009) y aviso (D-010): confirmados en local.
- **«Marcas» junto a «Todos» en la barra de categorías del inicio (D-022): funciona perfectamente en local**, con las marcas como subcategorías, sus productos y sus imágenes.
- **Estilos del POS en tema claro y oscuro (D-020): confirmados** en «Marcas» y en la ventana F3.
- Pantalla «Marcas» del POS (D-015): funciona en local. Opcional: contrastar cifras con más pedidos y periodos.
- Cliente y NIF (D-013, `temp/plan-cliente-fiscal.md`): fases 1-3 implementadas y confirmadas en local (alta con DNI, antispam D-014). Faltan bloqueo en Pay, aviso post-venta y herramienta de NIF de clientes existentes.
- Ticket con variables `${vfwoo_*}` (tienda, factura, cliente F1/F3) y F2 sin datos del cliente (D-016): confirmado en local: el editor muestra la plantilla, el ticket imprime con QR, F1 con datos del cliente y F2 sin ellos.
- Bloqueo de venta ≥ límite sin cliente con NIF (D-017): funciona en local (aviso de Webkul con ×, dura 8 s). Requiere Resync de clientes. Sin guarda de servidor todavía.
- F3 desde el POS (D-018, `temp/plan-f3-rectificativas.md`): funciona en local (botón, ventana, emisión, ticket tras Resync). Corregido el «Algo salió mal» al cambiar de pedido; falta confirmar. Requiere una F2 confirmada (sandbox de VFWoo). Devoluciones por WooCommerce: verificar R5/R1 en sandbox.
- Idea: botón en `POS → Settings → VFWoo Bridge` para borrar la caché local del POS (ver roadmap Fase 5).

## Siguiente paso

Elegir entre QA detallada del filtro, diagnóstico en Settings o informes por marca.
