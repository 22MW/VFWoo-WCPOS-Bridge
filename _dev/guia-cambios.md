# Qué hace el bridge: guía corta y humana

Plugin: VFWoo Webkul POS Bridge. Conecta VFWoo (facturas Veri*Factu) con el POS de Webkul sin tocar ninguno de los dos.

## 1. Ticket con datos fiscales y QR

- El ticket muestra el número de factura, el tipo (F1/F2), el QR y la leyenda legal.
- El QR va **dentro** del ticket, como imagen incrustada. Así sale a la primera impresión, sin imagen rota.
- La URL de cotejo ya no se imprime en texto.
- Funciona en la venta y en la reimpresión.

## 2. Filtros de productos (marcas y más)

- En el panel de filtros del catálogo aparece una sección por cada taxonomía elegida, por ejemplo **BRANDS**.
- Se eligen en **POS → Settings → VFWoo Bridge** (por defecto solo marcas). No salen las categorías ni los atributos, porque Webkul ya los tiene.
- Se puede marcar varias marcas a la vez y combinarlas con categoría, color o búsqueda.

## 3. Aviso cuando el catálogo está desactualizado

- El POS guarda una copia de los productos en el navegador. Si cambias algo (por ejemplo los filtros), esa copia se queda vieja.
- Al guardar los filtros, o pulsando **Forzar recarga del catálogo** en la misma pantalla, sube un número de versión.
- Cada caja lo comprueba sola (al abrir, al terminar una venta, al cambiar de pantalla y cada 5 minutos). Si su copia es vieja, sale un aviso: **Resync → Products**.
- No borra nada por su cuenta. Lo decide el cajero.

## 4. Clientes con DNI / NIE / CIF

- El formulario de cliente del POS tiene un campo **DNI / NIE / CIF**, **obligatorio**.
- Al guardar, el bridge comprueba:
  1. Que el formato sea válido (DNI, NIE, CIF o NIF-IVA europeo).
  2. Que el NIF español y el nombre coincidan en el censo de la AEAT.
- Si algo falla, el cajero ve un mensaje claro y el cliente no se guarda.
- Si el censo no responde, el cliente se guarda **«sin verificar»** con aviso. Si luego cambia el NIF o el nombre, vuelve a estar sin verificar.
- Desde el POS **no se aplica exención de IVA**.

## 5. Factura simplificada (F2) o completa (F1)

- **Cliente mostrador** (el cliente por defecto, sin NIF): venta anónima, factura simplificada **F2**, sin datos del cliente.
- **Cliente con NIF**: el bridge copia el NIF al pedido y VFWoo emite factura completa **F1**, aunque el importe sea pequeño. Sirve para quien compra poco pero necesita factura como autónomo o empresa.
- **Cliente antiguo sin NIF**: se vende como el cliente mostrador (F2), sin error. Por encima del límite de simplificada (400 €, o 3.000 € en sectores autorizados) hará falta un cliente con NIF; ese bloqueo está pendiente de hacer.

## 6. El cliente por defecto (Cliente mostrador)

- Se crea en **Usuarios → Añadir nuevo** con rol **Cliente**, sin DNI.
- Luego, en la lista de Usuarios, pasa el ratón por su fila y pulsa **Set Pos Default Customer**.
- No uses el formulario **Point of Sale → Settings → Customer → Save Customer**: tiene un fallo de Webkul que, si falla la creación, modifica al usuario con ID 1 (informe para su soporte en `informe-soporte-webkul-default-customer.md`).

## 7. Antispam en el alta de clientes del POS

- El plugin WP Armour Extended bloqueaba el alta de clientes desde el POS («Spamming or your Javascript is disabled»).
- El bridge lo desactiva **solo durante el guardado de un cliente del POS**. En la web sigue activo.

## 8. Pantalla «Marcas» en el POS

- Entrada **Marcas** en el menú de la izquierda del POS.
- Botones **Hoy, Ayer, Esta semana, Este mes** y fechas **Desde / Hasta** (máximo 1 año).
- Por marca: unidades, ventas sin IVA, ventas con IVA, pedidos y devoluciones. Al pulsar una marca se ven **sus productos**. Se puede filtrar por marcas.
- Solo cuenta ventas del POS. La marca es la actual del producto; si un producto tiene varias marcas, cuenta en cada una.

## 9. Variables de VFWoo en el ticket

- En **Point of Sale → Invoice Templates**, la lista «Pre-defined Variables» tiene ahora las variables `${vfwoo_*}`: datos de la tienda tal como están en VFWoo (nombre, NIF, dirección, teléfono, email y logo), datos de la factura (número, tipo, fecha, leyenda legal, URL de cotejo y **QR**) y datos del cliente (nombre, NIF, domicilio, email y teléfono).
- Los datos del cliente **solo salen en F1 y F3**. En F2 salen vacíos, y también se vacían las variables de cliente de Webkul (nombre, apellidos y teléfono).
- Si usas alguna de estas variables, tú decides dónde va cada cosa y el bloque automático de factura se desactiva. Si no las usas, el ticket sigue como antes.
- Cuidado: si una plantilla usa estas variables y desactivas el bridge, el ticket dejará de imprimirse.
- Tras cambiar la plantilla, la caja debe hacer Resync o limpiar sus datos locales para verla.
- El editor de Webkul enseña estas variables como texto (`${vfwoo_qr}`), igual que las suyas, y las guarda sin cambios.

## Qué falta

- Bloquear Pay si hay cliente sin NIF por encima del límite, o venta anónima por encima del límite.
- Aviso después de la venta si la factura tendrá problemas.
- Herramienta para revisar y completar el NIF de clientes existentes.
- Diagnóstico de compatibilidad en Settings.
- Informes por marca dentro de WooCommerce Analytics (opcional, más esfuerzo).

Detalle técnico: `plan-cliente-fiscal.md`, `decisiones.md`, `roadmap.md`.
