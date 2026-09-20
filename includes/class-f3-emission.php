<?php
/**
 * Issue a substitute complete invoice (F3) for a POS order that was sold as a simplified one (F2).
 *
 * VFWoo does the work (census check, payload, submission); the bridge authenticates the cashier,
 * checks the order and hands over the identity of a registered customer.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class F3_Emission {
	public const ORDER_CUSTOMER_META = '_vfwoo_webkul_f3_customer';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		foreach ( array(
			'/f3-status' => 'handle_status',
			'/issue-f3'  => 'handle_issue',
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

	public function handle_status( \WP_REST_Request $request ) {
		if ( ! Pos_Auth::is_valid( $request->get_param( 'logged_in_user_id' ) ) ) {
			return Pos_Auth::unauthorized();
		}

		$state = $this->state( absint( $request->get_param( 'order_id' ) ) );
		return array(
			'success' => true,
			'state'   => $state['state'],
		);
	}

	public function handle_issue( \WP_REST_Request $request ) {
		if ( ! Pos_Auth::is_valid( $request->get_param( 'logged_in_user_id' ) ) ) {
			return Pos_Auth::unauthorized();
		}

		$order_id    = absint( $request->get_param( 'order_id' ) );
		$customer_id = absint( $request->get_param( 'customer_id' ) );

		$state = $this->state( $order_id );
		if ( 'ready' !== $state['state'] ) {
			return $this->failure( $this->state_message( $state['state'] ) );
		}

		$identity = Customer_Integration::identity( $customer_id );
		if ( '' === $identity['nif'] || '' === $identity['name'] ) {
			return $this->failure( __( 'El cliente elegido no tiene NIF y nombre. Créalo o complétalo primero.', 'vfwoo-webkul-pos-bridge' ) );
		}

		try {
			$result = \VFWoo\Helpers\Invoice_Helper::emitir_sustitutiva(
				(int) $state['invoice']->get_id(),
				array(
					'nif'    => $identity['nif'],
					'nombre' => $identity['name'],
				)
			);
		} catch ( \Throwable $e ) {
			return $this->failure( __( 'No se ha podido emitir la factura completa.', 'vfwoo-webkul-pos-bridge' ) );
		}

		if ( empty( $result['success'] ) ) {
			$message = $result['error'] ?? $result['error_message'] ?? '';
			return $this->failure( '' !== (string) $message ? (string) $message : __( 'VFWoo no ha podido emitir la factura completa.', 'vfwoo-webkul-pos-bridge' ) );
		}

		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->update_meta_data( self::ORDER_CUSTOMER_META, $customer_id );
			$order->save();
		}

		// Fresh ticket data, so the POS prints the F3 without reloading its orders.
		$bridge = $order ? Order_Integration::bridge_block( $order, true ) : null;

		return array(
			'success' => true,
			'message' => __( 'Factura completa (F3) enviada.', 'vfwoo-webkul-pos-bridge' ),
			'bridge'  => $bridge,
		);
	}

	/**
	 * State of the order's current invoice: ready (F2 accepted), pending (F2 not accepted yet),
	 * done (already F3) or none (not a POS order with a simplified invoice).
	 *
	 * @return array{state:string,invoice?:object}
	 */
	private function state( int $order_id ): array {
		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order || '' === (string) $order->get_meta( '_wk_wc_pos_outlet', true ) || ! class_exists( 'VFWoo\\Helpers\\Invoice_Helper' ) ) {
			return array( 'state' => 'none' );
		}

		$invoice = \VFWoo\Helpers\Invoice_Helper::get_main_invoice_by_order_id( $order_id );
		if ( ! $invoice ) {
			return array( 'state' => 'none' );
		}

		$type = strtoupper( (string) $invoice->get_tipo() );
		if ( 'F3' === $type ) {
			return array( 'state' => 'done' );
		}
		if ( 'F2' !== $type ) {
			return array( 'state' => 'none' );
		}

		$accepted = 'ROK' === strtoupper( substr( (string) $invoice->get_estado(), 0, 3 ) );
		return array(
			'state'   => $accepted ? 'ready' : 'pending',
			'invoice' => $invoice,
		);
	}

	private function state_message( string $state ): string {
		$messages = array(
			'pending' => __( 'La factura simplificada aún no está confirmada. Espera a que se confirme.', 'vfwoo-webkul-pos-bridge' ),
			'done'    => __( 'Este pedido ya tiene factura completa.', 'vfwoo-webkul-pos-bridge' ),
		);
		return $messages[ $state ] ?? __( 'Este pedido no admite factura completa desde el POS.', 'vfwoo-webkul-pos-bridge' );
	}

	private function failure( string $message ): array {
		return array(
			'success' => false,
			'status'  => 400,
			'message' => $message,
		);
	}
}
