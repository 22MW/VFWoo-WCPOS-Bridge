# Plan: F3 (factura sustitutiva) y rectificativas desde el POS

Estado: **F3 implementada y confirmada en local (D-018)**; rectificativas por WooCommerce, pendientes de verificar en sandbox. Basado en lectura de código de Webkul POS 7.1.1 y VFWoo, y en `vfwoo/_dev/proyecto/PLAN-F3-SUSTITUTIVA.md`. Nada probado en runtime.

## Objetivo

1. Cliente que compró sin dar NIF (F2) y vuelve pidiendo factura: convertir esa venta en factura completa **F3** desde el POS, con el NIF comprobado antes de enviar.
2. Tras emitirla, al imprimir el pedido sale ya la F3, no la F2.
3. Saber cómo encajan las rectificativas (devoluciones).

## Evidencia

### VFWoo ya tiene la F3
- `Invoice_Helper::emitir_sustitutiva( $invoice_id, array( 'nif' => ..., 'nombre' => ... ) )` (`class-invoice-helper.php`, público, no documentado como contrato): valida que la factura original sea una F2 **aceptada (ROK)**, que no exista ya una F3, que haya NIF y nombre, que el modo sea Sandbox o Producción, contrasta NIF y nombre con el censo de la AEAT, construye el payload y lo envía. Si sale bien, actualiza el pedido (`_billing_nif`, empresa o nombre y apellidos de facturación).
- Reglas del plan de VFWoo: una F3 por F2; **mismo pedido**, segunda fila en la tabla de facturas; solo desde ROK (con la F2 en pendiente el botón no aparece); **la F3 pasa a ser la factura oficial** del pedido para PDF, email y área de cliente; no existe en modo Básico. La F2 no se borra.
- Ya hay un botón «Emitir factura completa (F3)» en el metabox del pedido de WooCommerce (`class-vitamin-order.php`).
- `Invoice_Helper` da prioridad a la F3 al elegir la factura vigente, y `Ticket_Fiscal_Data::for_order` usa esa factura vigente: tras la F3, el bridge recibirá `invoice_type = F3` sin cambios.
- El bridge documentado de VFWoo (`Vitamin_Bridge`) **no** tiene método para F3: solo `emitir`, `anular`, `subsanar`, `corregir_nif` y `poll_estado`.

### Webkul POS
- Detalle de pedido (Orders): `applyFilters( 'wkwcpos_add_after_print_invoice_button', '', order )` pinta lo que devuelvas **justo después del botón «Imprimir factura»**, con el objeto del pedido. Es el sitio para «un botón al lado de imprimir».
- Al reimprimir un pedido del historial, Webkul pide el pedido completo por su ID (ya lo enriquecemos con `enrich_order`), así que al reimprimir llegarán los datos vigentes.
- **El POS no crea reembolsos:** no hay endpoint ni pantalla; solo muestra los reembolsos que ya existen (`total_refund`, `refunds`). En el detalle hay un contenedor vacío `#pos-order-return` para un complemento de devoluciones que aquí no está.

### Rectificativas
- VFWoo las genera a partir de los reembolsos de WooCommerce (`woocommerce_refund_created`, `woocommerce_order_status_refunded`): R5 sobre simplificada, R1 sobre completa.
- Por tanto, un reembolso hecho en el pedido de WooCommerce ya produce su rectificativa. El POS solo lo muestra.

## Diseño propuesto para la F3 en el POS

1. **Botón** «Factura completa (F3)» junto a «Imprimir factura», solo si el pedido es del POS, su factura vigente es **F2 confirmada** (estado OK) y no hay F3. Si la F2 está pendiente, botón desactivado con la explicación «espera a que se confirme» y opción de refrescar el estado (`Vitamin_Bridge::poll_estado`, documentado).
2. **Pantalla emergente** para elegir un cliente registrado (mismo buscador del POS) o escribir NIF y nombre. Al elegir un cliente se rellenan sus datos.
3. **Comprobación antes de enviar:** formato + censo AEAT (misma validación compartida que el formulario de cliente). Error claro si no cuadra.
4. **Envío:** endpoint del bridge `POST vfwoo-webkul/v1/issue-f3`, autenticado con la sesión del POS, que comprueba que el pedido es del POS y llama a `Invoice_Helper::emitir_sustitutiva` con la F2 vigente. Devuelve éxito o el error de VFWoo.
5. **Después:** mensaje «F3 emitida» y ofrecer imprimir. Actualizar el pedido guardado en el POS para que el botón desaparezca.
6. **Ticket F3:** ya muestra datos del cliente (F3 está en `CUSTOMER_TYPES`), pero `customer_block` lee el nombre del **perfil del cliente del pedido** (el cliente mostrador) y no el comprador de la F3. Hay que hacer que, para F3, use el comprador que VFWoo guarda en el pedido (empresa o nombre y apellidos de facturación). Además, `fiscal` debe incluir `confirmed`.
7. **Domicilio:** VFWoo pide solo NIF y nombre para la F3. El domicilio saldría del cliente registrado si se elige uno.

## Rectificativas: opciones

- **A (recomendada de inicio): las devoluciones se hacen en WooCommerce** y VFWoo emite la rectificativa. No hace falta código; hay que **verificarlo en sandbox** con un pedido del POS: devolución total y parcial de una F2 (R5), de una F1 y de una F3.
- **B (más adelante): botón «Devolver» en el POS.** Crearía un reembolso de WooCommerce desde el POS. Toca el cajón de efectivo, el stock y los informes del cajero de Webkul: riesgo alto, hay que estudiarlo aparte.

## Decisiones del usuario (2026-09-20)

- **Quién emite la F3:** cualquier cajero (basta con estar autenticado en el POS).
- **Cliente de la F3:** se **elige o se crea** con las herramientas del POS; no se escriben NIF y nombre sueltos. Un cliente elegido ya es una venta con NIF (F1/F3), así que su NIF viene del perfil (obligatorio y verificado al crearlo); no hace falta guardar nada más en su perfil.
- **Devoluciones:** por WooCommerce por ahora (opción A). Nada que hacer en el POS.
- Sigue en pie: solo desde F2 confirmada, una F3 por F2, sobre el mismo pedido.

## Cómo elegir o crear el cliente (hallazgo)

La pantalla nativa de Clientes del POS **cambia el cliente de la venta en curso** (`handleCustomerChange` despacha `updateDefaultCustomer` y vuelve al inicio). Si se usara para la F3, el cliente elegido se quedaría en el carrito y la **siguiente venta anónima saldría a su nombre**, con su NIF (F1). Es un riesgo de facturación.

Propuesta: una ventana propia del bridge que usa los **mismos servicios del POS** sin tocar el carrito:
- Buscar: `WK_GET_CUSTOMERS_SEARCH_ENDPOINT` (la respuesta ya lleva `vfwoo_webkul_nif` y su estado).
- Crear: `WK_CREATE_CUSTOMER_ENDPOINT` con un formulario corto (nombre, apellidos, teléfono, email, NIF); la validación de NIF del servidor (fases 1-2) se aplica igual.
- Un cliente elegido sin NIF no puede usarse: se pide crearlo o completarlo.
- Alternativa descartada por defecto: usar la pantalla nativa y devolver luego el cliente por defecto. Exige conocer el objeto del cliente mostrador y la acción interna de Webkul, y falla en silencio si algo cambia.

## Riesgos y por confirmar

- `emitir_sustitutiva` es público pero no es un contrato documentado; pedir a VFWoo un método documentado en `Vitamin_Bridge` (otro plugin, autorización aparte).
- La confirmación de la F2 tarda (cron); en el mostrador puede no estar lista al momento.
- Qué ve el cajero si la F3 falla en la AEAT.
- Que la reimpresión tras la F3 muestre F3 (deducido del código, sin probar).
- Rectificativas sobre pedidos con F3: comprobar cómo las trata VFWoo.
- Todo se prueba en sandbox de VFWoo, sin facturas reales.
