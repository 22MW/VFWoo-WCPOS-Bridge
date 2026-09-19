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
		add_filter( 'wkwcpos_modify_settings_tabs', array( $this, 'add_pos_settings_tab' ) );
		add_action( 'pos_vfwoo-bridge', array( $this, 'render' ) );
		add_action( 'admin_post_vfwoo_webkul_save_filters', array( $this, 'save_filters' ) );
	}

	public function add_pos_settings_tab( $tabs ): array {
		$tabs['vfwoo-bridge'] = '<span class="dashicons dashicons-shield"></span>' . esc_html__( 'VFWoo Bridge', 'vfwoo-webkul-pos-bridge' );
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

	public function save_filters(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para hacer esto.', 'vfwoo-webkul-pos-bridge' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'vfwoo_webkul_save_filters' );

		$posted = isset( $_POST['vfwoo_filter_taxonomies'] ) && is_array( $_POST['vfwoo_filter_taxonomies'] )
			? array_map( 'sanitize_key', wp_unslash( $_POST['vfwoo_filter_taxonomies'] ) )
			: array();
		update_option( Catalog_Integration::OPTION, array_values( array_intersect( $posted, array_keys( Catalog_Integration::available_taxonomies() ) ) ) );

		wp_safe_redirect( add_query_arg( 'vfwoo_saved', '1', wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=vfwoo-webkul-pos-bridge' ) ) );
		exit;
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
			<h2><?php esc_html_e( 'Filtros del catálogo POS', 'vfwoo-webkul-pos-bridge' ); ?></h2>
			<?php if ( isset( $_GET['vfwoo_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Filtros guardados. Cada caja debe recargar su catálogo (borrar los datos locales del POS) para verlos.', 'vfwoo-webkul-pos-bridge' ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Elige qué taxonomías de producto aparecerán en el panel de filtros del POS. Las categorías y los atributos ya los muestra Webkul.', 'vfwoo-webkul-pos-bridge' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="vfwoo_webkul_save_filters" />
				<?php wp_nonce_field( 'vfwoo_webkul_save_filters' ); ?>
				<?php $selected = Catalog_Integration::selected_taxonomies(); ?>
				<?php foreach ( Catalog_Integration::available_taxonomies() as $slug => $taxonomy ) : ?>
					<p><label>
						<input type="checkbox" name="vfwoo_filter_taxonomies[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected, true ) ); ?> />
						<?php echo esc_html( $taxonomy->labels->name ); ?> <code><?php echo esc_html( $slug ); ?></code>
					</label></p>
				<?php endforeach; ?>
				<?php submit_button( __( 'Guardar filtros', 'vfwoo-webkul-pos-bridge' ) ); ?>
			</form>
			<?php if ( ! empty( $missing ) ) : ?>
				<div class="notice notice-warning"><p><?php echo esc_html( sprintf( __( 'Dependencias pendientes: %s', 'vfwoo-webkul-pos-bridge' ), implode( ', ', $missing ) ) ); ?></p></div>
			<?php endif; ?>
		</div>
		<?php
	}

}
