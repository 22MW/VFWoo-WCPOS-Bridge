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

Junto a **Volver a imprimir**, el botón **Factura PDF** imprime el **PDF de la factura** (A4) sin salir de la caja. Tarda unos segundos en prepararse.

## Enviar el recibo al cliente

Desde el pedido puedes **enviar el recibo al cliente por correo** con **Send order email**. Si la tienda tiene el plugin de facturas, el correo lleva el **PDF de la factura adjunto**, cada vez que lo envías. Si tu tienda lo tiene activado, la caja también puede enviarlo automáticamente al cobrar.

## Devolver un producto

Las devoluciones se hacen sobre el **pedido original**:

- **Devolución total:** el pedido pasa a **Reembolsado**.
- **Devolución parcial:** el pedido pasa a **Parcialmente reembolsado**, con el importe devuelto.
- **Cambio:** un cambio se registra como un pedido nuevo enlazado al original.

La devolución genera su **factura rectificativa** sobre la factura de la venta, con el plugin de facturación. No se borra nada: la venta original sigue en el historial.

> Antes de depender de las devoluciones con clientes reales, haz una de prueba, con la facturación en modo de pruebas, y comprueba que sale la rectificativa.

## Cuando el cliente pide factura después

Si el cliente ya se llevó su ticket y ahora quiere una factura con sus datos, pulsa **Factura completa** en el pedido y elige o crea el cliente (también una empresa). → [Factura completa después de la venta](factura-completa-posterior.md)

## Pedidos hechos sin conexión

Los pedidos que se cobraron sin internet se ven en la sección de **pedidos sin conexión** y se envían al volver. → [Resync y trabajo sin conexión](resync-y-sin-conexion.md)
