# vfwoo-webkul-pos-bridge — reglas propias de este plugin

Estas reglas se suman a las del sistema (`.claude/rules/`). Valen para todo lo que el bridge pinte dentro del POS de Webkul.

## Pantallas dentro del POS: leer `_dev/estilos-pos.md` ANTES de escribir nada

Resumen obligatorio (el detalle, el procedimiento y los errores ya cometidos están en ese documento):

1. **Reutilizar siempre lo que ya hay de Webkul** (clases, HTML, **ids** y variables de color), **sin preguntar**.
2. **Copiar la pantalla modelo de Webkul entera**, con sus ids y sus `style`: formulario → Clientes «Editar cliente»; lista con detalle → Pedidos; resumen → Pedidos o Informe.
3. **Cero CSS de aspecto.** Solo disposición (flex, grid, anchos, alturas).
4. **No usar `wp.components` ni otros frameworks:** no siguen los ajustes de Apariencia de Webkul (color, modo claro/oscuro, plantillas).
5. **Si hace falta algo que Webkul no tiene: AVISAR, crearlo UNA vez como componente en `assets/js/core.js` (`ui`), documentarlo en `estilos-pos.md` y reutilizarlo** en todas las pantallas. Nunca copiar y pegar un bloque en dos pantallas.
6. **No cambiar un diseño aprobado sin avisar.**
7. **Mínimo código** y **preparado para updates de Webkul**: lo acoplado a sus clases vive en `core.js` y en el documento; si algo falta, la pantalla se ve peor pero no rompe la caja.
8. **Comprobar contra el modelo con números** (fondo, radio, margen, alto, posición), en claro y oscuro y sin scroll horizontal.

## Cómo se prueba

La caja local está en `http://pos.local/pos` (la opción `_pos_endpoint_name` no está definida). La caja guarda el JS y el CSS en caché: `fetch(url, {cache:'reload'})` y recargar dos veces. No se introducen contraseñas: el usuario abre sesión.
