# Qué hace este plugin, en cristiano

## En una frase

Tu caja (el TPV) y tu programa de facturas (VFWoo) son dos herramientas distintas y no se hablaban. Este plugin las conecta para que **cada venta en la tienda física salga con su factura correcta**, sin que tengas que hacer nada a mano.

**Lo que no hace:** no emite facturas él solo (las emite VFWoo), no habla con Hacienda (lo hace el módulo Verifactu) y no toca precios, stock ni cobros.

## Qué necesita y qué activa cada cosa

El plugin solo depende de la caja (Webkul POS). Lo que añade con las facturas **se enciende solo cuando existe lo que necesita**:

| Si tienes… | Tienes además… |
|---|---|
| Solo la caja | Marcas, filtros, accesos de la cabecera, aviso de catálogo, informes por marca |
| **VFWoo Invoices** (el core, gratuito) | Número y fecha de factura en el ticket, plantilla de ticket propia, botón **Factura PDF**, PDF adjunto al correo, **Factura completa** a nombre del cliente, NIF del cliente (opcional) |
| **VFWoo Invoices + Verifactu con su módulo activado** | **QR y frase legal** en el ticket, tipo de factura, **NIF obligatorio**, **límite de venta de 3.000 €**, comprobación del nombre contra el censo de Hacienda y la **F3** |

**Ojo:** con Verifactu cuenta el **módulo activado** en VFWoo (pestaña Módulos), no solo el plugin instalado. Con el módulo apagado, la caja se comporta como si no lo tuviera.

## Tres palabras que conviene saber

- **Factura simplificada (F2):** el ticket de toda la vida. No lleva datos del cliente. Con Verifactu solo vale por debajo del **límite de 3.000 €** (el que corresponde a la venta al por menor, el «sector autorizado»). La caja lo aplica siempre: no depende de los ajustes de VFWoo. Si tu actividad no lo permite, consúltalo con tu gestor.
- **Factura completa (F1):** lleva los datos del cliente (nombre o empresa y DNI/NIE/CIF). Con Verifactu es obligatoria a partir del límite, y siempre si el cliente la pide.
- **Factura completa posterior:** sirve para cuando alguien **ya se llevó su ticket** y después pide factura con sus datos, a su nombre o al de su empresa.

## Qué pasa en una venta

1. **Sin cliente** (el «Cliente mostrador»): sale el ticket, con el número de factura y, **con Verifactu**, el **código QR** y la frase legal. No aparece ningún dato de cliente.
2. **Con un cliente que tiene DNI/NIE/CIF:** sale una factura completa con sus datos, aunque la compra sea pequeña.
3. **Con Verifactu, si la venta llega a 3.000 € y no hay cliente con DNI:** el TPV **no te deja cobrar** y te avisa de que elijas un cliente. (El aviso funciona en la pantalla del TPV; una venta hecha sin conexión y enviada después no pasa por esa comprobación.)

## Todo lo que hace

- **Ticket con número de factura** (y QR con Verifactu). Sale a la primera, al cobrar y al reimprimir.
- **Tu ticket a tu manera.** O dejas que el plugin use **su propia plantilla**, con tus datos de VFWoo (logo al 50 % del ancho, razón social, NIF, dirección…), o usas las etiquetas nuevas en **Point of Sale → Invoice Templates**.
- **Factura PDF desde la caja.** En el detalle del pedido, un botón imprime el PDF de la factura sin salir del TPV. Tarda unos segundos en prepararse.
- **PDF adjunto al correo.** El botón **Send order email** manda el correo con el PDF de la factura, cada vez.
- **Factura completa después de la venta.** En **Pedidos**, abres la venta y pulsas **Factura completa**. Eliges o creas el cliente (persona o **empresa**, con domicilio y **país**, España por defecto) y listo:
  - **Sin Verifactu**, el ticket **pasa a ser la factura completa con el mismo número**. Si te equivocas de cliente, lo cambias.
  - **Con Verifactu**, se emite la **F3** que sustituye al ticket. Una factura ya enviada a Hacienda con cliente no se cambia sin rectificarla, y **todavía no se puede a un cliente de otro país**.
- **DNI/NIE/CIF del cliente.** Obligatorio solo con Verifactu. Con España se comprueba que esté bien escrito (y con Verifactu, el nombre contra Hacienda). Con **otro país no se comprueba nada** por defecto (cabe un pasaporte), y hay una casilla **«No comprobar el documento»** para cambiarlo.
- **Ventas por marca.** Una pantalla nueva, **Marcas**: hoy, ayer, esta semana, este mes o unas fechas.
- **Marcas junto a «Todos»** en la pantalla principal, como subcategorías con su imagen, y **filtros** por marca u otras clasificaciones.
- **Aviso cuando el catálogo cambia** para recargar los productos (**Resync → Products**).
- **Accesos en la cabecera del TPV** (Productos, Informes, Análisis, Tienda online) y botón **POS-CAJA** en la barra de administrador.
- **Preparado para actualizarse** desde Escritorio › Actualizaciones.

## Lo que tienes que hacer tú

- Crear **un cliente por defecto** («Cliente mostrador»), sin DNI. Se hace en **Usuarios**: crea el usuario con rol Cliente y, pasando el ratón por su fila, pulsa **Set Pos Default Customer**.
- Si el aviso dice que el catálogo cambió, pulsar **Resync → Products** en cada caja.
- **Con Verifactu,** tener activado **«Sector autorizado»** para que el tipo de factura coincida con el límite de 3.000 € de la caja.

## Ten en cuenta

- **Factura completa sin Verifactu:** el ticket conserva su número y cambia los datos del cliente, así que el PDF original, a nombre del cliente de mostrador, deja de existir tal cual; solo queda una nota en el pedido. Si necesitas que la factura completa se refiera al ticket, confírmalo con tu gestor.
- Con Verifactu, para una factura completa posterior el ticket tiene que estar **confirmado**. Si aún no lo está, el TPV te lo dice y basta esperar un momento.
- Las **devoluciones** se hacen en WooCommerce, como siempre; VFWoo crea la factura rectificativa. Conviene probarlo antes de depender de ello.
- Si pones las etiquetas nuevas en el ticket, **no desactives el plugin** sin quitarlas antes: el ticket dejaría de imprimirse.
- Antes de usarlo con clientes reales, haz unas ventas de prueba con Verifactu en modo de pruebas (Sandbox).
