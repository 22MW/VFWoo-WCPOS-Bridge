# Roadmap futuro

## Fase siguiente: filtros de producto

- Conectar `vfwoo_webkul_brands` con el panel nativo acumulable de filtros del catálogo.
- Mostrar la sección `BRANDS` junto a los atributos existentes.
- Probar selección múltiple de marcas y combinación con color, talla, categoría y búsqueda.
- Confirmar el comportamiento con productos variables y paginación.
- Resolver la recarga segura del catálogo cuando Webkul conserve datos antiguos.
1. Confirmar la firma del hook visual de filtros React de Webkul.
2. Conectar los términos `product_brand` al panel nativo.
3. Mostrar la sección `BRANDS` junto a los atributos existentes.
4. Mantener “Todas” y selección múltiple.
5. Validar catálogo, categorías, búsqueda, variaciones y paginación.

## Validación del filtro

- Marca única y varias marcas.
- Marca combinada con color, talla y categoría.
- Productos simples y variables.
- Recarga y caché local del POS.

## Fase futura: recarga segura del catálogo

- Detectar respuestas antiguas conservadas por Webkul.
- Estudiar una recarga automática o una acción controlada para limpiar solo `pos.local`.
- No borrar pedidos, configuración ni datos fiscales.

## Fase futura: informes

- Añadir `Brands` al selector acumulable de Informes.
- Filtrar las líneas de producto de cada pedido por `product_brand`.
- Recalcular totales, unidades, impuestos y devoluciones.
- Mantener fechas, estados, outlet y cajero.
- Imprimir el informe filtrado.

## Ampliación posterior

- Reutilizar el sistema para cualquier taxonomía pública de productos.
- Mantener la lógica en el bridge, sin modificar Webkul, WooCommerce ni VFWoo.

## Restricciones

- No crear pedidos ni emitir facturas durante las pruebas.
- No modificar archivos del plugin Webkul.
- No duplicar numeración, emisión ni llamadas fiscales de VFWoo.
- No borrar datos de WooCommerce para resolver caché.
