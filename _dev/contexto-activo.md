# Contexto activo

> **ANTES DE TOCAR CUALQUIER PANTALLA DEL POS: leer `_dev/estilos-pos.md` y `_dev/CLAUDE.md`.** Se reutiliza siempre el sistema de diseño de Webkul (clases, **ids**, variables) copiando su pantalla modelo, sin preguntar; cero CSS de aspecto; si hace falta algo nuevo, avisar, crearlo una vez en `core.js` (`ui`) y reutilizarlo; comprobar con números contra el modelo.

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

## Tarea activa: módulos del bridge y módulo «Productos»

- **Fases 0 a 4 hechas y subidas a la rama dev (versión dev 2.0.0.2).** Detalle en D-030 y D-031; ayuda en `ayuda-caja/gestor-de-productos.md`.
- **Estilos:** la pantalla «Productos» ya usa el HTML y las clases del POS (ver `estilos-pos.md`, referencia obligatoria; D-020 ampliada). Barra superior: pestaña «Productos», buscador, «Nuevo producto» y «Editar en WordPress».
- **Subido a la rama dev (versión dev 2.0.0.3):** panel lateral, fotos, clasificación, componentes compartidos (`core.js` `ui` + `ui.css`), «Marcas» rehecha como la pantalla Informe (D-032, D-034, D-035) y la documentación de construcción (`estilos-pos.md`, `_dev/CLAUDE.md`). Archivos: `core.js`, `ui.css`, `modules/products.{js,css}`, `class-products-module.php`, `class-pos-script.php`, `pos-bridge.js`, `pos-bridge.css`, docs.
- **Regla D-033 (2026-11-19):** el formulario del panel copia «Editar cliente» de Webkul (`ui.formShell`); todo con sus clases y variables, para que siga los ajustes de Apariencia (colores, modo, plantillas). Falta comprobar con otro color primario y en tema oscuro.
- **Traducción es_ES de Webkul (2026-11-23):** movida fuera del plugin de Webkul a `wp-content/languages/plugins/` (`wc_pos-es_ES.mo` y el `.json` con hash de `assets/dist/app/index.js`) para que las actualizaciones no la borren; copia versionada en `_dev/traducciones/woo-point-of-sale/es_ES/` (versión dev 2.0.0.5). Confirmado en la caja local: sale en castellano. Webkul 7.4.1 instalada en local (la 7.1.1 está en `_copias/`, fuera de git). El usuario revisa la caja con capturas y se corrigen los textos a medias en el `.po`; el `.json` se regenera con `po2json.py`. En producción hay que copiar el `.mo` y el `.json` a mano. Falta comprobar el service worker (offline) y, sin tocar, las pantallas nuevas de Webkul (asignación masiva, etc.), que no tienen `.json`.
- **Venta sin conexión con inventario centralizado (2026-11-23, D-036, dev 2.0.0.6):** `pos-bridge.js` hace que la bandera `apif_script.wk_pos_validate_product_at_pay` valga 1 con red y 0 sin ella; así la caja vende offline con el stock guardado y el pedido se sincroniza luego. El usuario volvió a «Centralized Stock Inventory» en local. **Falta probar:** (1) con red, vender y ver que la validación normal sigue; (2) con la caja abierta y «Offline» en DevTools, vender sin que salga el aviso rojo; (3) volver a conectar, «Sincronizar pedidos sin conexión» y ver el pedido en WooCommerce; (4) que el stock del producto baja 1. Probar siempre por `https://pos.local/pos` (por `http` no hay service worker) y sin recargar la caja estando offline.
- **Pendiente:** «Cambiar factura» (ventana) y el aviso de catálogo con los componentes del POS; fotos de variaciones.
- **Sin probar:** cajero sin modo gestor (no debe ver la pestaña); vendedor con WCFM; Master Stock; Safari y tablet; tema oscuro con el diseño nuevo.
- **Datos de prueba en `pos.local` (borrar a mano):** productos «ZZ prueba borrar» (39223) y «ZZ prueba fotos borrar» (39225) en la papelera, y **9 imágenes `prueba-c0392b/27ae60/2980b9`** en la biblioteca (ids 39226-39234; WPML las triplica): el POS no puede borrar adjuntos, hay que hacerlo desde Medios; la opción `vfwoo_webkul_modules` guardada con `products` activo.
- **Ruta del POS en local:** `http://pos.local/pos` (`_pos_endpoint_name` sin definir). La caja guarda el JS/CSS en caché: tras un cambio, `fetch(url, {cache:'reload'})` y recargar.

## Release

- **Publicada `pos-v2.0.0` (2026-11-17)**, versión mayor: detección por capacidad del core y Verifactu (con su módulo activado), «Cambiar factura» (ticket → factura con o sin Verifactu, con país y empresa), estado del pedido Completado / Anular / Devolver con regla en el servidor, «Imprimir rectificativa» y vocabulario «ticket» y «factura». Commit `47ac75e`; ZIP en `https://github.com/22MW/VFWoo-WCPOS-Bridge/releases/download/pos-v2.0.0/vfwoo-webkul-pos-bridge.zip`. Detalle en `CHANGELOG.md`, `release-notes.md` y `docs/funcionalidades-pos-bridge.md`. El desarrollo continúa desde `2.0.0.1`.
- **`pos.vfwoo.online` sigue con la dev 0.1.3.5**: no se ha actualizado a la 0.1.4 ni a la 2.0.0 (hace falta `validar-deploy` y OK).
- Versiones anteriores: `pos-v0.1.1` a `pos-v0.1.4` (la 0.1.4, el 2026-11-17).
- Falta probar la actualización en un sitio de staging instalado desde el ZIP (la copia de desarrollo no se actualiza por diseño). Token de GitHub en `/Users/22mw/Local Sites/pos/.env` (fuera de la raíz web, sin versionar).

## Pendiente

- **Pruebas en `pos.local` de lo publicado en 2.0.0** (detalle en `roadmap.md`): activar el core y Verifactu (copia → desactivar el `vfwoo` antiguo → activar «VFWoo Invoices» y «VFWoo Verifactu» con su módulo); «Cambiar factura» con y sin Verifactu (empresa, país, cambio de cliente, F3 con la F2 aceptada); «Imprimir rectificativa» de #39212; Anular y Devolver con su confirmación (y en Safari); correo con PDF; plantilla de ticket propia con F1.
- **Confirmar con la gestoría** el «mismo número» al hacer factura sin Verifactu.
- **Webkul:** enviar `temp/informe-soporte-webkul-service-worker.md` (service worker en 404 y cómo actualizar el plugin); probar el stock en modo Master Stock (si una venta del POS descuenta el stock de la web) antes de decidir sobre la venta sin conexión.
- **Esperando a VFWoo:** filtro `vfwoo_simplified_limit` (`temp/prompt-vfwoo-filtro-limite.md`), F3 y factura a clientes de otro país, tipo de documento (pasaporte), marcar como anulada una factura del core con el pedido cancelado. Ver `roadmap.md`.
- Decisión pendiente: devolver un solo producto desde el POS (Return POS de Webkul, hacerlo en el bridge o dejarlo en el escritorio).
- Abiertos sin tocar: estrategia de licencias de Webkul para demos; cuenta pública `vfwoo-cash` con la contraseña visible; teléfono de la empresa en el ticket; icono de «Chaqueta porteo»; `_import-tmp` y 4 productos con SKU duplicado (6072, 35949, 37679, 37694); precios (`comercial/`, sin versionar).

## Siguiente paso

Probar en `pos.local` lo publicado en 2.0.0 y decidir si se actualiza `pos.vfwoo.online`.
