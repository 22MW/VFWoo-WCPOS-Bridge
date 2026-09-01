<?php
/**
 * Plugin Name:       VFWoo WCPOS Bridge
 * Plugin URI:        https://verifacwoo.com/
 * Description:       Integraciones de Veri*Fac*WOO para WooCommerce POS.
 * Version:           0.1.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce, vfwoo, woocommerce-pos
 * Author:            Veri*Fac*WOO
 * Text Domain:       vfwoo-wcpos-bridge
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package VFWoo_WCPOS_Bridge
 */

defined( 'ABSPATH' ) || exit;

define( 'VFWOO_WCPOS_BRIDGE_VERSION', '0.1.0' );
define( 'VFWOO_WCPOS_BRIDGE_FILE', __FILE__ );
define( 'VFWOO_WCPOS_BRIDGE_PATH', plugin_dir_path( __FILE__ ) );
define( 'VFWOO_WCPOS_BRIDGE_URL', plugin_dir_url( __FILE__ ) );

require_once VFWOO_WCPOS_BRIDGE_PATH . 'includes/class-plugin.php';

add_action(
	'plugins_loaded',
	static function (): void {
		\VFWooWCPOSBridge\Plugin::boot();
	},
	20
);
