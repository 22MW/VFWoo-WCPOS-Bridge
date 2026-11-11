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
		require_once VFWOO_WEBKUL_PATH . 'includes/class-login-screen.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-admin-notices.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-admin-shortcuts.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-admin-bar.php';
		require_once VFWOO_WEBKUL_PATH . 'includes/class-cashier-roles.php';
		new Order_Integration();
		new Catalog_Integration();
		new Brand_Report();
		new POS_Script();
		new Login_Screen();
		new Admin_Notices();
		new Admin_Bar();
		new Cashier_Roles();

		// Fiscal modules: they need VFWoo. Without it their hooks and routes are not registered.
		if ( Requirements::vfwoo_active() ) {
			new Customer_Integration();
			new F3_Emission();
		}
	}
}
