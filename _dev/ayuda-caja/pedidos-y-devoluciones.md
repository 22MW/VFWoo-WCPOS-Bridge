---
title: Pedidos, devoluciones y reimpresión
slug: pedidos-y-devoluciones
description: Buscar una venta ya hecha, volver a imprimir el ticket, enviar el recibo y devolver un producto.
categoria: Vender en la caja
---

# Pedidos, devoluciones y reimpresión

## Buscar una venta ya hecha

Abre **Pedidos** en el menú de la caja. Verás las ventas del punto de venta, y puedes ver su detalle, su estado y su cliente.

## Volver a imprimir el ticket

Abre el pedido y pulsa **Volver a imprimir**. El ticket sale **igual que el original**: con el mismo número de factura y, si hay módulo VeriFacWoo, el mismo QR.

## Imprimir la factura en PDF

Junto a **Volver a imprimir**, el botón **Imprimir factura** imprime el **PDF de la factura** (A4) sin salir de la caja. Tarda unos segundos en prepararse.

## Enviar el recibo al cliente

Desde el pedido puedes **enviar el recibo al cliente por correo** con **Send order email**. Si la tienda tiene el plugin de facturas, el correo lleva el **PDF de la factura adjunto**, cada vez que lo envías. Si tu tienda lo tiene activado, la caja también puede enviarlo automáticamente al cobrar.

## Cambiar el estado de un pedido

En el detalle del pedido, el desplegable **Pedido Estado** ofrece solo tres cosas:

- **Completado.**
- **Anular:** cancela el pedido. Con VeriFacWoo, también anula su factura en la Agencia Tributaria.
- **Devolver:** devolución **total** (ver abajo).

La caja te pide confirmación antes de cambiarlo y te explica qué va a pasar.

## Devolver una venta

- **Devolución total:** elige **Devolver**. WooCommerce anota el reembolso por todo el importe y el total del pedido pasa a 0. **El dinero lo devuelves tú** (por ejemplo, el efectivo de la caja).
- **Devolución de un solo producto (parcial):** **la caja no la hace todavía.** Se hace en el escritorio de WooCommerce, en el pedido, con **Reembolsar**.
- **Cambio:** se registra como un pedido nuevo.

La devolución genera su **factura rectificativa**, con el plugin de facturación. Para imprimirla, abre el pedido y pulsa **Imprimir rectificativa**. No se borra nada: la venta original sigue en el historial.

En un pedido **reembolsado o cancelado ya no se puede cambiar la factura** (**Cambiar factura**): la caja te avisa.

> Antes de depender de las devoluciones con clientes reales, haz una de prueba, con la facturación en modo de pruebas, y comprueba que sale la rectificativa.

## Cuando el cliente pide factura después

Si el cliente ya se llevó su ticket y ahora quiere una factura con sus datos, pulsa **Cambiar factura** en el pedido y elige o crea el cliente (también una empresa). → [Convertir un ticket en factura](convertir-ticket-en-factura.md)

## Pedidos hechos sin conexión

Si la tienda usa stock propio de la caja (Master Stock), los pedidos cobrados sin internet se ven en la sección de **pedidos sin conexión** y se envían al volver. Con el stock compartido con la tienda online no se puede cobrar sin conexión. → [Resync y trabajo sin conexión](resync-y-sin-conexion.md)
