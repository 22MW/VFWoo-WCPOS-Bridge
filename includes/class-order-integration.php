<?php
/**
 * Enrich Webkul order responses with namespaced fiscal data.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Order_Integration {
	private $provider;

	public function __construct() {
		$this->provider = new Fiscal_Provider();
		add_filter( 'wkwcpos_modify_order_details_response_at_pos', array( $this, 'enrich_single_order' ), 20, 3 );
		add_filter( 'wkwcpos_modify_get_orders_api_response', array( $this, 'enrich_orders' ), 20, 4 );
	}

	public function enrich_single_order( $response, $order, $user_id ) {
		unset( $user_id );
		return $this->enrich_order( $response, $order );
	}

	public function enrich_orders( $responses, $pos_user, $outlet_id, $request ) {
		unset( $pos_user, $outlet_id, $request );
		if ( ! is_array( $responses ) ) {
			return $responses;
		}

		foreach ( $responses as $index => $response ) {
			$order_id = isset( $response['order_id'] ) ? absint( $response['order_id'] ) : 0;
			$order    = $order_id ? wc_get_order( $order_id ) : false;
			$responses[ $index ] = $this->enrich_order( $response, $order );
		}

		return $responses;
	}

	private function enrich_order( $response, $order ): array {
		$response = is_array( $response ) ? $response : array();
		if ( ! $order instanceof \WC_Order ) {
			$response['vfwoo_webkul_bridge'] = array( 'fiscal' => array( 'available' => false ) );
			return $response;
		}

		$data = $this->provider->for_order( $order );
		$response['vfwoo_webkul_bridge'] = array(
			'fiscal' => array(
				'available'        => ! empty( $data['available'] ),
				'invoice_number'   => (string) ( $data['invoice_number'] ?? '' ),
				'invoice_type'     => (string) ( $data['invoice_type'] ?? '' ),
				'issued_at'        => (string) ( $data['issued_at'] ?? '' ),
				'verification_url' => (string) ( $data['verification_url'] ?? '' ),
				'qr_src'           => (string) ( $data['qr_src'] ?? '' ),
				'legal_legend'     => (string) ( $data['legal_legend'] ?? '' ),
			),
		);

		return $response;
	}
}
