<?php
/**
 * Read-only VFWoo adapter.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Fiscal_Provider {
	public function for_order( $order ): array {
		if ( ! $order instanceof \WC_Order || ! class_exists( 'VFWoo\\Ticket_Fiscal_Data' ) ) {
			return array( 'available' => false );
		}

		$data = \VFWoo\Ticket_Fiscal_Data::for_order( $order->get_id(), true );
		return is_array( $data ) ? $data : array( 'available' => false );
	}

	/**
	 * Inline PNG for the ticket: the print starts 500 ms after Webkul builds it,
	 * too soon for the QR endpoint to answer. Empty when VFWoo's QR helper is missing.
	 */
	public function qr_data_uri( array $data ): string {
		$payload = (string) ( $data['qr_payload'] ?? '' );
		if ( '' === $payload || ! class_exists( 'VFWoo\\QR' ) || ! method_exists( 'VFWoo\\QR', 'png_url' ) ) {
			return '';
		}

		try {
			$uri = (string) \VFWoo\QR::png_url( $payload, 300 );
		} catch ( \Throwable $e ) {
			return '';
		}

		return 0 === strpos( $uri, 'data:image/png;base64,' ) ? $uri : '';
	}
}
