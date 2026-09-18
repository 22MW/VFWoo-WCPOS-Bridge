<?php
/**
 * Plugin Name: VFWoo Webkul POS Bridge
 * Description: Integra datos fiscales de VFWoo en el ticket del WooCommerce Point of Sale de Webkul.
 * Version: 0.1.0.1
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: 22MW
 * License: GPL-2.0-or-later
 * Text Domain: vfwoo-webkul-pos-bridge
 * Domain Path: /languages
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

defined( 'ABSPATH' ) || exit;

define( 'VFWOO_WEBKUL_VERSION', '0.1.0.1' );
define( 'VFWOO_WEBKUL_FILE', __FILE__ );
define( 'VFWOO_WEBKUL_PATH', plugin_dir_path( __FILE__ ) );
define( 'VFWOO_WEBKUL_URL', plugin_dir_url( __FILE__ ) );

require_once VFWOO_WEBKUL_PATH . 'includes/class-requirements.php';
require_once VFWOO_WEBKUL_PATH . 'includes/class-fiscal-provider.php';
require_once VFWOO_WEBKUL_PATH . 'includes/class-order-integration.php';
require_once VFWOO_WEBKUL_PATH . 'includes/class-pos-script.php';
require_once VFWOO_WEBKUL_PATH . 'includes/class-admin.php';
require_once VFWOO_WEBKUL_PATH . 'includes/class-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! VFWoo_Webkul_POS_Bridge\Requirements::can_boot() ) {
			return;
		}

		VFWoo_Webkul_POS_Bridge\Plugin::instance();
	}
);
