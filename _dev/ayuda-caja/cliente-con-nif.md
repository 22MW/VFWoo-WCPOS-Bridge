---
title: Clientes con DNI/NIE/CIF
slug: cliente-con-nif
description: Cuándo pide la caja el documento de identidad al crear un cliente, cómo se comprueba y qué pasa con otros países.
categoria: Lo que añade DEFACWOO
---

# Clientes con DNI/NIE/CIF

Para emitir una **factura completa** hace falta el cliente con sus datos. Al **crear un cliente desde la caja** puedes indicar su **DNI / NIE / CIF**.

## ¿Es obligatorio?

- **Con VeriFacWoo:** sí. La Agencia Tributaria exige el documento en las facturas completas.
- **Sin VeriFacWoo:** **es opcional.** Si lo dejas vacío, el cliente se guarda sin documento.

## Qué se comprueba

La comprobación depende del **país** del cliente (España por defecto):

- **España:** que el documento esté **bien escrito** (DNI con su letra, NIE o CIF con su dígito de control). Con **VeriFacWoo**, además, que el **nombre coincida** con el del censo de la **Agencia Tributaria**. Para una empresa, se comprueba la razón social.
- **Otro país:** **no se comprueba nada por defecto**. La casilla **«No comprobar el documento»** sale marcada, así que se acepta un pasaporte u otro documento tal cual. Si la desmarcas, se comprueba el formato (por ejemplo, un número de IVA de la UE con su prefijo).

La casilla se puede marcar o desmarcar a mano en cualquier momento.

## Si la Agencia Tributaria no responde

El cliente **se guarda igualmente**, marcado como **sin verificar**, y la caja te lo dice. Un cliente sin verificar puede dar problemas al facturar. Los clientes de otro país no llevan este aviso.

## Clientes que ya existían

Los clientes antiguos pueden no tener documento. Para una venta pequeña sin cliente no hace falta nada. Para facturarles con sus datos:

- **Con VeriFacWoo**, la caja te pide completarlo («Sin NIF: no se puede usar. Complétalo o crea uno nuevo.»).
- **Sin VeriFacWoo**, se puede usar igualmente.

Lo más cómodo es completar el documento **al editarlo en la caja**. → [Clientes en la caja](clientes-en-la-caja.md)

## Empresas y otros países

- **Empresa:** escribe su razón social en **Empresa / razón social** y su CIF.
- **Otro país:** elige el país. Con VeriFacWoo, la factura completa a un cliente de otro país **todavía no está disponible**.

## Datos que se guardan

El documento, la empresa, el domicilio y el país se guardan en la ficha del cliente y se copian al pedido al hacer la factura completa.
