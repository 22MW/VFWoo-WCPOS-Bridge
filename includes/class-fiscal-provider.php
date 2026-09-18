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

		$data = \VFWoo\Ticket_Fiscal_Data::for_order( $order, true );
		return is_array( $data ) ? $data : array( 'available' => false );
	}
}
