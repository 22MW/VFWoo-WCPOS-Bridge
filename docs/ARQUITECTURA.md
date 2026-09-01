# Arquitectura de `vfwoo-wcpos-bridge`

## Propósito

Plugin de integración comercial entre VFWoo y WCPOS. No sustituye a ninguno de
los dos productos y no modifica sus archivos. VFWoo conserva la autoridad de
numeración, emisión, QR fiscal y comunicación con Hacienda; WCPOS conserva la
autoridad de caja, pedidos, impresoras y plantillas.

## Capas

```text
Plugin Core
├── Contracts/       contratos PHP y REST propios, versionados
├── Integrations/    adaptadores a VFWoo, WCPOS y WooCommerce
├── Modules/         funcionalidades comercializables independientes
├── Admin/           ajustes, diagnóstico, licencias y soporte
├── Rest/             endpoints propios, autenticados y versionados
├── Templates/        vistas y plantillas de ticket del puente
├── Assets/           scripts y estilos empaquetados por módulo
└── Tests/            unitarios, integración y regresión
```

## Módulos previstos

| Módulo | Responsabilidad | Estado |
| --- | --- | --- |
| `Fiscal_Ticket` | Inyectar datos fiscales VFWoo en el contrato de recibo WCPOS. | Implementación inicial. |
| `Reprints` | Reimpresión de ticket fiscal y trazabilidad. | Pendiente. |
| `Print_Routing` | Reglas por tienda, cajero, impresora y copia. | Pendiente. |
| `Cashier_Alerts` | Avisos de emisión, rechazo y reintento para caja. | Pendiente. |
| `Offline_Reconciliation` | Reconciliación tras sincronización del cliente offline. | Pendiente; requiere trabajo en la app WCPOS. |
| `Diagnostics` | Estado de dependencias, contrato, colas y última emisión. | Pendiente. |

## Reglas de diseño

- Cada módulo se registra solo si su contrato de dependencia se verifica.
- No usar HTML de shortcode como API; consumir `VFWoo\Ticket_Fiscal_Data`.
- No escribir metas ni snapshots de VFWoo. El bridge incorpora el bloque de
  presentación al filtro público de WCPOS antes de su persistencia y, si la
  factura VFWoo se confirma después, completa exclusivamente
  `fiscal.extra_fields.vfwoo`, manteniendo intacto el resto del snapshot.
- Toda persistencia del puente usa prefijo `_vfwoo_wcpos_bridge_` y una política
  de borrado explícita.
- Los endpoints propios usan namespace `vfwoo-wcpos-bridge/v1` y permisos
  explícitos. Nunca exponen la clave de pedido de WooCommerce.
- Las preferencias se guardan en la sección WCPOS `vfwoo_bridge` (opción
  `woocommerce_pos_settings_vfwoo_bridge`); las licencias y secretos nunca
  entran en Git, exports de soporte ni respuestas REST.
- Cada módulo debe poder desactivarse sin perder facturas, pedidos ni tickets
  emitidos.

## Contrato mínimo para activar `Fiscal_Ticket`

1. VFWoo debe devolver su contrato público estable para una venta de WCPOS.
2. VFWoo es la única autoridad: incluso ante una respuesta demorada, los datos
   devueltos deben ser los fiscales reales de esa venta.
3. `temp` solo pertenece a VFWoo y únicamente puede preparar QR/URL; el bridge
   no genera números, fechas ni QR alternativos.
4. Las rectificativas R1–R5 son una ampliación planificada, no parte de la
   primera entrega.

### Secuencia de una venta WCPOS

En una venta normal WCPOS persiste su snapshot al dispararse
`woocommerce_payment_complete`. VFWoo se ejecuta antes, en prioridad 5; WCPOS
crea su snapshot con la prioridad estándar 10. El bridge habilita la lectura
VFWoo también en estado `processing`, además de `completed`, porque el pedido
puede no haber alcanzado aún `completed` cuando se imprime. Esa ampliación solo
autoriza la lectura: VFWoo sigue exigiendo que exista una factura real.

Si la política de VFWoo emite al entrar en `completed` o la confirmación llega
después, el bridge vuelve a leer el contrato y completa solo su subárbol del
snapshot. Así el recibo WCPOS no pierde los datos fiscales por el orden de los
hooks, sin reemitir ni alterar los datos de venta WCPOS.

Cuando WCPOS está configurado en modo `live` y no existe snapshot, el bridge
inyecta el mismo bloque en la respuesta autenticada del endpoint oficial de
recibos de WCPOS. No se persiste ningún dato adicional: la lectura sigue siendo
VFWoo en tiempo de impresión.

## Estructura de archivos objetivo

```text
vfwoo-wcpos-bridge/
├── vfwoo-wcpos-bridge.php
├── uninstall.php
├── readme.txt
├── includes/
│   ├── Integrations/
│   ├── Settings/
│   ├── Templates/
│   ├── class-plugin.php
│   └── class-requirements.php
├── templates/
│   └── receipts/
├── assets/
│   ├── css/
│   └── js/
├── languages/
├── tests/
│   ├── phpunit/
│   └── integration/
└── docs/
```
