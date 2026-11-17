# Contexto activo

## Plugin objetivo

`vfwoo-webkul-pos-bridge` (última release estable 0.1.3, rama `dev-vfwoo-webkul-pos-bridge`, Webkul POS 7.1.1)

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

- Versiones **publicadas**: `pos-v0.1.1` y `pos-v0.1.2` (2026-09-20) y `pos-v0.1.3` (2026-11-10: pantalla de login configurable y VFWoo opcional) y **`pos-v0.1.4` (2026-11-17: accesos en la cabecera, botón POS-CAJA, cajeros gestores, plantilla de ticket propia, «Factura PDF» y PDF en el correo del POS)**. El desarrollo continúa desde `0.1.4.1`. Ver `proceso-release.md` y D-019.
- Instalación: `https://github.com/22MW/VFWoo-WCPOS-Bridge/releases/download/pos-v0.1.4/vfwoo-webkul-pos-bridge.zip`. Instalado y **activo** en `pos.vfwoo.online` (ZIP por SSH, `wp plugin install`): 0.1.3 el 2026-11-10 y después la **dev 0.1.3.3**, ya commiteada y subida a la rama dev (`88926ff` avisos del admin; `2883f59` atajos en la cabecera del POS, botón POS-CAJA, rol de gestor para cajeros nuevos y aviso de catálogo al guardar productos). Interruptores activados en el servidor: `vfwoo_webkul_hide_wc_compat_notice` y `vfwoo_webkul_cashier_manager_role`. **Pendiente de decidir:** la cuenta pública de demostración `vfwoo-cash` tiene el rol `shop_manager` y su contraseña sale en el login. **La 0.1.4 aún no está instalada en el servidor** (sigue la dev 0.1.3.5). Rollback: reinstalar `dist/vfwoo-webkul-pos-bridge-0.1.3.zip`. Ticket del servidor: plantilla id 1 basada en la del staging de La Simbiosi (sin su logo), copia de la BD en `_backups-pos/`.
- Falta probar la actualización en un sitio de staging instalado desde el ZIP (la copia de desarrollo no se actualiza por diseño).
- Token de GitHub en `/Users/22mw/Local Sites/pos/.env` (fuera de la raíz web, sin versionar).

## Pendiente

- **Adaptación al core + Verifactu de VFWoo (D-026):** hecha en código (dev 0.1.4.1), **sin commit**. Orden para probarla en `pos.local`: copia de seguridad → desactivar el `vfwoo` antiguo → activar «VFWoo Invoices» y después «VFWoo Verifactu». Detalle y pendientes en `roadmap.md`.

- **«Factura completa» desde el ticket, con o sin Verifactu (D-027):** hecha en código, **sin commit**, probada con simulador (38 casos). Pendiente de probar en `pos.local` (crear cliente con empresa y domicilio, convertir, cambiar de cliente, PDF y correo, ticket reimpreso, y con Verifactu enviando F2→F3). Confirmar con la gestoría el «mismo número». Añadidos: país (España por defecto) + casilla «No comprobar el documento», y Verifactu solo cuenta con su módulo activado; lo pendiente de VFWoo está en `roadmap.md`.

- **QA real pendiente de lo publicado en 0.1.4** (detalle en `roadmap.md`): correo con PDF desde «Send order email», botón «Factura PDF» (diálogo de imprimir, fallback a pestaña), plantilla de ticket propia con F1, reimpresión, F3 y rollo térmico, y el NIF mostrado (`seller_nif`). El PDF tarda unos segundos: causa sin medir.

- **Plantilla de ticket propia (D-024):** **sin commit**. Confirmado en local: F2 impresa con la plantilla y logo al 50 % del ancho (con `!important`, por el `fitCss` de Webkul). Falta probar F1 con cliente, reimpresión, F3, con y sin QR y rollo real. Verificar que el NIF mostrado es el de `seller_nif`.

- **Esperando a VFWoo:** el usuario aplicará el filtro `vfwoo_simplified_limit` en su workspace (prompt en `temp/prompt-vfwoo-filtro-limite.md`) y avisará con la versión. Hasta entonces no se toca el límite en el bridge. Plan en `temp/plan-limite-pos.md`.

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
