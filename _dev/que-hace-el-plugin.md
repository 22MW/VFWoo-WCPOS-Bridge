# Qué hace este plugin, en cristiano

## En una frase

Tu caja (el TPV) y tu programa de facturas (VFWoo) son dos herramientas distintas y no se hablaban. Este plugin las conecta para que **cada venta en la tienda física salga con su factura correcta**, sin que tengas que hacer nada a mano.

**Lo que no hace:** no emite facturas él solo (las emite VFWoo), no habla con Hacienda (lo hace VFWoo) y no toca precios, stock ni cobros.

## Tres palabras que conviene saber

- **Factura simplificada (F2):** el ticket de toda la vida. No lleva datos del cliente. Solo vale por debajo de un **límite**, que depende de tu actividad:
  - **3.000 €** si tu actividad está entre las que lo permiten (por ejemplo la **venta al por menor**, lo habitual en una tienda física). En VFWoo se marca con el ajuste **«sector autorizado»**.
  - **400 €** en los demás casos.
  Si no estás segura de cuál te corresponde, confírmalo con tu gestor. Con el límite en 3.000 €, una venta de menos de 3.000 € sin cliente sale como ticket simplificado sin ningún problema; a partir de 3.000 € el TPV pedirá un cliente con DNI.
- **Factura completa (F1):** lleva los datos del cliente (nombre y DNI/NIE/CIF). Es obligatoria a partir del límite, y también si el cliente la pide.
- **Factura completa posterior (F3):** sirve para cuando alguien **ya se llevó su ticket simplificado** y después pide factura con sus datos. No se borra ni se corrige el ticket: se emite una **factura nueva y completa que lo sustituye**, y esa pasa a ser la factura de la venta. Solo se puede hacer si el ticket ya está confirmado por VFWoo, y una sola vez por venta.

## Qué pasa en una venta

1. **Sin cliente** (el que llamas «Cliente mostrador»): sale el ticket simplificado, con el número de factura, un **código QR** para comprobarla en Hacienda y la frase legal. No aparece ningún dato de cliente.
2. **Con un cliente que tiene DNI/NIE/CIF:** sale una factura completa con sus datos, aunque la compra sea pequeña.
3. **Si la venta llega al límite y no hay cliente con DNI:** el TPV **no te deja cobrar** y te avisa de que elijas un cliente. Así se evita emitir una factura que Hacienda pueda rechazar. (El aviso funciona en la pantalla del TPV; una venta hecha sin conexión y enviada después no pasa por esa comprobación.)

## Todo lo que hace

- **Ticket con QR.** Sale a la primera, sin imagen rota, al cobrar y al reimprimir.
- **DNI/NIE/CIF obligatorio al crear un cliente en el TPV.** Comprueba que esté bien escrito y, para españoles, que el nombre coincida con el de Hacienda. Si Hacienda no responde en ese momento, el cliente se guarda «sin verificar» y te lo dice.
- **Te para la venta cuando hace falta un cliente** (lo de arriba). Si el cliente no tiene DNI y la venta es pequeña, se vende como simplificada sin problema.
- **Factura completa después de la venta (F3).** Si alguien compró sin dar sus datos y vuelve pidiendo factura: en **Pedidos**, abres la venta y pulsas **Factura completa (F3)**. Eliges o creas el cliente (con su DNI), confirmas, y al imprimir sale la factura completa. El ticket original no se borra.
- **Tu ticket a tu manera.** En **Point of Sale → Invoice Templates** hay etiquetas nuevas (datos de tu tienda, número de factura, QR, datos del cliente) para colocarlas donde quieras. Los datos del cliente solo salen en las facturas completas.
- **Ventas por marca.** Una pantalla nueva, **Marcas**, en el menú del TPV: eliges hoy, ayer, esta semana, este mes o unas fechas, y ves cuánto se ha vendido de cada marca y de cada producto.
- **Marcas junto a «Todos» en la pantalla principal.** Pulsas **Marcas** y ves tus marcas como si fueran subcategorías, cada una con su imagen si la tiene; pulsas una y solo se ven los productos de esa marca. Funciona igual que las categorías de siempre.
- **Filtrar productos por marca** en el TPV, y también por otras clasificaciones que elijas en **POS → Settings → VFWoo Bridge**.
- **Aviso cuando el catálogo cambia.** Si cambias marcas o filtros, cada caja muestra un aviso para que recargue sus productos (menú **Resync → Products**). Lo decide la persona de la caja, para no perder ventas a medias.
- **Preparado para actualizarse** desde Escritorio › Actualizaciones cuando salga una versión nueva.

## Lo que tienes que hacer tú

- Crear **un cliente por defecto** («Cliente mostrador»), sin DNI, para las ventas sin cliente. Se hace en **Usuarios**: crea el usuario con rol Cliente y, pasando el ratón por su fila, pulsa **Set Pos Default Customer**.
- Si el aviso dice que el catálogo cambió, pulsar **Resync → Products** en cada caja.
- Si un cliente antiguo no tiene DNI, se le pide al editarlo en el TPV.

## Ten en cuenta

- **El límite es el mismo para la web y para el TPV**, el que marca VFWoo. Está previsto poder poner uno propio para el TPV (por ejemplo 3.000 € en la tienda y 400 € en la web).
- Para poner una factura completa después de la venta, la simplificada tiene que estar **confirmada** por VFWoo. Si aún no lo está, el TPV te lo dice y basta esperar un momento.
- Las **devoluciones** se hacen en WooCommerce, como siempre; VFWoo crea la factura rectificativa. Conviene probarlo antes de depender de ello.
- Si pones las etiquetas nuevas en el ticket, **no desactives el plugin** sin quitarlas antes: el ticket dejaría de imprimirse.
- Antes de usarlo con clientes reales, haz unas ventas de prueba con VFWoo en modo de pruebas (Sandbox).
