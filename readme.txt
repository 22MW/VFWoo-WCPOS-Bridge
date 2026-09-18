=== VFWoo Webkul POS Bridge ===
Contributors: verifacwoo
Tags: woocommerce, pos, verifactu, receipts, webkul
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 0.1.0
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

== Estado de desarrollo ==

La integración está en validación local. La sintaxis PHP y JavaScript está
comprobada; la impresión inicial y la reimpresión con QR real siguen pendientes
de QA funcional.
