#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy-release.sh — Release de VFWoo Webkul POS Bridge en GitHub
#
# Uso (desde cualquier carpeta):
#   ./_dev/deploy-release.sh --dry-run      # comprueba todo y genera el ZIP; no toca Git remoto ni GitHub
#   ./_dev/deploy-release.sh --branch-only  # publica solo la rama pos-release (sin _dev) y genera el ZIP; sin token
#   ./_dev/deploy-release.sh             # release real
#
# Flujo:
#   1. Lee la versión estable del header de vfwoo-webkul-pos-bridge.php (X.Y.Z, sin cuarto número)
#   2. Comprueba coherencia: constante, "Stable tag" de readme.txt y entrada en CHANGELOG.md
#   3. Comprueba sintaxis (php -l, node --check) y que el tag no exista ya
#   4. Comprueba que estás en la rama dev, sin cambios pendientes y sincronizada con origin
#   5. Crea/sobrescribe la rama "pos-release" desde origin/<dev> sin _dev ni archivos de desarrollo
#   6. Genera dist/vfwoo-webkul-pos-bridge-<version>.zip y verifica su contenido
#   7. Crea la release "pos-v<version>" en GitHub y sube el ZIP como vfwoo-webkul-pos-bridge.zip
#      (con --branch-only se para antes de este paso)
#
# DIFERENCIAS con el script de ai-knowledge, a propósito:
#   - El remoto (22MW/VFWoo-WCPOS-Bridge) lo comparte otro plugin: NO se fusiona nada en main.
#   - El tag lleva prefijo "pos-v" y el actualizador del plugin filtra por ese prefijo, porque la
#     "última release" de GitHub es de todo el repositorio.
#
# Token: GITHUB_TOKEN en _dev/.env, .env.local, .env de la raíz del workspace, en el archivo que
# indique GITHUB_ENV_FILE, o en el entorno. Esos archivos no se versionan (.gitignore: .env*).
# Solo se lee la línea GITHUB_TOKEN=; el token nunca se imprime.
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
WORKSPACE_ROOT="$(cd "$REPO_DIR/../../../../.." && pwd)"
cd "$REPO_DIR"

REPO="22MW/VFWoo-WCPOS-Bridge"
BRANCH_DEV="dev-vfwoo-webkul-pos-bridge"
BRANCH_RELEASE="pos-release"
PLUGIN_FOLDER="vfwoo-webkul-pos-bridge"
MAIN_FILE="vfwoo-webkul-pos-bridge.php"
ASSET_NAME="vfwoo-webkul-pos-bridge.zip"
TAG_PREFIX="pos-v"
VERSION_CONSTANT="VFWOO_WEBKUL_VERSION"

DRY_RUN=0
BRANCH_ONLY=0
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
        --branch-only) BRANCH_ONLY=1 ;;
        -h|--help) sed -n '2,31p' "${BASH_SOURCE[0]}"; exit 0 ;;
        *) echo "Argumento desconocido: $arg (usa --dry-run, --branch-only o --help)"; exit 1 ;;
    esac
done

fail() { echo "Error: $*" >&2; exit 1; }

TEMP_DIR="$(mktemp -d)"
WORKTREE="$TEMP_DIR/worktree"
cleanup() {
    # La rama de release se prepara en una copia de trabajo temporal: la carpeta del plugin
    # (y su _dev/, con sus .env) no se toca nunca.
    if [ -d "$WORKTREE" ]; then
        git worktree remove --force "$WORKTREE" >/dev/null 2>&1 || true
    fi
    git worktree prune >/dev/null 2>&1 || true
    rm -rf "$TEMP_DIR"
}
trap cleanup EXIT

# ── Token (solo si es release completa) ──────────────────────────────────────────

read_token_from() {
    [ -f "$1" ] || return 1
    local line
    line="$(grep -E '^GITHUB_TOKEN=' "$1" | head -1 || true)"
    [ -n "$line" ] || return 1
    line="${line#GITHUB_TOKEN=}"
    line="${line%\"}"; line="${line#\"}"; line="${line%\'}"; line="${line#\'}"
    GITHUB_TOKEN="$line"
}

NEEDS_TOKEN=1
[ "$DRY_RUN" -eq 1 ] && NEEDS_TOKEN=0
[ "$BRANCH_ONLY" -eq 1 ] && NEEDS_TOKEN=0

if [ "$NEEDS_TOKEN" -eq 1 ] && [ -z "${GITHUB_TOKEN:-}" ]; then
    read_token_from "$SCRIPT_DIR/.env" \
        || read_token_from "$REPO_DIR/.env.local" \
        || read_token_from "$WORKSPACE_ROOT/.env" \
        || { [ -n "${GITHUB_ENV_FILE:-}" ] && read_token_from "$GITHUB_ENV_FILE"; } \
        || true
fi

if [ "$NEEDS_TOKEN" -eq 1 ] && [ -z "${GITHUB_TOKEN:-}" ]; then
    fail "define GITHUB_TOKEN en _dev/.env, .env.local, $WORKSPACE_ROOT/.env, GITHUB_ENV_FILE o el entorno"
fi

# ── Versión y coherencia ─────────────────────────────────────────────────────

VERSION="$(grep -E '^ \* Version:' "$MAIN_FILE" | head -1 | sed -E 's/^ \* Version:[[:space:]]*//' | tr -d '[:space:]')"
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] \
    || fail "la versión del header es '$VERSION'. Una release usa X.Y.Z (sin cuarto número, que es de desarrollo)."

CONSTANT_VERSION="$(grep -E "define\( *'$VERSION_CONSTANT'" "$MAIN_FILE" | head -1 | sed -E "s/.*'$VERSION_CONSTANT', *'([^']*)'.*/\1/")"
[ "$CONSTANT_VERSION" = "$VERSION" ] || fail "la constante $VERSION_CONSTANT ($CONSTANT_VERSION) no coincide con el header ($VERSION)"

STABLE_TAG="$(grep -E '^Stable tag:' readme.txt | head -1 | sed -E 's/^Stable tag:[[:space:]]*//' | tr -d '[:space:]')"
[ "$STABLE_TAG" = "$VERSION" ] || fail "el Stable tag de readme.txt ($STABLE_TAG) no coincide con el header ($VERSION)"

grep -qE "^## \[$VERSION\]" CHANGELOG.md || fail "CHANGELOG.md no tiene la sección '## [$VERSION]'"

TAG="${TAG_PREFIX}${VERSION}"
MODE_NOTE=""
[ "$DRY_RUN" -eq 1 ] && MODE_NOTE=" (SIMULACRO: no se publica nada)"
[ "$BRANCH_ONLY" -eq 1 ] && MODE_NOTE=" (solo rama $BRANCH_RELEASE y ZIP, sin release en GitHub)"
echo "━━━ Release $TAG desde $BRANCH_DEV$MODE_NOTE ━━━"

# ── Sintaxis ─────────────────────────────────────────────────────────────────

PHP_BIN="${PHP_BIN:-$(command -v php || true)}"
[ -n "$PHP_BIN" ] || fail "no encuentro php. Indícalo con PHP_BIN=/ruta/a/php"
echo "[1/6] Comprobando sintaxis..."
while IFS= read -r file; do
    "$PHP_BIN" -l "$file" >/dev/null || fail "php -l falla en $file"
done < <(find . -name '*.php' -not -path './_dev/*' -not -path './.git/*' -not -path './node_modules/*')

if command -v node >/dev/null 2>&1; then
    while IFS= read -r file; do
        node --check "$file" || fail "node --check falla en $file"
    done < <(find . -name '*.js' -not -path './_dev/*' -not -path './.git/*' -not -path './node_modules/*')
else
    echo "    (aviso: node no está instalado, no se comprueba el JavaScript)"
fi

# ── Estado de Git ────────────────────────────────────────────────────────────

echo "[2/6] Comprobando Git..."
CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
[ "$CURRENT_BRANCH" = "$BRANCH_DEV" ] || fail "debes estar en $BRANCH_DEV (rama actual: $CURRENT_BRANCH)"
git fetch origin "$BRANCH_DEV" --quiet
if [ "$DRY_RUN" -eq 1 ]; then
    # El simulacro sirve para revisar el ZIP antes de hacer commit: solo avisa.
    [ -z "$(git status --porcelain)" ] || echo "    (simulacro: hay cambios sin commitear; se usa el árbol de trabajo actual. La release real exige commit y push)"
else
    [ -z "$(git status --porcelain)" ] || fail "hay cambios sin commitear en $BRANCH_DEV. Haz commit antes de la release."
    [ "$(git rev-parse HEAD)" = "$(git rev-parse "origin/$BRANCH_DEV")" ] \
        || fail "$BRANCH_DEV local y origin/$BRANCH_DEV no coinciden. Haz push (o pull) antes de la release."
fi

EXISTING_TAG="$(git ls-remote --tags origin "refs/tags/$TAG")"
[ -z "$EXISTING_TAG" ] || fail "el tag $TAG ya existe en remote. Incrementa la versión."

# ── Changelog de la versión ──────────────────────────────────────────────────

CHANGELOG_BODY="$(awk "/^## \[$VERSION\]/{found=1; next} found && /^## \[/{exit} found{print}" CHANGELOG.md | sed '/^[[:space:]]*$/d' | head -80)"
[ -n "$CHANGELOG_BODY" ] || CHANGELOG_BODY="Release $VERSION"

# ── Árbol limpio (sin _dev ni archivos de desarrollo) ────────────────────────

strip_dev_files() {
    rm -rf "$1/_dev" "$1/.kilo" "$1/.claude" "$1/node_modules"
    rm -f "$1/.gitignore" "$1/.gitattributes" "$1/.env" "$1/.env.local" "$1/deploy-release.sh"
    find "$1" -name '.DS_Store' -delete 2>/dev/null || true
    find "$1" -name '*.log' -delete 2>/dev/null || true
}

if [ "$DRY_RUN" -eq 1 ]; then
    echo "[3/6] (simulacro) Preparando árbol limpio en una carpeta temporal..."
    SOURCE_TREE="$TEMP_DIR/tree"
    mkdir -p "$SOURCE_TREE"
    # Archivos versionados y nuevos no ignorados, tal como quedarían tras un commit.
    git ls-files -co --exclude-standard | while IFS= read -r f; do [ -e "$f" ] && printf '%s\n' "$f"; done \
        | tar -cf - -T - | tar -x -C "$SOURCE_TREE"
    strip_dev_files "$SOURCE_TREE"
else
    echo "[3/6] Preparando rama $BRANCH_RELEASE (copia de trabajo temporal)..."
    git worktree add -q -B "$BRANCH_RELEASE" "$WORKTREE" "origin/$BRANCH_DEV"
    strip_dev_files "$WORKTREE"
    git -C "$WORKTREE" add -A
    git -C "$WORKTREE" commit -q -m "Release $TAG" || true
    git -C "$WORKTREE" push origin "$BRANCH_RELEASE" --force
    echo "    Rama $BRANCH_RELEASE publicada"
    SOURCE_TREE="$TEMP_DIR/tree"
    mkdir -p "$SOURCE_TREE"
    git archive "$BRANCH_RELEASE" | tar -x -C "$SOURCE_TREE"
fi

# ── ZIP en dist/ del workspace, con la carpeta del plugin dentro ─────────────

echo "[4/6] Creando ZIP..."
DIST_DIR="$WORKSPACE_ROOT/dist"
mkdir -p "$DIST_DIR"
ZIP_PATH="$DIST_DIR/$PLUGIN_FOLDER-$VERSION.zip"
rm -f "$ZIP_PATH"

STAGE="$TEMP_DIR/stage"
mkdir -p "$STAGE/$PLUGIN_FOLDER"
cp -R "$SOURCE_TREE"/. "$STAGE/$PLUGIN_FOLDER/"
( cd "$STAGE" && zip -r "$ZIP_PATH" "$PLUGIN_FOLDER" --quiet )

ZIP_LIST="$(unzip -l "$ZIP_PATH")"
FORBIDDEN="$(printf '%s\n' "$ZIP_LIST" | grep -E '_dev/|\.git/|\.claude/|node_modules/|\.env|\.log$|\.DS_Store|deploy-release' || true)"
[ -z "$FORBIDDEN" ] || { echo "$FORBIDDEN"; fail "el ZIP contiene archivos que no deben ir"; }
# Se lee el listado ya guardado: con pipefail, "unzip | grep -q" falla por SIGPIPE aunque el archivo exista.
case "$ZIP_LIST" in *"$PLUGIN_FOLDER/$MAIN_FILE"*) ;; *) fail "el ZIP no contiene $MAIN_FILE" ;; esac
case "$ZIP_LIST" in *"$PLUGIN_FOLDER/readme.txt"*) ;; *) fail "el ZIP no contiene readme.txt" ;; esac
ZIP_FILES="$(printf '%s\n' "$ZIP_LIST" | tail -1 | awk '{print $2}')"
echo "    $ZIP_PATH ($(du -h "$ZIP_PATH" | cut -f1), $ZIP_FILES archivos) — contenido verificado"

if [ "$DRY_RUN" -eq 1 ]; then
    echo ""
    echo "━━━ Simulacro terminado: todo correcto para publicar $TAG ━━━"
    echo "  Para publicar de verdad: ./_dev/deploy-release.sh"
    exit 0
fi

if [ "$BRANCH_ONLY" -eq 1 ]; then
    echo ""
    echo "━━━ Rama $BRANCH_RELEASE publicada con la versión $VERSION (sin _dev) ━━━"
    echo "  Rama: https://github.com/$REPO/tree/$BRANCH_RELEASE"
    echo "  ZIP local: $ZIP_PATH"
    echo "  La release en GitHub queda pendiente: ./_dev/deploy-release.sh (necesita GITHUB_TOKEN)"
    echo ""
    exit 0
fi

# ── Release en GitHub ────────────────────────────────────────────────────────

echo "[5/6] Creando release en GitHub ($TAG)..."
printf '%s' "$CHANGELOG_BODY" > "$TEMP_DIR/changelog.txt"
RELEASE_JSON="$(python3 - "$TEMP_DIR/changelog.txt" "$TAG" "$BRANCH_RELEASE" <<'PYEOF'
import json, sys
body = open(sys.argv[1]).read().strip()
print(json.dumps({
    'tag_name': sys.argv[2],
    'target_commitish': sys.argv[3],
    'name': sys.argv[2],
    'body': body,
    'draft': False,
    'prerelease': False,
}))
PYEOF
)"

HTTP_CODE="$(curl -sS -o "$TEMP_DIR/release.json" -w '%{http_code}' -X POST \
    -H "Authorization: token $GITHUB_TOKEN" \
    -H "Accept: application/vnd.github+json" \
    -H "Content-Type: application/json" \
    "https://api.github.com/repos/$REPO/releases" \
    -d "$RELEASE_JSON")"
[ "$HTTP_CODE" = "201" ] || { cat "$TEMP_DIR/release.json"; echo; fail "GitHub respondió $HTTP_CODE al crear la release"; }

RELEASE_ID="$(python3 -c "import json,sys; print(json.load(open(sys.argv[1])).get('id',''))" "$TEMP_DIR/release.json")"
[ -n "$RELEASE_ID" ] || fail "no se pudo leer el ID de la release"
echo "    Release ID: $RELEASE_ID"

echo "[6/6] Subiendo $ASSET_NAME..."
HTTP_CODE="$(curl -sS -o "$TEMP_DIR/asset.json" -w '%{http_code}' -X POST \
    -H "Authorization: token $GITHUB_TOKEN" \
    -H "Content-Type: application/zip" \
    --data-binary @"$ZIP_PATH" \
    "https://uploads.github.com/repos/$REPO/releases/$RELEASE_ID/assets?name=$ASSET_NAME")"
[ "$HTTP_CODE" = "201" ] || { cat "$TEMP_DIR/asset.json"; echo; fail "GitHub respondió $HTTP_CODE al subir el ZIP (la release $TAG ya existe sin ZIP: súbelo a mano o bórrala)"; }

echo ""
echo "━━━ Release $TAG publicada ━━━"
echo "  Release:  https://github.com/$REPO/releases/tag/$TAG"
echo "  ZIP:      https://github.com/$REPO/releases/download/$TAG/$ASSET_NAME"
echo "  Local:    $ZIP_PATH"
echo ""
echo "Los sitios con el plugin instalado verán la actualización en Escritorio › Actualizaciones"
echo "(el actualizador consulta GitHub como máximo cada hora; 'Buscar de nuevo' fuerza la consulta)."
echo ""
echo "Próximos pasos:"
echo "  1. En $BRANCH_DEV, sube el desarrollo a $VERSION.1 (cabecera, constante y CHANGELOG)."
echo "  2. Limpia _dev/contexto-activo.md y pasa lo pendiente a roadmap.md."
echo ""
