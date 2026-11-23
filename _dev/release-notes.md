# Release notes

## 2.1.0 — release

- **Versión menor (2026-11-23):** consolida las entradas de desarrollo 2.0.0.1 a 2.0.0.6. Detalle en `CHANGELOG.md` y `docs/funcionalidades-pos-bridge.md` (3.5).
- **Qué cambia:** módulo «Productos» y base de módulos; «Productos» y «Marcas» con los estilos del propio POS (panel lateral, fotos, clasificación, «Marcas» como la pantalla Informe); **venta sin conexión con inventario centralizado** (D-036); traducción al castellano de Woo Point of Sale 7.4.1 guardada en `_dev/traducciones/`.
- **Probado con Woo Point of Sale 7.4.1** (instalada en local; el usuario confirmó que todo funciona, incluida la venta sin conexión).
- **Notas de actualización:** recargar cada caja con Ctrl+F5. La traducción **no va en el ZIP**: copiar a mano `wc_pos-es_ES.mo` y el `.json` con hash a `wp-content/languages/plugins/` de cada sitio (instrucciones en `_dev/traducciones/woo-point-of-sale/es_ES/README.md`).
- **Riesgos aceptados al publicar:** sobreventa posible en la venta sin conexión; recargar la caja sin conexión falla (service worker de Webkul); sin probar: cajero sin modo gestor, vendedor con WCFM, Master Stock, Safari y tablet, tema oscuro con el diseño nuevo.
- **Publicación:** pendiente de registrar aquí la URL y el commit tras ejecutar `deploy-release.sh`.

## 2.0.0 — release

- **Publicada el 2026-11-17:** https://github.com/22MW/VFWoo-WCPOS-Bridge/releases/tag/pos-v2.0.0 (ZIP `vfwoo-webkul-pos-bridge.zip`, 37 archivos, contenido verificado; commit `47ac75e`).
- Versión mayor (2026-11-17): consolida las entradas de desarrollo 0.1.4.1 a 0.1.4.2 y los cambios posteriores. Detalle en `CHANGELOG.md` y `docs/funcionalidades-pos-bridge.md`.
- **Qué cambia:** el bridge detecta por capacidad el core de VFWoo y Verifactu (con su módulo activado); «Cambiar factura» hace factura a un ticket ya entregado con o sin Verifactu (persona o empresa, con país); cambio de estado del pedido solo a Completado / Anular / Devolver (con regla en el servidor); «Imprimir rectificativa»; vocabulario de la caja «ticket» y «factura».
- **Por qué 2.0.0:** cambia el modo de trabajar con las facturas (dependencia, botones, rutas internas, límite fijo de 3.000 € y NIF obligatorio solo con Verifactu).
- **Notas de actualización:** recargar cada caja con Ctrl+F5 (sin Resync). Quien pase del VFWoo antiguo al core: copia, desactivar el antiguo, activar «VFWoo Invoices» y «VFWoo Verifactu» con su módulo activado.
- **Riesgos aceptados al publicar** (el usuario decidió publicar antes de las pruebas): factura desde el ticket con Verifactu enviando a la AEAT, impresión de la rectificativa desde el POS, cambio de estado en Safari y venta sin conexión, sin probar; «mismo número» sin Verifactu por confirmar con la gestoría; el core solo no marca como anulada la factura de un pedido cancelado; venta sin conexión limitada por Webkul (inventario centralizado y service worker que no se registra en la 7.1.1).

## 0.1.2 — release

- **Publicada el 2026-09-20:** https://github.com/22MW/VFWoo-WCPOS-Bridge/releases/tag/pos-v0.1.2 (ZIP `vfwoo-webkul-pos-bridge.zip`, 25 archivos, verificado tras descargarlo y probado el actualizador contra las releases reales).

- «Marcas» como una categoría más en la barra del inicio, con las marcas como subcategorías y sus imágenes (D-022). Confirmado en local: funciona perfectamente.
- Estilos de la pantalla «Marcas» y de la ventana F3 basados en las variables y clases del POS, en tema claro y oscuro (D-020). Confirmado en local en los dos temas.
- Consolida la entrada de desarrollo 0.1.1.1. Notas de actualización: hacer Resync → Products en cada caja para que los productos traigan las marcas y sus imágenes.
- `_dev/` ordenado: planes e investigaciones en `_dev/temp/` (D-021).
- Instrucciones vigentes registradas en `contexto-activo.md`.

## 0.1.1 — release

- Primera versión estable: consolida 0.1.0.1 a 0.1.0.13 (ticket fiscal con QR, filtros de catálogo, aviso de catálogo, clientes con NIF, F1/F2, parada de venta, pantalla «Marcas», variables de ticket, F3).
- Añade el actualizador desde GitHub y el sistema de release (`proceso-release.md`).
- **Publicada el 2026-09-20:** https://github.com/22MW/VFWoo-WCPOS-Bridge/releases/tag/pos-v0.1.1 (ZIP `vfwoo-webkul-pos-bridge.zip`, 25 archivos, verificado tras descargarlo).
- Confirmado por el usuario antes de publicar: la F3 funciona bien.
- Riesgos aceptados al publicar: rectificativas sin verificar en sandbox; sin red de seguridad en servidor para ventas que se salten el navegador; actualización probada solo con simulación (una copia con `.git` no se actualiza por diseño), falta probarla en un sitio de staging.

## 0.1.0.13 — desarrollo

- F3 desde el POS: botón junto a «Imprimir factura», ventana para buscar o crear el cliente, comprobación de F2 confirmada y emisión con VFWoo. Confirmado en local: botones, alta de la F3 y ticket con datos del cliente tras Resync de pedidos.
- Ticket F3 con el comprador de la F3; QR incrustado y con `temp=1` mientras la factura está pendiente.
- Corrección: el botón se oculta, no se elimina, para no romper el render de Webkul (todos los pedidos mostraban «Algo salió mal» tras emitir).
- Estilos de la ventana y del botón alineados con el tema del POS (modo oscuro).
- Pendiente de confirmar: que tras emitir se pueda cambiar de pedido e imprimir la F3 sin Resync.
- Pendiente: verificar en sandbox las rectificativas (devolución total y parcial de F2, F1 y F3); herramienta de NIF de clientes existentes; aviso posterior a la venta.

## 0.1.0.12 — desarrollo

- Parada de la venta antes de cobrar (botón Pay del carrito y botón final de pago) si el total alcanza el límite de la factura simplificada o las simplificadas están desactivadas y no hay cliente con NIF. Confirmado en local.
- El aviso de venta bloqueada es el aviso emergente de Webkul: tiene ×, y se cierra solo a los 8 segundos (se deja así de momento).
- Pendiente: red de seguridad en servidor / aviso posterior a la venta, herramienta de NIF de clientes existentes.

## 0.1.0.11 — desarrollo

- Variables `${vfwoo_*}` (tienda, factura, cliente F1/F3) en el editor de plantillas y sustitución en el ticket; F2 sin datos del cliente.
- Corrección del editor de plantillas de Webkul (se quedaba en blanco con variables desconocidas).
- Cargado del script del editor por `?page=wc-pos-invoice-templates`, no por el id de pantalla (depende de la traducción del menú).
- Retirado el debug temporal (PHP y JavaScript).
- Confirmado en local: editor, impresión con QR, F1 con datos del cliente y F2 sin ellos.
- Pendiente: aviso posterior a la venta, herramienta de NIF de clientes existentes, bloqueo del Pay.

## 0.1.0.10 — desarrollo

- Pantalla «Marcas» en el POS: ventas por marca y por producto con selector de periodo; ruta añadida con `wkwcpos_pages_list`; endpoint REST de solo lectura autenticado con la sesión del POS. Confirmado en local.
- Pendiente: página «Marcas» en WooCommerce Analytics (opcional, más esfuerzo).

## 0.1.0.9 — desarrollo

- Clientes del POS con DNI/NIE/CIF obligatorio, validado (formato y censo AEAT) al guardar; NIF copiado al pedido para emitir F1. Confirmado en local.
- Omitida la comprobación antispam de WP Armour Extended solo en el alta de clientes del POS.
- Documentación: `guia-cambios.md`, `temp/plan-cliente-fiscal.md`, `temp/informe-soporte-webkul-default-customer.md`.
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
