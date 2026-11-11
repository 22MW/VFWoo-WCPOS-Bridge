<?php
/**
 * "POS-CAJA" button in the WordPress admin bar.
 *
 * The address is always built from Webkul's own endpoint option, because each store can change it.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Admin_Bar {
	public function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'add_button' ), 90 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Address of the POS: the site URL plus Webkul's endpoint (default "pos").
	 */
	public static function pos_url(): string {
		$endpoint = get_option( '_pos_endpoint_name' );
		$endpoint = ! empty( $endpoint ) ? (string) $endpoint : 'pos';
		return site_url( $endpoint );
	}

	private static function can_see(): bool {
		return is_user_logged_in() && ( current_user_can( 'manage_pos' ) || current_user_can( 'manage_woocommerce' ) );
	}

	public function add_button( $bar ): void {
		if ( ! $bar instanceof \WP_Admin_Bar || ! self::can_see() ) {
			return;
		}

		$bar->add_node(
			array(
				'id'     => 'vfwoo-pos-caja',
				'parent' => 'top-secondary',
				'title'  => '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">' . esc_html__( 'POS-CAJA', 'vfwoo-webkul-pos-bridge' ) . '</span>',
				'href'   => self::pos_url(),
				'meta'   => array( 'title' => __( 'Abrir el POS', 'vfwoo-webkul-pos-bridge' ) ),
			)
		);
	}

	public function enqueue(): void {
		if ( ! is_admin_bar_showing() || ! self::can_see() ) {
			return;
		}
		wp_enqueue_style( 'vfwoo-webkul-admin-bar', VFWOO_WEBKUL_URL . 'assets/css/admin-bar.css', array( 'admin-bar' ), VFWOO_WEBKUL_VERSION );
	}
}
