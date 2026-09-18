<?php
/**
 * Dependency checks.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Requirements {
	public static function can_boot(): bool {
		return class_exists( 'WooCommerce' ) && class_exists( 'VFWoo\\Ticket_Fiscal_Data' );
	}

	public static function missing(): array {
		$missing = array();
		if ( ! class_exists( 'WooCommerce' ) ) {
			$missing[] = __( 'WooCommerce', 'vfwoo-webkul-pos-bridge' );
		}
		if ( ! class_exists( 'VFWoo\\Ticket_Fiscal_Data' ) ) {
			$missing[] = __( 'VFWoo', 'vfwoo-webkul-pos-bridge' );
		}

		return $missing;
	}
}
