# Plan: cliente, NIF y tipo de factura (F1/F2) en el POS

Estado: decisiones tomadas; **fases 1-3 implementadas y confirmadas en local**. Basado en lectura de código de Webkul POS 7.1.1 y VFWoo.

## Objetivo

1. Poder vender sin elegir cliente (tienda local, España): factura simplificada F2, sin datos del cliente.
2. Si se elige cliente: factura completa F1 con NIF/DNI/NIE **verificado antes de aceptar la venta**, con error claro si no es válido.
3. Quien compra por debajo del límite pero pide F1 (autónomo/empresa) puede tenerla: se elige cliente con NIF.

## Evidencia (leído en código)

### Webkul
- Pay solo funciona si hay `customers.default[0].id`; el servidor exige `customer_id` para crear el pedido. No existe «invitado» real: la vía nativa es el **cliente por defecto**.
- Cliente por defecto: `wp-admin → POS → Settings → pestaña Customer settings` (`admin.php?page=wc-pos-settings&tab=customer-settings`). El formulario exige email, nombre, apellidos, contraseña y teléfono (9-12 dígitos). Alternativa: `Usuarios`, pasar el ratón por un usuario con rol `customer` → «Set Pos Default Customer».
- Formulario de cliente del POS (React, campos fijos): ganchos `wkwc_add_custom_field_in_form_after_email` y `wkwc_add_custom_field_in_address_group`. Envía `serializeArray()` de todo el formulario; el servidor llama `wkwc_add_meta_customer_data( $data, $user_id )` al crear/actualizar. Filtro `wkwcpos_modify_customer_update_request`. La validación del formulario está fija en el bundle (sin gancho).
- Pedido: `wkwcpos_alter_pos_order` (justo tras `wc_create_order`). Webkul **no copia el NIF** al pedido y rellena la dirección de facturación con la del **local**, no la del cliente.
- Pay: filtro JS `wkwcpos_allow_pay_btn_add_product_in_cart`.
- Datos de cliente hacia el POS: `manage_custom_customer_details_support`. El POS cachea clientes en IndexedDB.

### VFWoo
- `Invoice_Type_Calculator::requiere_factura_completa`: si el pedido tiene NIF (`_billing_nif`, o `_shipping_nif` si los impuestos se basan en envío) la operación exige **F1 sea cual sea el importe**. Sin NIF y por debajo del límite: F2. También fuerza F1 la marca `_billing_factura_ampliada`. **No hace falta cambiar VFWoo para el paso F2→F1.**
- Límite de simplificada: 400 € (3.000 € sector autorizado): `NIF_Rules::simplified_limit`.
- Claves NIF: pedido `_billing_nif`, usuario `billing_nif`.
- `NIF_Format::is_valid( $valor )`: valida formato (NIF, NIE, CIF, VAT). Público.
- `NIF_Census::validate( $nif, $nombre )`: consulta el censo AEAT vía `api.verifacti.com/nifs/validar`, timeout 5 s. **Falla en abierto**: sin clave de API o con error de red devuelve `valid = true`. Público, pero no aparece como contrato documentado.
- `Emission_Check::comprobar( $order_id )`: construye el payload igual que al emitir y valida sin enviar nada ni tocar el pedido. Necesita un pedido ya creado.
- `Vitamin_Bridge` (documentado): `emitir`, `anular`, `subsanar`, `corregir_nif`, `poll_estado`. No tiene método de comprobar NIF.

## Diseño propuesto

- **Verificar al guardar el cliente, no al cobrar.** La consulta al censo tarda hasta 5 s: no puede ir en el momento del cobro. El NIF queda verificado en el cliente (hash de NIF+nombre y fecha en user meta); si cambia el NIF o el nombre, deja de estar verificado.
- **Al cobrar** solo se comprueba localmente: cliente distinto del por defecto ⇒ NIF verificado. Sin consulta de red.
- El **cliente por defecto** (Cliente mostrador) no lleva NIF ⇒ F2. Nunca se copia NIF al pedido.
- Importe ≥ límite de simplificada con cliente por defecto: bloquear Pay con mensaje «requiere cliente con NIF».
- Puente VFWoo: usar `NIF_Format` y `NIF_Census` con `class_exists` y degradación segura. Opcionalmente pedir a VFWoo un método documentado (ver Fase 7).

## Fases

| # | Fase | Qué | Código |
|---|---|---|---|
| 0 | Cliente por defecto | Crear «Cliente mostrador» (sin NIF) y marcarlo por defecto en `POS → Settings → Customer settings`. | No |
| 1 | Campo NIF (hecho, sin probar) | Campo DNI/NIE en el formulario de cliente del POS; guardado en `billing_nif`. | Bridge |
| 2 | Verificación (hecho, sin probar) | Al guardar cliente: formato + censo (nombre = nombre y apellidos). Error claro al cajero; marca de verificado. | Bridge |
| 3 | NIF al pedido (hecho, sin probar) | Copiar `billing_nif` a `_billing_nif` del pedido si el cliente no es el por defecto. VFWoo emite F1. | Bridge |
| 4 | Bloqueo en Pay (hecho en cliente, confirmado en local) | Importe ≥ límite sin cliente con NIF: parar la venta en el botón Pay y en el botón final de pago (D-017). Sin guarda de servidor. | Bridge |
| 5 | Ticket (hecho, sin probar) | Variables `${vfwoo_*}` en el editor de plantillas; F2 sin datos del cliente, F1/F3 con datos (D-016). | Bridge |
| 6 | Aviso post-venta | `Emission_Check::comprobar` tras crear el pedido; solo aviso, no bloquea. | Bridge |
| 8 | Herramienta comprobar y añadir NIF | Lista de clientes sin NIF o sin verificar y forma de completarlos. | Bridge |
| 7 | Puente en VFWoo (opcional) | Método documentado en `Vitamin_Bridge` para validar NIF+nombre. **Es otro plugin: requiere autorización aparte.** | VFWoo |

## Decisiones del usuario (2026-09-19)

- **Tipos de identificador:** todos (DNI, NIE, CIF y NIF-IVA europeo). Se valida el formato con `NIF_Format::is_valid`.
- **Censo AEAT sin respuesta:** se guarda el cliente como **«sin verificar»** con aviso. Pero desde el POS **no se debe aplicar exención de IVA** (VIES) ni nada parecido: esa lógica de VFWoo vive en el checkout web y el bridge no la activa. Por eso el censo/VIES se consulta solo para NIF españoles.
- **Cliente antiguo sin NIF:** no da error si el importe está por debajo del límite: se vende igual que con el cliente por defecto (F2, sin NIF en el pedido). Por encima del límite sí debe salir un mensaje de error (fase 4). Hace falta una **herramienta para comprobar y añadir** NIF a clientes existentes (fase 8).
- **Clientes de fuera de España:** de momento no.
- **Comprobación previa:** si es F1, se comprueba antes de aceptar la venta (se hace al guardar el cliente; ver diseño) y se devuelve el error desde VFWoo. Como VFWoo no tiene un método documentado para esto, el bridge usa `NIF_Census::validate` y `NIF_Format::is_valid` con degradación segura (fase 7 opcional: método documentado en VFWoo, requiere autorización aparte).

## Implementado (sin probar en runtime)

`includes/class-customer-integration.php`, más el campo en `assets/js/pos-bridge.js`:

- **Fase 1:** campo «DNI / NIE / CIF» obligatorio en el formulario de cliente del POS (`wkwc_add_custom_field_in_form_after_email`, elemento con `wp.element`). Se rellena al editar un cliente. Guardado en el usuario como `billing_nif` (clave de VFWoo).
- **Fase 2:** al guardar el cliente (`wkwcpos_modify_customer_update_request`, tras la autenticación de Webkul) se rechaza con mensaje claro si falta, si el formato no es válido o si el censo dice que no coincide con el nombre. Webkul muestra al cajero el mensaje de cualquier excepción lanzada ahí. Si el censo no responde (VFWoo devuelve `valid` sin `resultado`), se guarda sin verificar. La marca de verificado es un hash de NIF+nombre en `_vfwoo_webkul_nif_check`: si cambia el NIF o el nombre, vuelve a «sin verificar». Los datos del cliente hacia el POS incluyen `vfwoo_webkul_nif` y `vfwoo_webkul_nif_status` (`verified`, `unverified`, `missing`).
- **Fase 3:** al crear el pedido (`wkwcpos_alter_pos_order`) se copia `billing_nif` a `_billing_nif` del pedido, salvo para el cliente por defecto y para clientes sin NIF. Con NIF en el pedido VFWoo emite F1.

Antispam: WP Armour Extended bloqueaba `wc_create_new_customer` en el alta desde el POS; el bridge desactiva ese filtro solo en esa petición (D-014).

Notas de diseño: el campo se marca obligatorio en servidor (el formulario de Webkul no permite validar en cliente). Al actualizar cualquier cliente desde el POS se exige el NIF, lo que sirve también como vía para completar clientes antiguos.

## Por confirmar

- Qué tipo de elemento espera cada gancho del formulario de cliente (HTML o componente React).
- Cómo muestra el POS un error devuelto por el servidor al crear un cliente.
- Cómo distinguir en el ticket los datos del cliente para ocultarlos en F2.
- Si `NIF_Census::validate` acepta el nombre completo en el formato que exige la AEAT (persona física: NIF y nombre completo tal como figura).

## Pruebas (sin emitir facturas reales)

- Usar el modo sandbox de VFWoo. El NIF de pruebas es `B75777847` (según la documentación de VFWoo).
- Staging y copia de seguridad antes de tocar pedidos y facturación.
- Casos: venta anónima < límite (F2), venta anónima ≥ límite (bloqueo), cliente con NIF válido (F1), NIF inválido, nombre que no coincide, censo caído, cliente sin NIF, cambio de NIF tras verificar.

## Fase 8 — Herramienta de NIF de clientes existentes (plan, sin implementar)

### Datos reales del sitio local (2026-09-20, solo lectura)

- 619 clientes (rol `customer`); solo 1 tiene `billing_nif` (el de las pruebas, verificado).
- Pedidos del POS: 18 en total, con solo 7 clientes registrados distintos; 2 pedidos con `_billing_nif`.
- Clientes con NIF en algún pedido pero sin NIF en su ficha: 1.
- Conclusión: casi ningún cliente antiguo tiene NIF, pero **casi todos son de la web y no compran en el POS**. Una lista de 619 filas sería ruido; lo útil es empezar por los que compran en el POS.

### Qué ya cubre el POS
Editar un cliente desde el formulario del POS exige el NIF y lo verifica (fases 1-2). La herramienta no sustituye eso: sirve para **revisar en bloque desde wp-admin** y para completar los que aún no se han tocado.

### Diseño propuesto

1. **Dónde:** bloque nuevo en `POS → Settings → VFWoo Bridge`.
2. **Lista** con pestañas: *Con compras en el POS* (por defecto), *Sin NIF*, *Sin verificar* y *Todos*; buscador por nombre o email; 25 por página. Columnas: cliente, email, última compra en el POS, estado del NIF (sin NIF / sin verificar / verificado), domicilio (completo o no), campo NIF y botón **Guardar y verificar**, y enlace a su ficha de usuario.
3. **Guardar y verificar:** misma validación que el POS (formato, censo AEAT para NIF españoles, hash de NIF+nombre). Se extrae un único método compartido en `Customer_Integration` para que el POS y wp-admin no diverjan. Resultado con mensaje claro; si el censo no responde, «sin verificar» con aviso.
4. **Sugerencia de NIF:** si el cliente tiene un `_billing_nif` en pedidos anteriores (por ejemplo del checkout web de VFWoo), se rellena el campo como propuesta con el número de pedido de origen. **Nunca se guarda solo**: el usuario confirma.
5. **Seguridad:** `manage_woocommerce`, nonce, saneado, sin acciones por GET. Un formulario por fila con recarga (sin JavaScript nuevo), salvo que se prefiera AJAX en línea.
6. **Caché del POS:** al guardar un NIF, la caché de clientes de las cajas queda vieja. Subir la versión de catálogo y cambiar el aviso del POS para que hable de «Resync» en general (clientes y productos).
7. **Trazabilidad:** guardar quién y cuándo cambió el NIF (`_vfwoo_webkul_nif_by`, fecha).
8. **Fuera de alcance:** fusionar duplicados, borrar clientes, editar domicilios (se enlaza a la ficha de usuario).

### Decisiones pendientes del usuario
- ¿Lista por defecto solo con clientes con compras en el POS, o todos?
- ¿Sugerir el NIF de pedidos anteriores?
- ¿Formulario por fila con recarga, o edición en línea con AJAX?
- ¿Columna de domicilio, dado que una F1 lo lleva?
- ¿Botón «Verificar todos los pendientes» por lotes (el censo tarda hasta 5 s por cliente), o más adelante?

### Validación prevista
`php -l`, `git diff --check`, prueba con clientes de distintos estados (sin NIF, con NIF válido, NIF inválido, nombre que no coincide, censo caído), sin emitir facturas.
