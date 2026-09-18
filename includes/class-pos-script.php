<?php
/**
 * POS frontend extension.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class POS_Script {
	public function __construct() {
		add_action( 'wkwcpos_enqueue_pos_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue(): void {
		wp_enqueue_script(
			'vfwoo-webkul-pos-bridge',
			VFWOO_WEBKUL_URL . 'assets/js/pos-bridge.js',
			array( 'wp-hooks', 'wk-wc-pos-script' ),
			VFWOO_WEBKUL_VERSION,
			false
		);
	}
}
