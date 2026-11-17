# Plan: límite de factura simplificada propio del POS

Estado: **plan, sin implementar**. Fecha: 2026-09-20. Depende de un cambio en VFWoo (otro plugin, lo hace el usuario en su workspace).

## Objetivo

Que la tienda pueda tener el límite de la factura simplificada **en 3.000 € en el TPV** (venta al por menor) y **en 400 € en la web**, o al revés. Hoy hay un único límite para todo.

## Decisiones del usuario (2026-09-20)

| Pregunta | Respuesta |
|---|---|
| ¿Filtro en VFWoo o ajuste por canal en su pantalla? | **Filtro.** Se crea en VFWoo; el bridge lo usa. |
| ¿El límite del POS solo admite 400 o 3.000, o cualquier importe? | Respondió «sí». **Interpretación aplicada: solo 400 € o 3.000 €** (son los dos importes de la norma). *Por confirmar:* si se quería cualquier importe, cambia solo la lista permitida. |
| ¿Quién hace el cambio de VFWoo? | **El usuario, en su workspace de VFWoo.** Avisará cuando esté aplicado. |
| Prompt para el desarrollador de VFWoo | Escrito: `temp/prompt-vfwoo-filtro-limite.md`. |

## Evidencia (leída en el código)

- El límite lo da `NIF_Rules::simplified_limit()`: 3.000 si `authorized_sector`, 400 si no. Sin filtros. Un solo ajuste global.
- El tipo F1/F2 de un pedido lo decide un único sitio: `Invoice_Type_Calculator::calculate_from_order()` (`total >= límite` ⇒ F1). El checkout web usa otra función (`calculate_from_checkout_data`).
- El bridge **no puede forzar F2** por encima del límite: solo puede pedir F1 poniendo NIF. Por eso VFWoo debe aplicar el límite.
- Las rectificativas siguen el **tipo de la factura original ya emitida**, no recalculan: cambiar el límite no altera devoluciones antiguas.
- Webkul guarda la marca de pedido del POS (`_wk_wc_pos_outlet`) **antes** de completar el pedido (líneas 1006 y 1017 de `class-wkwcpos-api-create-order.php`), que es lo que dispara la emisión de VFWoo: el filtro podrá reconocer un pedido del POS en ese momento.
- Repositorio de VFWoo: `Local Sites/vfwoo`, rama `mishaVFWdev`, versión `0.9.17.2`. La copia del sitio POS (0.9.17) no tiene `.git`: es una copia instalada. Hay 6 cambios sin commitear en ese repositorio que **no** son de esta tarea.

## Fases y orden

**Fase A — VFWoo (usuario).** Añadir el filtro `vfwoo_simplified_limit( $limite, $pedido )` en `calculate_from_order`, aceptando solo 400 o 3.000, sin tocar el checkout web ni las rectificativas. Documentarlo en `HOOKS.md`, subir versión y publicar. Todo el detalle y las pruebas están en el prompt. **Entrega:** el número de versión de VFWoo.

**Fase B — Bridge (yo, cuando el usuario confirme la fase A).**
1. **Ajuste** `vfwoo_webkul_pos_simplified_limit` en `POS → Settings → VFWoo Bridge`: un selector con «Usar el de VFWoo (X €)» (por defecto), «400 €» y «3.000 €». Con nonce y permisos como el resto de ajustes.
2. **Un único cálculo del límite del POS**, usado a la vez por el filtro y por la parada de venta, para que no puedan diverger: si el ajuste está vacío o VFWoo no soporta el filtro, vale el de VFWoo.
3. **Enganche al filtro** `vfwoo_simplified_limit`: solo para pedidos del POS (meta `_wk_wc_pos_outlet`; si llega una devolución, se mira su pedido padre). Los pedidos web no se tocan.
4. **Compatibilidad:** versión mínima de VFWoo (la que devuelva la fase A). Con una anterior, el selector queda desactivado con un aviso «Requiere VFWoo ≥ X» y se usa el límite de VFWoo. Así nunca se acepta un límite que VFWoo no aplicaría.
5. **Parada de venta (D-017):** `sale_rules()` lee el límite efectivo del POS; los mensajes ya muestran el límite que recibe.
6. Documentación: `que-hace-el-plugin.md` (la nota «el límite es el mismo para web y TPV» pasa a explicar el ajuste), `guia-cambios.md`, `decisiones.md`, `roadmap.md`, CHANGELOG y readme.
7. Versión de desarrollo del bridge y, cuando se pruebe, release.

**Fase C — Pruebas en sandbox (usuario y yo).**
- TPV, ajuste en 3.000 €: venta de 1.000 € sin cliente ⇒ F2 y no se para; venta de 3.000 € o más sin cliente ⇒ se para; con cliente con NIF ⇒ F1.
- Web con 400 €: pedido de 1.000 € ⇒ F1 y el checkout pide NIF.
- Ajuste «Usar el de VFWoo»: comportamiento idéntico al actual.
- VFWoo antiguo: selector desactivado y aviso.
- Devolución de un pedido del TPV emitido con el límite nuevo ⇒ R5.
- Pedido del TPV **sin conexión** sincronizado después: mismo tipo que uno en línea.

**Orden de publicación:** primero VFWoo, luego el bridge. Nunca al revés.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Bridge con límite 3.000 y VFWoo antiguo: el TPV deja vender sin cliente y VFWoo emite F1 sin NIF | Versión mínima y selector desactivado con aviso (fase B.4) |
| Dos cálculos del límite que divergen (bridge y VFWoo) | El bridge no calcula el tipo: solo le da a VFWoo el límite, y la parada de venta usa el mismo valor |
| Un pedido no se reconoce como del POS en el momento de emitir | Comprobado que la meta se guarda antes; verificar en la prueba con un pedido real |
| Cambiar de 400 a 3.000 con el TPV en marcha | El cambio solo afecta a ventas nuevas; las emitidas mantienen su tipo |
| Cambios pendientes ajenos en el repositorio de VFWoo | El prompt pide un commit solo con los archivos de esta tarea |

## Por confirmar

- Si «solo 400 o 3.000» era lo que se quería decir con el «sí».
- Que la ruta de venta sin conexión pase por el mismo cálculo.
- La versión de VFWoo que incluya el filtro (la dará el usuario).

## Qué no se hace en este plan

Ajuste por canal dentro de VFWoo, límites arbitrarios, ni modificar el checkout web.
