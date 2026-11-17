<?php
/**
 * Order status changes from the POS: only complete, cancel (anular) and refund (devolver).
 *
 * The dropdown of the order detail is tidied in the browser (see pos-bridge.js); this class is the
 * server side of the same rule, so a call made by hand to Webkul's endpoint cannot set any other status.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Order_Status {
	private const ROUTE = '/pos/v1/update-order-status';

	/**
	 * Statuses the POS may set. Keeping the order's current status is always accepted (it changes nothing).
	 */
	public const ALLOWED = array( 'completed', 'cancelled', 'refunded' );

	public function __construct() {
		add_filter( 'rest_pre_dispatch', array( $this, 'restrict' ), 10, 3 );
	}

	/**
	 * Answers with Webkul's own failure shape before its endpoint runs, so the POS shows its usual error.
	 *
	 * @param mixed            $result  Pre-calculated result (null to go on).
	 * @param mixed            $server  REST server.
	 * @param \WP_REST_Request $request Request.
	 */
	public function restrict( $result, $server, $request ) {
		unset( $server );
		if ( null !== $result || ! $request instanceof \WP_REST_Request || self::ROUTE !== $request->get_route() ) {
			return $result;
		}

		$status = sanitize_key( str_replace( 'wc-', '', (string) $request->get_param( 'order_status' ) ) );
		if ( in_array( $status, self::ALLOWED, true ) ) {
			return $result;
		}

		$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );
		if ( $order && $order->get_status() === $status ) {
			return $result;
		}

		return array(
			'success' => false,
			'status'  => 400,
			'message' => __( 'Desde el POS solo se puede completar, anular o devolver un pedido.', 'vfwoo-webkul-pos-bridge' ),
		);
	}
}
