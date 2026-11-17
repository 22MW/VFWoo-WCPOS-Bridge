<?php
/**
 * Complete invoice (in the name of a customer) for a POS order that was sold as a ticket.
 *
 * It works with any invoicing plugin: the customer's data are written on the order and the invoice
 * keeps its number. When Verifactu has already registered the simplified invoice (F2), the add-on
 * issues the substitute complete invoice (F3) instead, which is what the AEAT requires.
 *
 * The bridge authenticates the cashier, checks the order and hands over the identity of a registered
 * customer; the invoicing plugins do the rest.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Ticket_Invoice {
	/**
	 * Customer chosen when the ticket became a complete invoice. The value is kept from the first
	 * version of this feature, which only issued the Verifactu F3.
	 */
	public const ORDER_CUSTOMER_META = '_vfwoo_webkul_f3_customer';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		foreach ( array(
			'/full-invoice-status' => 'handle_status',
			'/full-invoice-issue'  => 'handle_issue',
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
	 * A ticket that became a complete invoice here shows as one (F1) when it is printed, unless
	 * Verifactu already gave it a type of its own (F1 or F3).
	 *
	 * @param array $data Ticket data of the order.
	 */
	public static function mark_complete( \WC_Order $order, array $data ): array {
		$converted = '' !== (string) $order->get_meta( self::ORDER_CUSTOMER_META, true );
		$type      = strtoupper( (string) ( $data['invoice_type'] ?? '' ) );
		if ( $converted && ! empty( $data['available'] ) && ! in_array( $type, array( 'F1', 'F3' ), true ) ) {
			$data['invoice_type'] = 'F1';
		}
		return $data;
	}

	public function handle_status( \WP_REST_Request $request ) {
		if ( ! Pos_Auth::is_valid( $request->get_param( 'logged_in_user_id' ) ) ) {
			return Pos_Auth::unauthorized();
		}

		$state = $this->state( absint( $request->get_param( 'order_id' ) ) );
		return array(
			'success' => true,
			'state'   => $state['state'],
			'route'   => $state['route'] ?? '',
			'message' => 'ready' === $state['state'] ? '' : $this->state_message( $state['state'] ),
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
		$name     = '' !== $identity['company'] ? $identity['company'] : $identity['name'];
		if ( '' === $name ) {
			return $this->failure( __( 'El cliente elegido no tiene nombre. Complétalo o crea uno nuevo.', 'vfwoo-webkul-pos-bridge' ) );
		}
		if ( '' === $identity['nif'] && Customer_Integration::nif_required() ) {
			return $this->failure( __( 'El cliente elegido no tiene NIF. Complétalo o crea uno nuevo.', 'vfwoo-webkul-pos-bridge' ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return $this->failure( $this->state_message( 'none' ) );
		}

		if ( 'verifactu' === $state['route'] ) {
			// Verifactu's F3 only sends a Spanish tax ID: it has no country or document type for a foreign buyer
			// (pending in VFWoo). Better to stop here than to send the AEAT a badly formed invoice.
			if ( '' !== $identity['country'] && 'ES' !== $identity['country'] ) {
				return $this->failure( __( 'Verifactu todavía no hace facturas a clientes de otro país. Elige un cliente de España.', 'vfwoo-webkul-pos-bridge' ) );
			}
			$emitter = Capabilities::f3_emitter();
			if ( null === $emitter ) {
				return $this->failure( $this->state_message( 'none' ) );
			}
			try {
				$result = call_user_func(
					$emitter,
					(int) $state['invoice']->get_id(),
					array(
						'nif'    => $identity['nif'],
						'nombre' => $name,
					)
				);
			} catch ( \Throwable $e ) {
				return $this->failure( __( 'No se ha podido hacer la factura.', 'vfwoo-webkul-pos-bridge' ) );
			}

			if ( empty( $result['success'] ) ) {
				$message = $result['error'] ?? $result['error_message'] ?? '';
				return $this->failure( '' !== (string) $message ? (string) $message : __( 'VFWoo no ha podido hacer la factura.', 'vfwoo-webkul-pos-bridge' ) );
			}
			// The add-on already wrote the tax ID and the name on the order; the address is not part of its registry.
			$this->write_customer( $order, $customer_id, $identity, false );
			$message = __( 'Factura enviada a Verifactu.', 'vfwoo-webkul-pos-bridge' );
		} else {
			$this->write_customer( $order, $customer_id, $identity, true );
			$message = __( 'Factura guardada con los datos del cliente.', 'vfwoo-webkul-pos-bridge' );
		}

		$order = wc_get_order( $order_id );

		// Fresh ticket data, so the POS prints the complete invoice without reloading its orders.
		return array(
			'success' => true,
			'message' => $message,
			'bridge'  => $order ? Order_Integration::bridge_block( $order, true ) : null,
		);
	}

	/**
	 * Writes the customer on the order: the invoicing plugin's PDF reads these fields.
	 *
	 * @param bool $identity_too Also write the tax ID and the name (Verifactu already did it for its own invoice).
	 */
	private function write_customer( \WC_Order $order, int $customer_id, array $identity, bool $identity_too ): void {
		$customer = new \WC_Customer( $customer_id );

		if ( $identity_too ) {
			$before = trim(
				implode(
					' · ',
					array_filter(
						array(
							trim( $order->get_billing_company() . ' ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
							trim( (string) $order->get_meta( Customer_Integration::ORDER_NIF_META, true ) ),
						)
					)
				)
			);

			$nif        = $identity['nif'];
			$is_company = '' !== $identity['company'] || ( '' !== $nif && (bool) preg_match( '/^[A-HJNPQRSUVW]/', $nif ) );
			if ( '' !== $nif ) {
				$order->update_meta_data( Customer_Integration::ORDER_NIF_META, $nif );
			} else {
				$order->delete_meta_data( Customer_Integration::ORDER_NIF_META );
			}
			if ( $is_company ) {
				// A company goes to the company field; its contact person, if any, stays as first and last name.
				$order->set_billing_company( '' !== $identity['company'] ? $identity['company'] : $identity['name'] );
				$order->set_billing_first_name( '' !== $identity['company'] ? $identity['first'] : '' );
				$order->set_billing_last_name( '' !== $identity['company'] ? $identity['last'] : '' );
			} else {
				$order->set_billing_company( '' );
				$order->set_billing_first_name( $identity['first'] );
				$order->set_billing_last_name( $identity['last'] );
			}

			$order->add_order_note(
				sprintf(
					/* translators: 1: previous customer data of the order, 2: new customer data. */
					__( 'Ticket convertido en factura desde el POS. Antes: %1$s. Ahora: %2$s.', 'vfwoo-webkul-pos-bridge' ),
					'' !== $before ? $before : __( 'sin cliente', 'vfwoo-webkul-pos-bridge' ),
					trim( ( '' !== $identity['company'] ? $identity['company'] : $identity['name'] ) . ' ' . $nif )
				)
			);
		}

		// The address is only written when the customer has one: an empty one must not wipe what the order has.
		if ( '' !== trim( $customer->get_billing_address_1() . $customer->get_billing_postcode() . $customer->get_billing_city() ) ) {
			$order->set_billing_address_1( $customer->get_billing_address_1() );
			$order->set_billing_address_2( $customer->get_billing_address_2() );
			$order->set_billing_postcode( $customer->get_billing_postcode() );
			$order->set_billing_city( $customer->get_billing_city() );
			$order->set_billing_state( $customer->get_billing_state() );
		}
		// The country travels on its own: a customer from abroad may have no address yet.
		if ( '' !== $customer->get_billing_country() ) {
			$order->set_billing_country( $customer->get_billing_country() );
		}

		// The order shows up in the customer's purchases, and the ticket reads the contact data from here.
		$order->set_customer_id( $customer_id );
		$order->update_meta_data( self::ORDER_CUSTOMER_META, $customer_id );
		$order->save();
	}

	/**
	 * State of the order's current invoice:
	 * - ready: it can become a complete invoice. Route «local» writes the customer on the order and keeps
	 *   the number; route «verifactu» issues the F3 that replaces an accepted F2.
	 * - pending: the F2 is registered but not accepted yet.
	 * - registered: the invoice is already registered with its customer (F1 or F3) and cannot change without a rectification.
	 * - refunded: the order has a refund (and its credit note): the customer cannot change.
	 * - cancelled: the order was cancelled: nothing to change.
	 * - noinvoice / none: nothing to convert.
	 *
	 * @return array{state:string,route?:string,invoice?:object}
	 */
	private function state( int $order_id ): array {
		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order || '' === (string) $order->get_meta( '_wk_wc_pos_outlet', true ) || ! Capabilities::invoices() ) {
			return array( 'state' => 'none' );
		}

		// A cancelled order has no invoice to change; a refunded one (all or part of it) already has its credit
		// note, so its customer can no longer change. Failed and draft orders were never a sale.
		$status = $order->get_status();
		if ( 'cancelled' === $status ) {
			return array( 'state' => 'cancelled' );
		}
		if ( 'refunded' === $status || (float) $order->get_total_refunded() > 0 ) {
			return array( 'state' => 'refunded' );
		}
		if ( in_array( $status, array( 'failed', 'trash', 'draft', 'checkout-draft' ), true ) ) {
			return array( 'state' => 'none' );
		}

		$invoice = \VFWoo\Helpers\Invoice_Helper::get_main_invoice_by_order_id( $order_id );
		if ( ! $invoice ) {
			return array( 'state' => 'noinvoice' );
		}

		$type   = strtoupper( (string) $invoice->get_tipo() );
		$estado = strtoupper( trim( (string) $invoice->get_estado() ) );
		$sent   = '' !== $estado; // Registered with the tax authority: it can no longer be edited, only substituted or rectified.

		if ( ! $sent ) {
			// Verifactu connected to the AEAT has not sent it yet: changing the customer now would change what it sends.
			if ( Capabilities::sending() ) {
				return array( 'state' => 'pending' );
			}
			return array(
				'state' => 'ready',
				'route' => 'local',
			);
		}

		if ( 'F2' === $type && Capabilities::f3() ) {
			$accepted = 'ROK' === substr( $estado, 0, 3 );
			return array(
				'state'   => $accepted ? 'ready' : 'pending',
				'route'   => 'verifactu',
				'invoice' => $invoice,
			);
		}

		return array( 'state' => 'registered' );
	}

	private function state_message( string $state ): string {
		$messages = array(
			'pending'    => __( 'El ticket aún no está confirmado. Espera a que se confirme.', 'vfwoo-webkul-pos-bridge' ),
			'registered' => __( 'Esta factura ya está registrada en Verifactu a nombre de un cliente. Para cambiarla hay que rectificarla desde VFWoo.', 'vfwoo-webkul-pos-bridge' ),
			'refunded'   => __( 'Este pedido está reembolsado (total o parcialmente): no se puede cambiar la factura. La rectificativa se imprime desde el pedido.', 'vfwoo-webkul-pos-bridge' ),
			'cancelled'  => __( 'Este pedido está cancelado: no se puede cambiar la factura.', 'vfwoo-webkul-pos-bridge' ),
			'noinvoice'  => __( 'El pedido aún no tiene factura. Espera unos segundos y vuelve a intentarlo.', 'vfwoo-webkul-pos-bridge' ),
		);
		return $messages[ $state ] ?? __( 'Este pedido no admite hacer factura desde el POS.', 'vfwoo-webkul-pos-bridge' );
	}

	private function failure( string $message ): array {
		return array(
			'success' => false,
			'status'  => 400,
			'message' => $message,
		);
	}
}
