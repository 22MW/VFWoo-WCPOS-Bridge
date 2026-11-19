<?php
/**
 * "Productos" module: product list and visibility switches for the POS.
 *
 * Uses what WooCommerce and Webkul already provide: it never creates products, variations, categories, tags or brands.
 * Every route checks the POS session (Pos_Auth) and that the user can edit products.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Products_Module {
	private const PER_PAGE = 30;
	private const STATUSES = array( 'publish', 'draft', 'pending', 'private' );

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'vfwoo_webkul_shortcuts', array( $this, 'replace_header_shortcut' ) );
	}

	/**
	 * The new tab replaces the "Productos" access of the header.
	 *
	 * @param mixed $items Header shortcuts.
	 */
	public function replace_header_shortcut( $items ): array {
		$items = is_array( $items ) ? $items : array();
		return array_values(
			array_filter(
				$items,
				static function ( $item ): bool {
					return ! is_array( $item ) || ! isset( $item['id'] ) || 'products' !== $item['id'];
				}
			)
		);
	}

	/**
	 * Data the script reads (see Module_Registry).
	 */
	public static function config(): array {
		$can = current_user_can( 'edit_products' );

		$config = array(
			'enabled' => $can,
		);
		if ( ! $can ) {
			return $config;
		}

		$categories = array();
		$terms      = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = array(
					'id'   => (int) $term->term_id,
					'name' => $term->name,
				);
			}
		}

		return $config + array(
			'listUrl'       => esc_url_raw( rest_url( 'vfwoo-webkul/v1/products' ) ),
			'visibilityUrl' => esc_url_raw( rest_url( 'vfwoo-webkul/v1/products/visibility' ) ),
			'getUrl'        => esc_url_raw( rest_url( 'vfwoo-webkul/v1/products/get' ) ),
			'updateUrl'     => esc_url_raw( rest_url( 'vfwoo-webkul/v1/products/update' ) ),
			'bulkUrl'       => esc_url_raw( rest_url( 'vfwoo-webkul/v1/products/bulk' ) ),
			'createUrl'     => esc_url_raw( rest_url( 'vfwoo-webkul/v1/products/create' ) ),
			'canCreate'     => current_user_can( 'publish_products' ),
			'allUrl'        => admin_url( 'edit.php?post_type=product' ),
			'categories'    => $categories,
			'brands'        => self::brands(),
			'labels'        => array(
				'menu'        => __( 'Productos', 'vfwoo-webkul-pos-bridge' ),
				'title'       => __( 'Productos', 'vfwoo-webkul-pos-bridge' ),
				'back'        => __( 'Volver', 'vfwoo-webkul-pos-bridge' ),
				'search'      => __( 'Buscar por nombre, SKU o código de barras', 'vfwoo-webkul-pos-bridge' ),
				'shopAll'     => __( 'Tienda: todos', 'vfwoo-webkul-pos-bridge' ),
				'shopYes'     => __( 'Visibles en la tienda', 'vfwoo-webkul-pos-bridge' ),
				'shopNo'      => __( 'Ocultos en la tienda', 'vfwoo-webkul-pos-bridge' ),
				'posAll'      => __( 'Caja: todos', 'vfwoo-webkul-pos-bridge' ),
				'posYes'      => __( 'En la caja', 'vfwoo-webkul-pos-bridge' ),
				'posNo'       => __( 'Fuera de la caja', 'vfwoo-webkul-pos-bridge' ),
				'stockAll'    => __( 'Stock: todos', 'vfwoo-webkul-pos-bridge' ),
				'stockIn'     => __( 'Con stock', 'vfwoo-webkul-pos-bridge' ),
				'stockOut'    => __( 'Agotados', 'vfwoo-webkul-pos-bridge' ),
				'categoryAll' => __( 'Categorías', 'vfwoo-webkul-pos-bridge' ),
				'product'     => __( 'Producto', 'vfwoo-webkul-pos-bridge' ),
				'price'       => __( 'Precio', 'vfwoo-webkul-pos-bridge' ),
				'stock'       => __( 'Stock', 'vfwoo-webkul-pos-bridge' ),
				'inShop'      => __( 'En la tienda', 'vfwoo-webkul-pos-bridge' ),
				'inPos'       => __( 'En la caja', 'vfwoo-webkul-pos-bridge' ),
				'openWp'      => __( 'Abrir en WordPress', 'vfwoo-webkul-pos-bridge' ),
				'allInWp'     => __( 'Editar en WordPress', 'vfwoo-webkul-pos-bridge' ),
				'loading'     => __( 'Cargando…', 'vfwoo-webkul-pos-bridge' ),
				'empty'       => __( 'No hay productos con estos filtros.', 'vfwoo-webkul-pos-bridge' ),
				'error'       => __( 'No se han podido cargar los productos.', 'vfwoo-webkul-pos-bridge' ),
				'saveError'   => __( 'No se ha podido guardar el cambio.', 'vfwoo-webkul-pos-bridge' ),
				'prev'        => __( 'Anterior', 'vfwoo-webkul-pos-bridge' ),
				'next'        => __( 'Siguiente', 'vfwoo-webkul-pos-bridge' ),
				/* translators: 1: current page, 2: number of pages, 3: number of products. */
				'pageOf'      => __( '%3$s productos · página %1$s de %2$s', 'vfwoo-webkul-pos-bridge' ),
				'draft'       => __( 'Borrador', 'vfwoo-webkul-pos-bridge' ),
				'pending'     => __( 'Pendiente', 'vfwoo-webkul-pos-bridge' ),
				'private'     => __( 'Privado', 'vfwoo-webkul-pos-bridge' ),
				'variable'    => __( 'Variable', 'vfwoo-webkul-pos-bridge' ),
				'notPublished' => __( 'Solo se puede cambiar en productos publicados.', 'vfwoo-webkul-pos-bridge' ),
				'resync'      => __( 'Para ver el cambio en esta caja, pulsa Resync → Products.', 'vfwoo-webkul-pos-bridge' ),
				'outOfStock'  => __( 'Agotado', 'vfwoo-webkul-pos-bridge' ),
				'inStock'     => __( 'En stock', 'vfwoo-webkul-pos-bridge' ),
				'backorder'   => __( 'Bajo pedido', 'vfwoo-webkul-pos-bridge' ),
				'edit'        => __( 'Editar', 'vfwoo-webkul-pos-bridge' ),
				'incAll'      => __( 'Incompletos', 'vfwoo-webkul-pos-bridge' ),
				'brandAll'    => __( 'Marcas', 'vfwoo-webkul-pos-bridge' ),
				'saleAll'     => __( 'Ofertas: todas', 'vfwoo-webkul-pos-bridge' ),
				'saleYes'     => __( 'En oferta', 'vfwoo-webkul-pos-bridge' ),
				'saleNo'      => __( 'Sin oferta', 'vfwoo-webkul-pos-bridge' ),
				'sortNewest'  => __( 'Más recientes', 'vfwoo-webkul-pos-bridge' ),
				'sortSold'    => __( 'Más vendidos', 'vfwoo-webkul-pos-bridge' ),
				'sortName'    => __( 'Nombre A-Z', 'vfwoo-webkul-pos-bridge' ),
				'sortNameDesc' => __( 'Nombre Z-A', 'vfwoo-webkul-pos-bridge' ),
				'sortPriceAsc' => __( 'Precio: menor a mayor', 'vfwoo-webkul-pos-bridge' ),
				'sortPriceDesc' => __( 'Precio: mayor a menor', 'vfwoo-webkul-pos-bridge' ),
				'sortStockAsc' => __( 'Stock: menor a mayor', 'vfwoo-webkul-pos-bridge' ),
				'sortStockDesc' => __( 'Stock: mayor a menor', 'vfwoo-webkul-pos-bridge' ),
				'first'       => __( 'Primera página', 'vfwoo-webkul-pos-bridge' ),
				'last'        => __( 'Última página', 'vfwoo-webkul-pos-bridge' ),
				'incPhoto'    => __( 'Sin foto', 'vfwoo-webkul-pos-bridge' ),
				'incPrice'    => __( 'Sin precio', 'vfwoo-webkul-pos-bridge' ),
				'incSku'      => __( 'Sin SKU', 'vfwoo-webkul-pos-bridge' ),
				'incGtin'     => __( 'Sin código de barras', 'vfwoo-webkul-pos-bridge' ),
				'incDuplicate' => __( 'SKU repetido', 'vfwoo-webkul-pos-bridge' ),
				'selectPage'  => __( 'Seleccionar todos los de la página', 'vfwoo-webkul-pos-bridge' ),
				/* translators: %s: number of selected products. */
				'selected'    => __( '%s seleccionados', 'vfwoo-webkul-pos-bridge' ),
				'bulkPosOn'   => __( 'Incluir en la caja', 'vfwoo-webkul-pos-bridge' ),
				'bulkPosOff'  => __( 'Quitar de la caja', 'vfwoo-webkul-pos-bridge' ),
				'bulkShopOn'  => __( 'Mostrar en la tienda', 'vfwoo-webkul-pos-bridge' ),
				'bulkShopOff' => __( 'Ocultar en la tienda', 'vfwoo-webkul-pos-bridge' ),
				/* translators: 1: products changed, 2: products skipped. */
				'bulkDone'    => __( 'Hecho: %1$s cambiados, %2$s sin cambiar.', 'vfwoo-webkul-pos-bridge' ),
				'newQuick'    => __( 'Nuevo producto', 'vfwoo-webkul-pos-bridge' ),
				'newTitle'    => __( 'Nuevo producto rápido', 'vfwoo-webkul-pos-bridge' ),
				'newNote'     => __( 'Crea un producto simple y lo incluye en esta caja. Para categorías, marcas, fotos y más datos, edítalo después en WordPress.', 'vfwoo-webkul-pos-bridge' ),
				'create'      => __( 'Crear producto', 'vfwoo-webkul-pos-bridge' ),
				'showInShop'  => __( 'Mostrar también en la tienda online', 'vfwoo-webkul-pos-bridge' ),
				'created'     => __( 'Producto creado.', 'vfwoo-webkul-pos-bridge' ),
				/* translators: %s: scanned code. */
				'createWithCode' => __( 'Crear producto con el código %s', 'vfwoo-webkul-pos-bridge' ),
				'save'        => __( 'Guardar', 'vfwoo-webkul-pos-bridge' ),
				'saving'      => __( 'Guardando…', 'vfwoo-webkul-pos-bridge' ),
				'cancel'      => __( 'Cancelar', 'vfwoo-webkul-pos-bridge' ),
				'close'       => __( 'Cerrar', 'vfwoo-webkul-pos-bridge' ),
				'saved'       => __( 'Cambios guardados.', 'vfwoo-webkul-pos-bridge' ),
				'name'        => __( 'Nombre', 'vfwoo-webkul-pos-bridge' ),
				'statusLabel' => __( 'Estado', 'vfwoo-webkul-pos-bridge' ),
				'published'   => __( 'Publicado', 'vfwoo-webkul-pos-bridge' ),
				'regularPrice' => __( 'Precio normal', 'vfwoo-webkul-pos-bridge' ),
				'salePrice'   => __( 'Precio de oferta', 'vfwoo-webkul-pos-bridge' ),
				'saleFrom'    => __( 'Oferta desde', 'vfwoo-webkul-pos-bridge' ),
				'saleTo'      => __( 'Oferta hasta', 'vfwoo-webkul-pos-bridge' ),
				'sku'         => __( 'SKU', 'vfwoo-webkul-pos-bridge' ),
				'gtin'        => __( 'Código de barras (GTIN, UPC, EAN, ISBN)', 'vfwoo-webkul-pos-bridge' ),
				'manageStock' => __( 'Gestionar el stock', 'vfwoo-webkul-pos-bridge' ),
				'quantity'    => __( 'Cantidad', 'vfwoo-webkul-pos-bridge' ),
				'stockStatus' => __( 'Estado del stock', 'vfwoo-webkul-pos-bridge' ),
				'variations'  => __( 'Variaciones', 'vfwoo-webkul-pos-bridge' ),
				'variationsNote' => __( 'Solo se editan las variaciones que ya existen. Para crear otras, usa WordPress.', 'vfwoo-webkul-pos-bridge' ),
				'priceNote'   => __( 'El precio de un producto variable sale de sus variaciones.', 'vfwoo-webkul-pos-bridge' ),
				'stockLocked' => __( 'El stock del POS no es el de la tienda (Master Stock): se edita en WordPress.', 'vfwoo-webkul-pos-bridge' ),
				'unsupported' => __( 'Este tipo de producto se edita en WordPress.', 'vfwoo-webkul-pos-bridge' ),
			),
		);
	}

	/**
	 * Existing brands (the product_brand taxonomy), or none when the shop has no brands.
	 *
	 * @return array<int,array{id:int,name:string}>
	 */
	private static function brands(): array {
		if ( ! taxonomy_exists( 'product_brand' ) ) {
			return array();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_brand',
				'hide_empty' => false,
			)
		);
		$out   = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$out[] = array(
				'id'   => (int) $term->term_id,
				'name' => $term->name,
			);
		}
		return $out;
	}

	public function register_routes(): void {
		foreach ( array(
			'/products'            => 'handle_list',
			'/products/visibility' => 'handle_visibility',
			'/products/get'        => 'handle_get',
			'/products/update'     => 'handle_update',
			'/products/bulk'       => 'handle_bulk',
			'/products/create'     => 'handle_create',
		) as $route => $method ) {
			register_rest_route(
				'vfwoo-webkul/v1',
				$route,
				array(
					'methods'             => 'POST',
					'permission_callback' => '__return_true', // Authenticated inside with Webkul's POS session.
					'callback'            => array( $this, $method ),
				)
			);
		}
	}

	/**
	 * POS session, permission and the user's outlet. Returns the context, or a ready error response.
	 *
	 * @return array{user_id:int,outlet_id:int}|array<string,mixed>
	 */
	private function context( \WP_REST_Request $request ): array {
		$user_id = absint( $request->get_param( 'logged_in_user_id' ) );
		if ( ! Pos_Auth::is_valid( $user_id ) ) {
			return Pos_Auth::unauthorized();
		}
		if ( ! user_can( $user_id, 'edit_products' ) ) {
			return $this->error( 403, __( 'Tu usuario no puede gestionar productos.', 'vfwoo-webkul-pos-bridge' ) );
		}
		if ( ! class_exists( 'WKWC_POS\\Api\\Helper\\WKWCPOS_API_User_Outlet_Helper' ) || ! class_exists( 'WKWC_POS\\Helper\\Outlet\\Product\\Outlet_Product_Helper' ) ) {
			return $this->error( 500, __( 'No se encuentra el plugin del POS.', 'vfwoo-webkul-pos-bridge' ) );
		}

		$outlet_id = absint( ( new \WKWC_POS\Api\Helper\WKWCPOS_API_User_Outlet_Helper() )->get_pos_user_outlet_with_status( $user_id ) );
		if ( ! $outlet_id ) {
			return $this->error( 400, __( 'Tu usuario no tiene una tienda del POS asignada.', 'vfwoo-webkul-pos-bridge' ) );
		}

		return array(
			'user_id'   => $user_id,
			'outlet_id' => $outlet_id,
		);
	}

	private function error( int $status, string $message ): array {
		return array(
			'success' => false,
			'status'  => $status,
			'message' => $message,
		);
	}

	private function outlet_products(): \WKWC_POS\Helper\Outlet\Product\Outlet_Product_Helper {
		return new \WKWC_POS\Helper\Outlet\Product\Outlet_Product_Helper();
	}

	public function handle_list( \WP_REST_Request $request ) {
		$context = $this->context( $request );
		if ( isset( $context['success'] ) ) {
			return $context;
		}

		$search = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$shop   = $this->choice( $request->get_param( 'shop' ), array( 'yes', 'no' ) );
		$pos    = $this->choice( $request->get_param( 'pos' ), array( 'yes', 'no' ) );
		$stock  = $this->choice( $request->get_param( 'stock' ), array( 'in', 'out' ) );
		$cat    = absint( $request->get_param( 'category' ) );
		$page   = max( 1, absint( $request->get_param( 'page' ) ) );
		$incomplete = $this->choice( $request->get_param( 'incomplete' ), array( 'photo', 'price', 'sku', 'gtin', 'duplicate' ) );
		$sort       = $this->choice( $request->get_param( 'orderby' ), array( 'name_desc', 'date_desc', 'sales_desc', 'price_asc', 'price_desc', 'stock_asc', 'stock_desc' ) );
		$sale       = $this->choice( $request->get_param( 'sale' ), array( 'yes', 'no' ) );
		$brand      = absint( $request->get_param( 'brand' ) );

		$args = array(
			'post_type'      => 'product',
			'post_status'    => self::STATUSES,
			'fields'         => 'ids',
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		// null = no restriction; an array = only these ids (an empty one means no results).
		$include = null;
		$exclude = array();

		if ( '' !== $search ) {
			$include = $this->search_ids( $search );
		}
		if ( $pos ) {
			$in_pos = $this->pos_ids( $context['outlet_id'] );
			if ( 'yes' === $pos ) {
				$include = null === $include ? $in_pos : array_values( array_intersect( $include, $in_pos ) );
			} else {
				$exclude = array_merge( $exclude, $in_pos );
			}
		}
		if ( $shop ) {
			$in_shop = $this->shop_ids();
			if ( 'yes' === $shop ) {
				$include = null === $include ? $in_shop : array_values( array_intersect( $include, $in_shop ) );
			} else {
				$exclude = array_merge( $exclude, $in_shop );
			}
		}

		if ( $sale ) {
			$on_sale = array_map( 'absint', wc_get_product_ids_on_sale() ); // Variable products count when a variation is on sale.
			if ( 'yes' === $sale ) {
				$include = null === $include ? $on_sale : array_values( array_intersect( $include, $on_sale ) );
			} else {
				$exclude = array_merge( $exclude, $on_sale );
			}
		}

		if ( is_array( $include ) ) {
			if ( empty( $include ) ) {
				return $this->list_response( array(), 0, 0, $page, $context['outlet_id'] );
			}
			$args['post__in'] = $include;
		}
		if ( $exclude ) {
			$args['post__not_in'] = array_values( array_unique( $exclude ) );
		}
		$tax = array();
		if ( $cat ) {
			$tax[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => array( $cat ),
			);
		}
		if ( $brand && taxonomy_exists( 'product_brand' ) ) {
			$tax[] = array(
				'taxonomy' => 'product_brand',
				'field'    => 'term_id',
				'terms'    => array( $brand ),
			);
		}
		if ( $tax ) {
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filters chosen by the user, one page of results.
		}
		// Someone who cannot edit other people's products (a vendor) only sees their own.
		if ( ! user_can( $context['user_id'], 'edit_others_products' ) ) {
			$args['author'] = $context['user_id'];
		}

		$meta = array();
		if ( $stock ) {
			$meta[] = array(
				'key'   => '_stock_status',
				'value' => 'in' === $stock ? 'instock' : 'outofstock',
			);
		}
		if ( 'duplicate' === $incomplete ) {
			$duplicates = $this->duplicate_sku_ids();
			if ( empty( $duplicates ) ) {
				return $this->list_response( array(), 0, 0, $page, $context['outlet_id'] );
			}
			$args['post__in'] = isset( $args['post__in'] ) ? array_values( array_intersect( $args['post__in'], $duplicates ) ) : $duplicates;
			if ( empty( $args['post__in'] ) ) {
				return $this->list_response( array(), 0, 0, $page, $context['outlet_id'] );
			}
		} elseif ( $incomplete ) {
			$key    = array(
				'photo' => '_thumbnail_id',
				'price' => '_price',
				'sku'   => '_sku',
				'gtin'  => '_global_unique_id',
			)[ $incomplete ];
			$meta[] = array(
				'relation' => 'OR',
				array(
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => $key,
					'value' => '',
				),
			);
		}
		if ( 'name_desc' === $sort ) {
			$args['order'] = 'DESC';
		} elseif ( 'date_desc' === $sort ) {
			$args['orderby'] = array(
				'date'  => 'DESC',
				'title' => 'ASC',
			);
		} elseif ( $sort ) {
			// Price, stock or sales: ordered by a numeric meta value. Products without it are kept (NOT EXISTS) and
			// listed first in ascending order. Ties are broken by name.
			list( $field, $direction ) = explode( '_', $sort );
			$key                       = array(
				'price' => '_price',
				'stock' => '_stock',
				'sales' => 'total_sales',
			)[ $field ];
			$meta[]                    = array(
				'relation'    => 'OR',
				'sort_clause' => array(
					'key'     => $key,
					'type'    => 'NUMERIC',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				),
			);
			$args['orderby']           = array(
				'sort_clause' => strtoupper( $direction ),
				'title'       => 'ASC',
			);
		}
		if ( $meta ) {
			$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filter chosen by the user, one page of results.
		}

		$query = new \WP_Query( $args );

		return $this->list_response( array_map( 'absint', $query->posts ), (int) $query->found_posts, (int) $query->max_num_pages, $page, $context['outlet_id'] );
	}

	private function list_response( array $ids, int $total, int $pages, int $page, int $outlet_id ): array {
		$items = array();
		foreach ( $ids as $id ) {
			$row = $this->row( $id, $outlet_id );
			if ( $row ) {
				$items[] = $row;
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'items' => $items,
				'total' => $total,
				'pages' => $pages,
				'page'  => $page,
			),
		);
	}

	/**
	 * A closed list of values for a filter; anything else means "no filter".
	 *
	 * @param mixed    $value   Posted value.
	 * @param string[] $allowed Allowed values.
	 */
	private function choice( $value, array $allowed ): string {
		$value = sanitize_key( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/**
	 * Product ids matching a text in name, SKU or barcode. A variation counts for its parent.
	 *
	 * @return int[]
	 */
	private function search_ids( string $term ): array {
		$ids = \WC_Data_Store::load( 'product' )->search_products( $term, '', true, false, 500 );
		$out = array();
		foreach ( array_map( 'absint', (array) $ids ) as $id ) {
			$parent = (int) wp_get_post_parent_id( $id );
			$out[]  = $parent ? $parent : $id;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Products the POS shows in this outlet: the same list the cashier gets.
	 *
	 * @return int[]
	 */
	private function pos_ids( int $outlet_id ): array {
		$rows = ( new \WKWC_POS\Api\Helper\WKWCPOS_API_User_Outlet_Helper() )->get_pos_user_outlet_products( $outlet_id );
		return is_array( $rows ) ? array_values( array_map( 'absint', wp_list_pluck( $rows, 'product_id' ) ) ) : array();
	}

	/**
	 * Published products that are not hidden in the catalog (visible in the shop or its search).
	 *
	 * @return int[]
	 */
	private function shop_ids(): array {
		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Same rule WooCommerce uses for "hidden".
					'relation' => 'OR',
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'name',
						'terms'    => array( 'exclude-from-catalog' ),
						'operator' => 'NOT IN',
					),
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'name',
						'terms'    => array( 'exclude-from-search' ),
						'operator' => 'NOT IN',
					),
				),
			)
		);
		return array_map( 'absint', $query->posts );
	}

	/**
	 * The price as short text: "78,00 € → 70,50 €" in a sale, a range for a variable product.
	 */
	private function plain_price( \WC_Product $product ): string {
		$html = (string) $product->get_price_html();
		$html = preg_replace( '#<span class="screen-reader-text">.*?</span>#s', '', $html );
		$html = preg_replace( '#<del[^>]*>(.*?)</del>#s', '$1 → ', (string) $html );
		return trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' ) ) );
	}

	/**
	 * One row of the list.
	 */
	private function row( int $id, int $outlet_id ): ?array {
		$product = wc_get_product( $id );
		if ( ! $product || 'product' !== get_post_type( $id ) ) {
			return null;
		}

		$status = $product->get_status();
		$hidden = 'hidden' === $product->get_catalog_visibility();
		$image  = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';
		$stock  = $product->managing_stock() ? $product->get_stock_quantity() : null;
		$terms  = get_the_terms( $id, 'product_cat' );

		return array(
			'id'           => $id,
			'name'         => $product->get_name(),
			'sku'          => $product->get_sku(),
			'gtin'         => method_exists( $product, 'get_global_unique_id' ) ? (string) $product->get_global_unique_id() : '',
			'type'         => $product->get_type(),
			'variations'   => $product->is_type( 'variable' ) ? count( $product->get_children() ) : 0,
			'status'       => $status,
			'shop'         => 'publish' === $status && ! $hidden,
			'pos'          => 'enabled' === $this->outlet_products()->get_pos_product_status( $id, $outlet_id ),
			'price'        => $this->plain_price( $product ),
			'stockStatus'  => $product->get_stock_status(),
			'stockQty'     => null === $stock ? null : (int) $stock,
			'image'        => $image ? $image : '',
			'categories'   => is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array(),
			'editUrl'      => admin_url( 'post.php?post=' . $id . '&action=edit' ),
		);
	}


	/**
	 * Types the popup can edit. Others are opened in WordPress.
	 */
	private function editable_type( \WC_Product $product ): bool {
		return in_array( $product->get_type(), array( 'simple', 'variable' ), true );
	}

	private function stock_is_editable(): bool {
		return (bool) apply_filters( 'wk_wc_pos_enable_centralized_inventory', false );
	}

	private function date_value( $date ): string {
		return $date ? $date->date( 'Y-m-d' ) : '';
	}

	/**
	 * Fields the popup edits, shared by products and variations.
	 *
	 * @param \WC_Product $product Product or variation.
	 */
	private function fields( \WC_Product $product ): array {
		return array(
			'id'           => $product->get_id(),
			'regularPrice' => (string) $product->get_regular_price( 'edit' ),
			'salePrice'    => (string) $product->get_sale_price( 'edit' ),
			'saleFrom'     => $this->date_value( $product->get_date_on_sale_from( 'edit' ) ),
			'saleTo'       => $this->date_value( $product->get_date_on_sale_to( 'edit' ) ),
			'sku'          => (string) $product->get_sku( 'edit' ),
			'gtin'         => method_exists( $product, 'get_global_unique_id' ) ? (string) $product->get_global_unique_id( 'edit' ) : '',
			'manageStock'  => (bool) $product->get_manage_stock( 'edit' ),
			'stockQty'     => null === $product->get_stock_quantity( 'edit' ) ? '' : (int) $product->get_stock_quantity( 'edit' ),
			'stockStatus'  => $product->get_stock_status( 'edit' ),
		);
	}

	private function editable_payload( \WC_Product $product ): array {
		$data = $this->fields( $product ) + array(
			'name'           => $product->get_name( 'edit' ),
			'status'         => $product->get_status( 'edit' ),
			'type'           => $product->get_type(),
			'editable'       => $this->editable_type( $product ),
			'stockEditable'  => $this->stock_is_editable(),
			'editUrl'        => admin_url( 'post.php?post=' . $product->get_id() . '&action=edit' ),
			'variations'     => array(),
		);

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( $variation instanceof \WC_Product_Variation ) {
					$data['variations'][] = $this->fields( $variation ) + array(
						'label' => wc_get_formatted_variation( $variation, true, false, false ),
					);
				}
			}
		}

		return $data;
	}

	public function handle_get( \WP_REST_Request $request ) {
		$context = $this->context( $request );
		if ( isset( $context['success'] ) ) {
			return $context;
		}

		$id      = absint( $request->get_param( 'product_id' ) );
		$product = $id && 'product' === get_post_type( $id ) ? wc_get_product( $id ) : null;
		if ( ! $product ) {
			return $this->error( 400, __( 'Petición no válida.', 'vfwoo-webkul-pos-bridge' ) );
		}
		if ( ! user_can( $context['user_id'], 'edit_post', $id ) ) {
			return $this->error( 403, __( 'No puedes editar este producto.', 'vfwoo-webkul-pos-bridge' ) );
		}

		return array(
			'success' => true,
			'data'    => $this->editable_payload( $product ),
		);
	}

	/**
	 * Sets the fields of a product or variation from the request. Throws on an invalid value.
	 *
	 * @param \WC_Product $product Product or variation.
	 * @param array       $input   Posted fields.
	 * @param bool        $prices  Whether this item has its own price (not a variable parent).
	 * @param bool        $stock   Whether stock can be edited.
	 *
	 * @throws \Exception With a message for the user.
	 */
	private function apply_fields( \WC_Product $product, array $input, bool $prices, bool $stock ): void {
		if ( $prices ) {
			$regular = $this->price( $input['regularPrice'] ?? '' );
			$sale    = $this->price( $input['salePrice'] ?? '' );
			if ( '' !== $sale && '' === $regular ) {
				throw new \Exception( esc_html__( 'Para poner una oferta hace falta el precio normal.', 'vfwoo-webkul-pos-bridge' ) );
			}
			if ( '' !== $sale && (float) $sale >= (float) $regular ) {
				throw new \Exception( esc_html__( 'El precio de oferta debe ser menor que el precio normal.', 'vfwoo-webkul-pos-bridge' ) );
			}
			$product->set_regular_price( $regular );
			$product->set_sale_price( $sale );

			$from = $this->date( $input['saleFrom'] ?? '' );
			$to   = $this->date( $input['saleTo'] ?? '' );
			if ( $from && $to && $to < $from ) {
				throw new \Exception( esc_html__( 'La fecha final de la oferta es anterior a la inicial.', 'vfwoo-webkul-pos-bridge' ) );
			}
			$product->set_date_on_sale_from( $from ? $from . ' 00:00:00' : '' );
			$product->set_date_on_sale_to( $to ? $to . ' 23:59:59' : '' );
		}

		// Only a value the user changed is sent to WooCommerce, which rejects a repeated SKU or code: a product that
		// already shares one (old data) can still be saved.
		if ( array_key_exists( 'sku', $input ) ) {
			$sku = sanitize_text_field( (string) $input['sku'] );
			if ( $sku !== (string) $product->get_sku( 'edit' ) ) {
				$product->set_sku( $sku );
			}
		}
		if ( array_key_exists( 'gtin', $input ) && method_exists( $product, 'set_global_unique_id' ) ) {
			$gtin = sanitize_text_field( (string) $input['gtin'] );
			if ( $gtin !== (string) $product->get_global_unique_id( 'edit' ) ) {
				$product->set_global_unique_id( $gtin );
			}
		}

		if ( $stock ) {
			$manage = ! empty( $input['manageStock'] );
			$product->set_manage_stock( $manage );
			if ( $manage ) {
				$quantity = $input['stockQty'] ?? '';
				if ( '' === $quantity || ! is_numeric( $quantity ) ) {
					throw new \Exception( esc_html__( 'La cantidad de stock no es válida.', 'vfwoo-webkul-pos-bridge' ) );
				}
				$product->set_stock_quantity( (int) $quantity );
			} else {
				$status = $this->choice( $input['stockStatus'] ?? '', array( 'instock', 'outofstock', 'onbackorder' ) );
				if ( $status ) {
					$product->set_stock_status( $status );
				}
			}
		}
	}

	/**
	 * A price as WooCommerce stores it, or empty. Throws on text that is not a price.
	 *
	 * @param mixed $value Posted value.
	 *
	 * @throws \Exception When the value is not a valid price.
	 */
	private function price( $value ): string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		$value = wc_format_decimal( $value );
		if ( ! is_numeric( $value ) || (float) $value < 0 ) {
			throw new \Exception( esc_html__( 'Un precio no es válido.', 'vfwoo-webkul-pos-bridge' ) );
		}
		return (string) $value;
	}

	/**
	 * A date as Y-m-d, or empty. Throws on anything else.
	 *
	 * @param mixed $value Posted value.
	 *
	 * @throws \Exception When the value is not a valid date.
	 */
	private function date( $value ): string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) {
			throw new \Exception( esc_html__( 'Una fecha no es válida.', 'vfwoo-webkul-pos-bridge' ) );
		}
		return $value;
	}

	public function handle_update( \WP_REST_Request $request ) {
		$context = $this->context( $request );
		if ( isset( $context['success'] ) ) {
			return $context;
		}

		$id      = absint( $request->get_param( 'product_id' ) );
		$product = $id && 'product' === get_post_type( $id ) ? wc_get_product( $id ) : null;
		if ( ! $product || ! $this->editable_type( $product ) ) {
			return $this->error( 400, __( 'Petición no válida.', 'vfwoo-webkul-pos-bridge' ) );
		}
		if ( ! user_can( $context['user_id'], 'edit_post', $id ) ) {
			return $this->error( 403, __( 'No puedes editar este producto.', 'vfwoo-webkul-pos-bridge' ) );
		}

		$input = $request->get_param( 'fields' );
		$input = is_array( $input ) ? $input : array();
		$stock = $this->stock_is_editable();

		try {
			$name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
			if ( '' === $name ) {
				throw new \Exception( esc_html__( 'El nombre no puede estar vacío.', 'vfwoo-webkul-pos-bridge' ) );
			}
			$product->set_name( $name );

			$status = $this->choice( $input['status'] ?? '', self::STATUSES );
			if ( $status && $status !== $product->get_status( 'edit' ) ) {
				$type = get_post_type_object( 'product' );
				if ( 'publish' === $status && ( ! $type || ! user_can( $context['user_id'], $type->cap->publish_posts ) ) ) {
					throw new \Exception( esc_html__( 'No puedes publicar productos.', 'vfwoo-webkul-pos-bridge' ) );
				}
				$product->set_status( $status );
			}

			$this->apply_fields( $product, $input, ! $product->is_type( 'variable' ), $stock );

			// Only variations that already belong to this product; none is created.
			$changes = array();
			if ( $product->is_type( 'variable' ) && isset( $input['variations'] ) && is_array( $input['variations'] ) ) {
				$children = array_map( 'absint', $product->get_children() );
				foreach ( $input['variations'] as $posted ) {
					$variation_id = is_array( $posted ) ? absint( $posted['id'] ?? 0 ) : 0;
					if ( ! $variation_id || ! in_array( $variation_id, $children, true ) ) {
						throw new \Exception( esc_html__( 'Una variación no pertenece a este producto.', 'vfwoo-webkul-pos-bridge' ) );
					}
					$variation = wc_get_product( $variation_id );
					if ( ! $variation instanceof \WC_Product_Variation ) {
						throw new \Exception( esc_html__( 'Una variación no es válida.', 'vfwoo-webkul-pos-bridge' ) );
					}
					$this->apply_fields( $variation, $posted, true, $stock );
					$changes[] = $variation;
				}
			}
		} catch ( \Exception $e ) {
			return $this->error( 400, $e->getMessage() );
		}

		try {
			foreach ( $changes as $variation ) {
				$variation->save();
			}
			$product->save();
			if ( $changes ) {
				\WC_Product_Variable::sync( $id );
			}
		} catch ( \Exception $e ) {
			return $this->error( 400, $e->getMessage() );
		}

		Catalog_Integration::bump_catalog_version();
		$fresh = wc_get_product( $id );

		return array(
			'success' => true,
			'data'    => $this->editable_payload( $fresh ),
			'row'     => $this->row( $id, $context['outlet_id'] ),
		);
	}

	/**
	 * Products whose SKU is also used by another product or variation. A variation counts for its parent.
	 *
	 * @return int[]
	 */
	private function duplicate_sku_ids(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'wc_product_meta_lookup';
		// Static query: no user input. WooCommerce keeps the SKUs in this table and has no API to list repeated ones.
		$ids = $wpdb->get_col( "SELECT product_id FROM {$table} WHERE sku <> '' AND sku IN ( SELECT sku FROM {$table} WHERE sku <> '' GROUP BY sku HAVING COUNT(*) > 1 )" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$out = array();
		foreach ( array_map( 'absint', (array) $ids ) as $id ) {
			$parent = (int) wp_get_post_parent_id( $id );
			$out[]  = $parent ? $parent : $id;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Changes "En la tienda" or "En la caja" of one product. Returns an error message, or null when done.
	 *
	 * @param int    $id      Product id.
	 * @param string $target  `shop` or `pos`.
	 * @param bool   $value   Wanted state.
	 * @param array  $context User and outlet.
	 */
	private function apply_visibility( int $id, string $target, bool $value, array $context ): ?string {
		$product = $id && 'product' === get_post_type( $id ) ? wc_get_product( $id ) : null;
		if ( ! $product || ! $target ) {
			return __( 'Petición no válida.', 'vfwoo-webkul-pos-bridge' );
		}
		if ( ! user_can( $context['user_id'], 'edit_post', $id ) ) {
			return __( 'No puedes editar este producto.', 'vfwoo-webkul-pos-bridge' );
		}

		if ( 'shop' === $target ) {
			if ( 'publish' !== $product->get_status() ) {
				return __( 'Solo se puede cambiar en productos publicados.', 'vfwoo-webkul-pos-bridge' );
			}
			$product->set_catalog_visibility( $value ? 'visible' : 'hidden' );
			$product->save();
			return null;
		}

		$this->set_pos_status( $id, $context['outlet_id'], $value );
		return null;
	}

	/**
	 * Puts a product in or out of the user's outlet with Webkul's own helper.
	 */
	private function set_pos_status( int $id, int $outlet_id, bool $value ): void {
		$helper = $this->outlet_products();
		$status = $helper->get_pos_product_status( $id, $outlet_id );
		if ( $value ) {
			if ( null === $status || '' === $status ) {
				$helper->save_pos_product_status( $id, $outlet_id );
			} elseif ( 'enabled' !== $status ) {
				$helper->update_pos_product_status( $id, $outlet_id, 'enabled' );
			}
		} elseif ( 'enabled' === $status ) {
			$helper->update_pos_product_status( $id, $outlet_id, 'disabled' );
		}
		Catalog_Integration::bump_catalog_version();
	}

	public function handle_visibility( \WP_REST_Request $request ) {
		$context = $this->context( $request );
		if ( isset( $context['success'] ) ) {
			return $context;
		}

		$id     = absint( $request->get_param( 'product_id' ) );
		$target = $this->choice( $request->get_param( 'target' ), array( 'shop', 'pos' ) );
		$error  = $this->apply_visibility( $id, $target, rest_sanitize_boolean( $request->get_param( 'value' ) ), $context );
		if ( $error ) {
			return $this->error( 400, $error );
		}

		return array(
			'success' => true,
			'data'    => $this->row( $id, $context['outlet_id'] ),
		);
	}

	/**
	 * The same change on several products. A product that cannot be changed is skipped and reported.
	 */
	public function handle_bulk( \WP_REST_Request $request ) {
		$context = $this->context( $request );
		if ( isset( $context['success'] ) ) {
			return $context;
		}

		$ids    = $request->get_param( 'product_ids' );
		$ids    = is_array( $ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ) : array();
		$target = $this->choice( $request->get_param( 'target' ), array( 'shop', 'pos' ) );
		$value  = rest_sanitize_boolean( $request->get_param( 'value' ) );
		if ( ! $ids || ! $target || count( $ids ) > 100 ) {
			return $this->error( 400, __( 'Petición no válida.', 'vfwoo-webkul-pos-bridge' ) );
		}

		$done   = 0;
		$failed = array();
		foreach ( $ids as $id ) {
			$error = $this->apply_visibility( $id, $target, $value, $context );
			if ( $error ) {
				$failed[] = array(
					'id'      => $id,
					'message' => $error,
				);
			} else {
				++$done;
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'done'   => $done,
				'failed' => $failed,
			),
		);
	}

	/**
	 * Quick new simple product: name, price, stock and barcode. It uses no taxonomies: category, brand and photo
	 * are left for WordPress.
	 */
	public function handle_create( \WP_REST_Request $request ) {
		$context = $this->context( $request );
		if ( isset( $context['success'] ) ) {
			return $context;
		}
		if ( ! user_can( $context['user_id'], 'publish_products' ) ) {
			return $this->error( 403, __( 'Tu usuario no puede crear productos.', 'vfwoo-webkul-pos-bridge' ) );
		}

		$input = $request->get_param( 'fields' );
		$input = is_array( $input ) ? $input : array();

		$previous_user = get_current_user_id();
		wp_set_current_user( $context['user_id'] ); // WooCommerce stores the current user as the product's author.
		try {
			$name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
			if ( '' === $name ) {
				throw new \Exception( esc_html__( 'El nombre no puede estar vacío.', 'vfwoo-webkul-pos-bridge' ) );
			}
			$price = $this->price( $input['regularPrice'] ?? '' );
			if ( '' === $price ) {
				throw new \Exception( esc_html__( 'Indica el precio.', 'vfwoo-webkul-pos-bridge' ) );
			}

			$product = new \WC_Product_Simple();
			$product->set_name( $name );
			$product->set_status( 'publish' );
			$product->set_regular_price( $price );
			$product->set_catalog_visibility( empty( $input['showInShop'] ) ? 'hidden' : 'visible' );

			$sku = sanitize_text_field( (string) ( $input['sku'] ?? '' ) );
			if ( '' !== $sku ) {
				$product->set_sku( $sku );
			}
			$gtin = sanitize_text_field( (string) ( $input['gtin'] ?? '' ) );
			if ( '' !== $gtin && method_exists( $product, 'set_global_unique_id' ) ) {
				$product->set_global_unique_id( $gtin );
			}
			$quantity = $input['stockQty'] ?? '';
			if ( $this->stock_is_editable() && '' !== (string) $quantity ) {
				if ( ! is_numeric( $quantity ) ) {
					throw new \Exception( esc_html__( 'La cantidad de stock no es válida.', 'vfwoo-webkul-pos-bridge' ) );
				}
				$product->set_manage_stock( true );
				$product->set_stock_quantity( (int) $quantity );
			}

			$id = $product->save();
		} catch ( \Exception $e ) {
			wp_set_current_user( $previous_user );
			return $this->error( 400, $e->getMessage() );
		}
		wp_set_current_user( $previous_user );

		// In this outlet whatever Webkul's default setting does for the others.
		$this->set_pos_status( $id, $context['outlet_id'], true );

		return array(
			'success' => true,
			'data'    => $this->row( $id, $context['outlet_id'] ),
		);
	}
}

new Products_Module();
