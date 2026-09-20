=== VFWoo Webkul POS Bridge ===
Contributors: verifacwoo
Tags: woocommerce, pos, verifactu, receipts, webkul
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 0.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integra los datos fiscales de Veri*Fac*WOO en los tickets del WooCommerce Point of Sale de Webkul.

== Descripción ==

Este plugin es un puente extensible. Lee el contrato público de ticket de
VFWoo y lo incorpora a los pedidos y tickets de Webkul mediante sus puntos de
extensión. Nunca asigna números fiscales, envía registros a Hacienda ni
modifica archivos del núcleo de VFWoo o Webkul.

Webkul conserva su editor de plantillas, la selección por outlet y el flujo
habitual de impresión. El bridge no instala un catálogo paralelo de plantillas.

== Funciones ==

* Ticket fiscal con número de factura, QR y leyenda legal, en la venta y en la reimpresión. El QR va incrustado para que salga a la primera impresión.
* Variables `${vfwoo_*}` de VFWoo (tienda, factura y cliente) para las plantillas del ticket. Los datos del cliente solo salen en F1 y F3; en F2 se ocultan.
* Filtros del catálogo del POS por marca u otras taxonomías de producto, que se eligen en `POS → Settings → VFWoo Bridge`, y aviso en el POS cuando hay que recargar el catálogo.
* Clientes del POS con DNI/NIE/CIF obligatorio y validado (formato y censo de la AEAT); con NIF se emite factura completa (F1).
* El POS impide cobrar por encima del límite de la factura simplificada sin un cliente con NIF.
* Factura completa (F3) desde el detalle de un pedido F2 confirmado.
* «Marcas» junto a «Todos» en la pantalla principal del POS: las marcas se comportan como subcategorías, con su imagen, y al pulsar una se ven sus productos.
* Pantalla «Marcas» en el POS con las ventas por marca y producto.
* Actualizaciones desde GitHub en Escritorio › Actualizaciones.

== Notas ==

Las devoluciones se hacen en WooCommerce; VFWoo genera la rectificativa.
Antes de usarlo en una tienda real, prueba el flujo en staging con el modo Sandbox de VFWoo.
