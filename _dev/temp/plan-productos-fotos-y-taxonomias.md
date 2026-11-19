# Plan: fotos y categorías / etiquetas / marcas en el popup de Productos

Estado: **idea, sin empezar.** Pedido por el usuario el 2026-11-19, tras probar las fases 0 a 4. Regla: solo se usan los términos que ya existen; no se crea ninguno.

## Fotos

Hoy: la lista enseña la miniatura, pero el popup no permite cambiarla ni añadir fotos.

Qué haría falta:
- **Subida:** el POS no usa las cookies de WordPress, así que la subida iría por una ruta propia (`POST /products/image`) con `FormData` (no JSON), la cabecera `authkey` y `logged_in_user_id`, como el resto, comprobando `Pos_Auth`, `edit_post` del producto y `upload_files` del usuario.
- **En el servidor:** `media_handle_upload()` (cargando `wp-admin/includes/file.php`, `image.php` y `media.php`) con el producto como padre, `wp_set_current_user()` durante la petición para que el autor sea el cajero, y `set_image_id()` / `set_gallery_image_ids()` en el producto.
- **En la tablet:** `<input type="file" accept="image/*" capture="environment">` abre la cámara. Reducir la imagen en el navegador (canvas, ~1600 px) antes de subirla.
- **Qué se podría hacer:** poner o cambiar la foto principal, añadir fotos a la galería, quitar una foto del producto (no borrarla de la biblioteca) y, para las variaciones, su foto.
- **Sin verificar:** que el usuario de la caja tenga `upload_files` (el rol gestor de WooCommerce lo trae; hay que comprobarlo); límite de subida del servidor; que el POS muestre la foto nueva tras Resync.
- Riesgo medio: es lo único que escribe archivos. Probar en local con una imagen de prueba y dejar la biblioteca limpia.

## Categorías, etiquetas, marcas y atributos (solo los que existen)

Idea: en el popup, un bloque **Clasificación** con una lista con buscador y casillas por cada taxonomía:
- **Categorías** (`product_cat`), **etiquetas** (`product_tag`) y **marcas** (`product_brand`, si existe). Las que ya usa `Catalog_Integration` para los filtros del POS se pueden reutilizar.
- Se cargan los términos existentes; el usuario marca o desmarca. Se guarda con `set_category_ids()` / `set_tag_ids()` o `wp_set_object_terms()` con ids existentes (nunca nombres, para que WordPress no cree términos). El servidor comprueba que cada id exista en esa taxonomía y que el usuario tenga `assign_product_terms`.
- **Filtros de la lista:** ya hay filtro por categoría; se podría añadir por etiqueta y marca.
- **Acciones masivas:** «añadir a categoría» / «quitar de categoría» sobre las filas marcadas, también con términos existentes.

**Atributos** (talla, color…): en un producto variable hay dos niveles distintos.
- *Qué valores usa el producto* (p. ej. tallas S, M, L): cambiarlo altera qué variaciones son posibles. **No se tocaría** desde el popup.
- *Qué valor tiene cada variación*: ya existe y es lo que se ve en su etiqueta; tampoco se cambia.
- Lo que sí tiene sentido: **mostrar** los atributos de solo lectura, para saber con qué se trabaja.

## Orden propuesto
1. Clasificación (categorías, etiquetas, marcas): bajo riesgo, solo guarda relaciones con términos existentes.
2. Filtros por etiqueta y marca y acción masiva de categoría.
3. Fotos.
