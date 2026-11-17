---
title: Resync y trabajo sin conexión
slug: resync-y-sin-conexion
description: Cuándo y cómo recargar los datos de la caja, y qué se puede y qué no se puede hacer sin internet.
categoria: Vender en la caja
---

# Resync y trabajo sin conexión

## Por qué existe Resync

Para ir rápida, la caja **guarda una copia de tus productos y tus clientes en el propio dispositivo**. Cuando algo cambia en la web (un precio, un producto, una marca, un cliente nuevo), la copia de la caja no se entera sola: hay que **recargarla**.

## Cómo recargar

En el menú de la caja, **Resync**, y elige qué recargar:

- **Products:** los productos, precios, categorías y marcas.
- **Customers:** los clientes.

Tarda unos segundos. Hazlo **cuando no estés en mitad de una venta**.

## Cuándo hacerlo

- Cuando la caja te muestre el aviso de que hay **cambios en los productos**. → [Aviso de cambios en el catálogo](aviso-de-catalogo.md)
- Cuando acabas de crear o cambiar un producto y no lo ves.
- Cuando un cliente creado en la web o en otra caja no aparece.
- Cuando el precio o el stock de la caja no coincide con la web.

## Si sigue sin verse

Algunas veces la caja conserva datos antiguos. Haz **Resync** y recarga la página con **Ctrl + F5** (en Mac, **Cmd + Mayús + R**). Si aun así no cambia, escríbenos.

## Sin conexión a internet

La caja detecta cuándo pierde la conexión y avisa de que estás **sin conexión**. Los pedidos cobrados sin internet se guardan en el dispositivo y se envían al volver la conexión, con **Sincronizar pedidos sin conexión**.

**Cuándo se puede vender sin conexión (condiciones):**

- **Solo si la tienda usa stock propio de la caja (Master Stock).** Si la caja comparte el stock con la tienda online (inventario centralizado, lo habitual), **no deja cobrar sin conexión** y avisa: «Cannot process orders with centralized inventory at offline mode».
- **La caja tiene que estar ya abierta** cuando se corta la conexión. Si la cierras o la recargas sin internet, **no arranca**: la copia de la caja para arrancar sin conexión no está funcionando en esta versión del plugin del POS.
- Hay que haber hecho **Resync** de productos y clientes antes, con conexión.

**Lo que no se puede hacer sin internet:**

- Aplicar **cupones**.
- **Añadir, editar o eliminar clientes** (el cliente con su NIF tiene que existir ya).
- Sincronizar pedidos, hasta que vuelva la conexión.

**El ticket sin conexión** sale probablemente **sin número de factura ni QR**: el pedido todavía no existe en la tienda. Consulta con tu gestor antes de entregar un ticket así.

## Consejo

La solución más segura para no parar de vender es una **conexión de respaldo** (router 4G o los datos del móvil). No cierres la caja con ventas sin sincronizar.
