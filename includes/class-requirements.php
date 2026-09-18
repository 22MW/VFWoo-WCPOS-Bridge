<?php
/**
 * Dependency checks.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Requirements {
	public static function are_met(): bool {
		return class_exists( 'WooCommerce' )
			&& class_exists( 'VFWoo\\Ticket_Fiscal_Data' )
			&& defined( 'WK_WC_POS_VERSION' );
	}

	public static function missing(): array {
		$missing = array();
		if ( ! class_exists( 'WooCommerce' ) ) {
			$missing[] = __( 'WooCommerce', 'vfwoo-webkul-pos-bridge' );
		}
		if ( ! class_exists( 'VFWoo\\Ticket_Fiscal_Data' ) ) {
			$missing[] = __( 'VFWoo', 'vfwoo-webkul-pos-bridge' );
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
