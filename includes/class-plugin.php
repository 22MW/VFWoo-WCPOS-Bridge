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

		// Registered before the requirements check so a plugin with a missing dependency can still be updated.
		require_once VFWOO_WEBKUL_PATH . 'includes/class-github-updater.php';
		( new Github_Updater() )->register_hooks();

		require_once VFWOO_WEBKUL_PATH . 'includes/class-requirements.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-catalog-integration.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-invoice-variables.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-admin.php';
		new Admin();
		new Invoice_Variables();

		if ( ! Requirements::are_met() ) {
			add_action( 'admin_notices', array( Requirements::class, 'render_admin_notice' ) );
			return;
		}

		require_once VFWOO_WEBKUL_PATH . 'includes/class-fiscal-provider.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-order-integration.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-customer-integration.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-pos-auth.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-brand-report.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-f3-emission.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-pos-script.php';
		new Order_Integration();
		new Catalog_Integration();
		new Customer_Integration();
		new Brand_Report();
		new F3_Emission();
		new POS_Script();
	}
}
