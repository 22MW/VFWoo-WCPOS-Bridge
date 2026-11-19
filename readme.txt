=== VFWoo Webkul POS Bridge ===
Contributors: verifacwoo
Tags: woocommerce, pos, verifactu, receipts, webkul
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adapta el WooCommerce Point of Sale de Webkul a una tienda española: ticket y factura con su número, clientes con NIF, PDF de la factura, marcas y más, con VFWoo.

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
* Clientes del POS con DNI/NIE/CIF obligatorio y validado (formato y censo de la AEAT); con NIF se emite factura (F1).
* El POS impide cobrar por encima del límite del ticket sin un cliente con NIF.
* «Cambiar factura» en el detalle de un pedido del POS: con el plugin de facturas, el ticket pasa a ser la factura a nombre del cliente (persona o empresa, de España o de otro país) con el mismo número; con Verifactu enviando a la AEAT se emite la F3 sustitutiva.
* Cambio de estado del pedido desde el POS: solo «Completado», «Anular» y «Devolver», con confirmación; y «Imprimir rectificativa» en los pedidos con reembolso.
* «Marcas» junto a «Todos» en la pantalla principal del POS: las marcas se comportan como subcategorías, con su imagen, y al pulsar una se ven sus productos.
* Pantalla «Marcas» en el POS con las ventas por marca y producto.
* Plantilla de ticket propia (opcional): logo, razón social, NIF, dirección, teléfono, email y pie salen de VFWoo, con número de factura, QR y datos del cliente en facturas. Logo al 50 % del ancho del ticket.
* «Imprimir factura» en el detalle del pedido del POS (imprime el PDF de VFWoo sin salir del POS) y PDF adjunto al correo del botón «Send order email».
* Pestaña «Productos» en el POS (módulo que se puede desactivar): lista con filtros y orden, interruptores «En la tienda» y «En la caja», acciones masivas, edición básica y alta rápida por código de barras, para cajeros con el modo de gestor de tienda; sustituye al acceso «Productos» de la cabecera.
* Accesos en la cabecera del POS a Productos, Informes, Análisis y la tienda online, con un cuadro explicativo al pasar el ratón; botón «POS-CAJA» en la barra de administrador; rol de gestor opcional para los cajeros nuevos.
* Pantalla de login del POS configurable: titular, pie, ocultar la versión de Webkul y modo demo con usuario y contraseña rellenados.
* VFWoo es opcional: el bridge detecta lo que hay instalado (el core de facturas y, con su módulo activado, Verifactu) y enciende cada función solo si existe lo que necesita; el resto sigue funcionando.
* Actualizaciones desde GitHub en Escritorio › Actualizaciones.

== Notas ==

Desde el POS se devuelve el pedido entero («Devolver»); la devolución de un solo producto se hace en WooCommerce. VFWoo genera la rectificativa.
La venta sin conexión depende de Webkul: con inventario centralizado no cobra sin conexión.
Antes de usarlo en una tienda real, prueba el flujo en staging con el modo Sandbox de VFWoo.
