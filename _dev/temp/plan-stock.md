# Plan: pantalla «Stock» en la caja

Estado: **plan, sin empezar.** Pedido del usuario (2026-11-19), a petición de un cliente: ver, filtrar y descargar estadísticas de stock.

## Decisión

Botón nuevo **«Stock»** en el menú de la caja, junto a «Marcas». No se mete dentro de la pantalla Informes de Webkul: es una pantalla de su React (react-select, rsuite) y tocarla por dentro se rompería con un update. Un botón propio cuesta lo mismo que «Marcas» y es robusto.

## Cómo se construye (reglas de `estilos-pos.md`)

- **Modelo: pantalla Informe de Webkul**, con los componentes que ya existen en `core.js` (`ui.report`, `ui.reportField`, `ui.reportButton`, `ui.reportCards`, `ui.reportTable`). Ningún CSS nuevo.
- **Módulo nuevo `includes/modules/stock/`** (sistema de módulos): manifiesto, clase PHP con la ruta REST y script JS. Se puede desactivar en el bloque «Módulos».
- **Servidor:** ruta `POST /stock/report` con `Pos_Auth` y permiso `view_woocommerce_reports`. Lee de las tablas de WooCommerce (stock, estado, precio, umbral), con filtros y paginación; quien no puede ver productos ajenos solo ve los suyos (como en «Productos»).

## Qué enseña (propuesta, a ajustar)

**Filtros:** estado (todos, en stock, stock bajo, agotado, negativo, sin control de stock), categoría, marca, búsqueda por nombre o SKU, umbral de «bajo».

**Tarjetas:** productos con control de stock · unidades en stock · agotados · stock bajo · stock negativo · valor a **precio de venta** (no hay precio de coste en WooCommerce).

**Tabla:** producto o variación · SKU · categoría · stock · estado · precio · valor del stock. Ordenable y paginada.

**«Descargar CSV»:** todas las filas del filtro actual, UTF-8 con BOM y `;` como separador (se abre bien en Excel en español).

## Datos de la tienda de pruebas (para dimensionar)

615 productos y variaciones publicados; 267 con control de stock (55.325 unidades); 99 agotados; 44 con stock 0; 1 negativo; umbral de stock bajo = 10; inventario centralizado; sin campo de coste.

## Límites

- Sin precio de coste: el valor del stock solo puede ser a precio de venta.
- WooCommerce no guarda la **evolución** del stock: para ver historial hay que registrar una foto diaria (cron) desde el día que se active. No va en la primera versión.

## Respuestas del usuario (2026-11-20)

- La propuesta de números está bien. El **historial** («cómo ha variado el stock») **interesa**: entra como fase 3 (foto diaria con cron desde el día que se active).
- **Pregunta abierta:** ¿stock distinto para la web y para la tienda? Ver el apartado siguiente.

## Stock distinto en la web y en la tienda (Master Stock de Webkul)

Es posible y Webkul lo trae de serie: ajuste `_pos_inventory_type` (**centralizado** = un solo stock compartido, el modo de ahora en la tienda de pruebas; **Master Stock** = stock de la tienda separado del de la web). Leído en su código (sin probar en esta instalación):

- **Web = stock de WooCommerce. Tienda = stock propio del POS**: un total (`_pos_master_stock`, o `_pos_variation_master_stock` en variaciones) que se **reparte entre las tiendas** (`pos_stock` de cada una, con asignación masiva).
- Una venta del POS descuenta solo el stock de la tienda (y devuelve a WooCommerce lo que éste descontó al crear el pedido); un pedido web descuenta solo el de WooCommerce. Cancelar o reembolsar en el administrador lo repone en el lado que corresponda.
- **No hay traspaso automático** entre web y tienda: se asigna a mano.
- En este modo la caja solo enseña los productos con stock de tienda mayor que 0, y **permite vender sin conexión** (en centralizado no, porque necesita el stock en vivo).

**Que se pueda elegir (pedido del usuario, 2026-11-20):** el modo ya se elige en Webkul, en *POS › Ajustes › General › «Select Inventory Type»* (Centralized Stock Inventory / Master Stock Inventory; también en el asistente de instalación). **El bridge no duplica ese ajuste: lo lee** (el mismo filtro `wk_wc_pos_enable_centralized_inventory`) **y se adapta solo**: con stock centralizado enseña y edita una columna «Stock»; con Master Stock, «Stock web» y «Stock tienda». Cada cliente usa el que quiera sin tocar el bridge.

**Cambiar de modo en una tienda con datos:** al pasar a Master Stock el stock de tienda empieza en 0 y los productos desaparecen de la caja hasta que se asigna. Opcional en la fase 5: acción **«Inicializar stock de tienda»** (copia el stock de WooCommerce al stock de tienda y lo asigna a la tienda del usuario, con vista previa). Hasta entonces, el cambio se hace con la asignación masiva de Webkul.

**Qué habría que adaptar en el bridge:** hoy «Productos» enseña y edita solo el stock de WooCommerce y bloquea el stock en Master Stock. Habría que mostrar y editar **«Stock web» y «Stock tienda»** (con los helpers de Webkul), y la pantalla «Stock» y su historial tendrían dos columnas. **Primero hay que probarlo en una copia** con el ajuste cambiado (cambiarlo en la tienda real altera cómo se vende).

## Fases

1. **Pantalla** con filtros, tarjetas y tabla.
2. **Descarga CSV.**
3. **Historial** con foto diaria (pedido por el usuario; guarda los dos stocks si el cliente usa Master Stock).
4. (Opcional) rotación: ventas de los últimos N días contra stock y días de cobertura.
5. (Si el cliente separa web y tienda) **Stock web y Stock tienda** en «Productos» y «Stock», tras probar Master Stock en una copia.

## Pendiente de decidir con el usuario

- Qué estadísticas concretas necesita el cliente (la lista de arriba es una propuesta).
- Si el cliente quiere stock separado para web y tienda (Master Stock).
- Quién puede ver la pantalla: `view_woocommerce_reports` (gestor de tienda) por defecto.
