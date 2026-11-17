# LEEME (no se publica)

Este archivo **no es una página de la ayuda**: no se vuelca a la web. Tiene dos partes: instrucciones para el agente que vuelca las páginas y notas para el responsable del proyecto.

---

## Para el agente que vuelca las páginas

Esta carpeta es la copia de la documentación de ayuda de la caja DEFACWOO. Cada `.md` es **una página**, salvo este LEEME.

1. **Qué volcar:** todos los `.md` de esta carpeta **excepto `LEEME.md`**.
2. **Cabecera de cada página** (entre `---`): `title` (título de la página), `slug` (dirección), `description` (descripción corta) y `categoria` (agrupación). Se usan tal cual; no van dentro del cuerpo.
3. **Cuerpo:** Markdown. Empieza con un `# Título` igual al `title`: si la plantilla de la web ya pone el título, se quita ese `#`.
4. **Índice:** `index.md` es la **página índice** de la ayuda (`slug: index`). Lleva la lista de todas las páginas por apartados; los apartados coinciden con `categoria`.
5. **Dirección:** las páginas cuelgan de `/ayuda-caja/<slug>/`, y el índice de `/ayuda-caja/`.
6. **Enlaces:** entre páginas son relativos (`pagina.md`, para poder leerlos en un visor Markdown). Hay que convertirlos a la dirección de la web: `pagina.md` → `/ayuda-caja/pagina/`. Equivale a:

   ```bash
   sed -i '' -E 's#\]\(([a-z0-9-]+)\.md\)#](/ayuda-caja/\1/)#g' *.md
   ```

7. **Contenido:** no se reescribe ni se amplía. Si algo no cuadra, se avisa; no se corrige a mano en la web.

---

## Notas para el responsable

**Un solo perfil.** Cajero y gestor de la tienda son el mismo usuario: el bridge da el rol *Gestor de tienda* a los cajeros que se crean con el ajuste **Cajeros gestores** activado (por defecto está desactivado, y no cambia a los usuarios que ya existían). Por eso las páginas no separan «cajero» y «gestor».

**La configuración no está aquí a propósito.** La caja se entrega configurada: usuarios, puntos de venta, ajustes, formas de pago, plantillas de ticket, licencia y ajustes del bridge los hace el equipo de DEFACWOO. Cuando una página toca algo de eso, remite a «escríbenos».

## De dónde sale lo que dicen

- **Lo que añade DEFACWOO** (accesos de la cabecera, ticket con número de factura, NIF del cliente, factura completa posterior, marcas, aviso de catálogo): escrito con el plugin delante.
- **Lo nativo del POS de Webkul** (pantalla de venta, cobrar, cierres, informes, impresión…): escrito a partir de los textos y ajustes del propio plugin, **sin verlo en pantalla**. La caja está traducida a medias y algunos botones salen en inglés (por ejemplo *Resync* o *Hold Cart*): las páginas los dejan tal cual aparecen.

Las páginas no llevan capturas de pantalla.

## Contenido sin verificar en pantalla

- **Cobrar:** el nombre exacto del botón de pago, cómo se pide el efectivo recibido y el cambio, y cómo se ve el pago dividido.
- **Ventas aparcadas:** que una venta aparcada no descuenta stock y cómo se abre la lista.
- **Pedidos y devoluciones:** dónde está el botón de enviar el recibo y cómo se registra un cambio.
- **Sin conexión:** que con la app de la caja se puede seguir vendiendo sin internet y sincronizar al volver (lo indica el responsable, vale para cualquier tipo de stock). Falta confirmar en pantalla: qué no se puede hacer sin conexión (cupones, clientes) y el botón *Sincronizar pedidos sin conexión*.
- **Factura en PDF por correo** (página *Ticket con número de factura y QR*): se ha escrito a partir de la descripción del plugin de facturación (adjunta el PDF a los correos de WooCommerce y permite verlo y descargarlo desde el pedido). No se ha visto que una venta de la caja envíe ese correo al cliente; depende de que la tienda envíe correos de las ventas de la caja.
- **Atajos de teclado:** la lista de teclas (la página solo remite al panel de la caja).
- **Tarjeta:** que la caja no maneja el datáfono.
- **Impresión con Epson** (página *Imprimir con una impresora Epson*):
  - En el plugin la Epson TM-T88V es un **formato de página** (ancho y largo del rollo), no un controlador. La página dice que el ticket se manda a imprimir con la ventana de impresión del navegador; no se ha probado con una Epson real.
  - No se ha encontrado nada que abra el cajón de monedas al cobrar: la página dice que se consulte.
  - La reimpresión con el mismo número de factura tampoco se ha visto en pantalla.

## Notas

- Las páginas de facturas (ticket, NIF, factura completa) dependen del plugin de facturación VFWoo. Si una tienda no lo tiene, esas funciones no aparecen.
- **El código QR solo existe con el módulo VeriFacWoo (VeriFactu) activado.** Sin él, el ticket y la factura salen sin QR, el NIF del cliente es opcional y no hay límite de venta. Las páginas lo dicen donde lo mencionan.
- Las páginas no dan asesoramiento fiscal. El límite de la factura simplificada y el tipo de documento que corresponde a cada venta se consultan con el gestor.
- Contacto que usan las páginas: soporte@verifacwoo.com.
