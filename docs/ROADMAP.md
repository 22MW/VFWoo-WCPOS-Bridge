# Roadmap de `vfwoo-wcpos-bridge`

## Principio de alcance

El puente solo consume contratos públicos de VFWoo y WCPOS. No asigna números,
no envía registros a Hacienda y no reconstruye datos fiscales. Cada fase se
valida y se autoriza antes de implementar la siguiente.

## Fase 0 — Contrato fiscal VFWoo

**Objetivo:** que el puente pueda leer datos fiscales de un pedido WCPOS sin
duplicar lógica.

**Dependencias de VFWoo:**

- `VFWoo\Ticket_Fiscal_Data::for_order( $order_id, true )` estable y documentada.
- Numeración y fecha ya reservadas antes de construir el ticket.
- QR preparado fiable cuando el proveedor no responde.
- URL firmada para servir el PNG al POS, sin exponer `order_key` en el ticket.

**Criterio de salida:** cobro normal, timeout del proveedor y reimpresión
devuelven el mismo número y un QR verificable.

## Fase 1 — Ticket fiscal para ventas normales

**Objetivo:** añadir al ticket WCPOS de pedidos de venta los datos proporcionados
por VFWoo: número, fecha, QR, URL y leyenda.

**Límites:**

- Solo facturas normales F1/F2/F3.
- Sin estado AEAT impreso.
- Sin rectificativas, devoluciones, reimpresión automática ni modo offline.

**Integración prevista:** adaptador WCPOS que enriquece el dato de recibo con
la API VFWoo, sin alterar el snapshot original ni archivos de WCPOS.

**Criterio de salida:** ticket correcto en caja y reimpresión manual con los
mismos datos fiscales.

## Fase 2 — Administración y diagnóstico

**Objetivo:** convertir la integración en producto mantenible.

- Página de estado de dependencias, contratos, colas y última emisión.
- Ajustes por tienda WCPOS y activación del módulo fiscal.
- Logs propios sin datos personales innecesarios.
- Ayuda, traducciones, información de versión y soporte.

## Fase 3 — Reimpresiones y routing de impresión

**Objetivo:** gestionar copias y reglas operativas de caja.

- Reimpresión fiscal manual desde WCPOS/pedido.
- Identificación de copia/reimpresión sin alterar datos fiscales.
- Reglas por tienda, impresora y cajero.
- Auditoría de cada acción del puente.

## Fase 4 — Rectificativas y devoluciones

**Objetivo:** integrar R1–R5 cuando se decida el flujo de WCPOS.

**Trabajo previo obligatorio:**

- Confirmar qué tipo de devolución crea WCPOS y cuándo se considera definitiva.
- Ampliar el contrato de VFWoo para devolver facturas rectificativas.
- Diseñar qué ticket se imprime antes y después de la rectificativa.
- Validar numeración, QR, factura original y comportamiento de reimpresión.

**No forma parte de Fase 1.**

## Fase 5 — Offline y reconciliación

**Objetivo:** abordar ventas creadas sin conexión una vez definido el soporte
real del cliente WCPOS usado.

- No generar número, QR ni URL fiscal mientras el pedido no exista en WordPress.
- Tras sincronizar: creación del pedido, emisión VFWoo y disponibilidad de
  ticket fiscal.
- Evaluar cambios necesarios en la app WCPOS para aviso o autoimpresión.

## Fase 6 — Extensiones comerciales

Posibles módulos independientes, siempre bajo contratos versionados:

- avisos de emisión/rechazo en caja;
- exportación y conciliación de turnos;
- formatos de ticket por tienda;
- integraciones de impresora o gateway;
- panel de soporte y diagnóstico remoto con datos anonimizados.
