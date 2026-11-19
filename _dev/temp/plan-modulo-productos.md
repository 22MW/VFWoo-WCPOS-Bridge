# Plan: módulo «Productos» en el POS y sistema de módulos del bridge

Estado: **idea investigada, sin código.** Nada de esto está construido ni probado.

## Objetivo

Una pestaña nueva en el POS, **«Productos»**, que sea un gestor ligero para el cajero o el encargado:

- Ver los productos de la tienda y **filtrar por si se ven en la tienda online y/o en la caja**.
- **Incluir o quitar** cada producto de la caja, y mostrarlo u ocultarlo en la tienda.
- Un **popup** para editar lo básico sin salir del POS: título, precios, oferta, SKU, código de barras (GTIN/EAN), stock y fotos. Solo campos que ya da WooCommerce, nada inventado.
- Un enlace para **abrir el producto en WordPress** (ventana nueva) cuando haga falta algo más.
- Con el diseño del POS, no el de WordPress.

Sustituiría el acceso «Productos» de la cabecera, que hoy abre las pantallas de WooCommerce dentro de una ventana (`Admin_Shortcuts`).

Además, el bridge está creciendo, así que se plantea a la vez **una estructura de módulos** para que cada función viva separada y sea fácil de manejar.

## Lo que ya existe (confirmado leyendo el código)

**En el bridge**
- La pantalla **Marcas** es el antecedente exacto: se añade al menú con el filtro `wkwcpos_menus_list` y como página con `wkwcpos_pages_list`, y se dibuja con JavaScript dentro del diseño del POS.
- Los accesos de la cabecera salen de `Admin_Shortcuts` (el de «Productos» abre WooCommerce en una ventana dentro del POS).
- Las rutas REST propias (`vfwoo-webkul/v1/...`) se autentican con la sesión del POS (`Pos_Auth::is_valid`).
- `Catalog_Integration` sube una **versión de catálogo** al guardar o borrar un producto, y el POS avisa de que hay que hacer Resync.
- Hay un rol opcional de encargado para los cajeros (`Cashier_Roles`, les añade `shop_manager`).
- `pos-bridge.js` tiene unas 1.550 líneas con todo mezclado. El PHP ya está dividido por clases, pero sin una estructura de módulos.

**En Webkul**
- La tabla `woocommerce_pos_outlet_product_map` guarda, por tienda (outlet) y producto, el estado `pos_status` (`enabled` / `disabled`).
- Funciones propias para leerlo y cambiarlo: `Outlet_Product_Helper::get_pos_product_status()` y `update_pos_product_status()` (namespace `WKWC_POS\Helper\Outlet\Product`).
- La tabla `woocommerce_pos_outlet_map` une cada usuario con su tienda.
- Por defecto, todo producto nuevo se habilita en todas las tiendas (ajuste `_pos_product_default_status`). Importante en un marketplace.
- El POS ya edita el GTIN de un producto desde la caja (ruta `update-product-gtin`), con comprobación de duplicados.
- Con stock centralizado el stock es el de WooCommerce. Con Master Stock cada tienda lleva el suyo (`pos_stock`).

## Qué significa «se ve» (dos cosas distintas)

| Dónde | Qué lo decide |
|---|---|
| **En la tienda online** | El estado del producto (publicado, borrador, privado) y su visibilidad en el catálogo (`catalog_visibility`: tienda y búsqueda, solo tienda, solo búsqueda, oculto). |
| **En la caja** | `pos_status` del producto en la tienda (outlet) del usuario. |

Son independientes: un producto puede estar en la caja y no en la web, o al revés. La pantalla los muestra como dos interruptores y dos filtros.

## Pantalla

**Listado.** Una fila por producto, con foto pequeña, nombre, SKU y GTIN, precio (con la oferta si la hay), stock y los dos interruptores **En la tienda** y **En la caja**. Paginado, para catálogos grandes.

**Filtros.** Búsqueda por nombre, SKU o GTIN; en la caja / no; en la tienda / no; categoría; marca; stock (con, sin, bajo); y un filtro de **«incompletos»**: sin foto, sin precio, sin SKU o GTIN, o con SKU o GTIN repetido.

**Acciones por fila.** Los dos interruptores, «Editar» (abre el popup) y «Abrir en WordPress» (ventana nueva).

**Popup de edición básica.** Título, precio normal, precio de oferta con sus fechas, SKU, GTIN/EAN, gestión de stock y cantidad, estado (publicado/borrador), foto principal y galería. La foto, desde la tablet, puede venir **directamente de la cámara**.

**Fuera de la primera versión.** Crear productos, variaciones, categorías, etiquetas o marcas (el módulo solo usa lo que ya existe), atributos, productos agrupados o externos, descripciones largas y envío. Las fotos quedan para después si dan problemas.

## Cómo se construiría (técnico mínimo)

**Rutas REST** en `vfwoo-webkul/v1`, todas con `Pos_Auth` y comprobación de permisos:

| Ruta | Para qué |
|---|---|
| `GET products` | Lista paginada con filtros |
| `GET products/{id}` | Datos para el popup |
| `POST products/{id}` | Guardar los datos básicos |
| `POST products/{id}/visibility` | Cambiar «tienda» o «caja» |
| `POST products/{id}/image` | Subir o quitar fotos |

- **Datos y guardado con las APIs de WooCommerce** (`WC_Product` y sus métodos), no con SQL, como pide la regla de WordPress del sistema.
- **Estado en la caja** con las funciones de Webkul citadas arriba, no con SQL propio.
- **Fotos:** `media_handle_upload` / `media_handle_sideload`, con límites de tamaño y de tipo. Sin comprobar cómo se comporta con la autenticación del POS, que no usa las cookies de WordPress.
- **Permisos:** un cajero normal no debe editar precios. Se comprueba una capacidad concreta del usuario (`edit_products`) y se podría separar «ver», «activar/quitar de la caja» y «editar», con un ajuste en el administrador.
- **Aviso al POS:** al guardar, el catálogo sube de versión (ya existe) y la caja avisa del Resync. Pendiente de ver si se puede actualizar solo el producto editado sin Resync completo.

## Estructura de módulos (propuesta)

### Idea

Cada función grande es **un módulo**: sus clases PHP, su JavaScript, su CSS y su ayuda, en su carpeta, con un pequeño archivo que lo describe. Un registro central sabe qué módulos existen, cuáles están activos y cuáles pueden activarse. En el administrador hay una lista con un interruptor por módulo.

Para no confundir con los «módulos» de VFWoo (otro plugin), en el bridge se llaman **módulos del bridge**.

### Carpetas

```
vfwoo-webkul-pos-bridge/
├── vfwoo-webkul-pos-bridge.php
├── includes/
│   ├── class-plugin.php            ← arranca; pide al registro los módulos activos
│   ├── core/                       ← lo que no se puede apagar
│   │   ├── class-module-registry.php   (registro y carga de módulos)
│   │   ├── class-capabilities.php
│   │   ├── class-pos-auth.php
│   │   ├── class-requirements.php
│   │   └── class-github-updater.php
│   └── modules/
│       ├── products/               ← el módulo nuevo
│       │   ├── module.php          (manifiesto: id, nombre, requisitos, clase de arranque)
│       │   ├── class-products-module.php
│       │   ├── class-products-api.php
│       │   ├── class-products-query.php
│       │   └── class-products-visibility.php
│       ├── brands/                 ← futuro: marcas (filtro, informe)
│       ├── invoices/               ← futuro: cliente fiscal, ticket→factura, PDF
│       └── ...
└── assets/
    ├── js/
    │   ├── core.js                 ← compartido: ganchos de Webkul, configuración, peticiones, iconos
    │   └── modules/products.js     ← solo se carga si el módulo está activo
    └── css/modules/products.css
```

### Manifiesto de un módulo (idea)

Un archivo `module.php` que devuelve un array:

```php
return array(
    'id'          => 'products',
    'label'       => 'Productos',
    'description' => 'Gestor ligero de productos dentro del POS.',
    'default'     => true,                       // activo por defecto
    'requires'    => static function () {        // misma idea que Capabilities
        return class_exists( 'WKWC_POS\\Helper\\Outlet\\Product\\Outlet_Product_Helper' );
    },
    'bootstrap'   => 'Products_Module',          // clase que registra hooks y rutas
    'script'      => 'modules/products.js',
    'style'       => 'modules/products.css',
);
```

### Reglas

1. Un módulo **no usa clases de otro módulo.** Lo común va a `core`.
2. Cada módulo tiene **sus propias rutas REST, su clave de configuración y su JS y CSS.** Nada de añadir 300 líneas más a `pos-bridge.js`.
3. Si le falta un requisito, **no se registra** y el administrador muestra por qué (como el panel «Qué está activo»).
4. Un interruptor apagado deja el POS exactamente como estaba, sin errores.
5. Cada módulo añade su página en `_dev/ayuda-caja/` y su decisión en `_dev/decisiones.md`.

### Qué cuenta como módulo hoy

| Pieza actual | Sería módulo |
|---|---|
| Marcas: filtro, categoría, informe | `brands` |
| Cliente fiscal, ticket→factura, PDF, estado del pedido | `invoices` |
| Accesos de la cabecera y barra de administrador | `shortcuts` |
| Pantalla de acceso | `login` |
| Capabilities, Requirements, Github_Updater, Pos_Auth | `core` (no se apaga) |

### Cómo migrar sin liarla

- **No mover nada que ya funciona ahora.** El módulo de productos se hace nuevo, ya con esta estructura.
- Primero, sacar de `pos-bridge.js` lo compartido a un `core.js` (configuración, `wp.hooks`, peticiones, iconos), **sin cambiar el comportamiento**. Es el único cambio sobre código existente y se prueba antes de seguir.
- Después, mover los módulos antiguos de uno en uno, cuando haya motivo (cuando se toquen por otra razón).

## Funcionalidades que creo útiles

**Primera versión (lo que pides)**
1. Listado, buscador y filtros por tienda y caja.
2. Interruptores «en la tienda» y «en la caja».
3. Popup de edición básica (la foto, si no da problemas; si no, después). Incluye productos simples y variables, solo actualizando.
4. Abrir en WordPress.

**Segunda versión (poco esfuerzo y mucho valor)**
5. **Alta rápida por código de barras:** se escanea un código que no existe y se crea el producto con nombre, precio, stock y foto, ya con ese GTIN.
6. **Filtro de «incompletos»** (sin foto, sin SKU o GTIN, repetidos).
7. **Acciones masivas:** quitar o incluir varios productos de la caja a la vez.
8. **Ajuste de stock** rápido, con nota.

**Más adelante**
9. Imprimir etiquetas con el código de barras (reusando lo de Webkul).
10. Productos variables, categorías y marcas desde el popup.
11. Registro de cambios (quién cambió qué y cuándo).

## Fases

| Fase | Qué se hace |
|---|---|
| 0. Base | **Hecha en código (2026-11-19), sin commit.** Registro de módulos y `core.js`; `pos-bridge.js` usa sus ayudantes. Probado: sintaxis, filtros y `addScreen` con ganchos simulados, registro PHP con módulos falsos. **Falta** abrir la caja con sesión y comprobar que todo sigue igual (login a cargo del usuario). Detalle en `decisiones.md` D-030. |
| 1. Lista | **Hecha.** Pantalla de productos con listado, filtros e interruptores de tienda y caja. |
| 2. Edición | **Hecha sin fotos.** Popup de edición básica (simples y variaciones existentes). Fotos aplazadas. |
| 3. Permisos | **Hecha.** Se comprueba en el servidor; sin probar con un vendedor real. |
| 4. Mejoras | **Hecha** (alta rápida, incompletos, masivas). Queda el ajuste de stock con nota. |

## Riesgos

- **Permisos:** editar precios desde la caja es delicado. Por defecto, solo para quien ya puede editar productos en WordPress.
- **Catálogo local del POS:** la caja guarda los productos en el navegador. Un cambio puede no verse hasta el Resync.
- **Marketplace con WCFM:** si cada vendedor tiene su caja, la lista debe mostrar solo sus productos (ver `plan-marketplace-vendedores.md`).
- **Master Stock:** el stock de la caja no es el de WooCommerce. En la primera versión, solo se edita con stock centralizado.
- **Actualizaciones de Webkul:** los ganchos `wkwcpos_menus_list` y `wkwcpos_pages_list` ya los usa la pantalla de Marcas, pero pueden cambiar de firma. Se comprueba tras cada actualización.
- **Variables:** entran en la primera versión pero solo para actualizar datos de lo existente, nunca para crear variaciones.

## Sin verificar

- Cómo se sube una foto con la autenticación del POS (no usa cookies de WordPress).
- Si se puede refrescar solo un producto en el catálogo local sin Resync completo.
- Qué hace el POS si un producto desaparece de su caja mientras hay una venta abierta.
- Si el hook `wkwcpos_pages_list` admite varias páginas nuevas (hoy solo usamos una).

## Decisiones tomadas (19/11/2026)

1. **Productos variables: sí en la primera versión, pero solo editar lo que ya existe.** El módulo **no crea** variaciones, ni categorías, ni etiquetas, ni marcas. Usa siempre lo que hay en WooCommerce y solo actualiza datos, tanto en productos simples como en variables (en las variaciones: precio, oferta, SKU, GTIN, stock).
2. **Fotos: se dejan para después** si resultan complicadas. La primera versión puede salir sin subir fotos.
3. **Quién edita: el cajero que además tiene el modo de gestor de tienda** (rol `shop_manager`, opción `vfwoo_webkul_cashier_manager_role` de `Cashier_Roles`). Un cajero sin ese modo no ve la edición.
4. **La pestaña «Productos» sustituye al acceso actual «Productos» de la cabecera.**
5. **Orden de trabajo: primero la estructura de módulos (fase 0) y luego los módulos.**

## Pendiente de comprobar por estas decisiones

- Cómo se editan las variaciones de forma segura con la API de WooCommerce (`WC_Product_Variation`) y cómo se muestran en el listado (agrupadas bajo su producto).
- Cómo reconocer desde el POS que el cajero tiene el modo de gestor (capability que se comprueba en el servidor, nunca solo en el navegador).
- Si quitar el acceso de la cabecera afecta a `Admin_Shortcuts::items()` y a otros accesos que dependan de él.
