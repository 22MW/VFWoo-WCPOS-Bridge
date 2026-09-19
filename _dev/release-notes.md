# Release notes

## 0.1.0.5 — desarrollo

- Filtro `BRANDS` en el panel nativo de filtros del POS mediante `product.attributes`; confirmado en local.
- Pendiente: QA de variables, categoría y paginación; taxonomías configurables; borrado de caché desde ajustes.

## 0.1.0.4 — desarrollo

- Identidad de plugin corregida a Veri*Fac*WOO.
- Dependencias declaradas: WooCommerce, VFWoo y Webkul POS.
- Bootstrap y avisos de requisitos alineados con el puente original.
- Pestaña renombrada a `VFWoo Bridge`.
- Script POS cargado mediante `wkwcpos_enqueue_pos_scripts`.
- Estado `processing` admitido para lectura fiscal en caja.
- Añadidos `CHANGELOG.md`, `uninstall.php` y readme ampliado.
- Descartado el instalador de plantillas porque Webkul ya mantiene editor, selección por outlet e impresión nativa.
- Añadido debug temporal PHP/JavaScript para diagnosticar el transporte fiscal al ticket.
- QA local confirmado: pedido nuevo y reimpresión muestran datos fiscales y QR.
- Detectada caché local de Webkul; limpiar los datos de `pos.local` fuerza la carga enriquecida.
- Preparación de datos `product_brand` para el filtro nativo de productos.

## 0.1.0.2 — desarrollo

- Documentación de desarrollo actualizada con el estado de F0, la pestaña de administración y el remoto compartido.
- Pendiente la activación local y el QA del ticket con QR real.

## 0.1.0.1 — desarrollo

- Estructura inicial del addon.
- Pantalla de estado en Webkul POS y fallback bajo WooCommerce.
- Lectura de datos fiscales VFWoo.
- Enriquecimiento de venta inicial e historial Webkul.
- Extensión JavaScript del ticket preparada para QR y datos fiscales.
- Documentación de investigación y plan F0 incorporada al plugin.

Pendiente: diseñar una recarga segura para evitar depender de la limpieza manual de caché.

Repositorio:

- Rama: `dev-vfwoo-webkul-pos-bridge`.
- Commit: `2b81773`.
- Push completado en `origin/dev-vfwoo-webkul-pos-bridge`.
