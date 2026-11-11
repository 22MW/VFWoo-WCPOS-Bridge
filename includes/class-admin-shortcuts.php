<?php
/**
 * Shortcuts shown in the POS header: WooCommerce admin screens and the public shop.
 *
 * Each admin shortcut is only listed for users who can open that screen; the public shop is
 * listed for everyone. The screens open inside a window in the POS (see pos-bridge.js); the shop
 * opens in a new tab.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Admin_Shortcuts {
	/**
	 * Shortcuts for the current user.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function items(): array {
		$items = array();

		if ( current_user_can( 'manage_woocommerce' ) ) {
			$items[] = array(
				'id'    => 'panel',
				'kind'  => 'frame',
				'icon'  => 'woo',
				'title' => __( 'Panel de WooCommerce', 'vfwoo-webkul-pos-bridge' ),
				'url'   => admin_url( 'admin.php?page=wc-admin' ),
			);
		}

		if ( current_user_can( 'edit_products' ) ) {
			$tabs = array(
				array(
					'title' => __( 'Todos los productos', 'vfwoo-webkul-pos-bridge' ),
					'url'   => admin_url( 'edit.php?post_type=product' ),
				),
				array(
					'title' => __( 'Añadir nuevo', 'vfwoo-webkul-pos-bridge' ),
					'url'   => admin_url( 'post-new.php?post_type=product' ),
				),
			);
			if ( current_user_can( 'manage_product_terms' ) ) {
				$tabs[] = array(
					'title' => __( 'Categorías', 'vfwoo-webkul-pos-bridge' ),
					'url'   => admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ),
				);
				$tabs[] = array(
					'title' => __( 'Atributos', 'vfwoo-webkul-pos-bridge' ),
					'url'   => admin_url( 'edit.php?post_type=product&page=product_attributes' ),
				);
				$tabs[] = array(
					'title' => __( 'Etiquetas', 'vfwoo-webkul-pos-bridge' ),
					'url'   => admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=product' ),
				);
			}
			$items[] = array(
				'id'    => 'products',
				'kind'  => 'frame',
				'icon'  => 'archive',
				'title' => __( 'Productos', 'vfwoo-webkul-pos-bridge' ),
				'url'   => $tabs[0]['url'],
				'tabs'  => $tabs,
			);
		}

		if ( current_user_can( 'view_woocommerce_reports' ) ) {
			$items[] = array(
				'id'    => 'reports',
				'kind'  => 'frame',
				'icon'  => 'chart-line',
				'title' => __( 'Informes de ventas', 'vfwoo-webkul-pos-bridge' ),
				'url'   => admin_url( 'admin.php?page=wc-reports' ),
			);
			$items[] = array(
				'id'    => 'analytics',
				'kind'  => 'frame',
				'icon'  => 'chart-bar',
				'title' => __( 'Análisis', 'vfwoo-webkul-pos-bridge' ),
				'url'   => add_query_arg(
					array(
						'page' => 'wc-admin',
						'path' => '/analytics/overview',
					),
					admin_url( 'admin.php' )
				),
			);
		}

		$items[] = array(
			'id'    => 'shop',
			'kind'  => 'tab',
			'icon'  => 'store',
			'title' => __( 'Tienda online', 'vfwoo-webkul-pos-bridge' ),
			'url'   => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
		);

		return $items;
	}
}
