# Prompt para el desarrollador de VFWoo: filtro `vfwoo_simplified_limit`

> Copiar este documento entero en la sesión de trabajo del plugin **VFWoo** (repositorio `22MW/vfwoo`, workspace `Local Sites/vfwoo`). Es autosuficiente: no necesita el contexto del plugin puente.

---

## Contexto

Existe un plugin puente, **VFWoo Webkul POS Bridge**, que conecta VFWoo con el TPV de Webkul (WooCommerce Point of Sale). Una tienda puede tener **la web con el límite de factura simplificada en 400 €** y **la tienda física (TPV) en 3.000 €** (actividades como la venta al por menor). Hoy no se puede: el límite es un solo ajuste global (`authorized_sector`) que vale para todo.

Quien decide si un pedido sale como **F1 o F2** es VFWoo, en un único sitio: `Invoice_Type_Calculator::calculate_from_order()`. Por eso el límite del TPV solo funciona si **VFWoo lo aplica**. Si el puente pusiera 3.000 € pero VFWoo siguiera con 400 €, el TPV dejaría vender 1.000 € sin cliente y VFWoo emitiría una F1 sin NIF, que fallaría.

VFWoo no tiene hoy ningún gancho para cambiar ese límite (comprobado: `NIF_Rules::simplified_limit()` devuelve 400 o 3.000 según `authorized_sector`, sin filtros).

## Objetivo

Añadir un filtro **`vfwoo_simplified_limit`** para que otro plugin pueda cambiar el límite de la factura simplificada **de un pedido concreto**, sin tocar el comportamiento por defecto ni el checkout web.

## Cambio exacto

Archivo: `includes/helpers/class-invoice-type-calculator.php`.

1. En `calculate_from_order( $order )` (hoy: `$simplified_limit = NIF_Rules::simplified_limit( $settings );`), cambiar por:

```php
$simplified_limit = self::limit_for_order( $order, NIF_Rules::simplified_limit( $settings ) );
```

2. Añadir el método privado:

```php
/**
 * Límite de la factura simplificada para un pedido concreto.
 *
 * Un plugin puede cambiarlo con el filtro `vfwoo_simplified_limit` (por ejemplo, un TPV con actividad
 * de venta al por menor a 3.000 € mientras la web sigue en 400 €). Solo se aceptan los importes que
 * reconoce la norma; cualquier otro valor, o un valor no numérico, se ignora y vale el ajuste de VFWoo.
 *
 * @param \WC_Order $order   Pedido.
 * @param float     $default Límite según el ajuste `authorized_sector`.
 * @return float
 */
private static function limit_for_order( $order, $default )
{
    $default = (float) $default;
    $limit   = apply_filters( 'vfwoo_simplified_limit', $default, $order );

    if ( ! is_numeric( $limit ) ) {
        return $default;
    }

    $limit = (float) $limit;
    return in_array( $limit, array( 400.0, 3000.0 ), true ) ? $limit : $default;
}
```

3. Definir la lista permitida (`400.0` y `3000.0`) de forma que sea fácil cambiarla más adelante (constante de clase), si se prefiere a dejarla en el método.

## Qué NO tocar

- `calculate_from_checkout_data()` (línea ~42, checkout web) **sigue igual**: el límite de la web no cambia.
- `NIF_Rules::is_required_now()` y `NIF_Rules::simplified_limit()` **sin cambios** (los usa el checkout web y el puente para leer el valor base).
- Las rectificativas: ya siguen el tipo de la **factura original ya emitida**, no se recalculan. No cambiar eso.
- Si `enable_simplified_invoice` está desactivado, todo pedido es F1 y el filtro no debe cambiarlo.
- No mezclar este cambio con los 6 cambios sin commitear que hay ahora en la rama `mishaVFWdev` (`CLAUDE.md` borrado, `CLAUDE.md_disabled` y `_dev/contexto-activo.md`, `decisiones.md`, `roadmap.md`, `visual.html` sin añadir): no son de esta tarea. Commit solo de los archivos de este cambio.

## Documentación

- Añadir `vfwoo_simplified_limit` a la tabla de filtros de extensión de `_dev/docs/HOOKS.md`: firma `(float $limite, \WC_Order $pedido)`, devuelve `400.0` o `3000.0`, ámbito solo emisión de facturas de pedidos (no el checkout web), y que un valor no válido se ignora.
- Nota en el `CHANGELOG.md` y subida de versión según el proceso de VFWoo.

## Pruebas (sandbox, sin facturas reales)

Con un pequeño filtro de prueba que devuelva `3000.0` solo para pedidos con la meta `_wk_wc_pos_outlet` (los del TPV):

| Caso | Esperado |
| --- | --- |
| Sin filtro, pedido de 1.000 € sin NIF (`authorized_sector` desactivado) | F1 (comportamiento actual) |
| Con filtro, pedido del TPV de 1.000 € sin NIF | **F2** |
| Con filtro, pedido del TPV de 2.999,99 € sin NIF | F2 |
| Con filtro, pedido del TPV de 3.000 € sin NIF | F1 (`total >= límite`) |
| Con filtro, pedido **web** de 1.000 € | F1 (el filtro no lo afecta) |
| Filtro que devuelve `500`, `"abc"`, `null` o `false` | Se ignora: vale el ajuste de VFWoo |
| `enable_simplified_invoice` desactivado + filtro a 3.000 | Siempre F1 |
| Pedido con NIF de un cliente | F1 en cualquier caso |
| `Emission_Check::comprobar()` de un pedido con filtro | Coincide con el tipo que se emite |
| Devolución de un pedido F2 del TPV emitido con el filtro | R5 (sigue a la factura original) |
| Checkout web con el carrito de 1.000 € | Igual que antes: pide NIF |

## Criterios de aceptación

- El filtro existe, está documentado y tiene el comportamiento de la tabla.
- Sin ningún plugin que lo use, VFWoo se comporta **exactamente igual** que antes.
- No cambia el checkout web ni las rectificativas.
- Versión publicada según el proceso de VFWoo.

## Qué devolver al final

**El número de versión de VFWoo** que incluye el filtro (por ejemplo `0.9.18`), y la confirmación de que pasan las pruebas. El puente la usará como versión mínima: con una versión anterior de VFWoo, el ajuste del límite del TPV quedará desactivado con un aviso, para que nadie ponga un límite que VFWoo no aplicaría.
