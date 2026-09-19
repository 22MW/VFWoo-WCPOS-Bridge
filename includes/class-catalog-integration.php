<?php
/**
 * Add product taxonomies as filters to Webkul product responses.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Catalog_Integration {
	public const OPTION         = 'vfwoo_webkul_filter_taxonomies';
	public const VERSION_OPTION = 'vfwoo_webkul_catalog_version';

	/**
	 * Taxonomies Webkul already covers or that are internal to WooCommerce.
	 */
	private const EXCLUDED = array( 'product_cat', 'product_type', 'product_visibility', 'product_shipping_class' );

	public function __construct() {
		add_filter( 'manage_custom_product_type_support', array( $this, 'add_taxonomies' ), 20, 4 );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Product taxonomies that can be offered as POS filters, keyed by slug.
	 *
	 * @return array<string,\WP_Taxonomy>
	 */
	public static function available_taxonomies(): array {
		$available = array();
		foreach ( get_object_taxonomies( 'product', 'objects' ) as $slug => $taxonomy ) {
			if ( in_array( $slug, self::EXCLUDED, true ) || 0 === strpos( $slug, 'pa_' ) || ! $taxonomy->show_ui ) {
				continue;
			}
			$available[ $slug ] = $taxonomy;
		}
		return $available;
	}

	/**
	 * Selected taxonomy slugs, limited to the ones still available.
	 *
	 * @return string[]
	 */
	public static function selected_taxonomies(): array {
		$saved = get_option( self::OPTION, array( 'product_brand' ) );
		if ( ! is_array( $saved ) ) {
			return array();
		}
		return array_values( array_intersect( array_map( 'sanitize_key', $saved ), array_keys( self::available_taxonomies() ) ) );
	}

	/**
	 * Catalog version stamped on every product; raising it flags cached POS catalogs as stale.
	 */
	public static function catalog_version(): int {
		return max( 1, (int) get_option( self::VERSION_OPTION, 1 ) );
	}

	public static function bump_catalog_version(): void {
		update_option( self::VERSION_OPTION, self::catalog_version() + 1 );
	}

	public function register_routes(): void {
		register_rest_route(
			'vfwoo-webkul/v1',
			'/catalog-version',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true', // Only exposes an integer, no sensitive data.
				'callback'            => static function () {
					$response = new \WP_REST_Response( array( 'version' => self::catalog_version() ) );
					$response->header( 'Cache-Control', 'no-store, max-age=0' );
					return $response;
				},
			)
		);
	}

	public function add_taxonomies( $product_data, $product, $index, $outlet_id ) {
		unset( $index, $outlet_id );
		if ( ! is_array( $product_data ) || ! $product instanceof \WC_Product ) {
			return $product_data;
		}

		$available                           = self::available_taxonomies();
		$product_data['vfwoo_webkul_brands'] = array();
		$product_data['vfwoo_webkul_catalog_version'] = self::catalog_version();

		foreach ( self::selected_taxonomies() as $slug ) {
			$terms   = get_the_terms( $product->get_id(), $slug );
			$options = array();
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$options[ $term->slug ] = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
					if ( 'product_brand' === $slug ) {
						$product_data['vfwoo_webkul_brands'][] = array(
							'id'   => (int) $term->term_id,
							'name' => (string) $term->name,
							'slug' => (string) $term->slug,
						);
					}
				}
			}

			// Webkul builds its native filter panel from product attributes.
			if ( empty( $options ) ) {
				continue;
			}
			if ( ! isset( $product_data['attributes'] ) || ! is_array( $product_data['attributes'] ) ) {
				$product_data['attributes'] = array();
			}
			$product_data['attributes'][] = array(
				'key'       => 'vfwoo_' . $slug,
				'slug'      => 'vfwoo_' . $slug,
				'name'      => mb_strtoupper( (string) $available[ $slug ]->labels->name ),
				'visible'   => true,
				'variation' => false,
				'options'   => $options,
			);
		}

		return $product_data;
	}
}
