---
title: Ticket con número de factura y QR
slug: ticket-con-factura-y-qr
description: Qué lleva el ticket de cada venta, cuándo sale con datos del cliente y cuándo lleva código QR.
categoria: Lo que añade DEFACWOO
---

# Ticket con número de factura y QR

Cada venta de la caja sale con su **factura correcta**. El ticket que entregas lleva el número de factura y los datos de tu negocio.

## Qué lleva el ticket

- Los **datos de tu tienda** (nombre, NIF, dirección, teléfono, logo…).
- El **número de factura** y su **fecha**.
- El **código QR** que permite **comprobar la factura** en la Agencia Tributaria, y la frase legal que lo acompaña. **Solo sale si tu tienda tiene el módulo VeriFacWoo (VeriFactu).** Sin ese módulo el ticket sale igual, pero sin QR.
- Los **datos del cliente**, solo cuando es una factura.

Con VeriFacWoo, el QR sale **a la primera**, al cobrar y al reimprimir.

## Ticket en el mostrador, factura en PDF por correo

- **El cliente que está en el mostrador** se lleva el **ticket** que imprime la caja.
- **Si el cliente necesita la factura por correo**, o la necesita tu gestor, recibe un **PDF** bien hecho, con tu logo, tus datos y su número de factura. Lo crea el plugin **VFWoo Invoices for WooCommerce** y se adjunta al correo del pedido.
- Desde el escritorio de la tienda, en el propio pedido, puedes **ver y descargar ese PDF** cuando quieras.

El ticket de la caja y el PDF llevan **el mismo número de factura**. El correo con el PDF sale si tu tienda envía correos de las ventas de la caja.

## Qué documento sale en cada venta

| Situación | Documento |
|---|---|
| Venta sin cliente, por debajo del límite | **Ticket**, sin datos del cliente |
| Venta con un cliente que tiene DNI/NIE/CIF | **Factura**, con sus datos, aunque la compra sea pequeña |
| Venta que llega al límite | La caja pide un cliente con DNI/NIE/CIF antes de cobrar |
| El cliente pide factura después de llevarse el ticket | **Factura de un ticket ya entregado**: el ticket pasa a ser la factura (con VeriFacWoo, una factura nueva que lo sustituye) |

→ [Venta grande: elegir un cliente](venta-con-limite.md)
→ [Convertir un ticket en factura](convertir-ticket-en-factura.md)

> **El límite del ticket** lo marca la ley y depende de tu actividad. Si no estás seguro de cuál te corresponde, consúltalo con tu gestor.

## VeriFacWoo: módulo opcional

El código QR y el envío de las facturas a la Agencia Tributaria (**VeriFactu**) los da **VeriFacWoo**, un módulo opcional que se contrata aparte. Con él, el ticket y el PDF llevan el QR para comprobar la factura. Sin él, todo funciona igual pero **sin QR**, y la caja **no bloquea ventas por importe ni exige el NIF del cliente**.

> Cuenta que el **módulo esté activado** en VFWoo (pestaña Módulos). Con el plugin instalado pero el módulo apagado, la caja se comporta como si no lo tuviera: sin QR.

## Cuándo se crea la factura

La crea el plugin de facturación **VFWoo Invoices for WooCommerce**. Según cómo lo hayamos dejado en tu tienda, se crea al cobrar o cuando el pedido se completa.

## Reimprimir

Un ticket reimpreso sale **igual que el original**, con el mismo número y, si hay módulo VeriFacWoo, el mismo QR. → [Pedidos, devoluciones y reimpresión](pedidos-y-devoluciones.md)

## Imprimir el PDF de la factura desde la caja

En el detalle de un pedido, el botón **Imprimir factura** abre el diálogo de imprimir con el **PDF de la factura** (A4), sin salir de la caja. Tarda unos segundos, porque el PDF se prepara en ese momento: el botón muestra «Preparando PDF…». Según el navegador, si no puede abrir el diálogo, abre el PDF en una pestaña nueva.

## Diseñar tu ticket

El aspecto del ticket (logo, textos, orden) lo dejamos a tu gusto al configurar la caja. Si quieres cambiarlo, escríbenos. → [Imprimir con una impresora Epson](impresora-epson.md)

## Si algo no sale

- **No sale el QR:** si tu tienda no tiene el módulo VeriFacWoo, es lo normal: no existe. Si lo tiene, la factura puede estar todavía pendiente de confirmar: espera un momento y vuelve a imprimir desde **Pedidos**.
- **No sale el número de factura:** espera un momento y vuelve a imprimir desde **Pedidos**.
- **Hay una imagen rota donde iría el QR (con VeriFacWoo):** pulsa **Resync** y vuelve a imprimir.
- **Salen datos del cliente en un ticket:** no debería pasar; avísanos y lo corregimos.

Antes de usarlo con clientes reales, haz **ventas de prueba** con la facturación en **modo de pruebas**.
