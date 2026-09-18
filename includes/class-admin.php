<?php
/**
 * Bridge status page.
 *
 * @package VFWoo_Webkul_POS_Bridge
 */

namespace VFWoo_Webkul_POS_Bridge;

defined( 'ABSPATH' ) || exit;

final class Admin {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 30 );
		add_action( 'admin_notices', array( $this, 'missing_notice' ) );
		add_filter( 'wkwcpos_modify_settings_tabs', array( $this, 'add_pos_settings_tab' ) );
		add_action( 'pos_vfwoo-webkul-bridge', array( $this, 'render' ) );
	}

	public function add_pos_settings_tab( $tabs ): array {
		$tabs['vfwoo-webkul-bridge'] = '<span class="dashicons dashicons-shield"></span>' . esc_html__( 'VFWoo Fiscal', 'vfwoo-webkul-pos-bridge' );
		return $tabs;
	}

	public function add_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'VFWoo Webkul POS Bridge', 'vfwoo-webkul-pos-bridge' ),
			__( 'VFWoo Webkul Bridge', 'vfwoo-webkul-pos-bridge' ),
			'manage_woocommerce',
			'vfwoo-webkul-pos-bridge',
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$missing = Requirements::missing();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'VFWoo Webkul POS Bridge', 'vfwoo-webkul-pos-bridge' ); ?></h1>
			<p><?php esc_html_e( 'Estado inicial de la integración. La configuración fiscal sigue perteneciendo a VFWoo.', 'vfwoo-webkul-pos-bridge' ); ?></p>
			<table class="widefat striped" style="max-width:720px">
				<tbody>
					<tr><td><?php esc_html_e( 'WooCommerce', 'vfwoo-webkul-pos-bridge' ); ?></td><td><?php echo class_exists( 'WooCommerce' ) ? esc_html__( 'Detectado', 'vfwoo-webkul-pos-bridge' ) : esc_html__( 'Falta', 'vfwoo-webkul-pos-bridge' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'VFWoo', 'vfwoo-webkul-pos-bridge' ); ?></td><td><?php echo class_exists( 'VFWoo\\Ticket_Fiscal_Data' ) ? esc_html__( 'Detectado', 'vfwoo-webkul-pos-bridge' ) : esc_html__( 'Falta', 'vfwoo-webkul-pos-bridge' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Webkul POS', 'vfwoo-webkul-pos-bridge' ); ?></td><td><?php echo defined( 'WK_WC_POS_VERSION' ) ? esc_html( WK_WC_POS_VERSION ) : esc_html__( 'No detectado', 'vfwoo-webkul-pos-bridge' ); ?></td></tr>
				</tbody>
			</table>
			<?php if ( ! empty( $missing ) ) : ?>
				<div class="notice notice-warning"><p><?php echo esc_html( sprintf( __( 'Dependencias pendientes: %s', 'vfwoo-webkul-pos-bridge' ), implode( ', ', $missing ) ) ); ?></p></div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function missing_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) || Requirements::can_boot() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>' . esc_html__( 'VFWoo Webkul POS Bridge necesita WooCommerce y VFWoo activos.', 'vfwoo-webkul-pos-bridge' ) . '</p></div>';
	}
}
