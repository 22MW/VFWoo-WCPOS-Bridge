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

Recompilar el `.mo` (Poedit lo hace al guardar) y, si cambian textos del JS, regenerar el `.json`. Después volver a copiar ambos.
