<?php
/**
 * Read-only adapter to the invoicing plugins.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Fiscal_Provider {
	/**
	 * Ticket data of an order. Verifactu gives it all (number, type, QR, legend). Without it the invoice
	 * of the invoicing plugin gives the number, date and type, and the ticket carries no QR.
	 */
	public function for_order( $order ): array {
		if ( ! $order instanceof \WC_Order ) {
			return array( 'available' => false );
		}

		if ( Capabilities::verifactu() ) {
			$data = \VFWoo\Ticket_Fiscal_Data::for_order( $order->get_id(), true );
			if ( is_array( $data ) && ! empty( $data['available'] ) ) {
				return $data;
			}
		}

		return $this->from_invoice( $order );
	}

	/**
	 * Number, type and date of the order's invoice, read from the invoicing plugin. An issued invoice
	 * has no confirmation to wait for, so it counts as confirmed.
	 */
	private function from_invoice( \WC_Order $order ): array {
		if ( ! Capabilities::invoices() ) {
			return array( 'available' => false );
		}

		try {
			$invoice = \VFWoo\Helpers\Invoice_Helper::get_main_invoice_by_order_id( $order->get_id() );
		} catch ( \Throwable $e ) {
			return array( 'available' => false );
		}

		$number = $invoice && method_exists( $invoice, 'get_numero_factura_completo' ) ? trim( (string) $invoice->get_numero_factura_completo() ) : '';
		if ( '' === $number ) {
			return array( 'available' => false );
		}

		$date      = $invoice->get_fecha() ? (string) $invoice->get_fecha() : '';
		$timestamp = '' !== $date ? strtotime( $date ) : false;

		return array(
			'available'        => true,
			'invoice_number'   => $number,
			'invoice_type'     => strtoupper( (string) $invoice->get_tipo() ),
			'issued_at'        => $timestamp ? wp_date( 'd-m-Y', $timestamp ) : $date,
			'verification_url' => '',
			'qr_src'           => '',
			'qr_payload'       => '',
			'legal_legend'     => '',
			'confirmed'        => true,
		);
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
