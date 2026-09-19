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
			array( 'wp-hooks', 'wp-element', 'wk-wc-pos-script' ),
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
					'brandReportUrl' => esc_url_raw( rest_url( 'vfwoo-webkul/v1/brand-report' ) ),
					'brands'         => array(
						'menu'      => __( 'Marcas', 'vfwoo-webkul-pos-bridge' ),
						'title'     => __( 'Ventas por marca', 'vfwoo-webkul-pos-bridge' ),
						'back'      => __( 'Volver', 'vfwoo-webkul-pos-bridge' ),
						'today'     => __( 'Hoy', 'vfwoo-webkul-pos-bridge' ),
						'yesterday' => __( 'Ayer', 'vfwoo-webkul-pos-bridge' ),
						'week'      => __( 'Esta semana', 'vfwoo-webkul-pos-bridge' ),
						'month'     => __( 'Este mes', 'vfwoo-webkul-pos-bridge' ),
						'from'      => __( 'Desde', 'vfwoo-webkul-pos-bridge' ),
						'to'        => __( 'Hasta', 'vfwoo-webkul-pos-bridge' ),
						'apply'     => __( 'Ver', 'vfwoo-webkul-pos-bridge' ),
						'allBrands' => __( 'Todas las marcas', 'vfwoo-webkul-pos-bridge' ),
						'brand'     => __( 'Marca / producto', 'vfwoo-webkul-pos-bridge' ),
						'units'     => __( 'Unidades', 'vfwoo-webkul-pos-bridge' ),
						'net'       => __( 'Ventas sin IVA', 'vfwoo-webkul-pos-bridge' ),
						'gross'     => __( 'Ventas con IVA', 'vfwoo-webkul-pos-bridge' ),
						'orders'    => __( 'Pedidos', 'vfwoo-webkul-pos-bridge' ),
						'refunds'   => __( 'Devoluciones', 'vfwoo-webkul-pos-bridge' ),
						'total'     => __( 'Total', 'vfwoo-webkul-pos-bridge' ),
						'loading'   => __( 'Cargando…', 'vfwoo-webkul-pos-bridge' ),
						'empty'     => __( 'No hay ventas en este periodo.', 'vfwoo-webkul-pos-bridge' ),
						'error'     => __( 'No se han podido cargar los datos.', 'vfwoo-webkul-pos-bridge' ),
						'note'      => __( 'Solo ventas del POS. La marca es la actual del producto; un producto con varias marcas cuenta en cada una.', 'vfwoo-webkul-pos-bridge' ),
					),
					'nifLabel'       => __( 'DNI / NIE / CIF', 'vfwoo-webkul-pos-bridge' ),
					'nifUnverified'  => __( 'NIF sin verificar en el censo de la AEAT.', 'vfwoo-webkul-pos-bridge' ),
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
