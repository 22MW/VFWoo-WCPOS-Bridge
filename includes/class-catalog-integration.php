<?php
/**
 * Add WooCommerce Brands data to Webkul product responses.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Catalog_Integration {
	public function __construct() {
		add_filter( 'manage_custom_product_type_support', array( $this, 'add_brands' ), 20, 4 );
	}

	public function add_brands( $product_data, $product, $index, $outlet_id ) {
		unset( $index, $outlet_id );
		if ( ! is_array( $product_data ) || ! $product instanceof \WC_Product ) {
			return $product_data;
		}

		$terms = get_the_terms( $product->get_id(), 'product_brand' );
		$product_data['vfwoo_webkul_brands'] = array();
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$product_data['vfwoo_webkul_brands'][] = array(
					'id'   => (int) $term->term_id,
					'name' => (string) $term->name,
					'slug' => (string) $term->slug,
				);
			}
		}

		return $product_data;
	}
}
