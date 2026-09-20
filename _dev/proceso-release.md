# Proceso de release

Cómo se publica una versión de VFWoo Webkul POS Bridge y cómo la reciben los sitios.

## Piezas

| Pieza | Dónde | Qué hace |
|---|---|---|
| Script | `_dev/deploy-release.sh` | Comprueba, prepara la rama `pos-release` sin `_dev`, genera el ZIP y publica la release en GitHub. |
| Actualizador | `includes/class-github-updater.php` | Dentro del plugin: hace que WordPress ofrezca la actualización en Escritorio › Actualizaciones. |
| Rama `pos-release` | remoto `22MW/VFWoo-WCPOS-Bridge` | Copia de la versión estable sin `_dev`, `.claude`, `.env` ni `.gitignore`. |
| Tag y release | mismo remoto, `pos-v<versión>` | La release lleva el ZIP como `vfwoo-webkul-pos-bridge.zip`. |

## Por qué se diferencia del script de ai-knowledge

- El remoto **lo comparte otro plugin** (`vfwoo-wcpos-bridge`). Por eso el script **no fusiona nada en `main`**, el tag lleva el prefijo `pos-v` y el actualizador solo considera releases con ese prefijo (la «última release» de GitHub es de todo el repositorio).
- La rama de release se prepara en una **copia de trabajo temporal** (`git worktree`). El script de referencia borra `_dev/` de tu carpeta de trabajo, y eso destruiría un `_dev/.env` con el token (Git lo ignora, no se recuperaría).
- El token se lee solo de la línea `GITHUB_TOKEN=` y nunca se imprime.
- Una release exige versión `X.Y.Z` (sin cuarto número). Comprueba que cabecera, constante, `Stable tag` y `CHANGELOG.md` coinciden.
- Verifica el ZIP (sin `_dev`, `.git`, `.env`, logs ni `.DS_Store`; con el archivo principal y `readme.txt`).

## Uso

```bash
./_dev/deploy-release.sh --dry-run       # simulacro: comprueba y genera dist/…zip; no toca Git ni GitHub
./_dev/deploy-release.sh --branch-only   # publica solo la rama pos-release (sin token)
./_dev/deploy-release.sh                 # release completa: rama + tag + release + ZIP (necesita GITHUB_TOKEN)
```

El token se busca en `_dev/.env`, `.env.local`, el `.env` de la raíz del workspace, el archivo indicado en `GITHUB_ENV_FILE`, o el entorno. Ningún archivo `.env*` se versiona.

## Pasos de una release

1. En la rama dev, subir la versión a `X.Y.Z` (cabecera, constante `VFWOO_WEBKUL_VERSION`, `Stable tag` de `readme.txt`) y pasar la sección del `CHANGELOG.md` a `## [X.Y.Z] - fecha`.
2. Commit y push a `dev-vfwoo-webkul-pos-bridge`.
3. `./_dev/deploy-release.sh --dry-run` y revisar.
4. `./_dev/deploy-release.sh`.
5. Después: subir el desarrollo a `X.Y.Z.1`, limpiar `contexto-activo.md` y pasar lo pendiente a `roadmap.md`.

## Cómo se actualiza cada sitio

- El plugin consulta `https://api.github.com/repos/22MW/VFWoo-WCPOS-Bridge/releases`, se queda con la release estable más nueva cuyo tag empieza por `pos-v` y compara con la versión instalada.
- Si hay una más nueva, aparece en Actualizaciones con un clic. La consulta se guarda una hora (los fallos, diez minutos); «Buscar de nuevo» en el Escritorio la fuerza.
- Solo se acepta un paquete alojado en `github.com/22MW/VFWoo-WCPOS-Bridge/`. Un ZIP de otra procedencia se rechaza.
- **No se activa en una copia de desarrollo** (una carpeta con `.git`): una actualización de WordPress reemplaza la carpeta entera y borraría el historial y `_dev/`. Solo las instalaciones de producción (el ZIP, sin `.git`) reciben actualizaciones. Se puede forzar con la constante `VFWOO_WEBKUL_ALLOW_DEV_UPDATES`.
- El actualizador se registra antes de comprobar las dependencias: un plugin al que le falta WooCommerce, VFWoo o Webkul se puede actualizar igual.
- Las versiones de desarrollo (cuatro números) son anteriores a la siguiente estable, así que un sitio en `0.1.0.13` verá `0.1.1` como actualización.

## Recomendación

Un repositorio propio para este plugin evitaría el prefijo de tags y compartir remoto con otro producto. Hoy funciona así con la mitigación anterior; el cambio sería solo `REPO` y `TAG_PREFIX` en el actualizador y en el script.
