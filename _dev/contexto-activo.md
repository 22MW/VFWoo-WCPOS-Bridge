# Contexto activo

> **ANTES DE TOCAR CUALQUIER PANTALLA DEL POS: leer `_dev/estilos-pos.md` y `_dev/CLAUDE.md`.** Se reutiliza siempre el sistema de diseño de Webkul (clases, **ids**, variables) copiando su pantalla modelo, sin preguntar; cero CSS de aspecto; si hace falta algo nuevo, avisar, crearlo una vez en `core.js` (`ui`) y reutilizarlo; comprobar con números contra el modelo.

## Plugin objetivo

`vfwoo-webkul-pos-bridge` (última release estable **2.1.0**, rama `dev-vfwoo-webkul-pos-bridge`, desarrollo desde `2.1.0.1`, Woo Point of Sale **7.4.1** de Webkul)

## Instrucciones vigentes

- **Estilos:** primero los del POS (variables `--primary`, `--secondary`, `--text-color`, `--primary-accent`… y clases como `primary`), y siempre en tema claro y oscuro. Ver D-020.
- **Orden de `_dev/`:** solo memoria, scripts y lo que usamos; planes, ideas e investigaciones en `_dev/temp/`. Ver D-021.
- **Preguntas y comprobaciones:** cuando el usuario pregunta, pide «mira si se puede» o «comprueba», se **investiga y se entrega un plan**; no se aplica ningún cambio hasta que diga «hazlo» (o equivalente). Regla de la sesión del 2026-09-20.
- **Release:** con `_dev/deploy-release.sh` (primero `--dry-run`); token en `_dev/.env`; el actualizador no se activa en copias con `.git`. Ver `proceso-release.md` y D-019. El script exige el árbol limpio: los documentos internos sin versionar (`_dev/comercial/`, costes, landing de Rafa, respuesta a Webkul) están excluidos en local con `.git/info/exclude` (el repo es **público**: nunca subirlos).
- **Probar en local por HTTPS:** `https://pos.local/pos` (por `http` el navegador no permite service workers). La caja guarda el JS/CSS en caché: tras un cambio, recargar con Ctrl+F5.
- **Traducción de Webkul:** `_dev/traducciones/` (README allí). El `.po` es la fuente; el `.json` se regenera con `po2json.py`. **No va en el ZIP**: copiar a mano `wc_pos-es_ES.mo` y el `.json` con hash a `wp-content/languages/plugins/` de cada sitio.

## Hecho (2026-11-23)

- **Publicada `pos-v2.1.0`.** Consolida las dev 2.0.0.1 a 2.0.0.6. Detalle en `CHANGELOG.md`, `release-notes.md` y `docs/funcionalidades-pos-bridge.md` (3.5).
- Venta sin conexión con inventario centralizado (D-036): probada en local por el usuario.
- Traducción es_ES revisada de Webkul 7.4.1 (1.835 textos); instalada en local.

## Release

- **Publicada `pos-v2.1.0` (2026-11-23):** commit `737cae1`; ZIP en `https://github.com/22MW/VFWoo-WCPOS-Bridge/releases/download/pos-v2.1.0/vfwoo-webkul-pos-bridge.zip` (49 archivos, contenido verificado, 112 KB). Rama `pos-release` en `ffb26c5`.
- Anterior: `pos-v2.0.0` (2026-11-17, commit `47ac75e`) y `pos-v0.1.1` a `pos-v0.1.4`.
- **`pos.vfwoo.online` sigue con la dev 0.1.3.5**: no se ha actualizado a la 0.1.4, la 2.0.0 ni la 2.1.0 (hace falta `validar-deploy` y OK). Si se actualiza, copiar también la traducción.
- Falta probar la actualización en un sitio de staging instalado desde el ZIP (la copia de desarrollo no se actualiza por diseño). Token de GitHub en `_dev/.env` (sin versionar).

## Pendiente

- **Sin probar de la 2.1.0:** cajero sin modo gestor (no debe ver la pestaña Productos); vendedor con WCFM; Master Stock; Safari y tablet; tema oscuro y otro color primario con el diseño nuevo; fotos de variaciones; «Cambiar factura» (ventana) y aviso de catálogo con los componentes del POS.
- **Pruebas en `pos.local` de lo publicado en 2.0.0** (detalle en `roadmap.md`): activar el core y Verifactu (copia → desactivar el `vfwoo` antiguo → activar «VFWoo Invoices» y «VFWoo Verifactu» con su módulo); «Cambiar factura» con y sin Verifactu (empresa, país, cambio de cliente, F3 con la F2 aceptada); «Imprimir rectificativa» de #39212; Anular y Devolver con su confirmación (y en Safari); correo con PDF; plantilla de ticket propia con F1.
- **Tras actualizar Webkul:** repetir la prueba de venta sin conexión (la bandera `wk_pos_validate_product_at_pay` puede cambiar) y revisar la traducción (textos nuevos).
- **Opcional:** que el bridge lleve y cargue solo la traducción (hoy se copia a mano); `.json` para las pantallas JS nuevas de Webkul (asignación masiva…); service worker propio con alcance en `/pos` para arrancar la caja sin red.
- **Confirmar con la gestoría** el «mismo número» al hacer factura sin Verifactu.
- **Webkul:** el service worker de la 7.4.1 ya no da 404 pero no controla `/pos` (`temp/informe-soporte-webkul-service-worker.md` está desfasado; actualizarlo antes de enviarlo).
- **Esperando a VFWoo:** filtro `vfwoo_simplified_limit` (`temp/prompt-vfwoo-filtro-limite.md`), F3 y factura a clientes de otro país, tipo de documento (pasaporte), marcar como anulada una factura del core con el pedido cancelado. Ver `roadmap.md`.
- Decisión pendiente: devolver un solo producto desde el POS (Return POS de Webkul, hacerlo en el bridge o dejarlo en el escritorio).
- Abiertos sin tocar: estrategia de licencias de Webkul para demos; cuenta pública `vfwoo-cash` con la contraseña visible; teléfono de la empresa en el ticket; icono de «Chaqueta porteo»; `_import-tmp` y 4 productos con SKU duplicado (6072, 35949, 37679, 37694); precios (`comercial/`, sin versionar).
- **Datos de prueba en `pos.local` (borrar a mano):** productos «ZZ prueba borrar» (39223) y «ZZ prueba fotos borrar» (39225) en la papelera, y **9 imágenes `prueba-c0392b/27ae60/2980b9`** en la biblioteca (ids 39226-39234; WPML las triplica): el POS no puede borrar adjuntos, hay que hacerlo desde Medios.

## Siguiente paso

Decidir si se actualiza `pos.vfwoo.online` a la 2.1.0 (con `validar-deploy` y copiando la traducción) y seguir con las pruebas pendientes.
