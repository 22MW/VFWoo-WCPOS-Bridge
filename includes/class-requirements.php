<?php
/**
 * Comprobaciones de dependencias.
 *
 * @package VFWoo_WCPOS_Bridge
 */

namespace VFWooWCPOSBridge;

defined( 'ABSPATH' ) || exit;

/**
 * Valida los contratos públicos que necesitan los módulos del bridge.
 */
final class Requirements {
	/**
	 * Indica si WooCommerce, VFWoo y WCPOS exponen la API esperada.
	 */
	public static function are_met(): bool {
		return class_exists( 'WooCommerce' )
			&& class_exists( '\\VFWoo\\Ticket_Fiscal_Data' )
			&& class_exists( '\\WCPOS\\WooCommercePOS\\Services\\Fiscal_Receipt_Service' );
	}

	/**
	 * Muestra un aviso accionable para el administrador del sitio.
	 */
	public static function render_admin_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'VFWoo WCPOS Bridge requires WooCommerce, Veri*Fac*WOO with Ticket_Fiscal_Data, and WCPOS.', 'vfwoo-wcpos-bridge' ) . '</p></div>';
	}
}
