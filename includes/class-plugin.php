<?php
/**
 * Main plugin coordinator.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public static function boot(): void {
		load_plugin_textdomain( 'vfwoo-webkul-pos-bridge', false, dirname( plugin_basename( VFWOO_WEBKUL_FILE ) ) . '/languages' );

		require_once VFWOO_WEBKUL_PATH . 'includes/class-requirements.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-catalog-integration.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-admin.php';
		new Admin();

		if ( ! Requirements::are_met() ) {
			add_action( 'admin_notices', array( Requirements::class, 'render_admin_notice' ) );
			return;
		}

		require_once VFWOO_WEBKUL_PATH . 'includes/class-fiscal-provider.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-order-integration.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-customer-integration.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-brand-report.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-pos-script.php';
		new Order_Integration();
		new Catalog_Integration();
		new Customer_Integration();
		new Brand_Report();
		new POS_Script();
	}
}
