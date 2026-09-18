# Plan de cambio: filtro de marca en el catálogo POS

## Precheck

- Plugin objetivo: `vfwoo-webkul-pos-bridge`.
- Ruta: `app/public/wp-content/plugins/vfwoo-webkul-pos-bridge/`.
- Repo propio: sí.
- Rama: `dev-vfwoo-webkul-pos-bridge`.
- Hay cambios locales pendientes de la integración fiscal; no se deben mezclar ni sobrescribir.
- Webkul POS 7.1.1 expone hooks de productos y de menú en su aplicación POS.

## Objetivo

Añadir un filtro visible por la taxonomía de marca en el catálogo de productos de Webkul POS, manteniendo una base ampliable a otras taxonomías.

## MVP

- Detectar taxonomías públicas asociadas a `product`.
- Usar inicialmente la taxonomía de marca existente.
- Mostrar sus términos disponibles en el catálogo POS.
- Filtrar los productos mostrados por el término seleccionado.
- Mantener la opción de ver todas las marcas.

## Alcance incluido

- Bridge propio, sin editar Webkul.
- Transporte de la taxonomía y sus términos al frontend POS.
- Integración con la carga inicial, categorías y búsqueda si el hook lo permite.
- Preparación para reutilizar el mismo componente con otras taxonomías.

## Fuera de alcance

- Informes por marca.
- Cambios en productos, pedidos o stock.
- Nuevas taxonomías.
- Sustituir el buscador o las categorías nativas.

## Hooks candidatos confirmados en Webkul

- `wkwcpos_modify_homepage_products`
- `wkwcpos_modify_load_category_products`
- `wkwcpos_search_product_filter`
- `wkwcpos_show_menu_filter`
- `wkwcpos_menus_list`
- `wkwcpos_add_menus_before_settings`

La firma exacta y el momento de ejecución deben confirmarse leyendo el componente/código de carga antes de implementar.

## Riesgos

- Caché local de Webkul: puede conservar el catálogo anterior.
- Carga paginada: el filtro debe aplicarse antes de paginar o garantizar resultados coherentes.
- Variaciones: el filtro debe conservar el producto variable y sus variaciones.
- Taxonomía de marca: el slug real debe detectarse, no suponerse.
- Búsqueda y categorías pueden usar rutas distintas.

## Criterios de aceptación

- El catálogo muestra un selector de marca.
- “Todas” restaura el catálogo completo.
- Una marca solo muestra productos asignados a ella.
- Las variaciones siguen funcionando.
- La búsqueda respeta la marca seleccionada o se documenta la limitación.
- No se modifica el plugin Webkul.
- El cambio no afecta al ticket fiscal.

## Validación prevista

- PHP lint y JavaScript syntax check.
- Prueba con dos marcas y productos de ambas.
- Prueba con producto variable.
- Prueba combinada marca + categoría + búsqueda.
- Limpieza/recarga de caché local del POS tras instalar el cambio.

## Siguiente paso

Confirmar la firma de los hooks candidatos y la taxonomía de marca real. Después se implementará únicamente el MVP.
