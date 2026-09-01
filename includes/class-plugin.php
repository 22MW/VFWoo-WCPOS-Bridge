<?php
/**
 * Arranque del plugin.
 *
 * @package VFWoo_WCPOS_Bridge
 */

namespace VFWooWCPOSBridge;

defined( 'ABSPATH' ) || exit;

require_once VFWOO_WCPOS_BRIDGE_PATH . 'includes/class-requirements.php';

/**
 * Inicia únicamente los módulos seguros cuyas dependencias están verificadas.
 */
final class Plugin {
	/**
	 * Inicia el plugin cuando WordPress haya cargado los plugins activos.
	 */
	public static function boot(): void {
		load_plugin_textdomain( 'vfwoo-wcpos-bridge', false, dirname( plugin_basename( VFWOO_WCPOS_BRIDGE_FILE ) ) . '/languages' );

		if ( ! Requirements::are_met() ) {
			add_action( 'admin_notices', array( Requirements::class, 'render_admin_notice' ) );
			return;
		}

		require_once VFWOO_WCPOS_BRIDGE_PATH . 'includes/Settings/class-vfwoo-bridge-section.php';
		require_once VFWOO_WCPOS_BRIDGE_PATH . 'includes/Integrations/class-vfwoo-fiscal-data-provider.php';
		require_once VFWOO_WCPOS_BRIDGE_PATH . 'includes/Integrations/class-wcpos-receipt-integration.php';
		require_once VFWOO_WCPOS_BRIDGE_PATH . 'includes/Integrations/class-wcpos-snapshot-refresher.php';
		require_once VFWOO_WCPOS_BRIDGE_PATH . 'includes/Integrations/class-wcpos-live-receipt-integration.php';
		require_once VFWOO_WCPOS_BRIDGE_PATH . 'includes/Templates/class-template-catalog.php';
		require_once VFWOO_WCPOS_BRIDGE_PATH . 'includes/Templates/class-template-installer.php';

		add_action( 'woocommerce_pos_register_settings_sections', array( self::class, 'register_settings_section' ) );
		new Integrations\WCPOS_Receipt_Integration();
		new Integrations\WCPOS_Snapshot_Refresher();
		new Integrations\WCPOS_Live_Receipt_Integration();
		new Templates\Template_Installer();
	}

	/**
	 * Registra el bridge como una sección nativa de ajustes de WCPOS.
	 *
	 * @param \WCPOS\WooCommercePOS\Services\Settings\Section_Registry $registry Registro de secciones.
	 */
	public static function register_settings_section( $registry ): void {
		$registry->register( new Settings\VFWoo_Bridge_Section() );
	}
}
