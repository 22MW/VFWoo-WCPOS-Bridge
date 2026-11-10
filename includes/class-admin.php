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
		add_action( 'admin_post_vfwoo_webkul_bump_catalog', array( $this, 'bump_catalog' ) );
		add_action( 'admin_post_vfwoo_webkul_save_login', array( $this, 'save_login' ) );
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
		Catalog_Integration::bump_catalog_version();

		wp_safe_redirect( add_query_arg( 'vfwoo_saved', '1', wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=vfwoo-webkul-pos-bridge' ) ) );
		exit;
	}

	public function bump_catalog(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para hacer esto.', 'vfwoo-webkul-pos-bridge' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'vfwoo_webkul_bump_catalog' );

		Catalog_Integration::bump_catalog_version();

		wp_safe_redirect( add_query_arg( 'vfwoo_saved', '1', wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=vfwoo-webkul-pos-bridge' ) ) );
		exit;
	}

	public function save_login(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para hacer esto.', 'vfwoo-webkul-pos-bridge' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'vfwoo_webkul_save_login' );

		$text = static function ( string $key ): string {
			return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
		};

		update_option(
			Login_Screen::OPTION,
			array(
				'heading'      => $text( 'vfwoo_login_heading' ),
				'footer'       => $text( 'vfwoo_login_footer' ),
				'hide_version' => ! empty( $_POST['vfwoo_login_hide_version'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
				'demo'         => ! empty( $_POST['vfwoo_login_demo'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
				'user'         => $text( 'vfwoo_login_user' ),
				'pass'         => $text( 'vfwoo_login_pass' ),
			),
			false
		);

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
					<tr><td><?php esc_html_e( 'VFWoo', 'vfwoo-webkul-pos-bridge' ); ?></td><td><?php echo Requirements::vfwoo_active() ? esc_html__( 'Detectado', 'vfwoo-webkul-pos-bridge' ) : esc_html__( 'No detectado (opcional)', 'vfwoo-webkul-pos-bridge' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Webkul POS', 'vfwoo-webkul-pos-bridge' ); ?></td><td><?php echo defined( 'WK_WC_POS_VERSION' ) ? esc_html( WK_WC_POS_VERSION ) : esc_html__( 'No detectado', 'vfwoo-webkul-pos-bridge' ); ?></td></tr>
				</tbody>
			</table>
			<h2><?php esc_html_e( 'Módulos fiscales', 'vfwoo-webkul-pos-bridge' ); ?></h2>
			<?php $fiscal = Requirements::vfwoo_active(); ?>
			<table class="widefat striped" style="max-width:720px">
				<tbody>
					<?php
					foreach ( array(
						__( 'Ticket fiscal con QR', 'vfwoo-webkul-pos-bridge' ),
						__( 'Factura completa (F3) desde el POS', 'vfwoo-webkul-pos-bridge' ),
						__( 'NIF obligatorio y límite de factura simplificada', 'vfwoo-webkul-pos-bridge' ),
					) as $module ) :
						?>
						<tr>
							<td><?php echo esc_html( $module ); ?></td>
							<td>
								<?php if ( $fiscal ) : ?>
									<?php esc_html_e( 'Activo', 'vfwoo-webkul-pos-bridge' ); ?>
								<?php else : ?>
									<span class="description"><?php esc_html_e( 'Desactivado: requiere el plugin VFWoo', 'vfwoo-webkul-pos-bridge' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<h2><?php esc_html_e( 'Pantalla de login del POS', 'vfwoo-webkul-pos-bridge' ); ?></h2>
			<?php $login = Login_Screen::settings(); ?>
			<p><?php esc_html_e( 'Cambia los textos de la pantalla de acceso del POS sin tocar el plugin de Webkul. Los campos vacíos dejan el valor de Webkul.', 'vfwoo-webkul-pos-bridge' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="vfwoo_webkul_save_login" />
				<?php wp_nonce_field( 'vfwoo_webkul_save_login' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="vfwoo_login_heading"><?php esc_html_e( 'Titular', 'vfwoo-webkul-pos-bridge' ); ?></label></th>
						<td><input type="text" class="regular-text" id="vfwoo_login_heading" name="vfwoo_login_heading" value="<?php echo esc_attr( $login['heading'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="vfwoo_login_footer"><?php esc_html_e( 'Texto del pie', 'vfwoo-webkul-pos-bridge' ); ?></label></th>
						<td>
							<input type="text" class="large-text" id="vfwoo_login_footer" name="vfwoo_login_footer" value="<?php echo esc_attr( $login['footer'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Sustituye a «Un producto de Webkul».', 'vfwoo-webkul-pos-bridge' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Versión', 'vfwoo-webkul-pos-bridge' ); ?></th>
						<td><label><input type="checkbox" name="vfwoo_login_hide_version" value="1" <?php checked( $login['hide_version'] ); ?> /> <?php esc_html_e( 'Ocultar la línea «versión - … - Webkul»', 'vfwoo-webkul-pos-bridge' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Modo demo', 'vfwoo-webkul-pos-bridge' ); ?></th>
						<td>
							<label><input type="checkbox" name="vfwoo_login_demo" value="1" <?php checked( $login['demo'] ); ?> /> <?php esc_html_e( 'Rellenar usuario y contraseña en el formulario', 'vfwoo-webkul-pos-bridge' ); ?></label>
							<p><input type="text" class="regular-text" name="vfwoo_login_user" value="<?php echo esc_attr( $login['user'] ); ?>" placeholder="<?php esc_attr_e( 'Usuario', 'vfwoo-webkul-pos-bridge' ); ?>" autocomplete="off" /></p>
							<p><input type="text" class="regular-text" name="vfwoo_login_pass" value="<?php echo esc_attr( $login['pass'] ); ?>" placeholder="<?php esc_attr_e( 'Contraseña', 'vfwoo-webkul-pos-bridge' ); ?>" autocomplete="off" /></p>
							<p class="description"><strong><?php esc_html_e( 'Atención:', 'vfwoo-webkul-pos-bridge' ); ?></strong> <?php esc_html_e( 'con el modo demo activo, el usuario y la contraseña son visibles para cualquiera que abra la pantalla de acceso. Úsalo solo con una cuenta de demostración, sin datos reales y con una contraseña que no uses en ningún otro sitio.', 'vfwoo-webkul-pos-bridge' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Guardar pantalla de login', 'vfwoo-webkul-pos-bridge' ) ); ?>
			</form>
			<h2><?php esc_html_e( 'Filtros del catálogo POS', 'vfwoo-webkul-pos-bridge' ); ?></h2>
			<?php if ( isset( $_GET['vfwoo_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Guardado. Cada caja mostrará un aviso para recargar su catálogo (Resync → Products).', 'vfwoo-webkul-pos-bridge' ); ?></p></div>
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
			<h2><?php esc_html_e( 'Recarga del catálogo', 'vfwoo-webkul-pos-bridge' ); ?></h2>
			<p><?php echo esc_html( sprintf( __( 'Versión actual del catálogo: %d. Al subirla, cada POS avisa al cajero de que debe usar Resync → Products. No se borra nada desde aquí. Guardar los filtros también la sube.', 'vfwoo-webkul-pos-bridge' ), Catalog_Integration::catalog_version() ) ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="vfwoo_webkul_bump_catalog" />
				<?php wp_nonce_field( 'vfwoo_webkul_bump_catalog' ); ?>
				<?php submit_button( __( 'Forzar recarga del catálogo', 'vfwoo-webkul-pos-bridge' ), 'secondary' ); ?>
			</form>
			<?php if ( ! empty( $missing ) ) : ?>
				<div class="notice notice-warning"><p><?php echo esc_html( sprintf( __( 'Dependencias pendientes: %s', 'vfwoo-webkul-pos-bridge' ), implode( ', ', $missing ) ) ); ?></p></div>
			<?php endif; ?>
		</div>
		<?php
	}

}
