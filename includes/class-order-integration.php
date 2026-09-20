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
		add_filter( 'vfwoo_ticket_estados_validos', array( $this, 'allow_processing_status' ) );
		add_filter( 'wkwcpos_modify_order_details_response_at_pos', array( $this, 'enrich_single_order' ), 20, 3 );
		add_filter( 'wkwcpos_modify_get_orders_api_response', array( $this, 'enrich_orders' ), 20, 4 );
	}

	public function allow_processing_status( $statuses ): array {
		$statuses = is_array( $statuses ) ? $statuses : array();
		if ( ! in_array( 'processing', $statuses, true ) ) {
			$statuses[] = 'processing';
		}

		return $statuses;
	}

	public function enrich_single_order( $response, $order, $user_id ) {
		unset( $user_id );
		return $this->enrich_order( $response, $order, true );
	}

	public function enrich_orders( $responses, $pos_user, $outlet_id, $request ) {
		unset( $pos_user, $outlet_id, $request );
		if ( ! is_array( $responses ) ) {
			return $responses;
		}

		// Webkul returns a single associative order when reprinting by order_id.
		if ( isset( $responses['order_id'] ) || isset( $responses['id'] ) ) {
			$order_id = isset( $responses['order_id'] ) ? absint( $responses['order_id'] ) : absint( $responses['id'] );
			$order    = $order_id ? wc_get_order( $order_id ) : false;
			return $this->enrich_order( $responses, $order, true );
		}

		foreach ( $responses as $index => $response ) {
			$order_id = isset( $response['order_id'] ) ? absint( $response['order_id'] ) : ( isset( $response['id'] ) ? absint( $response['id'] ) : 0 );
			$order    = $order_id ? wc_get_order( $order_id ) : false;
			$responses[ $index ] = $this->enrich_order( $response, $order );
		}

		return $responses;
	}

	private function enrich_order( $response, $order, bool $with_qr_image = false ): array {
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
				'qr_data_uri'      => $with_qr_image ? $this->provider->qr_data_uri( $data ) : '',
				'legal_legend'     => (string) ( $data['legal_legend'] ?? '' ),
			),
			'customer' => Invoice_Variables::customer_block( $order, $data ),
		);

		return $response;
	}
}
