# Traducción es_ES de Woo Point of Sale (dominio `wc_pos`)

Copia de seguridad versionada. Las actualizaciones del plugin borran su carpeta `languages/`, así que los archivos viven en producción en `wp-content/languages/plugins/`, que WordPress no toca al actualizar.

| Archivo | Para qué |
|---|---|
| `wc_pos-es_ES.po` | Fuente editable (Poedit / Loco). |
| `wc_pos-es_ES.mo` | Textos PHP (compilado del `.po`). |
| `wc_pos-es_ES-d1e66de3a56a538cf5ad3e8ebd2ee5a9.json` | Textos del JS del POS. El hash es el md5 de `assets/dist/app/index.js`; si Webkul cambia esa ruta, el hash cambia. |

## Instalar en un sitio

Copiar el `.mo` y el `.json` a `wp-content/languages/plugins/` (con el mismo nombre). El `.po` no hace falta en el servidor.

## Si se edita el `.po`

1. Recompilar el `.mo`: `msgfmt -o wc_pos-es_ES.mo wc_pos-es_ES.po` (o Poedit al guardar).
2. Regenerar el `.json` (se hace desde el `.po` entero): `python3 -I ../../po2json.py wc_pos-es_ES.po wc_pos-es_ES-d1e66de3a56a538cf5ad3e8ebd2ee5a9.json`.
3. Copiar el `.mo` y el `.json` a `wp-content/languages/plugins/`.

Hecha sobre Woo Point of Sale 7.4.1 (1.835 textos). Con una versión nueva de Webkul: fusionar su `languages/wc_pos.pot` con `msgmerge --no-fuzzy-matching` y traducir lo nuevo. Los textos de la caja que no coincidan con el criterio (outlet = «punto de venta», till/drawer = «caja») se corrigen directamente en el `.po`.
