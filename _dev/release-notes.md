# Release notes

## 0.1.0.11 — desarrollo

- Variables `${vfwoo_*}` (tienda, factura, cliente F1/F3) en el editor de plantillas y sustitución en el ticket; F2 sin datos del cliente.
- Corrección del editor de plantillas de Webkul (se quedaba en blanco con variables desconocidas).
- Cargado del script del editor por `?page=wc-pos-invoice-templates`, no por el id de pantalla (depende de la traducción del menú).
- Retirado el debug temporal (PHP y JavaScript).
- Pendiente de confirmar en el POS: impresión real con una plantilla que use las variables (F1 y F2).
- Pendiente: aviso posterior a la venta, herramienta de NIF de clientes existentes, bloqueo del Pay.

## 0.1.0.10 — desarrollo

- Pantalla «Marcas» en el POS: ventas por marca y por producto con selector de periodo; ruta añadida con `wkwcpos_pages_list`; endpoint REST de solo lectura autenticado con la sesión del POS. Confirmado en local.
- Pendiente: página «Marcas» en WooCommerce Analytics (opcional, más esfuerzo).

## 0.1.0.9 — desarrollo

- Clientes del POS con DNI/NIE/CIF obligatorio, validado (formato y censo AEAT) al guardar; NIF copiado al pedido para emitir F1. Confirmado en local.
- Omitida la comprobación antispam de WP Armour Extended solo en el alta de clientes del POS.
- Documentación: `guia-cambios.md`, `plan-cliente-fiscal.md`, `informe-soporte-webkul-default-customer.md`.
- Pendiente: bloqueo en Pay, ticket F2 sin datos del cliente, aviso post-venta, herramienta de NIF de clientes existentes.

## 0.1.0.8 — desarrollo

- QR incrustado en base64 en el ticket (venta inicial y reimpresión); corrige el QR roto en la primera impresión. Confirmado en local.
- La URL de cotejo ya no se imprime en el ticket.
- Pendiente: el QR incrustado usa la clase interna `VFWoo\QR`, no documentada como contrato de VFWoo; vigilar en actualizaciones de VFWoo.

## 0.1.0.7 — desarrollo

- Versión de catálogo con botón en Settings y aviso en el POS (Resync → Products); confirmado en local.
- Consulta automática de la versión (REST `catalog-version`) sin recargar el POS; confirmado en local.
- Corrección: los estilos del bridge se descartaban en el POS; ahora entran en la lista blanca de Webkul.
- Pendiente: posible caché del service worker sobre la consulta; diagnóstico en Settings.

## 0.1.0.6 — desarrollo

- Taxonomías de filtro configurables desde `POS → Settings → VFWoo Bridge` (opción `vfwoo_webkul_filter_taxonomies`); confirmado en local.
- Límite: solo taxonomías registradas para `product`.

## 0.1.0.5 — desarrollo

- Filtro `BRANDS` en el panel nativo de filtros del POS mediante `product.attributes`; confirmado en local.
- Pendiente: QA de variables, categoría y paginación; borrado de caché desde ajustes.

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
