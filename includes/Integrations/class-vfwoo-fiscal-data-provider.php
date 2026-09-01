<?php
/**
 * Adaptador de solo lectura al contrato documentado de datos de ticket VFWoo.
 *
 * @package VFWoo_WCPOS_Bridge
 */

namespace VFWooWCPOSBridge\Integrations;

defined( 'ABSPATH' ) || exit;

final class VFWoo_Fiscal_Data_Provider {
	/**
	 * Obtiene datos de presentación normalizados. Nunca emite, numera ni actualiza VFWoo.
	 *
	 * @param int $order_id ID del pedido de WooCommerce.
	 * @return array<string,mixed>
	 */
	public function for_order( int $order_id ): array {
		$data = \VFWoo\Ticket_Fiscal_Data::for_order( $order_id, true );

		if ( ! is_array( $data ) || empty( $data['available'] ) ) {
			return array( 'available' => false );
		}

		return array(
			'available'        => true,
			'invoice_number'   => isset( $data['invoice_number'] ) ? (string) $data['invoice_number'] : '',
			'invoice_series'   => isset( $data['invoice_series'] ) ? (string) $data['invoice_series'] : '',
			'invoice_type'     => isset( $data['invoice_type'] ) ? (string) $data['invoice_type'] : '',
			'issued_at'        => isset( $data['issued_at'] ) ? (string) $data['issued_at'] : '',
			'taxpayer_id'      => isset( $data['taxpayer_id'] ) ? (string) $data['taxpayer_id'] : '',
			'verification_url' => isset( $data['verification_url'] ) ? esc_url_raw( $data['verification_url'] ) : '',
			'qr_payload'       => isset( $data['qr_payload'] ) ? (string) $data['qr_payload'] : '',
			'qr_src'           => isset( $data['qr_src'] ) ? esc_url_raw( $data['qr_src'] ) : '',
			'qr_is_prepared'   => ! empty( $data['qr_is_prepared'] ),
			'confirmed'        => ! empty( $data['confirmed'] ),
			'legal_legend'     => isset( $data['legal_legend'] ) ? (string) $data['legal_legend'] : '',
		);
	}
}
