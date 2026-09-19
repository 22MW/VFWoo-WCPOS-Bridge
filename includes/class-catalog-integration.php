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
		$options                             = array();
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$product_data['vfwoo_webkul_brands'][] = array(
					'id'   => (int) $term->term_id,
					'name' => (string) $term->name,
					'slug' => (string) $term->slug,
				);
				$options[ $term->slug ]                = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
			}
		}

		// Webkul builds its native filter panel from product attributes.
		if ( ! empty( $options ) ) {
			if ( ! isset( $product_data['attributes'] ) || ! is_array( $product_data['attributes'] ) ) {
				$product_data['attributes'] = array();
			}
			$product_data['attributes'][] = array(
				'key'       => 'vfwoo_brands',
				'slug'      => 'vfwoo_brands',
				'name'      => 'BRANDS',
				'visible'   => true,
				'variation' => false,
				'options'   => $options,
			);
		}

		return $product_data;
	}
}
