# Plan: marketplace con una caja (POS) por vendedor

Estado: **idea, sin empezar.** Nada de esto está construido ni probado.

## Contexto

- Un solo WordPress que es un **marketplace con WCFM** (en producción: lazona.coop). Un solo POS de Webkul y una sola licencia.
- Cada vendedor debería tener **su propia caja**, donde vea y gestione **solo sus productos**.
- De momento **el marketplace factura todo** y después factura a cada vendedor aparte. Hay un solo emisor, como funciona VFWoo hoy. Las facturas por vendedor quedan fuera.
- Comisiones, devoluciones y demás **se resuelven según cada caso, más adelante**. Lo que importa ahora es poder montarlo.

## Lo que ya tiene Webkul (confirmado leyendo su código)

- **Tiendas (outlets):** tabla `woocommerce_pos_outlets`. Se pueden crear varias. Cada una tiene su factura, sus métodos de pago y su cajón.
- **Usuarios de caja por tienda:** tabla `woocommerce_pos_outlet_map` (`user_id`, `outlet_id`). Un usuario trabaja con la tienda a la que está asignado. El readme dice que se puede asignar una misma tienda a varios usuarios.
- **Productos por tienda:** tabla `woocommerce_pos_outlet_product_map` (`outlet_id`, `product_id`, `pos_status`, `pos_stock`). La caja solo enseña los productos habilitados en su tienda.
- **Pedidos:** cada venta guarda su tienda en el meta `_wk_wc_pos_outlet`. Hay informes y cajón filtrados por tienda.

## Hallazgo importante: productos nuevos

Por defecto, **todo producto nuevo se habilita en TODAS las tiendas.** El ajuste es `_pos_product_default_status` (valor por defecto `enabled`), y lo aplica `wkwcpos_enable_product_in_outlet` en `includes/admin/class-wc-pos-function-handler.php`.

Para un marketplace esto es lo contrario de lo que se quiere: un producto de un vendedor aparecería en la caja de todos los demás. Hay que poner ese ajuste en **`disabled`** y que el gestor asigne cada producto solo a la tienda de su vendedor.

## Qué hay que montar para cada vendedor

1. **Una tienda (outlet)** con su nombre y ajustes.
2. **Un usuario de caja** (el vendedor o su empleado) asignado a esa tienda.
3. **Sus productos** habilitados en esa tienda y en ninguna otra.

Hoy se puede hacer a mano desde el administrador de Webkul.

## Fases

| Fase | Qué se hace | Código |
|---|---|---|
| 0. Prueba a mano | Con un vendedor de ejemplo: crear su tienda y su usuario, poner el ajuste de productos nuevos en `disabled`, asignarle sus productos y comprobar que su caja solo le enseña los suyos. | No |
| 1. Gestor | Pantalla en el administrador que, al dar de alta un vendedor, crea su tienda, vincula su usuario y asigna sus productos. | Sí |
| 2. Sincronía | Que los productos que el vendedor crea o borra se reflejen solos en su tienda. | Sí |
| 3. Detalles | Comisiones, devoluciones, stock, facturas por vendedor, según cada caso. | Según caso |

La fase 0 no necesita código y decide si merece la pena seguir.

## Por comprobar (sin verificar)

- **Cómo marca WCFM de quién es cada producto.** Suposición: por el autor del producto. Hay que verlo en el código de WCFM, que no está instalado en la copia local.
- **Qué rol de usuario necesita la caja** y cómo lo asigna Webkul. No lo he mirado.
- **Si WCFM calcula la comisión de los pedidos creados desde el POS.** Los pedidos del POS son pedidos normales de WooCommerce, pero no sé cuándo se engancha WCFM. Pendiente, según cada caso.
- **Si la licencia de Webkul limita agentes o tiendas.** No lo he mirado.

## Riesgos y límites ya conocidos

- Con un solo stock compartido con la web, el POS **no deja cobrar sin conexión** (stock centralizado).
- Un solo emisor de facturas: si más adelante cada vendedor debe facturar con su NIF, es un cambio grande.
- Probar en una copia de pruebas con WCFM instalado, no directamente en lazona.coop.

## Antes de empezar

1. Una copia de pruebas con WCFM (o descargar su código para leerlo, con OK del usuario).
2. Un vendedor de ejemplo con unos pocos productos.
3. Confirmar el rol de la caja y la licencia de Webkul.
