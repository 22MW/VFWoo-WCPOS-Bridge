# Extensión React de Webkul POS

## Objetivo

Definir cómo extender la interfaz React de Webkul POS desde el bridge, sin editar archivos del plugin original. Este documento servirá como referencia para filtros de productos, `BRANDS` y futuras extensiones del catálogo.

## Estado de las fases

| Fase | Estado | Nota |
|---|---|---|
| Precheck y contrato Webkul | Parcialmente hecho | Hooks y versión 7.1.1 localizados; las firmas React deben confirmarse antes de cerrar la fase. |
| Transporte de datos | Hecho | El bridge añade `vfwoo_webkul_brands` usando `product_brand`. |
| Diagnóstico en Settings | Pendiente | Está definido en roadmap; aún no implementado. |
| Filtro visual `BRANDS` | Hecho (paso 1) | Sin React propio: entrada `BRANDS` en `product.attributes`; confirmado en local. Pendiente QA detallada y taxonomías configurables. |
| Caché y compatibilidad | Parcialmente investigado | Se confirmó caché local; falta automatizar una recarga segura. |
| QA y mantenimiento | Parcial | El ticket fiscal y QR están validados; el filtro de marcas aún no. |

## Evidencia localizada en Webkul 7.1.1

Webkul carga su aplicación POS como un bundle compilado y utiliza `wp.hooks` para varios puntos de extensión. Se han localizado hooks JavaScript relacionados con el catálogo:

- `wkwcpos_search_product_filter`
- `wkwcpos_modify_homepage_products`
- `wkwcpos_modify_load_category_products`
- `wkwcpos_modify_product_page_component`
- `wkwcpos_modify_category_tab_ui`
- `wkwcpos_modify_all_category_tab`
- `wkwcpos_show_menu_filter` (solo visibilidad del menú; no sirve para inyectar UI)
- `wkwcpos_menus_list`
- `wkwcpos_add_menus_before_settings`
- `wkwcpos_add_additional_key_in_product_data`

El bundle también expone filtros de ticket y rutas de páginas. La existencia del nombre del hook está confirmada; la firma completa y el componente recibido deben verificarse en cada versión de Webkul antes de implementarlos.

## Datos preparados por el bridge

El bridge añade a la respuesta de producto:

```js
vfwoo_webkul_brands: [
  { id: 1, name: 'Marca', slug: 'marca' }
]
```

La taxonomía usada por WooCommerce es `product_brand`.

## Arquitectura prevista

1. PHP añade los términos `product_brand` a cada producto mediante el filtro de respuesta de producto de Webkul.
2. Un módulo JavaScript propio se carga con el hook de scripts POS de Webkul.
3. El módulo registra filtros `wp.hooks` sin modificar el bundle original.
4. Una extensión React presenta `BRANDS` dentro del sistema nativo de filtros.
5. El estado de marcas seleccionadas se combina con búsqueda, categorías y atributos.
6. El resultado vuelve al listado de productos mediante el hook de productos correspondiente.

## Reglas del componente React

- No manipular tarjetas mediante CSS o selectores DOM.
- No sustituir todo el estado global de Webkul sin una API confirmada.
- Mantener selección múltiple de marcas.
- Usar OR entre marcas seleccionadas.
- Usar AND entre marcas y el resto de filtros.
- Conservar categorías, búsqueda, ofertas, variaciones y paginación.
- Permitir limpiar marcas sin perder los demás filtros.
- No hacer peticiones fiscales ni modificar pedidos.

## Hallazgo: el panel de filtros nativo se alimenta de `product.attributes`

Confirmado en el bundle 7.1.1: `buildAttributeFilterMap` recorre `product.attributes` (`{slug|name|taxonomy, name, options}`) y genera las secciones del panel. La selección vive en `selectedAttributeFilters` y `doesProductMatchAttributeFilters` aplica OR dentro de una clave y AND entre claves. Consecuencia: una entrada `BRANDS` en `attributes` aparecería en el panel con selección múltiple sin código React propio (decisión D-004, pendiente de validar en runtime: carrito y variaciones).

Firmas confirmadas: `wkwcpos_modify_homepage_products(lista, componente)` se aplica tras elegir lista/búsqueda/categoría; `wkwcpos_search_product_filter(products, props, evento, componente)`; `wkwcpos_modify_load_category_products` es asíncrono `(estado, categoría, dispatch)`.

## Contrato de implementación pendiente

Antes de programar el componente definitivo hay que confirmar:

- firma de `wkwcpos_show_menu_filter`;
- firma de `wkwcpos_modify_homepage_products`;
- firma de `wkwcpos_modify_load_category_products`;
- forma interna de los filtros de atributos;
- si el listado llega paginado o completo;
- cómo se conserva el estado al cambiar de categoría;
- si Webkul guarda el catálogo en IndexedDB, caché o memoria.

## Compatibilidad con actualizaciones de Webkul

Una actualización puede:

- cambiar nombres o firmas de hooks JavaScript;
- cambiar la forma de los objetos de producto;
- mover el panel de filtros a otro componente;
- recompilar el bundle sin conservar un hook no documentado;
- cambiar el sistema de caché o el almacenamiento local;
- alterar los filtros de informes y la estructura de sus respuestas.

El bridge no debe editar ni copiar el bundle de Webkul. Si Webkul mantiene los hooks, el cambio debería ser compatible. Si elimina o cambia un hook, el filtro `BRANDS` dejará de aparecer o dejará de filtrar hasta adaptar el bridge.

## Diagnóstico en Settings

La pantalla `POS → Settings → VFWoo Bridge` debe incluir un bloque de compatibilidad con:

- versión del bridge;
- versión de VFWoo;
- versión de WooCommerce;
- versión de Webkul POS;
- estado de dependencias;
- existencia de `product_brand` y número de términos;
- disponibilidad de los hooks React esperados;
- estado de los datos `vfwoo_webkul_brands`;
- aviso de posible caché local.

Debe incluir un botón `Comprobar compatibilidad` que haga únicamente comprobaciones de lectura. No debe crear pedidos, emitir facturas, modificar productos ni ejecutar acciones fiscales. La comprobación puede consultar versiones, clases, taxonomías, hooks y una muestra controlada de producto.

Si el diagnóstico detecta que falta un hook, el bridge debe poder desactivar solo `BRANDS` y dejar intactos el catálogo base, las ventas, los tickets y la integración fiscal.

## Protocolo tras actualizar Webkul

1. Registrar la versión anterior y la nueva (`WK_WC_POS_VERSION`).
2. Revisar que los hooks anteriores siguen presentes en el bundle.
3. Validar que el producto sigue incluyendo `vfwoo_webkul_brands`.
4. Limpiar la caché local del POS.
5. Probar marca única y selección múltiple.
6. Probar marca más categoría, atributo y búsqueda.
7. Probar producto variable y paginación.
8. Probar ticket fiscal y reimpresión.
9. Registrar el resultado en `_dev/contexto-activo.md` y `CHANGELOG.md`.

## Estrategia de fallo seguro

Si no se detecta el hook o cambia la estructura:

- no ejecutar errores JavaScript;
- no ocultar productos de forma parcial;
- desactivar solo el filtro `BRANDS`;
- conservar catálogo, venta, ticket y fiscalidad de Webkul;
- mostrar en debug la versión y el hook ausente, sin datos personales.

## Conclusión

La extensión es viable sin modificar Webkul, pero el filtro visual depende del contrato React que Webkul exponga en cada versión. La capa PHP de datos es independiente; la capa visual debe validarse después de cada actualización del POS.
