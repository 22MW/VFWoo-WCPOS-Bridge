# Plan de desarrollo de `vfwoo-wcpos-bridge`

## Regla operativa

Cada fase termina con pruebas y validación explícita antes de empezar la
siguiente. No se modifican archivos de VFWoo ni WCPOS desde el bridge.

## Fase 0 — Cerrar contrato de VFWoo

**Objetivo:** garantizar que el bridge recibe datos fiscales reales y estables.

### Trabajo

- Confirmar `Ticket_Fiscal_Data::for_order()` como API pública versionada.
- Garantizar número, fecha e importe reales antes de solicitar datos de ticket.
- Garantizar QR/URL preparados con el algoritmo canónico de VFWoo.
- Eliminar cualquier comportamiento específico de NIF de prueba que pueda
  afectar a producción.
- Definir URL segura del PNG QR para consumo por WCPOS.

### Aceptación

- Mismo pedido: misma factura, fecha, importe y QR en todas las lecturas.
- Timeout del proveedor: ticket con datos fiscales reales.
- Respuesta posterior: no cambia la identidad fiscal impresa.

## Fase 1 — Núcleo del bridge y diagnóstico

**Objetivo:** habilitar una instalación segura y mantenible.

### Trabajo

- Completar comprobaciones de WooCommerce, VFWoo y WCPOS.
- Registrar sección de ajustes dentro de WCPOS.
- Añadir diagnóstico de solo lectura por ID de pedido para contrastar VFWoo y
  el snapshot WCPOS.
- Añadir pantalla de diagnóstico de contrato, versiones y disponibilidad.
- Definir logger propio sin secretos ni datos personales innecesarios.
- Añadir base de pruebas PHP.

### Aceptación

- El plugin no actúa si falta una dependencia.
- El administrador entiende qué falta y cómo corregirlo.
- No hay escrituras en pedidos, facturas ni snapshots.

## Fase 2 — Instalador de plantillas WCPOS

**Objetivo:** hacer visibles las plantillas VFWoo en WCPOS.

### Trabajo

- Crear catálogo interno de 58 mm, 80 mm y A4.
- Instalar cada plantilla como `wcpos_template` mediante acción explícita.
- Registrar origen, identificador y versión en metadatos del template.
- Detectar instalación existente y proteger personalizaciones.
- Ofrecer instalación de nueva versión sin sobrescritura.

### Aceptación

- Las plantillas aparecen junto a las demás en la interfaz WCPOS.
- Se pueden seleccionar e imprimir con el flujo habitual de POS.
- Reinstalar no crea duplicados ni pisa cambios del usuario.

## Fase 3 — Adaptador de datos fiscales

**Objetivo:** entregar a las plantillas el bloque fiscal VFWoo.

### Trabajo

- Implementar adaptador VFWoo de solo lectura.
- Implementar el punto de extensión WCPOS que incorpora los datos en el
  payload de recibo.
- Definir nombres estables del bloque de datos y documentación de template.
- Crear fixtures de previsualización para el editor de WCPOS.

### Aceptación

- Ticket de venta normal muestra número, fecha, QR, URL y leyenda.
- El QR se imprime y se puede abrir correctamente.
- Reimpresión conserva datos fiscales idénticos.

## Fase 4 — QA de impresión y publicación inicial

**Objetivo:** validar la experiencia física y publicar una primera versión.

### Trabajo

- Pruebas con 58 mm, 80 mm y PDF/A4.
- Pruebas de QR, textos largos, idiomas, impuestos, descuentos y reimpresión.
- Pruebas de timeout del proveedor y dos cajas simultáneas.
- Documentación de instalación, compatibilidad y soporte.
- Versionado, changelog y paquete distribuible.

### Aceptación

- Tickets legibles, sin cortes y con QR escaneable.
- Sin duplicación de facturas ni de números.
- Instalación y actualización reversibles.

## Posterior a la primera versión

| Fase | Tema | Condición previa |
| --- | --- | --- |
| 5 | Reimpresiones auditadas y routing por impresora. | Fases 1–4 estables. |
| 6 | Devoluciones y rectificativas R1–R5. | Flujo WCPOS y contrato VFWoo validados. |
| 7 | Operativa offline y reconciliación. | Extensión de la app cliente WCPOS. |
| 8 | Configuración dentro de la app de caja. | Desarrollo del cliente React WCPOS. |
