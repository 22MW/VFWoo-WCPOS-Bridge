# Producto: plantillas VFWoo dentro de WCPOS

## Decisión de producto

`vfwoo-wcpos-bridge` es un bridge de plantillas. El usuario final no debe
abandonar WCPOS para imprimir un ticket fiscal.

```text
VFWoo prepara datos fiscales reales
→ bridge los incorpora al dato de recibo
→ WCPOS lista, selecciona y manda a imprimir la plantilla
```

## Responsabilidades

| Producto | Responsabilidad |
| --- | --- |
| VFWoo | Número, fecha, tipo de factura, NIF emisor, URL de cotejo, QR, leyenda y comunicación fiscal. |
| Bridge | Contrato de datos entre plugins, catálogo instalable de plantillas y configuración de la integración. |
| WCPOS | Pedido/caja, interfaz de selección, impresoras, formatos de impresión y acción de imprimir. |

El bridge nunca asigna numeración, emite a Hacienda, calcula datos fiscales ni
envía órdenes directas a una impresora.

## Plantillas iniciales

| Identificador | Nombre visible | Motor WCPOS | Soporte |
| --- | --- | --- | --- |
| `vfwoo-fiscal-58mm` | Ticket fiscal VFWoo · 58 mm | Thermal | Impresoras térmicas 58 mm. |
| `vfwoo-fiscal-80mm` | Ticket fiscal VFWoo · 80 mm | Thermal | Impresoras térmicas 80 mm. |
| `vfwoo-fiscal-a4` | Factura/ticket fiscal VFWoo · A4 | Logicless HTML | PDF y impresión A4. |

Cada una incluirá datos de venta WCPOS y, cuando VFWoo los facilite, número de
factura, fecha, QR, URL verificable y leyenda legal.

No se imprime el estado AEAT en el ticket inicial.

## Aparición en la interfaz WCPOS

WCPOS no ofrece en la versión actual un registro público de galerías externas.
Por ello el bridge instalará sus recursos como plantillas nativas
`wcpos_template` mediante una acción explícita de administrador:

```text
POS → VFWoo Bridge → Instalar plantillas VFWoo
```

Después de instalarse, aparecen junto a las demás plantillas en la interfaz
normal de WCPOS. El cajero selecciona e imprime una plantilla VFWoo usando el
flujo habitual de POS.

## Propiedad y actualizaciones

- Cada plantilla instalada lleva metadatos con origen, identificador y versión
  del bridge.
- La activación del plugin no crea ni modifica plantillas automáticamente.
- El usuario puede duplicar y adaptar una plantilla en WCPOS.
- Una actualización nunca sobrescribe una plantilla personalizada.
- Si existe una versión nueva, el bridge ofrece instalar una nueva copia o
  conservar la existente.

## Configuración dentro de WCPOS

El bridge registra una sección propia de ajustes WCPOS. No crea una experiencia
paralela para el usuario.

Configuración inicial:

- activar/desactivar la integración fiscal;
- instalar y revisar plantillas VFWoo;
- tamaño de QR (reservado para la siguiente iteración de plantilla);
- diagnóstico de contrato VFWoo/WCPOS (siguiente iteración).

La app de caja no necesita una pantalla nueva en la primera versión: el cajero
sigue seleccionando plantillas dentro de WCPOS. Una configuración propia dentro
de la app React requerirá una extensión posterior del cliente WCPOS.

## Contrato de datos

El bridge consume exclusivamente:

```php
\VFWoo\Ticket_Fiscal_Data::for_order( $order_id, true );
```

No analiza HTML de shortcodes ni accede a tablas internas de VFWoo.

Los datos fiscales son siempre reales y proceden de VFWoo. `temp` únicamente
permite a VFWoo devolver URL/QR preparado cuando todavía no existe la respuesta
del proveedor; no crea datos temporales ni numeración alternativa.
