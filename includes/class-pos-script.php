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
		add_filter( 'wkwcpos_add_custom_css', array( $this, 'allow_style' ) );
	}

	/**
	 * Webkul deregisters every POS style that is not whitelisted.
	 *
	 * @param mixed $styles Whitelisted style handles.
	 */
	public function allow_style( $styles ): array {
		$styles   = is_array( $styles ) ? $styles : array();
		$styles[] = 'vfwoo-webkul-pos-bridge';
		return $styles;
	}

	public function enqueue(): void {
		wp_enqueue_script(
			'vfwoo-webkul-pos-bridge',
			VFWOO_WEBKUL_URL . 'assets/js/pos-bridge.js',
			array( 'wp-hooks', 'wk-wc-pos-script' ),
			VFWOO_WEBKUL_VERSION,
			false
		);
		wp_add_inline_script(
			'vfwoo-webkul-pos-bridge',
			'window.vfwooWebkulBridge = ' . wp_json_encode(
				array(
					'catalogVersion' => Catalog_Integration::catalog_version(),
					'versionUrl'     => esc_url_raw( rest_url( 'vfwoo-webkul/v1/catalog-version' ) ),
					'staleNotice'    => __( 'El catálogo del POS está desactualizado. Abre el menú Resync y elige Products.', 'vfwoo-webkul-pos-bridge' ),
					'close'          => __( 'Cerrar', 'vfwoo-webkul-pos-bridge' ),
				)
			) . ';',
			'before'
		);
		wp_enqueue_style(
			'vfwoo-webkul-pos-bridge',
			VFWOO_WEBKUL_URL . 'assets/css/pos-bridge.css',
			array(),
			VFWOO_WEBKUL_VERSION
		);
	}
}
