<?php
/**
 * Dependency checks.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Requirements {
	/**
	 * Required dependencies: without them the bridge does not load.
	 */
	public static function are_met(): bool {
		return class_exists( 'WooCommerce' ) && defined( 'WK_WC_POS_VERSION' );
	}

	/**
	 * VFWoo is optional: without it the fiscal modules (QR ticket, F3, tax ID checks) stay off.
	 */
	public static function vfwoo_active(): bool {
		return class_exists( 'VFWoo\\Ticket_Fiscal_Data' );
	}

	public static function missing(): array {
		$missing = array();
		if ( ! class_exists( 'WooCommerce' ) ) {
			$missing[] = __( 'WooCommerce', 'vfwoo-webkul-pos-bridge' );
		}
		if ( ! defined( 'WK_WC_POS_VERSION' ) ) {
			$missing[] = __( 'WooCommerce Point of Sale de Webkul', 'vfwoo-webkul-pos-bridge' );
		}

		return $missing;
	}

	public static function render_admin_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$missing = self::missing();
		if ( empty( $missing ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>' . esc_html( sprintf( __( 'VFWoo Bridge necesita estas dependencias activas: %s.', 'vfwoo-webkul-pos-bridge' ), implode( ', ', $missing ) ) ) . '</p></div>';
	}
}
