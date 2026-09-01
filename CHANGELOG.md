# Historial de cambios

Este proyecto sigue el formato de [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y utiliza versionado semántico.

## [Sin publicar]

### Pendiente de validación

- Pruebas funcionales en una instalación activa de WordPress, VFWoo y WCPOS.
- Validación física de impresión en 58 mm, 80 mm y A4.

### Corregido

- La lectura de datos VFWoo durante el cobro WCPOS incluye el estado
  `processing`, habitual en ventas físicas justo antes de crear el snapshot.
- Cuando la factura VFWoo queda disponible después del snapshot inicial, el
  bridge completa exclusivamente su bloque fiscal antes de mostrar el recibo.
- Los recibos WCPOS en modo `live`, sin snapshot fiscal, reciben los datos
  VFWoo en su respuesta autenticada de impresión.

## [0.1.0] - 2026-09-01

### Añadido

- Núcleo del plugin `vfwoo-wcpos-bridge` y comprobación de dependencias.
- Adaptador de solo lectura al contrato público `VFWoo\Ticket_Fiscal_Data`.
- Integración con el filtro oficial de snapshots fiscales de WCPOS.
- Plantillas editables nativas de WCPOS para 58 mm, 80 mm y A4.
- Instalación explícita de plantillas desde el menú POS.
- Documentación de producto, arquitectura, roadmap y plan de desarrollo.
- Diagnóstico de solo lectura de pedido desde POS → VFWoo Bridge.

### Garantías

- El bridge no emite facturas, no asigna numeración y no comunica con Hacienda.
- El bridge no ordena impresiones: WCPOS conserva el flujo de caja e impresión.
- La activación no crea plantillas ni altera plantillas existentes.
