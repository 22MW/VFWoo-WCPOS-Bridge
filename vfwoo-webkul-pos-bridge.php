<?php
/**
 * Plugin Name: VFWoo Webkul POS Bridge
 * Plugin URI: https://verifacwoo.com/
 * Description: Integraciones de Veri*Fac*WOO para WooCommerce Point of Sale de Webkul.
 * Version: 0.1.0.5
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce, vfwoo, woo-point-of-sale
 * Author: Veri*Fac*WOO
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vfwoo-webkul-pos-bridge
 * Domain Path: /languages
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

defined( 'ABSPATH' ) || exit;

define( 'VFWOO_WEBKUL_VERSION', '0.1.0.5' );
define( 'VFWOO_WEBKUL_FILE', __FILE__ );
define( 'VFWOO_WEBKUL_PATH', plugin_dir_path( __FILE__ ) );
define( 'VFWOO_WEBKUL_URL', plugin_dir_url( __FILE__ ) );

require_once VFWOO_WEBKUL_PATH . 'includes/class-plugin.php';

add_action(
	'plugins_loaded',
	static function (): void {
		VFWoo_Webkul_POS_Bridge\Plugin::boot();
	}
);
