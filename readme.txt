=== VFWoo WCPOS Bridge ===
Contributors: verifacwoo
Tags: woocommerce, pos, verifactu, receipts
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plantillas nativas de WCPOS enriquecidas con los datos fiscales de Veri*Fac*WOO.

== Descripción ==

Este plugin es un puente extensible. Lee el contrato público de ticket de
VFWoo y lo incorpora al snapshot fiscal inmutable mediante el punto de
extensión oficial de WCPOS. Nunca asigna números fiscales, envía registros a
Hacienda ni modifica archivos del núcleo de VFWoo o WCPOS.

Incluye tres plantillas nativas y editables de WCPOS: térmica de 58 mm,
térmica de 80 mm y HTML A4. Se instalan expresamente desde
POS > VFWoo Bridge; WCPOS continúa seleccionándolas e imprimiéndolas con el
flujo habitual de caja.

== Estado de desarrollo ==

La versión 0.1.0 es candidata de implementación. La sintaxis PHP y la revisión
estática de contratos están completadas; antes de distribuirla es necesario
validar la impresión funcional en una instalación activa de WordPress, VFWoo y
WCPOS.
